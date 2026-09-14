<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BalanceSyncRun extends Model
{
    protected $fillable = [
        'triggered_by', 'requested_at', 'scheduled_start', 'scheduled_end',
        'status', 'total_accounts', 'processed_count', 'success_count',
        'failed_count', 'skipped_count', 'started_at', 'completed_at', 'meta',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function items()
    {
        return $this->hasMany(BalanceSyncItem::class, 'run_id');
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['pending', 'running', 'paused_api_down']);
    }
}