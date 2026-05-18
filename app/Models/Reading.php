<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reading extends Model
{
    protected $fillable = [
        'account_number','csa_id','billing_cycle_id',
        'zone_id','dma_id',
        'previous_reading','current_reading',
        'status','reason_code',
        'photo_path','latitude','longitude',
        'reading_time','synced_at',
        'edited_by_id','edit_reason'
    ];
      protected $casts = [
        'synced_at' => 'datetime',
        'reading_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function csa()
    {
        return $this->belongsTo(User::class, 'csa_id');
    }

    public function billingCycle()
    {
        return $this->belongsTo(BillingCycle::class);
    }

    public function account()
    {
        return $this->belongsTo(CustomerAccount::class, 'account_number');
    }

    public function reason()
    {
        return $this->belongsTo(NonReadReason::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function dma()
    {
        return $this->belongsTo(DMA::class);
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }

    public function gpsCheck()
    {
        return $this->hasOne(ReadingGpsCheck::class);
    }

    public function exceptions()
    {
        return $this->hasMany(SystemException::class);
    }
}