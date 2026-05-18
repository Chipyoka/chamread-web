<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NonReadReason extends Model
{
    //
    public function readings()
    {
         return $this->hasMany(Reading::class);
    }
}
