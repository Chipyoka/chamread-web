<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Device extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'manufacturer',
        'model',
        'serial_number',
        'imei',
        'imei_2',
        'sim_serial_number',
        'phone_number',
        'operating_system',
        'os_version',
        'processor',
        'ram',
        'storage_capacity',
        'mac_address',
        'ip_address',
        'status',
        'assigned_at',
        'returned_at',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'metadata' => 'array',
        'assigned_at' => 'date',
        'returned_at' => 'date',
    ];
}