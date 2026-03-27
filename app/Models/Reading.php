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

    public function csa()
    {
        return $this->belongsTo(User::class, 'csa_id');
    }

    public function billingCycle()
    {
        return $this->belongsTo(BillingCycle::class);
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }
}