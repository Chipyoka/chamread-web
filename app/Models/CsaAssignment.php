<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CsaAssignment extends Model
{
    protected $fillable = [
        'csa_id','zone_id','dma_id','billing_cycle_id','status','assigned_at'
    ];

      protected $casts = [
        'assigned_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function csa()
    {
        return $this->belongsTo(User::class, 'csa_id');
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function dma()
    {
        return $this->belongsTo(Dma::class);
    }

    public function billingCycle()
    {
        return $this->belongsTo(BillingCycle::class);
    }
}