<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'username',
    'role',
    'device_id',
    'status',
    'zone_id',
    'last_login_at',
    'password'
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Attribute casting.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Zone the user belongs to.
     */
    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    /**
     * CSA assignments.
     */
    public function assignments()
    {
        return $this->hasMany(CsaAssignment::class, 'csa_id');
    }

    /**
     * Readings done by the CSA.
     */
    public function readings()
    {
        return $this->hasMany(Reading::class, 'csa_id');
    }

    /**
     * Readings edited by the CSA or admin.
     */
    public function editedReadings()
    {
        return $this->hasMany(Reading::class, 'edited_by_id');
    }

    /**
     * Alerts resolved by the user.
     */
    public function alertsResolved()
    {
        return $this->hasMany(Alert::class, 'resolved_by_id');
    }

    /**
     * CSA performance snapshots.
     */
    public function performance()
    {
        return $this->hasMany(CsaPerformance::class, 'csa_id');
    }
}