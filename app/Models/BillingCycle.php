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

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'can_download',
        'can_upload',
        'status'
    ];

    public function gpsChecks()
    {
        return $this->hasMany(ReadingGpsCheck::class);
    }

    public function exceptions()
    {
        return $this->hasMany(SystemException::class);
    }
}
