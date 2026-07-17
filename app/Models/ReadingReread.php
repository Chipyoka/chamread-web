<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class ReadingReread extends Model
{

    use HasFactory;



    protected $fillable = [

        'reading_id',
        'supervisor_id',
        'billing_cycle_id',
        'reason',
        'new_value',
        'old_value',
        'status'

    ];



    public function reading()
    {
        return $this->belongsTo(
            Reading::class
        );
    }



    public function supervisor()
    {
        return $this->belongsTo(
            User::class,
            'supervisor_id'
        );
    }



    public function billingCycle()
    {
        return $this->belongsTo(
            BillingCycle::class
        );
    }


}