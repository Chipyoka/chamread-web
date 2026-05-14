<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemException extends Model
{
    protected $table = 'exceptions';

    protected $fillable = [
        'type',
        'account_id',
        'reading_id',
        'billing_cycle_id',
        'severity',
        'status',
        'title',
        'description',
        'detected_at',
        'resolved_at',
        'metadata',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function gpsMismatch()
    {
        return $this->hasOne(ExceptionGpsMismatch::class, 'exception_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function reading()
    {
        return $this->belongsTo(Reading::class);
    }

    public function billingCycle()
    {
        return $this->belongsTo(BillingCycle::class);
    }
}