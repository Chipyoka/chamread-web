<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dma extends Model
{
    protected $fillable = ['code','name','zone_id','status'];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }
}