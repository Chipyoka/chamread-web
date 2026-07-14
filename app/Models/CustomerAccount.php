<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerAccount extends Model
{
    //
    protected $table = 'customer_accounts';

    // fillables
    protected $fillable = [
        'account_number',
        'meter_number',
        'customer_name',
        'address',
        'phone',
        'customer_category',
        'zone_id',
        'status'
    ];

    // casts
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Zone the account belongs to.
     */
    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }
    
    /**
     * DMA the account belongs to.
     */
    public function dma()
    {
        return $this->belongsTo(DMA::class);
    }
    
    /**
     * Readings with this account's account_number
     */
    public function readings()
    {
        return $this->hasMany(Reading::class, 'account_id');
    }

    /**
     * Location derived from address for GPS Service
     */
    public function location()
    {
        return $this->hasOne(AccountLocation::class);
    }

    /**
     * GPS checks for this account
     */
    public function gpsChecks()
    {
        return $this->hasMany(ReadingGpsCheck::class);
    }


    /**
     * Exceptions for this account
     */
    public function exceptions()
    {
        return $this->hasMany(SystemException::class);
    }
}
