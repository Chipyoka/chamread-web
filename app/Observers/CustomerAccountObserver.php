<?php
// app/Observers/CustomerAccountObserver.php

namespace App\Observers;

use App\Models\CustomerAccount;
use App\Services\FlagService;

class CustomerAccountObserver
{
    public function __construct(
        private FlagService $flagService
    ) {}

    /**
     * Handle the CustomerAccount "created" event.
     */
    public function created(CustomerAccount $account): void
    {
        $this->flagService->evaluate($account);
    }

    /**
     * Handle the CustomerAccount "updated" event.
     */
    public function updated(CustomerAccount $account): void
    {
        if ($this->relevantFieldsChanged($account)) {
            $this->flagService->reevaluate($account);
        }
    }

    private function relevantFieldsChanged(CustomerAccount $account): bool
    {
        $watchedFields = \App\Models\FlagRule::active()
            ->whereHas('flag', function ($query) {
                $query->where('applies_to', 'account')->where('active', true);
            })
            ->pluck('field')
            ->unique()
            ->toArray();

        return $account->wasChanged($watchedFields);
    }
}