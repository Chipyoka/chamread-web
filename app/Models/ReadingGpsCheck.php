<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReadingGpsCheck extends Model
{
    protected $fillable = [
        'reading_id',
        'account_id',
        'billing_cycle_id',
        'processed_at',
        'status',
        'distance_meters',
        'allowed_radius_meters',
        'exception_id',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'distance_meters' => 'float',
    ];

    public function reading()
    {
        return $this->belongsTo(Reading::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function billingCycle()
    {
        return $this->belongsTo(BillingCycle::class);
    }

    public function exception()
    {
        return $this->belongsTo(Exception::class);
    }
}