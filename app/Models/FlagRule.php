<?php
// app/Models/FlagRule.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlagRule extends Model
{
    protected $fillable = [
        'flag_id',
        'field',
        'operator',
        'value',
        'group_key',
        'order',
        'active',
        'evaluation_type',
        'description',
    ];

    protected $casts = [
        'active' => 'boolean',
        'order' => 'integer',
    ];

    // Valid operators for the expression engine
    public const OPERATORS = [
        '>' => 'Greater than',
        '<' => 'Less than',
        '>=' => 'Greater than or equal',
        '<=' => 'Less than or equal',
        '=' => 'Equals',
        '!=' => 'Not equal',
        'contains' => 'Contains',
        'starts_with' => 'Starts with',
        'ends_with' => 'Ends with',
        'is_null' => 'Is null',
        'is_not_null' => 'Is not null',
        'in' => 'In list',
    ];

    public function flag(): BelongsTo
    {
        return $this->belongsTo(Flag::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}