<?php

namespace App\Observers;

use App\Models\FlagRule;
use App\Models\Reading;
use App\Services\ReadingVerifier;

class ReadingObserver
{
    public function __construct(
        private ReadingVerifier $verifier
    ) {}

    /**
     * New reading: run the rules and set auto / flagged.
     */
    public function created(Reading $reading): void
    {
        $this->run($reading);
    }

    /**
     * Edited reading: re-verify only if a field used by an active rule changed.
     */
    public function updated(Reading $reading): void
    {
        if ($this->relevantFieldsChanged($reading)) {
            $this->run($reading);
        }
    }

    /**
     * A verification error must never break the upload loop.
     * The reading is kept and marked 'failed' so it can be retried
     * with `php artisan readings:verify --include-failed`.
     */
    private function run(Reading $reading): void
    {
        try {
            $this->verifier->verify($reading);
        } catch (\Throwable $e) {
            report($e);
            $this->verifier->markFailed($reading);
        }
    }

    private function relevantFieldsChanged(Reading $reading): bool
    {
        $watchedFields = FlagRule::active()
            ->whereHas('flag', function ($query) {
                $query->where('applies_to', 'reading')->where('active', true);
            })
            ->pluck('field')
            ->unique()
            ->toArray();

        return $reading->wasChanged($watchedFields);
    }
}