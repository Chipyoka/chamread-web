<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
      protected $fillable = [
        'code',
        'name',
        'district',
        'province',
        'status',
    ];


    public function dmas()
    {
        return $this->hasMany(Dma::class);
    }

    public function customerAccounts()
{
    return $this->hasMany(CustomerAccount::class);
}
public function assignments()
{
    return $this->hasMany(CsaAssignment::class);
}
}