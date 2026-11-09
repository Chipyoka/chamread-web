<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Outcome counts for one entity within one import process.
 *
 * @property int $id
 * @property int $import_process_id
 * @property string $entity One of the ImportChange::ENTITY_* constants.
 * @property int $created_count
 * @property int $updated_count
 * @property int $unchanged_count
 * @property int $skipped_count
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class ImportSummary extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'import_process_id',
        'entity',
        'created_count',
        'updated_count',
        'unchanged_count',
        'skipped_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_count' => 'integer',
            'updated_count' => 'integer',
            'unchanged_count' => 'integer',
            'skipped_count' => 'integer',
        ];
    }

    /**
     * Import process these counts belong to.
     *
     * @return BelongsTo<ImportProcess, $this>
     */
    public function importProcess(): BelongsTo
    {
        return $this->belongsTo(ImportProcess::class);
    }
}

// TODO: None