<?php

namespace App\Services;

use App\Models\ImportChange;
use App\Models\ImportProcess;
use App\Models\ImportSummary;
use Illuminate\Support\Carbon;

/**
 * Collects the per-record outcomes of one import process and persists them
 * to import_changes and import_summaries.
 *
 * Created, updated and skipped records are logged individually; unchanged
 * records are only counted. Log rows are buffered in memory and written by
 * flush(), so callers control when the log is written and can keep it
 * consistent with the data (for example, by flushing inside the transaction
 * that writes the data).
 *
 * Instances hold state for a single run and must not be shared or reused.
 */
class ImportStatsRecorder
{
    /**
     * Rows per insert statement, kept well under driver placeholder limits.
     */
    protected const INSERT_CHUNK_SIZE = 500;

    /**
     * @var list<array<string, mixed>>
     */
    protected array $buffer = [];

    /**
     * Outcome counts per entity, keyed by action name.
     *
     * @var array<string, array{created: int, updated: int, unchanged: int, skipped: int}>
     */
    protected array $counts = [];

    /**
     * @param  ImportProcess  $process  Process the recorded outcomes belong to.
     */
    public function __construct(protected ImportProcess $process)
    {
    }

    /**
     * Delete outcomes saved by earlier attempts of this process, so a retried
     * run does not log its changes twice.
     */
    public function reset(): void
    {
        ImportChange::where('import_process_id', $this->process->getKey())->delete();
        ImportSummary::where('import_process_id', $this->process->getKey())->delete();

        $this->buffer = [];
        $this->counts = [];
    }

    /**
     * Record a newly created record.
     *
     * @param  string  $entity  One of the ImportChange::ENTITY_* constants.
     * @param  string  $key     Natural key of the record.
     */
    public function created(string $entity, string $key): void
    {
        $this->record($entity, ImportChange::ACTION_CREATED, $key);
    }

    /**
     * Record an existing record whose values changed.
     *
     * @param  string                                       $entity   One of the ImportChange::ENTITY_* constants.
     * @param  string                                       $key      Natural key of the record.
     * @param  array<string, array{old: mixed, new: mixed}>  $changes  Changed fields only.
     */
    public function updated(string $entity, string $key, array $changes): void
    {
        $this->record($entity, ImportChange::ACTION_UPDATED, $key, changes: $changes);
    }

    /**
     * Record a template row that was not applied.
     *
     * @param  string       $entity     One of the ImportChange::ENTITY_* constants.
     * @param  string|null  $key        Natural key, when the row had one.
     * @param  int          $rowNumber  Spreadsheet row number.
     * @param  string       $reason     One of the ImportChange::REASON_* constants.
     */
    public function skipped(string $entity, ?string $key, int $rowNumber, string $reason): void
    {
        $this->record($entity, ImportChange::ACTION_SKIPPED, $key, $rowNumber, reason: $reason);
    }

    /**
     * Count an existing record whose values already matched the template.
     *
     * @param  string  $entity  One of the ImportChange::ENTITY_* constants.
     */
    public function unchanged(string $entity): void
    {
        $this->increment($entity, 'unchanged');
    }

    /**
     * Write buffered log rows.
     */
    public function flush(): void
    {
        foreach (array_chunk($this->buffer, self::INSERT_CHUNK_SIZE) as $chunk) {
            ImportChange::insert($chunk);
        }

        $this->buffer = [];
    }

    /**
     * Flush remaining log rows and write one summary row per entity.
     *
     * @param  list<string>  $entities  Entities to summarize; an entity with no
     *                                  recorded outcomes gets zero counts.
     * @return array<string, array{created: int, updated: int, unchanged: int, skipped: int}>
     *         Counts keyed by entity.
     */
    public function finish(array $entities): array
    {
        $this->flush();

        $now = Carbon::now();
        $stats = [];
        $rows = [];

        foreach ($entities as $entity) {
            $stats[$entity] = $this->counts[$entity] ?? $this->emptyCounts();

            $rows[] = [
                'import_process_id' => $this->process->getKey(),
                'entity' => $entity,
                'created_count' => $stats[$entity]['created'],
                'updated_count' => $stats[$entity]['updated'],
                'unchanged_count' => $stats[$entity]['unchanged'],
                'skipped_count' => $stats[$entity]['skipped'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        ImportSummary::insert($rows);

        return $stats;
    }

    /**
     * Buffer one log row and count it.
     *
     * Written with insert(), which bypasses model casts, so `changes` is
     * JSON-encoded here.
     *
     * @param  array<string, array{old: mixed, new: mixed}>|null  $changes
     */
    protected function record(
        string $entity,
        string $action,
        ?string $key,
        ?int $rowNumber = null,
        ?array $changes = null,
        ?string $reason = null
    ): void {
        $this->increment($entity, $action);

        $this->buffer[] = [
            'import_process_id' => $this->process->getKey(),
            'entity' => $entity,
            'action' => $action,
            'record_key' => $key,
            'row_number' => $rowNumber,
            'changes' => $changes === null
                ? null
                : json_encode($changes, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'reason' => $reason,
            'created_at' => Carbon::now(),
        ];
    }

    /**
     * @param  'created'|'updated'|'unchanged'|'skipped'  $outcome
     */
    protected function increment(string $entity, string $outcome): void
    {
        $this->counts[$entity] ??= $this->emptyCounts();
        $this->counts[$entity][$outcome]++;
    }

    /**
     * @return array{created: int, updated: int, unchanged: int, skipped: int}
     */
    protected function emptyCounts(): array
    {
        return ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0];
    }
}

// TODO: None