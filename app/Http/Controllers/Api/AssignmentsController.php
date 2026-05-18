<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CsaAssignment;
use App\Models\BillingCycle;
use App\Models\Reading;
use App\Models\CustomerAccount;

class AssignmentsController extends Controller
{
    /**
     * Get current assignments and stats for logged-in CSA
     */
    public function current(Request $request)
    {
        $user = $request->user();

        // Ensure the user is a CSA
        // if ($user->role !== 'CSA') {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Access denied. Not a CSA account.'
        //     ], 403);
        // }

        // Get current billing cycle (latest active)
        $currentCycle = BillingCycle::latest()->first();

        if (!$currentCycle) {
            return response()->json([
                'success' => false,
                'message' => 'No billing cycle found.'
            ], 404);
        }

        // Get CSA assignments for current billing cycle
        $assignments = CsaAssignment::with(['zone', 'dma'])
            ->where('csa_id', $user->id)
            ->where('billing_cycle_id', $currentCycle->id)
            ->get();



        // Get CSA readings for current billing cycle
        $readings = Reading::where('csa_id', $user->id)
            ->where('billing_cycle_id', $currentCycle->id)
            ->get();

        //Get top 5 customer accounts withing the CSA's assigned zones/dmas for the current billing cycle
        //lets get the recent assignment's zone id.
        $assignedZone = $assignments->pluck('zone_id')->toArray();

        $topAccounts = CustomerAccount::whereIn('zone_id', $assignedZone)
            ->take(5)
            ->get(); 

        // Compute stats
        $totalTarget = $assignments->sum('target');

        $completed = Reading::where('csa_id', $user->id)
            ->where('billing_cycle_id', $currentCycle->id)
            ->count();

        $pending = max($totalTarget - $completed, 0);
        $completion = $totalTarget > 0 ? round(($completed / $totalTarget) * 100, 2) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'billing_cycle' => [
                    'id' => $currentCycle->id,
                    'name' => $currentCycle->name,
                    'start_date' => $currentCycle->start_date,
                    'end_date' => $currentCycle->end_date,
                ],
                'assignments' => $assignments->map(function ($a) {
                    return [
                        'id' => $a->id,
                        'zone' => $a->zone ? ['id' => $a->zone->id, 'name' => $a->zone->name] : null,
                        'dma' => $a->dma ? ['id' => $a->dma->id, 'name' => $a->dma->name] : null,
                        'target' => $a->target,
                        'assignment_type' => $a->assignment_type,
                        'status' => $a->status,
                    ];
                }),
                'readings' => $readings->map(function ($r) {
                    return [
                        'id' => $r->id,
                        'account_number' => $r->account_number,
                        'meter_number' => $r->meter_number,
                        'current_reading' => $r->current_reading,
                        'billing_area' => $r->billing_area,
                        'customer_name' => $r->account->name ?? null,
                        'photo_url' => $r->photo_path,
                        'reading_time' => $r->reading_time,
                        'reason' => $r->reason_code,
                    ];
                }),
                'top_accounts' => $topAccounts->map(function ($a) {
                    return [
                        'id' => $a->id,
                        'account_number' => $a->account_number,
                        'name' => $a->name,
                        'address' => $a->address,
                        'phone' => $a->phone,
                    ];
                }),
                'stats' => [
                    'target' => $totalTarget,
                    'completed' => $completed,
                    'pending' => $pending,
                    'completion_percent' => $completion,
                ]
            ]
        ]);
    }
}