<?php

namespace App\Console\Commands;

use App\Jobs\FetchAccountBalanceJob;
use App\Models\BalanceSyncItem;
use App\Models\BalanceSyncRun;
use App\Models\CustomerAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DispatchBalanceRun extends Command
{
    protected $signature = 'balance:dispatch-run';
    protected $description = 'Starts a pending balance sync run whose scheduled_start has arrived';

    public function handle(): void
    {
        $run = BalanceSyncRun::where('status', 'pending')
            ->where('scheduled_start', '<=', now())
            ->first();

        if (!$run) {
            return;
        }

        $run->update(['status' => 'running', 'started_at' => now()]);
        $this->info("Starting balance sync run #{$run->id}");

        $total = 0;

        CustomerAccount::query()->orderBy('id')->chunkById(1000, function ($accounts) use ($run, &$total) {
            $itemRows = [];
            foreach ($accounts as $account) {
                $itemRows[] = [
                    'run_id' => $run->id,
                    'account_number' => $account->account_number,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('balance_sync_items')->insert($itemRows);

            foreach ($accounts as $account) {
                FetchAccountBalanceJob::dispatch($run->id, $account->account_number)
                    ->onQueue('balance-sync');
            }

            $total += count($itemRows);
        });

        $run->update(['total_accounts' => $total]);
        $this->info("Dispatched {$total} jobs for run #{$run->id}");
    }
}