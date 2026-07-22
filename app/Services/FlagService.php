<?php
// app/Services/FlagService.php

namespace App\Services;

use App\Models\Flag;
use App\Models\FlagRule;
use App\Models\Flaggable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class FlagService
{
    public function __construct(
        private RuleEvaluator $evaluator
    ) {}

    /**
     * Evaluate a model against all rules and apply matching flags.
     * This is the main entry point called by observers or manually.
     * 
     * Returns array of applied flag codes.
     */
    public function evaluate(Model $model): array
    {
        $matchedRules = $this->evaluator->evaluate($model);

        $appliedFlags = [];

        foreach ($matchedRules as $rule) {
            $context = $this->evaluator->buildContext($rule, $model);
            
            // Use the trait method we defined
            $result = $model->attachFlag(
                flagCode: $rule->flag->code,
                source: 'rule',
                context: $context,
                userId: null, // System-applied
                expiresAt: null
            );

            if ($result !== false) {
                $appliedFlags[] = $rule->flag->code;
            }
        }

        return $appliedFlags;
    }

    /**
     * Re-evaluate a model: remove expired/incorrect flags and reapply.
     * Useful when a record is updated and rules may no longer match.
     */
        public function reevaluate(Model $model): array
        {
            // Remove only automatically applied, non-expired flags
           Flaggable::where('flaggable_type', get_class($model))
                ->where('flaggable_id', $model->getKey())
                ->where('source', 'rule')
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->delete();

            return $this->evaluate($model);
        }

    /**
     * Manually attach a flag to a model (for admin UI).
     */
    public function manuallyAttach(Model $model, string $flagCode, int $userId = null, string $note = null): bool
    {
        return (bool) $model->attachFlag(
            flagCode: $flagCode,
            source: 'manual',
            context: ['note' => $note, 'applied_by' => $userId],
            userId: $userId
        );
    }

    /**
     * Manually detach a flag from a model.
     */
    public function manuallyDetach(Model $model, string $flagCode): bool
    {
        return $model->detachFlag($flagCode) > 0;
    }

    /**
     * Get all active rules grouped by entity type.
     * Useful for admin views.
     */
    public function getActiveRules(): Collection
    {
        return FlagRule::active()
            ->with('flag')
            ->get()
            ->groupBy('flag.applies_to');
    }

    /**
     * Validate that a rule is syntactically correct by testing it
     * against a dummy model instance.
     */
    public function validateRule(FlagRule $rule): bool
    {
        // Create an empty model of the correct type
        $modelClass = match ($rule->flag->applies_to) {
            'account' => 'App\Models\CustomerAccount',
            'reading' => 'App\Models\Reading',
            'meter_reader' => 'App\Models\User',
        };

        $model = new $modelClass();

        try {
            // This will always return false for an empty model,
            // but it validates the field exists and operator is valid
            $this->evaluator->evaluateRule($rule, $model);
            return true;
        } catch (\Exception) {
            return false;
        }
    }



        /**
         * Get total count of currently flagged entities.
         * 
         * @param string $type  'account', 'reading', or 'meter_reader'
         * @param string|null $flagCode  Optional: filter by specific flag code
         */
        public function totalFlagged(string $type, ?string $flagCode = null): int
        {
            $modelClass = match ($type) {
                'account'      => 'App\Models\CustomerAccount',
                'reading'      => 'App\Models\Reading',
                'meter_reader' => 'App\Models\User',
            };

            $query = \App\Models\Flaggable::active()
                ->where('flaggable_type', $modelClass);

            if ($flagCode) {
                $query->whereHas('flag', fn($q) => $q->where('code', $flagCode));
            }

            return $query->distinct('flaggable_id')->count('flaggable_id');
        }
}