<?php
// app/Models/Flaggable.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Flaggable extends Model
{
    protected $table = 'flaggables';

    protected $fillable = [
        'flag_id',
        'flaggable_type',
        'flaggable_id',
        'source',
        'created_by',
        'context',
        'expires_at',
    ];

    protected $casts = [
        'context' => 'array',
        'expires_at' => 'datetime',
    ];

    // Relationships
    public function flag(): BelongsTo
    {
        return $this->belongsTo(Flag::class);
    }

    public function flaggable(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNull('expires_at')
                     ->orWhere('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
                     ->where('expires_at', '<=', now());
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeManual($query)
    {
        return $query->bySource('manual');
    }

    public function scopeAutomatic($query)
    {
        return $query->bySource('rule');
    }
}