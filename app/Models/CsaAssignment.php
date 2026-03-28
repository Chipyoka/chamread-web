<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CsaAssignment extends Model
{
    protected $fillable = [
        'csa_id','zone_id','dma_id','target','billing_cycle_id',
        'status','assignment_type','assigned_at', 'covering_reason',
        'covered_csa_id'
    ];

      protected $casts = [
        'assigned_at' => 'datetime',
        'end_at' => 'datetime',
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