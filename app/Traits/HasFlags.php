<?php
// app/Traits/HasFlags.php

namespace App\Traits;

use App\Models\Flag;
use App\Models\Flaggable;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasFlags
{
    /**
     * Get all flags attached to this model.
     */
    public function flags(): MorphToMany
    {
        return $this->morphToMany(Flag::class, 'flaggable', 'flaggables')
                    ->withPivot(['source', 'created_by', 'context', 'expires_at', 'id'])
                    ->withTimestamps()
                    ->where(function ($query) {
                        $query->whereNull('flaggables.expires_at')
                            ->orWhere('flaggables.expires_at', '>', now());
                    });
    }

    /**
     * Get all flags including expired (for history/audit).
     */
    public function allFlags(): MorphToMany
    {
        return $this->morphToMany(Flag::class, 'flaggable', 'flaggables')
                    ->withPivot(['source', 'created_by', 'context', 'expires_at', 'id'])
                    ->withTimestamps();
    }

    /**
     * Attach a flag by its code.
     * Returns the pivot record or false if already flagged.
     */

    public function attachFlag(string $flagCode, string $source = 'manual', array $context = null, $userId = null, $expiresAt = null): bool
    {
        $flag = Flag::where('code', $flagCode)->first();
        
        if (!$flag) {
            return false;
        }

        // Check if already flagged (non-expired)
        $existing = $this->flags()->where('flags.id', $flag->id)->exists();
        
        if ($existing) {
            return false;
        }

        $this->flags()->attach($flag->id, [
            'source' => $source,
            'created_by' => $userId,
            'context' => $context ? json_encode($context) : null,  // <-- Fix: encode array to JSON
            'expires_at' => $expiresAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }
    /**
     * Detach a flag by its code.
     */
    public function detachFlag(string $flagCode): int
    {
        $flag = Flag::where('code', $flagCode)->first();
        
        if (!$flag) {
            return 0;
        }

        return $this->flags()->detach($flag->id);
    }

    /**
     * Check if model has a specific flag.
     */
    public function hasFlag(string $flagCode): bool
    {
        return $this->flags()->where('flags.code', $flagCode)->exists();
    }

    /**
     * Sync flags - detach all and attach given codes.
     * Useful for batch re-evaluation.
     */
    public function syncFlags(array $flagCodes, string $source = 'rule', array $context = []): array
    {
        $flagIds = Flag::whereIn('code', $flagCodes)->pluck('id')->toArray();
        
        return $this->flags()->syncWithPivotValues($flagIds, [
            'source' => $source,
            'context' => json_encode($context),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}