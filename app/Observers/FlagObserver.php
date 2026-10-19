<?php

namespace App\Observers;

use App\Models\Flag;
use Illuminate\Support\Facades\Cache;

/**
 * Register for both Flag and FlagRule in AppServiceProvider::boot():
 *   Flag::observe(FlagObserver::class);
 *   FlagRule::observe(FlagObserver::class);
 *
 * Busts the RuleEvaluator's per-entity-type rule cache whenever a flag or
 * rule is created, updated, or deleted — so admin edits (new rule, threshold
 * change, activate/deactivate) take effect on the next reading without a
 * deploy or cache:clear.
 */
class FlagObserver
{
    public function saved($model): void
    {
        $this->bust($model);
    }

    public function deleted($model): void
    {
        $this->bust($model);
    }

    private function bust($model): void
    {
        $appliesTo = $model instanceof Flag
            ? $model->applies_to
            : $model->flag?->applies_to;

        foreach (array_filter([$appliesTo, 'account', 'reading', 'meter_reader']) as $type) {
            Cache::forget("flag_rules.active.{$type}");
        }
    }
}