<?php
// app/Models/Flag.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Flag extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'applies_to',
        'color',
        'icon',
        'is_system',
        'active',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'active' => 'boolean',
    ];

    // Relationships
    public function rules(): HasMany
    {
        return $this->hasMany(FlagRule::class);
    }

    public function activeRules(): HasMany
    {
        return $this->rules()->where('active', true)->orderBy('order');
    }

    // Polymorphic relationships to each entity type
    public function accounts(): MorphToMany
    {
        return $this->morphedByMany(CustomerAccount::class, 'flaggable')
                    ->withTimestamps()
                    ->withPivot(['source', 'created_by', 'context', 'expires_at']);
    }

    public function readings(): MorphToMany
    {
        return $this->morphedByMany(Reading::class, 'flaggable')
                    ->withTimestamps()
                    ->withPivot(['source', 'created_by', 'context', 'expires_at']);
    }

    public function meterReaders(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'flaggable')
                    ->withTimestamps()
                    ->withPivot(['source', 'created_by', 'context', 'expires_at']);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeForEntity($query, string $entityType)
    {
        return $query->where('applies_to', $entityType);
    }
}