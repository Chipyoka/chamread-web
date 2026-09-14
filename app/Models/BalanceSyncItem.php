<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BalanceSyncItem extends Model
{
    protected $fillable = [
        'run_id', 'account_number', 'status', 'balance', 'attempts', 'last_error',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function run()
    {
        return $this->belongsTo(BalanceSyncRun::class, 'run_id');
    }
}