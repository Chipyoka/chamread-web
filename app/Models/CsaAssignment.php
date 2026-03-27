<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CsaAssignment extends Model
{
    protected $fillable = [
        'csa_id','zone_id','dma_id','billing_cycle_id','status','assigned_at'
    ];

    public function csa()
    {
        return $this->belongsTo(User::class,'csa_id');
    }
}