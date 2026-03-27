<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingCycle extends Model
{
       protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
