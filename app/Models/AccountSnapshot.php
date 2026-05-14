<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountSnapshot extends Model
{
    //

    public function location()
    {
        return $this->hasOne(AccountLocation::class);
    }

    public function gpsChecks()
    {
        return $this->hasMany(ReadingGpsCheck::class);
    }

    public function exceptions()
    {
        return $this->hasMany(SystemException::class);
    }
}
