<?php

namespace App\Jobs;

use App\Models\BalanceSyncItem;
use App\Models\BalanceSyncRun;
use App\Models\CustomerAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Http;

class FetchAccountBalanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 3;

    public function __construct(
        public int $runId,
        public string $accountNumber,
    ) {}

    public function middleware(): array
    {
        return [new RateLimited('balance-api')];
    }

    public function handle(): void
    {
        $run = BalanceSyncRun::find($this->runId);

        // If the run was paused (circuit breaker) or already closed, don't burn attempts.
        if (!$run || !in_array($run->status, ['running'])) {
            $this->release(30);
            return;
        }

        $item = BalanceSyncItem::where('run_id', $this->runId)
            ->where('account_number', $this->accountNumber)
            ->first();

        if (!$item) {
            return; // shouldn't happen, but don't crash the worker over it
        }

        try {
            $response = Http::timeout(config('services.balance_api.timeout'))
                ->retry(2, 500) // handles transient connection blips only
                ->get(config('services.balance_api.url'), [
                    'query' => $this->accountNumber,
                ]);

            if ($response->status() === 429) {
                $retryAfter = (int) $response->header('Retry-After', 60);
                $this->release($retryAfter);
                return;
            }

            if ($response->failed()) {
                throw new \RuntimeException("HTTP {$response->status()}");
            }

            $rawBalance = $response->json('balance');
            if ($rawBalance === null) {
                throw new \RuntimeException('Response missing balance field');
            }

            $balance = $this->parseBalance($rawBalance);

            $item->update([
                'status' => 'success',
                'balance' => $balance,
                'attempts' => $item->attempts + 1,
            ]);

            CustomerAccount::where('account_number', $this->accountNumber)->update([
                'balance' => $balance,
                'checked_at' => now(),
            ]);

            $run->increment('success_count');
        } catch (\Throwable $e) {
            $item->update([
                'status' => 'failed',
                'last_error' => substr($e->getMessage(), 0, 250),
                'attempts' => $item->attempts + 1,
            ]);

            $run->increment('failed_count');

            $this->checkCircuitBreaker($run);

            throw $e; // let tries/backoff() handle the retry
        } finally {
            $run->increment('processed_count');
        }
    }

    public function backoff(): array
    {
        return [15, 60, 300];
    }

    private function parseBalance(string $raw): string
    {
        // "-10,343.45" -> "-10343.45"
        return str_replace(',', '', $raw);
    }

    private function checkCircuitBreaker(BalanceSyncRun $run): void
    {
        $recentFailures = BalanceSyncItem::where('run_id', $run->id)
            ->where('status', 'failed')
            ->where('updated_at', '>=', now()->subMinutes(5))
            ->count();

        if ($recentFailures >= 20 && $run->status === 'running') {
            $run->update(['status' => 'paused_api_down']);
            // TODO: notify admin — Slack/email/Notification class of your choice
        }
    }
}