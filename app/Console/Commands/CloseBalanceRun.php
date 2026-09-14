<?php

namespace App\Console\Commands;

use App\Models\BalanceSyncItem;
use App\Models\BalanceSyncRun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CloseBalanceRun extends Command
{
    protected $signature = 'balance:close-run';
    protected $description = 'Closes out the active balance sync run at the end of its window';

    public function handle(): void
    {
        $run = BalanceSyncRun::whereIn('status', ['running', 'paused_api_down'])->first();

        if (!$run) {
            return;
        }

        $remaining = BalanceSyncItem::where('run_id', $run->id)
            ->where('status', 'pending')
            ->count();

        BalanceSyncItem::where('run_id', $run->id)
            ->where('status', 'pending')
            ->update(['status' => 'not_attempted']);

        // Remove any of this run's jobs still sitting in the queue table
        // so they don't fire outside the 18:00-06:00 window.
        DB::table('jobs')
            ->where('payload', 'like', '%"runId":' . $run->id . ',%')
            ->delete();

        $run->update([
            'status' => $remaining > 0 ? 'completed_with_gaps' : 'completed',
            'skipped_count' => $remaining,
            'completed_at' => now(),
            'meta' => [
                'note' => $remaining > 0 ? "{$remaining} accounts not reached in window" : null,
            ],
        ]);

        $this->info("Closed run #{$run->id} — status: {$run->status}, skipped: {$remaining}");
    }
}