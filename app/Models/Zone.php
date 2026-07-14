<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    protected $fillable = ['code','name','status', 'district', 'province'];

    public function dmas()
    {
        return $this->hasMany(Dma::class);
    }
}