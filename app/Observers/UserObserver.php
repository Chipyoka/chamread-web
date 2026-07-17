<?php
// app/Observers/UserObserver.php

namespace App\Observers;

use App\Models\User;
use App\Services\FlagService;

class UserObserver
{
    public function __construct(
        private FlagService $flagService
    ) {}

    /**
     * Handle the User "created" event.
     * Only evaluate meter readers.
     */
    public function created(User $user): void
    {
        if ($user->isMeterReader()) {
            $this->flagService->evaluate($user);
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        if ($user->isMeterReader() && $this->relevantFieldsChanged($user)) {
            $this->flagService->reevaluate($user);
        }
    }

    private function relevantFieldsChanged(User $user): bool
    {
        $watchedFields = \App\Models\FlagRule::active()
            ->whereHas('flag', function ($query) {
                $query->where('applies_to', 'meter_reader')->where('active', true);
            })
            ->pluck('field')
            ->unique()
            ->toArray();

        return $user->wasChanged($watchedFields);
    }
}