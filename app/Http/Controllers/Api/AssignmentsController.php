<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BillingCycle;
use App\Models\CsaAssignment;
use App\Models\CustomerAccount;
use App\Models\Reading;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AssignmentsController extends Controller
{
    private const STATUS_METERED = 'METERED';

    /**
     * Get current assignments and stats for the logged-in CSA.
     */
    public function current(Request $request): JsonResponse
    {
        $user = $request->user();

        // The most recent billing cycle must exist and be active.
        $currentCycle = BillingCycle::latest()->first();

        if (!$currentCycle) {
            return $this->notFound('No billing cycle found.');
        }

        if ($currentCycle->status !== 'active') {
            return $this->notFound('No active billing cycle found.');
        }

        $assignments = CsaAssignment::with(['zone', 'dma'])
            ->where('csa_id', $user->id)
            ->where('billing_cycle_id', $currentCycle->id)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'billing_cycle' => [
                    'id' => $currentCycle->id,
                    'name' => $currentCycle->name,
                    'start_date' => $currentCycle->start_date,
                    'end_date' => $currentCycle->end_date,
                    'can_upload' => $currentCycle->can_upload,
                ],
                'assignments' => $assignments->map(fn ($a) => [
                    'id' => $a->id,
                    'zone' => $a->zone ? ['id' => $a->zone->id, 'name' => $a->zone->name] : null,
                    'dma' => $a->dma ? ['id' => $a->dma->id, 'name' => $a->dma->name] : null,
                    'target' => $a->target,
                    'assignment_type' => $a->assignment_type,
                    'status' => $a->status,
                ]),
                'stats' => $this->buildStats($assignments, $user->id, $currentCycle->id),
            ],
        ]);
    }

    /**
     * Build the stats block for a CSA's assignments in a billing cycle.
     *
     * Every total is split into a metered and an unmetered part, and each
     * pair always adds up to its total:
     *
     *   metered           + unmetered           = target
     *   metered_completed + unmetered_completed = completed
     *   metered_pending   + unmetered_pending   = pending
     *
     * @param  Collection<int, CsaAssignment>  $assignments
     * @return array<string, int|float>
     */
    private function buildStats(Collection $assignments, int $csaId, int $billingCycleId): array
    {
        $target = (int) $assignments->sum('target');

        // Base query for this CSA's readings in the cycle; cloned per count.
        $readings = Reading::where('csa_id', $csaId)
            ->where('billing_cycle_id', $billingCycleId);

        $completed = (clone $readings)->count();
        $pending = max($target - $completed, 0);

        [$metered, $unmetered] = $this->meterBreakdown($assignments, $target);
        [$meteredCompleted, $unmeteredCompleted] = $this->completedBreakdown($readings, $completed);
        [$meteredPending, $unmeteredPending] = $this->pendingBreakdown($metered, $meteredCompleted, $pending);

        return [
            'target' => $target,
            'completed' => $completed,
            'pending' => $pending,
            'completion_percent' => $target > 0 ? round(($completed / $target) * 100, 2) : 0,

            'metered' => $metered,
            'unmetered' => $unmetered,

            'metered_completed' => $meteredCompleted,
            'unmetered_completed' => $unmeteredCompleted,

            'metered_pending' => $meteredPending,
            'unmetered_pending' => $unmeteredPending,
        ];
    }

    /**
     * Split the target into metered and unmetered accounts.
     *
     * Accounts are resolved through the zones of the CSA's assignments.
     *
     * The target is authoritative: metered + unmetered always equals it.
     * The metered count comes from the METERED accounts in those zones
     * (capped at the target) and unmetered is the remainder, so the two can
     * never drift from the target even when the zone's account count and the
     * assigned target differ.
     *
     * @param  Collection<int, CsaAssignment>  $assignments
     * @return array{0: int, 1: int}  [metered, unmetered]
     */
    private function meterBreakdown(Collection $assignments, int $target): array
    {
        $zoneIds = $assignments->pluck('zone_id')->filter()->unique()->values();

        if ($zoneIds->isEmpty() || $target <= 0) {
            return [0, max($target, 0)];
        }

        $meteredCount = (int) CustomerAccount::whereIn('zone_id', $zoneIds)
            ->where('account_status', self::STATUS_METERED)
            ->count();

        $metered = min($meteredCount, $target);
        $unmetered = $target - $metered;

        return [$metered, $unmetered];
    }

    /**
     * Split the completed readings into metered and unmetered.
     *
     * A reading is metered when its customer account is METERED. Readings
     * are matched to accounts through readings.account_id. Unmetered is the
     * remainder, so the two always add up to the completed total.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $readings  Base query for the CSA's readings in the cycle.
     * @return array{0: int, 1: int}  [metered_completed, unmetered_completed]
     */
    private function completedBreakdown($readings, int $completed): array
    {
        if ($completed <= 0) {
            return [0, 0];
        }

        $meteredAccountIds = CustomerAccount::select('id')
            ->where('account_status', self::STATUS_METERED);

        $meteredCompleted = (clone $readings)
            ->whereIn('account_id', $meteredAccountIds)
            ->count();

        $meteredCompleted = min($meteredCompleted, $completed);

        return [$meteredCompleted, $completed - $meteredCompleted];
    }

    /**
     * Split the pending (not read) total into metered and unmetered.
     *
     * Metered pending is the metered target minus the metered readings done,
     * kept within 0 and the overall pending total. Unmetered pending is the
     * remainder, so the two always add up to the pending total.
     *
     * @return array{0: int, 1: int}  [metered_pending, unmetered_pending]
     */
    private function pendingBreakdown(int $metered, int $meteredCompleted, int $pending): array
    {
        $meteredPending = min(max($metered - $meteredCompleted, 0), $pending);

        return [$meteredPending, $pending - $meteredPending];
    }

    private function notFound(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 404);
    }
}