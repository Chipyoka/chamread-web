<?php
// app/Services/RuleEvaluator.php

namespace App\Services;

use App\Models\FlagRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class RuleEvaluator
{
    /**
     * Evaluate a single model against all applicable rules
     * for its entity type. Returns collection of matched rules.
     */
    public function evaluate(Model $model): Collection
    {
        $entityType = $this->getEntityType($model);

        // Fetch all active rules for this entity type
        $rules = FlagRule::active()
            ->whereHas('flag', function ($query) use ($entityType) {
                $query->where('applies_to', $entityType)
                      ->where('active', true);
            })
            ->orderBy('order')
            ->get();

        // Evaluate each rule against the model
        return $rules->filter(function (FlagRule $rule) use ($model) {
            return $this->evaluateRule($rule, $model);
        });
    }

    /**
     * Evaluate a single rule against a model.
     * Returns true if the condition is met.
     */
    public function evaluateRule(FlagRule $rule, Model $model): bool
    {
        $field = $rule->field;
        $operator = $rule->operator;
        $threshold = $rule->value;

        // Get the actual value from the model
        $actualValue = $this->getModelValue($model, $field);

        // Null handling for is_null / is_not_null operators
        if ($operator === 'is_null') {
            return is_null($actualValue);
        }

        if ($operator === 'is_not_null') {
            return !is_null($actualValue);
        }

        // If actual value is null, comparison operators always return false
        if (is_null($actualValue)) {
            return false;
        }

        return match ($operator) {
            '>'           => (float) $actualValue > (float) $threshold,
            '<'           => (float) $actualValue < (float) $threshold,
            '>='          => (float) $actualValue >= (float) $threshold,
            '<='          => (float) $actualValue <= (float) $threshold,
            '='           => (string) $actualValue === (string) $threshold,
            '!='          => (string) $actualValue !== (string) $threshold,
            'contains'    => str_contains(
                strtolower((string) $actualValue), 
                strtolower((string) $threshold)
            ),
            'starts_with' => str_starts_with(
                strtolower((string) $actualValue), 
                strtolower((string) $threshold)
            ),
            'ends_with'   => str_ends_with(
                strtolower((string) $actualValue), 
                strtolower((string) $threshold)
            ),
            'in'          => in_array(
                (string) $actualValue, 
                explode(',', (string) $threshold)
            ),
            default       => false,
        };
    }

    /**
     * Build context array showing what triggered the rule.
     */
    public function buildContext(FlagRule $rule, Model $model): array
    {
        return [
            'rule_id' => $rule->id,
            'field' => $rule->field,
            'operator' => $rule->operator,
            'threshold' => $rule->value,
            'actual_value' => $this->getModelValue($model, $rule->field),
            'evaluated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Extract a value from the model, supporting dot notation
     * for nested attributes (e.g., 'account.status').
     */
    private function getModelValue(Model $model, string $field): mixed
    {
        // Support dot notation for relationships
        if (str_contains($field, '.')) {
            return $this->getNestedValue($model, $field);
        }

        // Try accessor method first (getStatusAttribute)
        $accessor = 'get' . studly_case($field) . 'Attribute';
        if (method_exists($model, $accessor)) {
            return $model->{$field};
        }

        // Try regular attribute
        return $model->getAttribute($field);
    }

    /**
     * Resolve dot notation: 'account.zone.code' -> $reading->account->zone->code
     * Max depth of 3 to prevent performance issues.
     */
    private function getNestedValue(Model $model, string $field): mixed
    {
        $parts = explode('.', $field);
        $value = $model;

        foreach ($parts as $index => $part) {
            if ($index > 2) break; // Safety limit
            
            if ($value instanceof Model && $value->relationLoaded($part)) {
                $value = $value->getRelation($part);
            } elseif ($value instanceof Model) {
                $value = $value->{$part} ?? null;
            } elseif (is_object($value) && isset($value->{$part})) {
                $value = $value->{$part};
            } elseif (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } else {
                return null;
            }

            if (is_null($value)) break;
        }

        // Return scalar, not object
        if ($value instanceof Model) {
            return $value->getKey();
        }

        return is_scalar($value) ? $value : (string) $value;
    }

    /**
     * Determine the entity type string from a model instance.
     */
    private function getEntityType(Model $model): string
    {
        return match (get_class($model)) {
            'App\Models\CustomerAccount' => 'account',
            'App\Models\Reading' => 'reading',
            'App\Models\User' => 'meter_reader',
            default => throw new \InvalidArgumentException(
                'Unsupported model type: ' . get_class($model)
            ),
        };
    }
}