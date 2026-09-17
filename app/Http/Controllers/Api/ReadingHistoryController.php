<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReadingHistoryResource;
use App\Models\BillingCycle;
use App\Models\Reading;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReadingHistoryController extends Controller
{
    /**
     * Serve the previous billing cycle's readings for mobile offline history.
     *
     * GET /api/reading-history?cycle_id=123&page=1&per_page=100
     *
     * `cycle_id` is the CURRENT cycle's id — resolved from the mobile
     * client's active cycle. This endpoint looks up the immediate
     * previous cycle relative to it and serves that cycle's readings.
     */
    public function index(Request $request): JsonResponse
    {
        // log start request
          Log::info('History download request ', [
            'billing_cycle_id' => $request->cycle_id,
            'csa_id' => auth()->id(),
        ]);

        $validated = $request->validate([
            'cycle_id' => ['required', 'integer', Rule::exists('billing_cycles', 'id')],
            'page'     => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:200'],
        ]);

        $currentCycle = BillingCycle::findOrFail($validated['cycle_id']);
        $previousCycle = $this->resolvePreviousCycle($currentCycle);

        if (!$previousCycle) {
            // No prior cycle exists (e.g. this is the very first cycle) —
            // return a valid, empty paginated shape rather than an error,
            // so the client's sync loop completes cleanly with 0 records.
            return response()->json([
                'data'         => [],
                'current_page' => 1,
                'last_page'    => 1,
                'total'        => 0,
            ]);
        }

        $perPage = $validated['per_page'] ?? 100;
        $page = $validated['page'] ?? 1;
        $csa_id = auth()->id();

        $paginator = Reading::query()
            ->where('csa_id', $csa_id)
            ->where('billing_cycle_id', $previousCycle->id)
            ->with(['account.zone', 'billingCycle', 'code'])
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'page',  (int) $page);

        return response()->json([
            'data'         => ReadingHistoryResource::collection($paginator->items()),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'total'        => $paginator->total(),
        ]);
    }

    /**
     * Resolve the cycle immediately preceding the given one.
     *
     * Ordered by start_date rather than id, since cycle ids aren't
     * guaranteed to be sequential in creation/chronological order.
     * Adjust the column name if your billing_cycles table uses a
     * different date field (e.g. starts_at).
     */
    private function resolvePreviousCycle(BillingCycle $currentCycle): ?BillingCycle
    {
        return BillingCycle::query()
            ->where('start_date', '<', $currentCycle->start_date)
            ->orderByDesc('start_date')
            ->first();
    }
}