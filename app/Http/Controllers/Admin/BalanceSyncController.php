<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\BalanceSyncRun;
use Illuminate\Http\Request;

class BalanceSyncController extends Controller
{
    public function trigger(Request $request)
    {
        $active = BalanceSyncRun::whereIn('status', ['pending', 'running', 'paused_api_down'])->first();

        if ($active) {
            return response()->json([
                'message' => "A sync is already {$active->status} (run #{$active->id}), scheduled {$active->scheduled_start}.",
            ], 409);
        }

        $now = now();
        $todaySixPm = $now->copy()->setTime(18, 0);
        $start = $now->lt($todaySixPm) ? $todaySixPm : $todaySixPm->addDay();
        $end = $start->copy()->addHours(12);

        $run = BalanceSyncRun::create([
            'triggered_by' => $request->user()?->id,
            'requested_at' => $now,
            'scheduled_start' => $start,
            'scheduled_end' => $end,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => "Sync scheduled: {$start->format('D, j M H:i')} → {$end->format('D, j M H:i')}. Check back after {$end->format('H:i')}.",
            'run' => $run,
        ]);
    }

    public function index()
    {
        return response()->json(
            BalanceSyncRun::orderByDesc('id')->paginate(20)
        );
    }

    public function show(BalanceSyncRun $run)
    {
        return response()->json([
            'run' => $run,
            'failed_items' => $run->items()->where('status', 'failed')->paginate(50),
        ]);
    }
}