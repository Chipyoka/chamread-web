<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExceptionGpsMismatch extends Model
{
    protected $fillable = [
        'exception_id',
        'expected_latitude',
        'expected_longitude',
        'actual_latitude',
        'actual_longitude',
        'distance_meters',
        'allowed_radius_meters',
    ];

    protected $casts = [
        'expected_latitude' => 'float',
        'expected_longitude' => 'float',
        'actual_latitude' => 'float',
        'actual_longitude' => 'float',
        'distance_meters' => 'float',
    ];

    public function exception()
    {
        return $this->belongsTo(SystemException::class, 'exception_id');
    }
}