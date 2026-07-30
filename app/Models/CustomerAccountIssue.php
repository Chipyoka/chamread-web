<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerAccountIssue extends Model
{
    protected $fillable = [
        'zone_id',
        'reported_by',
        'resolved_by',

        'account_number',
        'customer_name',
        'meter_number',
        'phone',

        'issue',
        'comment',
        'status',

        'resolved_at',

        'photo',
        'gps_latitude',
        'gps_longitude',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}