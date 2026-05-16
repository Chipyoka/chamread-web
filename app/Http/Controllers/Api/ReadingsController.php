<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reading;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ReadingsController extends Controller
{
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
            'account_number'     => 'required|string|max:255',

            'billing_cycle_id'  => 'required|exists:billing_cycles,id',

            'zone_id'           => 'required|exists:zones,id',
            'dma_id'            => 'required|exists:dmas,id',

            'current_reading'   => 'nullable|numeric',

            'status'            => 'required|in:read,not_read',
            'reason_code'       => 'nullable|string|max:255',

            'photo'             => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',

            'latitude'          => 'nullable|numeric',
            'longitude'         => 'nullable|numeric',

            'reading_time'      => 'required|date',
        ]);

        DB::beginTransaction();

        try {

            $photoPath = null;

            /*
            |--------------------------------------------------------------------------
            | Resolve Previous Reading
            |--------------------------------------------------------------------------
            */

            $lastReading = Reading::where('account_number', $validated['account_number'])
                ->where('billing_cycle_id', '<', $validated['billing_cycle_id'])
                ->whereNotNull('current_reading')
                ->orderByDesc('billing_cycle_id')
                ->first();

            $previousReading = $lastReading
                ? $lastReading->current_reading
                : $validated['current_reading'];

            Log::info('Previous reading resolved', [
                'account_number' => $validated['account_number'],
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
                    '%s_%s.%s',
                    $validated['account_number'],
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
                'account_number'    => $validated['account_number'],

                'csa_id'            => auth()->id(),
                'billing_cycle_id' => $validated['billing_cycle_id'],

                'zone_id'           => $validated['zone_id'],
                'dma_id'            => $validated['dma_id'],

                'previous_reading'  => $previousReading,
                'current_reading'   => $validated['current_reading'],

                'status'            => $validated['status'],
                'reason_code'       => $validated['reason_code'] ?? null,

                'photo_path'        => $photoPath,

                'latitude'          => $validated['latitude'] ?? null,
                'longitude'         => $validated['longitude'] ?? null,

                'reading_time'      => $validated['reading_time'],

                'synced_at'         => now(),
            ]);

            DB::commit();

            Log::info('Reading saved successfully', [
                'reading_id' => $reading->id,
                'account_number' => $reading->account_number,
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
                'account_number' => $request->account_number,
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

            try {

                /*
                |--------------------------------------------------------------------------
                | Validate Individual Reading
                |--------------------------------------------------------------------------
                */

                $validated = validator($readingData, [

                    'account_number'     => 'required|string|max:255',

                    'billing_cycle_id'  => 'required|exists:billing_cycles,id',

                    'zone_id'           => 'required|exists:zones,id',
                    'dma_id'            => 'required|exists:dmas,id',

                    'current_reading'   => 'nullable|numeric',

                    'status'            => 'required|in:read,not_read',
                    'reason_code'       => 'nullable|string|max:255',

                    'latitude'          => 'nullable|numeric',
                    'longitude'         => 'nullable|numeric',

                    'reading_time'      => 'required|date',

                ])->validate();

                Log::info('Processing batch reading', [
                    'account_number' => $validated['account_number'],
                ]);

                /*
                |--------------------------------------------------------------------------
                | Resolve Previous Reading
                |--------------------------------------------------------------------------
                */

                $lastReading = Reading::where('account_number', $validated['account_number'])
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

                $photoPath = null;

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

                    $hash = strtoupper(\Str::random(6));

                    $filename = sprintf(
                        '%s_%s.%s',
                        $validated['account_number'],
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
                    'account_number'    => $validated['account_number'],

                    'csa_id'            => auth()->id(),
                    'billing_cycle_id' => $validated['billing_cycle_id'],

                    'zone_id'           => $validated['zone_id'],
                    'dma_id'            => $validated['dma_id'],

                    'previous_reading'  => $previousReading,
                    'current_reading'   => $validated['current_reading'],

                    'status'            => $validated['status'],
                    'reason_code'       => $validated['reason_code'] ?? null,

                    'photo_path'        => $photoPath,

                    'latitude'          => $validated['latitude'] ?? null,
                    'longitude'         => $validated['longitude'] ?? null,

                    'reading_time'      => $validated['reading_time'],

                    'synced_at'         => now(),
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

            } catch (\Throwable $e) {

                DB::rollBack();

                $failed++;

                Log::error('Batch reading failed', [
                    'index' => $index,
                    'account_number' => $readingData['account_number'] ?? null,
                    'error' => $e->getMessage(),
                ]);

                $results[] = [
                    'account_number' => $readingData['account_number'] ?? null,
                    'success' => false,
                    'message' => $e->getMessage(),
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