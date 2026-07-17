<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class ReadingResolve extends Model
{

    use HasFactory;


    protected $fillable = [
        'reading_id',
        'resolved_by',
        'billing_cycle_id'
    ];



    public function reading()
    {
        return $this->belongsTo(
            Reading::class
        );
    }



    public function resolver()
    {
        return $this->belongsTo(
            User::class,
            'resolved_by'
        );
    }



    public function billingCycle()
    {
        return $this->belongsTo(
            BillingCycle::class
        );
    }

}