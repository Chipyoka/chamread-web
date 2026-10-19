<?php

namespace App\Services;

use App\Models\Reading;

/**
 * Thin verification layer on top of the flag engine.
 *
 * 1. Re-evaluates the reading's rule flags (FlagService).
 * 2. If any attached flag has requires_verification = true -> 'flagged', else 'auto'.
 *
 * A supervisor's decision ('manual') is never overwritten.
 */
class ReadingVerifier
{
    public function __construct(
        private FlagService $flags
    ) {}

    public function verify(Reading $reading): void
    {
        // Only loaded on fetched models; a freshly created reading can't be manual yet.
        if ($reading->verification_status === 'manual') {
            return;
        }

        $this->flags->reevaluate($reading);

        $holds = $reading->flags()
            ->where('flags.requires_verification', true)
            ->exists();

        $this->setStatus($reading, $holds ? 'flagged' : 'auto');
    }

    public function markFailed(Reading $reading): void
    {
        $this->setStatus($reading, 'failed');
    }

    /**
     * Writes the status with a direct query so no model events fire and the
     * caller's instance isn't re-saved. The != 'manual' guard means a stale
     * instance can never overwrite a supervisor's decision.
     */
    private function setStatus(Reading $reading, string $status): void
    {
        $reading->newQuery()
            ->whereKey($reading->getKey())
            ->where('verification_status', '!=', 'manual')
            ->toBase()
            ->update(['verification_status' => $status]);

        $reading->setAttribute('verification_status', $status);
        $reading->syncOriginalAttribute('verification_status');
    }
}