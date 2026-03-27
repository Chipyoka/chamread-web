<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $fillable = [
        'reading_id','account_number','type','severity',
        'details','status','resolved_by_id','resolved_notes','resolved_at'
    ];

    protected $casts = [
        'details' => 'array'
    ];

    public function reading()
    {
        return $this->belongsTo(Reading::class);
    }
}