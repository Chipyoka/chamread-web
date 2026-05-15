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
        'name',
        'address',
        'phone',
        'zone_id',
        'dma_id',
        'billing_area',
        'previous_reading'
    ];

    // casts
    protected $casts = [
        'previous_reading' => 'decimal:3',
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
    
    public function location()
    {
        return $this->hasOne(AccountLocation::class);
    }

    public function gpsChecks()
    {
        return $this->hasMany(ReadingGpsCheck::class);
    }

    public function exceptions()
    {
        return $this->hasMany(SystemException::class);
    }
}
