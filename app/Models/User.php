<?php

namespace App\Models;

use App\Traits\HasFlags;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
    use HasFactory, Notifiable, HasApiTokens, HasFlags;

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
     * CSA assignments.
     */
    public function assignments()
    {
        return $this->hasMany(CsaAssignment::class, 'csa_id');
    }

    public function activeAssignment()
    {
        return $this->hasOne(CsaAssignment::class, 'csa_id')
            ->where('status', 'active');
    }

    public function zone()
    {
        return $this->activeAssignment?->zone;
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Readings done by the CSA.
     */
    public function readings()
    {
        return $this->hasMany(Reading::class, 'csa_id');
    }

    /**
     * Readings edited by the CSA or readings.
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

        public function readingResolves()
    {
        return $this->hasMany(
            ReadingResolve::class,
            'resolved_by'
        );
    }



    public function supervisedRereads()
    {
        return $this->hasMany(
            ReadingReread::class,
            'supervisor_id'
        );
    }


  

    public function districts(): BelongsToMany
    {
        return $this->belongsToMany(District::class, 'district_user')
                    ->withPivot(['role', 'status', 'assigned_at', 'revoked_at', 'notes'])
                    ->withTimestamps();
    }

    /**
     * The active district assignment (District model with ->pivot), or null.
     */
    public function activeDistrictAssignment(): ?District
    {
        return $this->districts()
                    ->wherePivot('status', 'active')
                    ->wherePivotNull('revoked_at')
                    ->first();
    }

    /**
     * Accessor: $user->district  →  District model or null.
     */
    public function getDistrictAttribute(): ?District
    {
        return $this->activeDistrictAssignment();
    }

    /**
     * Accessor: $user->district_name  →  string or null.
     */
    public function getDistrictNameAttribute(): ?string
    {
        return $this->activeDistrictAssignment()?->name;
    }
}