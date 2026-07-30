<?php

namespace App\Models;

use App\Traits\HasFlags;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\CsaAssignment;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class CustomerAccount extends Model
{
    //
    use HasFlags; 
    
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

    /**
     * Assigned csa
     */
    public function assignedCsa(): HasOneThrough
    {
        return $this->hasOneThrough(
            User::class,           // Final model
            CsaAssignment::class,  // Intermediate model
            'zone_id',             // Foreign key on csa_assignments...
            'id',                  // Foreign key on users...
            'zone_id',             // Local key on customer_accounts...
            'csa_id'               // Local key on csa_assignments...
        )->where('users.role', 'CSA');
    }
}
