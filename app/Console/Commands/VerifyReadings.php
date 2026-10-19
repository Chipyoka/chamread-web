<?php

namespace App\Console\Commands;

use App\Models\Reading;
use App\Services\ReadingVerifier;
use Illuminate\Console\Command;

/**
 * Two jobs in one command:
 *
 *   php artisan readings:verify --backfill
 *     One-time, right after the migration: stamps every pre-existing
 *     'pending' reading as 'auto' so they don't flood the dashboard.
 *     Skips the current billing cycle by default since the dashboard
 *     already scopes to it — pass --include-current-cycle=ID to include it.
 *
 *   php artisan readings:verify --include-failed
 *     Re-runs verification for readings stuck at 'pending' or 'failed'
 *     (e.g. after a bug fix or a transient error).
 */
class VerifyReadings extends Command
{
    protected $signature = 'readings:verify
                            {--backfill : Stamp existing pending readings as auto (run once after migrating)}
                            {--include-current-cycle= : Billing cycle ID to exclude from --backfill}
                            {--include-failed : Also process readings currently marked failed}
                            {--chunk=200}';

    protected $description = 'Backfill or retry reading verification_status';

    public function handle(ReadingVerifier $verifier): int
    {
        if ($this->option('backfill')) {
            return $this->backfill();
        }

        $statuses = ['pending'];
        if ($this->option('include-failed')) {
            $statuses[] = 'failed';
        }

        $chunkSize = (int) $this->option('chunk');
        $total = 0;

        Reading::whereIn('verification_status', $statuses)
            ->chunkById($chunkSize, function ($readings) use ($verifier, &$total) {
                foreach ($readings as $reading) {
                    $verifier->verify($reading);
                    $total++;
                }
                $this->output->write('.');
            });

        $this->newLine();
        $this->info("Verified {$total} reading(s).");

        return self::SUCCESS;
    }

    private function backfill(): int
    {
        $query = Reading::where('verification_status', 'pending');

        if ($cycleId = $this->option('include-current-cycle')) {
            $query->where('billing_cycle_id', '!=', $cycleId);
        }

        $count = $query->count();

        if (!$this->confirm("Mark {$count} existing pending reading(s) as 'auto'?")) {
            return self::SUCCESS;
        }

        $query->update(['verification_status' => 'auto']);
        $this->info("Backfilled {$count} reading(s).");

        return self::SUCCESS;
    }
}