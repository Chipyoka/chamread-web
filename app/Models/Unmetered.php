<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A CSA's record for an unmetered account in a billing cycle.
 *
 * The unmetered counterpart of Reading: there is no meter value, so it
 * records the state of the connection, a photo of the connection/tap and an
 * optional comment. One record per account per billing cycle.
 */
class Unmetered extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    /** Allowed connection_status values (used by API validation and filters). */
    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
    ];

    /**
     * meter_reading_codes.code values a reader may select for an unmetered
     * account. Edit this list to change the options offered in the app.
     */
    public const ALLOWED_CODES = ['02', '03', '10', '23'];

    // Set explicitly: Laravel would otherwise guess "unmetereds".
    protected $table = 'unmetered';

    protected $fillable = [
        'account_id',
        'csa_id',
        'billing_cycle_id',
        'remapped_from_cycle_id',
        'connection_status',
        'meter_reading_code',
        'photo_path',
        'comment',
        'latitude',
        'longitude',
        'captured_at',
        'synced_at',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
        'synced_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function account()
    {
        return $this->belongsTo(CustomerAccount::class, 'account_id');
    }

    public function csa()
    {
        return $this->belongsTo(User::class, 'csa_id');
    }

    public function billingCycle()
    {
        return $this->belongsTo(BillingCycle::class);
    }

    /**
     * The selected meter reading code (same relation name as Reading::code()).
     */
    public function code()
    {
        return $this->belongsTo(MeterReadingCode::class, 'meter_reading_code');
    }
}