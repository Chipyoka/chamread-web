<?php

namespace App\Services;

use App\Models\FlagRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RuleEvaluator
{
    /**
     * Evaluate a model against all applicable rules for its entity type.
     * Rules sharing a flag_id + group_key are ANDed as one unit; everything
     * else (including every rule with a null group_key) stands alone, exactly
     * as before this change.
     *
     * Returns one FlagRule per matched group — the first rule in that group,
     * used downstream only to read ->flag. Use buildContext() for the full
     * set of rules that matched.
     */
    public function evaluate(Model $model): Collection
    {
        $entityType = $this->getEntityType($model);

        return $this->activeRulesFor($entityType)
            ->groupBy(fn (FlagRule $r) => $r->flag_id . '|' . ($r->group_key ?? 'solo-' . $r->id))
            ->filter(fn (Collection $group) => $group->every(
                fn (FlagRule $r) => $this->evaluateRule($r, $model)
            ))
            ->map(fn (Collection $group) => $group->first())
            ->values();
    }

    /**
     * Every rule belonging to the same group as $rule (including itself).
     * Used by buildContext() to report the full set of conditions that matched.
     */
    public function rulesInGroup(FlagRule $rule): Collection
    {
        return $this->activeRulesFor($this->applicableType($rule))
            ->filter(
                fn (FlagRule $r) =>
                    $r->flag_id === $rule->flag_id
                    && ($r->group_key ?? 'solo-' . $r->id)
                        === ($rule->group_key ?? 'solo-' . $rule->id)
            );
    }

    /**
     * Evaluate a single rule against a model.
     */
    public function evaluateRule(FlagRule $rule, Model $model): bool
    {
        $field = $rule->field;
        $operator = $rule->operator;
        $threshold = $rule->value;

        $actualValue = $this->getModelValue($model, $field);

        if ($operator === 'is_null') {
            return is_null($actualValue);
        }

        if ($operator === 'is_not_null') {
            return !is_null($actualValue);
        }

        if (is_null($actualValue)) {
            return false;
        }

        return match ($operator) {
            '>' => (float) $actualValue > (float) $threshold,

            '<' => (float) $actualValue < (float) $threshold,

            '>=' => (float) $actualValue >= (float) $threshold,

            '<=' => (float) $actualValue <= (float) $threshold,

            '=' => (string) $actualValue === (string) $threshold,

            '!=' => (string) $actualValue !== (string) $threshold,

            'contains' => str_contains(
                strtolower((string) $actualValue),
                strtolower((string) $threshold)
            ),

            'starts_with' => str_starts_with(
                strtolower((string) $actualValue),
                strtolower((string) $threshold)
            ),

            'ends_with' => str_ends_with(
                strtolower((string) $actualValue),
                strtolower((string) $threshold)
            ),

            'in' => in_array(
                (string) $actualValue,
                explode(',', (string) $threshold)
            ),

            default => false,
        };
    }

    /**
     * Context lists every rule in the matched group.
     */
    public function buildContext(FlagRule $rule, Model $model): array
    {
        $group = $this->rulesInGroup($rule);

        return [
            'conditions' => $group
                ->map(fn (FlagRule $r) => [
                    'field' => $r->field,
                    'operator' => $r->operator,
                    'threshold' => $r->value,
                    'actual_value' => $this->getModelValue($model, $r->field),
                ])
                ->values()
                ->all(),

            'evaluated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Get all active rules for an entity type.
     *
     * Caching has intentionally been removed. This ensures that Eloquent
     * Collections are not serialized into the application cache and avoids
     * __PHP_Incomplete_Class errors when cached objects cannot be restored.
     */
    private function activeRulesFor(string $entityType): Collection
    {
        return FlagRule::active()
            ->whereHas('flag', function ($query) use ($entityType) {
                $query->where('applies_to', $entityType)
                    ->where('active', true);
            })
            ->with('flag')
            ->orderBy('order')
            ->get();
    }

    private function applicableType(FlagRule $rule): string
    {
        return $rule->flag->applies_to;
    }

    private function getModelValue(Model $model, string $field): mixed
    {
        if (str_contains($field, '.')) {
            return $this->getNestedValue($model, $field);
        }

        $accessor = 'get' . Str::studly($field) . 'Attribute';

        if (method_exists($model, $accessor)) {
            return $model->{$field};
        }

        return $model->getAttribute($field);
    }

    private function getNestedValue(Model $model, string $field): mixed
    {
        $parts = explode('.', $field);
        $value = $model;

        foreach ($parts as $index => $part) {
            if ($index > 2) {
                break;
            }

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

            if (is_null($value)) {
                break;
            }
        }

        if ($value instanceof Model) {
            return $value->getKey();
        }

        return is_scalar($value) ? $value : (string) $value;
    }

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

