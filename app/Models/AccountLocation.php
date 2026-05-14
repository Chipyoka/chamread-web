<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountLocation extends Model
{
    protected $fillable = [
        'account_id',
        'address_snapshot',
        'address_hash',
        'latitude',
        'longitude',
        'geocode_provider',
        'geocode_confidence',
        'geocoded_at',
        'status',
        'retry_count',
    ];

    protected $casts = [
        'geocoded_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'geocode_confidence' => 'float',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}