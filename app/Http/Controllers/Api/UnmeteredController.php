<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BillingCycle;
use App\Models\CustomerAccount;
use App\Models\MeterReadingCode;
use App\Models\Unmetered;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class UnmeteredController extends Controller
{
    /**
     * Codes a reader can select for an unmetered account.
     *
     * Route: GET /unmetered/codes
     */
    public function codes(): JsonResponse
    {
        $codes = MeterReadingCode::where('status', 'active')
            ->whereIn('code', Unmetered::ALLOWED_CODES)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type']);

        return response()->json([
            'success' => true,
            'data'    => $codes,
        ]);
    }

    /**
     * Correct a stale cycle id sent by the mobile client.
     *
     * The app only learns about a new cycle when the user manually refreshes
     * online, so readings captured in the new cycle can still carry the previous
     * cycle's id. We remap ONLY when all of these hold:
     *   1. incoming id is the cycle immediately before the current one
     *      (resolved by lookup, not id - 1, so gaps in ids are safe), and
     *   2. the reading was captured on/after the current cycle's start_date,
     *      so a genuinely late reading from the old cycle is NOT pulled forward.
     * Anything else is returned unchanged and falls through to the normal
     * wrong-cycle rejection.
     */
    private function reconcileStaleCycle(int $incomingCycleId, BillingCycle $currentCycle, Carbon $readingTime): int
    {
        $currentCycleId = (int) $currentCycle->id;

        if ($incomingCycleId === $currentCycleId) {
            return $incomingCycleId;
        }

        $previousCycleId = Cache::remember(
            "billing_cycle:previous:{$currentCycleId}",
            600,
            fn () => BillingCycle::where('id', '<', $currentCycleId)->orderByDesc('id')->value('id')
        );

        if ($previousCycleId === null || $incomingCycleId !== (int) $previousCycleId) {
            return $incomingCycleId;
        }

        $currentCycleStart = Carbon::parse($currentCycle->start_date)->startOfDay();

        if ($readingTime->lt($currentCycleStart)) {
            return $incomingCycleId; // captured before the new cycle began: genuine old-cycle reading
        }

        return $currentCycleId;
    }

    /**
     * Sync one unmetered record from the mobile app (multipart/form-data).
     *
     * Mirrors ReadingsController::syncReading(): one record per request, the
     * photo as a real file, the same cycle gates and stale-cycle remapping,
     * and a duplicate returned as a 200 so the app treats it as already
     * synced instead of retrying.
     *
     * Route: POST /unmetered/sync
     */
    public function sync(Request $request): JsonResponse
    {
        $currentCycle = BillingCycle::where('status', 'active')->latest()->first();

        if (! $currentCycle) {
            Log::warning('Unmetered sync blocked: no active billing cycle', [
                'csa_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No active billing cycle',
            ], 409);
        }

        if (! $currentCycle->can_upload) {
            return response()->json([
                'success' => false,
                'message' => 'Upload is locked',
            ], 423);
        }

        $validated = $request->validate([
            'account_id'        => 'required|exists:customer_accounts,id',
            'billing_cycle_id'  => 'required|exists:billing_cycles,id',
            // Must be the id of an active code from Unmetered::ALLOWED_CODES.
            'meter_reading_code' => [
                'required',
                Rule::exists('meter_reading_codes', 'id')
                    ->whereIn('code', Unmetered::ALLOWED_CODES)
                    ->where('status', 'active'),
            ],
            'comment'           => 'nullable|string|max:255',
            'latitude'          => 'nullable|numeric',
            'longitude'         => 'nullable|numeric',
            'captured_at'       => 'required|date',
            'photo'             => 'required|image|max:8192', // 8MB ceiling
        ]);

        $capturedAt = Carbon::parse($validated['captured_at']);

        // Stale-cycle reconciliation: from here on $cycleId is the effective
        // cycle, so the gate, the duplicate check and the saved row agree.
        $clientCycleId = (int) $validated['billing_cycle_id'];
        $cycleId = $this->reconcileStaleCycle($clientCycleId, $currentCycle, $capturedAt);
        $remappedFrom = $cycleId !== $clientCycleId ? $clientCycleId : null;

        if ($cycleId !== (int) $currentCycle->id) {
            Log::warning('Unmetered sync blocked: wrong billing cycle', [
                'account_id'       => $validated['account_id'],
                'billing_cycle_id' => $clientCycleId,
                'current_cycle_id' => (int) $currentCycle->id,
                'csa_id'           => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Wrong billing cycle',
            ], 400);
        }

        $account = CustomerAccount::findOrFail($validated['account_id']);

        // Only unmetered accounts belong here; metered ones go through the
        // readings endpoint.
        if (! $account->isUnmetered()) {
            return response()->json([
                'success'    => false,
                'error_code' => 'ACCOUNT_NOT_UNMETERED',
                'message'    => 'This account is not unmetered. Submit a meter reading instead.',
            ], 422);
        }

        $existing = Unmetered::where('account_id', $account->id)
            ->where('billing_cycle_id', $cycleId)
            ->first();

        if ($existing) {
            return $this->duplicateResponse($existing->id, $account->id, $cycleId);
        }

        $photoPath = null;

        try {
            // Name the file from the server's own account number and the
            // detected file type, not from values supplied by the client.
            $filename = sprintf(
                '%s_%s_%s.%s',
                preg_replace('/[^A-Za-z0-9_-]/', '', $account->account_number),
                $capturedAt->format('Ymd_His'),
                strtoupper(Str::random(6)),
                $request->file('photo')->extension() ?: 'jpg'
            );

            $photoPath = $request->file('photo')->storeAs('unmetered', $filename, 'public');

            $unmetered = Unmetered::create([
                'account_id'             => $account->id,
                'csa_id'                 => auth()->id(),
                'billing_cycle_id'       => $cycleId,
                'remapped_from_cycle_id' => $remappedFrom,
                'meter_reading_code'     => $validated['meter_reading_code'],
                'photo_path'             => $photoPath,
                'comment'                => $validated['comment'] ?? null,
                'latitude'               => $validated['latitude'] ?? null,
                'longitude'              => $validated['longitude'] ?? null,
                'captured_at'            => $capturedAt,
                'synced_at'              => now(),
            ]);

            Log::info('Unmetered synced', [
                'unmetered_id'   => $unmetered->id,
                'account_number' => $account->account_number,
                'was_remapped'   => $remappedFrom !== null,
            ]);

            return response()->json([
                'success'      => true,
                'unmetered_id' => $unmetered->id,
                'message'      => 'Unmetered synced successfully',
            ]);
        } catch (Throwable $e) {
            $this->deletePhoto($photoPath);

            // Two requests for the same account raced past the check above;
            // the unique index rejected the second one. Report it as a duplicate,
            // but only when the other row really exists: the same SQLSTATE also
            // covers foreign key and NOT NULL failures, which are real errors.
            if ($e instanceof QueryException && in_array((string) $e->getCode(), ['23000', '23505'], true)) {
                $winner = Unmetered::where('account_id', $account->id)
                    ->where('billing_cycle_id', $cycleId)
                    ->value('id');

                if ($winner) {
                    return $this->duplicateResponse($winner, $account->id, $cycleId);
                }
            }

            Log::error('Unmetered sync failed', [
                'account_number' => $account->account_number,
                'error'          => $e->getMessage(),
                'line'           => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => app()->environment('production') ? 'Failed to sync unmetered' : $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 200 response telling the app this account is already done for the cycle.
     */
    private function duplicateResponse(?int $unmeteredId, int $accountId, int $cycleId): JsonResponse
    {
        Log::warning('Duplicate unmetered blocked', [
            'account_id'            => $accountId,
            'billing_cycle_id'      => $cycleId,
            'existing_unmetered_id' => $unmeteredId,
            'csa_id'                => auth()->id(),
        ]);

        return response()->json([
            'success'      => false,
            'error_code'   => 'DUPLICATE_UNMETERED',
            'message'      => 'This account has already been captured in the current cycle.',
            'unmetered_id' => $unmeteredId,
        ]);
    }

    /**
     * Remove a stored photo whose row was never saved.
     */
    private function deletePhoto(?string $photoPath): void
    {
        if ($photoPath && Storage::disk('public')->exists($photoPath)) {
            Storage::disk('public')->delete($photoPath);

            Log::warning('Orphaned unmetered photo deleted', ['photo_path' => $photoPath]);
        }
    }
}