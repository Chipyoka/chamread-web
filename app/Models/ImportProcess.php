<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracks the lifecycle of a monthly template import.
 *
 * This model is used by the import job to report progress,
 * current processing stage, and the final outcome. The UI
 * polls this record periodically to display real-time
 * progress to the user.
 */
class ImportProcess extends Model
{
    /**
     * Import status constants.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'billing_cycle_id',
        'file_name',
        'progress',
        'current_step',
        'status',
        'message',
    ];

    /**
     * Attribute casting.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'progress' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * User who initiated the import.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Billing cycle associated with this import.
     */
    public function billingCycle(): BelongsTo
    {
        return $this->belongsTo(BillingCycle::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Mark the import as currently processing.
     *
     * @param int $progress
     * @param string $step
     * @param string|null $message
     */
    public function markProcessing(
        int $progress,
        string $step,
        ?string $message = null
    ): void {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'progress' => $progress,
            'current_step' => $step,
            'message' => $message,
        ]);
    }

    /**
     * Mark the import as successfully completed.
     *
     * @param string|null $message
     */
    public function markCompleted(?string $message = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'progress' => 100,
            'current_step' => 'Completed',
            'message' => $message,
        ]);
    }

    /**
     * Mark the import as failed.
     *
     * @param string $message
     */
    public function markFailed(string $message): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'message' => $message,
        ]);
    }
}