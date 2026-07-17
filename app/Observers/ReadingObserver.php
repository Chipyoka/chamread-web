<?php
// app/Observers/ReadingObserver.php

namespace App\Observers;

use App\Models\Reading;
use App\Services\FlagService;

class ReadingObserver
{
    public function __construct(
        private FlagService $flagService
    ) {}

    /**
     * Handle the Reading "created" event.
     * Evaluate against all active reading rules.
     */
    public function created(Reading $reading): void
    {
        $this->flagService->evaluate($reading);
    }

    /**
     * Handle the Reading "updated" event.
     * Re-evaluate: remove flags that no longer apply, add new ones.
     */
    public function updated(Reading $reading): void
    {
        // Only re-evaluate if relevant fields changed
        if ($this->relevantFieldsChanged($reading)) {
            $this->flagService->reevaluate($reading);
        }
    }

    /**
     * Check if any fields used in active rules were modified.
     * Prevents unnecessary re-evaluation on unrelated updates.
     */
    private function relevantFieldsChanged(Reading $reading): bool
    {
        // Get all field names used in active reading rules
        $watchedFields = \App\Models\FlagRule::active()
            ->whereHas('flag', function ($query) {
                $query->where('applies_to', 'reading')->where('active', true);
            })
            ->pluck('field')
            ->unique()
            ->toArray();

        // Check if any watched field was changed
        return $reading->wasChanged($watchedFields);
    }
}