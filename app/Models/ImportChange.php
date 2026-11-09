<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One created, updated or skipped record from an import process.
 *
 * Rows are append-only, so the model has no updated_at column.
 *
 * @property int $id
 * @property int $import_process_id
 * @property string $entity One of the ENTITY_* constants.
 * @property string $action One of the ACTION_* constants.
 * @property string|null $record_key Natural key (zone code or account number); null when the row had none.
 * @property int|null $row_number Spreadsheet row number; set for skipped rows.
 * @property array<string, array{old: mixed, new: mixed}>|null $changes Field diffs; set for updated rows.
 * @property string|null $reason One of the REASON_* constants; set for skipped rows.
 * @property \Illuminate\Support\Carbon $created_at
 */
class ImportChange extends Model
{
    public const ENTITY_ZONE = 'zone';
    public const ENTITY_CUSTOMER_ACCOUNT = 'customer_account';

    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_SKIPPED = 'skipped';

    public const REASON_MISSING_ACCOUNT = 'missing_account';
    public const REASON_MISSING_ZONE = 'missing_zone';
    public const REASON_UNKNOWN_ZONE = 'unknown_zone';
    public const REASON_SUPERSEDED = 'superseded_by_later_row';

    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'import_process_id',
        'entity',
        'action',
        'record_key',
        'row_number',
        'changes',
        'reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'row_number' => 'integer',
            'changes' => 'array',
        ];
    }

    /**
     * Import process this change belongs to.
     *
     * @return BelongsTo<ImportProcess, $this>
     */
    public function importProcess(): BelongsTo
    {
        return $this->belongsTo(ImportProcess::class);
    }

    /**
     * Limit to changes for one record, across all imports.
     *
     * @param  Builder<ImportChange>  $query
     * @param  string                 $entity     One of the ENTITY_* constants.
     * @param  string                 $recordKey  Zone code or account number.
     */
    public function scopeForRecord(Builder $query, string $entity, string $recordKey): void
    {
        $query->where('entity', $entity)->where('record_key', $recordKey);
    }
}

// TODO: None