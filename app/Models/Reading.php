<?php

namespace App\Models;

use App\Traits\HasFlags;
use Illuminate\Database\Eloquent\Model;

class Reading extends Model
{
    use HasFlags;

    protected $fillable = [
         'account_id','account_number','csa_id','billing_cycle_id',
        'zone_id','dma_id',
        'previous_reading','current_reading',
        'status','reason_code',
        'photo_path','latitude','longitude',
        'reading_time','synced_at','comment'
    ];
      protected $casts = [
        'synced_at' => 'datetime',
        'reading_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',

        'previous_reading' => 'float',
        'current_reading' => 'float',
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
        return $this->belongsTo(CustomerAccount::class);
    }

    public function reason()
    {
        return $this->belongsTo(NonReadReason::class, 'reason_code');
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
    public function resolves()
    {
        return $this->hasMany(
            ReadingResolve::class
        );
    }
    public function latestResolve()
    {
        return $this->hasOne(
            ReadingResolve::class
        )
        ->latestOfMany();
    }
    public function rereads()
    {
        return $this->hasMany(
            ReadingReread::class
        );
    }
    public function latestReread()
    {
        return $this->hasOne(
            ReadingReread::class
        )
        ->latestOfMany();
    }
}