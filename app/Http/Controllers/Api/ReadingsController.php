<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reading;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ReadingsController extends Controller
{
    /**
     * Fetch all reasons for not reading a meter
     */
    public function reasons()
    {
        $reasons = MeterReadingCode::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $reasons,
        ]);
    }

    /**
     * Normalize a validated date value into a Carbon instance.
     * Laravel's validate() does not cast 'date' rule fields, so this
     * guards against getting a raw string vs a DateTimeInterface.
     */
    private function resolveReadingTime($value): Carbon
    {
        return $value instanceof \DateTimeInterface
            ? Carbon::instance($value)
            : Carbon::parse($value);
    }

    /**
     * Store a new reading
     * This endpoint is for syncing a single reading from the mobile app
     */
    public function store(Request $request)
    {
        Log::info('Reading sync request received', [
            'account_number' => $request->account_number,
            'billing_cycle_id' => $request->billing_cycle_id,
            'csa_id' => auth()->id(),
        ]);

        $validated = $request->validate([
            'account_id'     => 'required|exists:customer_accounts,id',
            'account_number' => 'required|string|max:255',

            'billing_cycle_id'  => 'required|exists:billing_cycles,id',

            'current_reading'   => 'nullable|numeric',

            'status'            => 'required|in:read,not_read',
            'meter_reading_code' => 'nullable|exists:meter_reading_codes,id',
            'comment'           => 'nullable|string|max:255',

            'photo'             => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',

            'latitude'          => 'nullable|numeric',
            'longitude'         => 'nullable|numeric',

            'reading_time'      => 'required|date',
        ]);

        $readingTime = $this->resolveReadingTime($validated['reading_time']);

        DB::beginTransaction();

        $photoPath = null;

        try {
            /*
            |--------------------------------------------------------------------------
            | Prevent Duplicate Reading Per Billing Cycle
            |--------------------------------------------------------------------------
            */

            $existingReading = Reading::where('account_id', $validated['account_id'])
                ->where('billing_cycle_id', $validated['billing_cycle_id'])
                ->first();

            if ($existingReading) {

                DB::rollBack();

                Log::warning('Duplicate reading attempt blocked', [
                    'account_id' => $validated['account_id'],
                    'billing_cycle_id' => $validated['billing_cycle_id'],
                    'existing_reading_id' => $existingReading->id,
                    'csa_id' => auth()->id(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'A reading for this account already exists in the current cycle.',
                    'error_code' => 'DUPLICATE_READING',
                    'data' => [
                        'reading_id' => $existingReading->id,
                    ]
                ], 409);
            }

            /*
            |--------------------------------------------------------------------------
            | Resolve Previous Reading
            |--------------------------------------------------------------------------
            */

            $lastReading = Reading::where('account_id', $validated['account_id'])
                ->where('billing_cycle_id', '<', $validated['billing_cycle_id'])
                ->whereNotNull('current_reading')
                ->orderByDesc('billing_cycle_id')
                ->first();

            $previousReading = $lastReading
                ? $lastReading->current_reading
                : $validated['current_reading'];

            Log::info('Previous reading resolved', [
                'account_id' => $validated['account_id'],
                'previous_reading' => $previousReading,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Handle Photo Upload
            |--------------------------------------------------------------------------
            */

            if ($request->hasFile('photo')) {

                $photo = $request->file('photo');

                $hash = strtoupper(Str::random(6));

                $filename = sprintf(
                    '%s_%s_%s.%s',
                    $validated['account_number'],
                    $readingTime->format('Ymd_His'),
                    $hash,
                    $photo->getClientOriginalExtension()
                );

                $photoPath = $photo->storeAs(
                    'readings',
                    $filename,
                    'public'
                );

                Log::info('Photo stored successfully', [
                    'photo_path' => $photoPath,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Save Reading
            |--------------------------------------------------------------------------
            */

            $reading = Reading::create([
                'account_id'    => $validated['account_id'],
                'csa_id'        => auth()->id(),
                'billing_cycle_id' => $validated['billing_cycle_id'],

                'reading_date' => now()->toDateString(),
                'previous_reading' => $previousReading,
                'current_reading' => $validated['current_reading'],
                'meter_status' => null,

                'this_month_code' => null,

                'status' => $validated['status'],
                'meter_reading_code' => $validated['meter_reading_code'] ?? null,
                'comment' => $validated['comment'] ?? null,
                'consumption' => $validated['current_reading'] && $previousReading
                    ? $validated['current_reading'] - $previousReading
                    : null,

                'photo_path' => $photoPath,

                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,

                'reading_time' => $readingTime,
                'synced_at' => now(),
            ]);

            DB::commit();

            Log::info('Reading saved successfully', [
                'reading_id' => $reading->id,
                'account_id' => $reading->account_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reading synced successfully',
                'data' => [
                    'id' => $reading->id,
                ]
            ], 201);

        } catch (Throwable $e) {

            DB::rollBack();

            Log::error('Reading sync failed', [
                'account_id' => $request->account_id,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Cleanup orphaned photo
            |--------------------------------------------------------------------------
            */

            if ($photoPath && Storage::disk('public')->exists($photoPath)) {

                Storage::disk('public')->delete($photoPath);

                Log::warning('Orphaned photo deleted', [
                    'photo_path' => $photoPath,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to sync reading',
                'error' => app()->environment('production')
                    ? 'Internal server error'
                    : $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Batch reading processing
     * This endpoint is for storing multiple readings in one request
     * Mobile app can send an array of readings to this endpoint for bulk sync
     *
     * Mirrors store() for validation, duplicate protection, and persistence -
     * the only differences are: it loops over many readings, and photos arrive
     * as base64 strings (photo_base64) instead of multipart file uploads.
     */
    public function batchStore(Request $request)
    {
        $request->validate([
            'readings' => 'required|array|min:1',
        ]);

        Log::info('Batch reading sync started', [
            'total_readings' => count($request->readings),
            'csa_id' => auth()->id(),
        ]);

        $processed = 0;
        $failed = 0;

        $results = [];

        foreach ($request->readings as $index => $readingData) {

            DB::beginTransaction();

            $photoPath = null;

            try {

                /*
                |--------------------------------------------------------------------------
                | Validate Individual Reading
                |--------------------------------------------------------------------------
                */

                $validated = validator($readingData, [
                    'account_id'     => 'required|exists:customer_accounts,id',
                    'account_number' => 'required|string|max:255',

                    'billing_cycle_id'  => 'required|exists:billing_cycles,id',

                    'current_reading'   => 'nullable|numeric',

                    'status'            => 'required|in:read,not_read',
                    'meter_reading_code' => 'nullable|exists:meter_reading_codes,id',
                    'comment'           => 'nullable|string|max:255',

                    'latitude'          => 'nullable|numeric',
                    'longitude'         => 'nullable|numeric',

                    'reading_time'      => 'required|date',

                ])->validate();

                $readingTime = $this->resolveReadingTime($validated['reading_time']);

                Log::info('Processing batch reading', [
                    'account_number' => $validated['account_number'],
                ]);

                /*
                |--------------------------------------------------------------------------
                | Prevent Duplicate Reading Per Billing Cycle
                |--------------------------------------------------------------------------
                */

                $existingReading = Reading::where('account_id', $validated['account_id'])
                    ->where('billing_cycle_id', $validated['billing_cycle_id'])
                    ->first();

                if ($existingReading) {

                    DB::rollBack();

                    $failed++;

                    Log::warning('Duplicate reading attempt blocked (batch)', [
                        'index' => $index,
                        'account_id' => $validated['account_id'],
                        'billing_cycle_id' => $validated['billing_cycle_id'],
                        'existing_reading_id' => $existingReading->id,
                        'csa_id' => auth()->id(),
                    ]);

                    $results[] = [
                        'account_number' => $validated['account_number'],
                        'success' => false,
                        'message' => 'A reading for this account already exists in the current cycle.',
                        'error_code' => 'DUPLICATE_READING',
                        'reading_id' => $existingReading->id,
                    ];

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Resolve Previous Reading
                |--------------------------------------------------------------------------
                */

                $lastReading = Reading::where('account_id', $validated['account_id'])
                    ->where('billing_cycle_id', '<', $validated['billing_cycle_id'])
                    ->whereNotNull('current_reading')
                    ->orderByDesc('billing_cycle_id')
                    ->first();

                $previousReading = $lastReading
                    ? $lastReading->current_reading
                    : $validated['current_reading'];

                /*
                |--------------------------------------------------------------------------
                | Handle Photo
                |--------------------------------------------------------------------------
                |
                | Mobile app should send:
                | photo_base64
                |--------------------------------------------------------------------------
                */

                if (!empty($readingData['photo_base64'])) {

                    Log::info('Processing batch photo', [
                        'account_number' => $validated['account_number'],
                    ]);

                    $base64Image = $readingData['photo_base64'];

                    /*
                    |--------------------------------------------------------------------------
                    | Remove base64 metadata
                    |--------------------------------------------------------------------------
                    */

                    if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {

                        $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);

                        $extension = strtolower($type[1]);

                    } else {

                        $extension = 'jpg';
                    }

                    $imageData = base64_decode($base64Image);

                    if ($imageData === false) {
                        throw new \Exception('Invalid base64 image');
                    }

                    $hash = strtoupper(Str::random(6));

                    $filename = sprintf(
                        '%s_%s_%s.%s',
                        $validated['account_number'],
                        $readingTime->format('Ymd_His'),
                        $hash,
                        $extension
                    );

                    $photoPath = 'readings/' . $filename;

                    Storage::disk('public')->put($photoPath, $imageData);

                    Log::info('Batch photo stored', [
                        'photo_path' => $photoPath,
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Save Reading
                |--------------------------------------------------------------------------
                */

                $reading = Reading::create([
                    'account_id' => $validated['account_id'],
                    'csa_id' => auth()->id(),
                    'billing_cycle_id' => $validated['billing_cycle_id'],

                    'reading_date' => now()->toDateString(),
                    'previous_reading' => $previousReading,
                    'current_reading' => $validated['current_reading'],
                    'meter_status' => null,

                    'this_month_code' => null,

                    'status' => $validated['status'],
                    'meter_reading_code' => $validated['meter_reading_code'] ?? null,
                    'comment' => $validated['comment'] ?? null,
                    'consumption' => $validated['current_reading'] && $previousReading
                        ? $validated['current_reading'] - $previousReading
                        : null,

                    'photo_path' => $photoPath,

                    'latitude' => $validated['latitude'] ?? null,
                    'longitude' => $validated['longitude'] ?? null,

                    'reading_time' => $readingTime,
                    'synced_at' => now(),
                ]);

                DB::commit();

                $processed++;

                $results[] = [
                    'account_number' => $validated['account_number'],
                    'success' => true,
                    'reading_id' => $reading->id,
                    'message' => 'Reading synced successfully',
                ];

                Log::info('Batch reading synced successfully', [
                    'reading_id' => $reading->id,
                    'account_number' => $validated['account_number'],
                ]);

            } catch (Throwable $e) {

                DB::rollBack();

                $failed++;

                /*
                |--------------------------------------------------------------------------
                | Cleanup orphaned photo
                |--------------------------------------------------------------------------
                */

                if ($photoPath && Storage::disk('public')->exists($photoPath)) {

                    Storage::disk('public')->delete($photoPath);

                    Log::warning('Orphaned batch photo deleted', [
                        'photo_path' => $photoPath,
                    ]);
                }

                Log::error('Batch reading failed', [
                    'index' => $index,
                    'account_number' => $readingData['account_number'] ?? null,
                    'error' => $e->getMessage(),
                    'line' => $e->getLine(),
                ]);

                $results[] = [
                    'account_number' => $readingData['account_number'] ?? null,
                    'success' => false,
                    'message' => app()->environment('production')
                        ? 'Failed to sync reading'
                        : $e->getMessage(),
                ];
            }
        }

        Log::info('Batch reading sync completed', [
            'processed' => $processed,
            'failed' => $failed,
        ]);

        return response()->json([
            'success' => $failed === 0,

            'summary' => [
                'total' => count($request->readings),
                'processed' => $processed,
                'failed' => $failed,
            ],

            'results' => $results,
        ]);
    }
}