<?php

namespace App\Services;

use App\Imports\MonthlyTemplateImport;
use App\Models\BillingCycle;
use App\Models\CustomerAccount;
use App\Models\ImportChange;
use App\Models\ImportProcess;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;

/**
 * Synchronizes zones and customer accounts from the monthly ERP template,
 * records what each import changed, and builds the matching ERP export.
 *
 * The uploaded template is the source of truth. Every valid row is upserted:
 * missing records are created and existing records are overwritten with the
 * template's values, so re-importing a corrected file corrects the database
 * (for example, an account moved to a different zone).
 *
 * Reading data in the template (current and historical readings, reading
 * codes, consumption) is ignored; the application manages it internally.
 *
 * Import semantics:
 * - Keys are matched on `zones.code` and `customer_accounts.account_number`,
 *   both of which must carry unique indexes for upserts to update rather
 *   than insert.
 * - When a key appears on several rows, the last row wins; earlier rows are
 *   logged as skipped.
 * - Blank cells are stored as null (or empty string for non-nullable
 *   columns) and overwrite existing values.
 * - Only new and changed records are written, so unchanged records keep
 *   their updated_at.
 * - Records absent from the template are never deleted.
 * - The import is idempotent; a failed run is recovered by re-running it,
 *   which also replaces that run's audit log.
 *
 * Outcomes are persisted through ImportStatsRecorder to import_summaries
 * (counts) and import_changes (created, updated and skipped records with
 * field-level diffs).
 *
 * Writes use query-builder upserts, which bypass Eloquent model events and
 * observers.
 */
class MonthlyTemplateService
{
    /**
     * Normalized header names the template must contain.
     *
     * @var list<string>
     */
    protected const REQUIRED_COLUMNS = [
        'account',
        'name',
        'address',
        'meter_number',
        'customer_category',
        'meter_status_erp',
        'phone_number',
        'district',
        'zone',
        'province',
    ];

    /**
     * Zone columns the template may overwrite. `name` is excluded so that
     * names edited in the application survive re-imports.
     *
     * @var list<string>
     */
    protected const ZONE_UPDATE_COLUMNS = [
        'district',
        'province',
    ];

    /**
     * Customer account columns the template may overwrite.
     *
     * @var list<string>
     */
    protected const CUSTOMER_UPDATE_COLUMNS = [
        'customer_name',
        'address',
        'phone',
        'meter_number',
        'account_status',
        'customer_category',
        'zone_id',
    ];

    /**
     * Spreadsheet row holding the headings; data starts on the next row.
     * Must match MonthlyTemplateImport's heading row.
     */
    protected const HEADING_ROW = 1;

    /**
     * Progress percentages that bound each import stage.
     */
    protected const PROGRESS_EXTRACT = 10;
    protected const PROGRESS_ZONES_START = 25;
    protected const PROGRESS_ZONES_END = 55;
    protected const PROGRESS_CUSTOMERS_START = 60;
    protected const PROGRESS_CUSTOMERS_END = 95;

    /**
     * Records per upsert statement. Also sets progress-report granularity.
     */
    protected int $chunkSize = 500;

    /**
     * Import a monthly template into zones and customer accounts and save
     * its outcomes.
     *
     * Runs without a wrapping transaction so progress updates stay visible
     * to pollers on other connections. Each chunk's data write and its audit
     * rows commit together in one short transaction.
     *
     * @param  ImportProcess  $process   Import record that receives progress updates and owns the audit rows.
     * @param  string         $filePath  Path to the template on the `local` disk.
     * @return array{
     *     zones: array{created: int, updated: int, unchanged: int, skipped: int},
     *     customers: array{created: int, updated: int, unchanged: int, skipped: int}
     * } Per-entity outcome counts, as saved to import_summaries.
     *
     * @throws RuntimeException When the template is empty or a required column is missing.
     */
    public function process(ImportProcess $process, string $filePath): array
    {
        $rows = $this->extractRows($process, $filePath);

        $this->validateTemplate($rows);

        $recorder = new ImportStatsRecorder($process);
        $recorder->reset();

        $zoneMap = $this->importZones($rows, $process, $recorder);

        $this->importCustomers($rows, $process, $recorder, $zoneMap);

        $stats = $recorder->finish([
            ImportChange::ENTITY_ZONE,
            ImportChange::ENTITY_CUSTOMER_ACCOUNT,
        ]);

        $zones = $stats[ImportChange::ENTITY_ZONE];
        $customers = $stats[ImportChange::ENTITY_CUSTOMER_ACCOUNT];

        $process->markProcessing(
            self::PROGRESS_CUSTOMERS_END,
            'Finalizing import',
            sprintf(
                'Zones: %d created, %d updated. Accounts: %d created, %d updated, %d unchanged, %d skipped.',
                $zones['created'],
                $zones['updated'],
                $customers['created'],
                $customers['updated'],
                $customers['unchanged'],
                $customers['skipped']
            )
        );

        return [
            'zones' => $zones,
            'customers' => $customers,
        ];
    }

    /**
     * Build the monthly ERP export rows.
     *
     * Columns mirror the import template; reading-related columns are left
     * blank for the ERP to populate.
     *
     * @param  BillingCycle  $billingCycle  Cycle being exported.
     * @return Collection<int, array<string, mixed>> One row per customer account, ordered by account number.
     */
    public function exportRows(BillingCycle $billingCycle): Collection
    {
        return CustomerAccount::query()
            ->with('zone')
            ->orderBy('account_number')
            ->get()
            ->map(fn (CustomerAccount $account) => [
                'Account' => $account->account_number,
                'Name' => $account->customer_name,
                'Address' => $account->address,
                'Meter number' => $account->meter_number,
                'Customer Category' => $account->customer_category,
                'Current Reading Date' => '',
                'Current Reading' => '',
                'Meter reading ERP (incl estimates)' => '',
                'Meter Status ERP' => $account->account_status,
                'Optional Comment' => '',
                'MR: This month Code' => '',
                'MR: Last month Code' => '',
                'Phone number' => $account->phone,
                'Previous Date' => '',
                'Previous2 Meter Code' => '',
                'Previous2 Reading' => '',
                'Previous2 Date' => '',
                'Previous3 Meter Code' => '',
                'Previous3 Reading' => '',
                'Previous3 Date' => '',
                'District' => $account->zone?->district,
                'Zone' => $account->zone?->code,
                'Consumption' => '',
                'Province' => $account->zone?->province,
            ]);
    }

    /**
     * Read the template's rows into memory.
     *
     * @param  ImportProcess  $process   Import record that receives progress updates.
     * @param  string         $filePath  Path to the template on the `local` disk.
     * @return list<array<string, mixed>> Rows keyed by normalized header name.
     */
    protected function extractRows(ImportProcess $process, string $filePath): array
    {
        $process->markProcessing(
            self::PROGRESS_EXTRACT,
            'Extracting file',
            'Reading spreadsheet contents...'
        );

        $import = new MonthlyTemplateImport();

        Excel::import($import, Storage::disk('local')->path($filePath));

        return $import->getRows()->toArray();
    }

    /**
     * Ensure the template has rows and every required column.
     *
     * Headers are taken from the first row, which Laravel Excel keys by the
     * normalized heading row.
     *
     * @param  list<array<string, mixed>>  $rows  Extracted template rows.
     *
     * @throws RuntimeException When the template is empty or a required column is missing.
     */
    protected function validateTemplate(array $rows): void
    {
        if ($rows === []) {
            throw new RuntimeException('The uploaded template is empty.');
        }

        $missing = array_diff(self::REQUIRED_COLUMNS, array_keys($rows[0]));

        if ($missing !== []) {
            throw new RuntimeException('Missing required column(s): ' . implode(', ', $missing));
        }
    }

    /**
     * Sync every zone referenced by the template.
     *
     * New zones take their code as their name. Existing zones receive the
     * template's district and province. Rows without a zone are logged as
     * skipped by importCustomers(), not here.
     *
     * @param  list<array<string, mixed>>  $rows      Validated template rows.
     * @param  ImportProcess               $process   Import record that receives progress updates.
     * @param  ImportStatsRecorder         $recorder  Collector for this run's outcomes.
     * @return array<string, int> Zone code to zone ID for every zone in the template.
     */
    protected function importZones(
        array $rows,
        ImportProcess $process,
        ImportStatsRecorder $recorder
    ): array {
        $process->markProcessing(
            self::PROGRESS_ZONES_START,
            'Loading zones',
            'Syncing zone records...'
        );

        $records = [];

        foreach ($rows as $row) {
            $code = $this->clean($row['zone'] ?? null);

            if ($code === null) {
                continue;
            }

            $records[$code] = [
                'code' => $code,
                'name' => $code,
                'district' => $this->clean($row['district'] ?? null) ?? '',
                'province' => $this->clean($row['province'] ?? null) ?? '',
            ];
        }

        $this->syncInChunks(
            Zone::class,
            ImportChange::ENTITY_ZONE,
            $records,
            'code',
            self::ZONE_UPDATE_COLUMNS,
            $recorder
        );

        $process->markProcessing(
            self::PROGRESS_ZONES_END,
            'Loading zones',
            sprintf('Synced %d zones.', count($records))
        );

        return Zone::whereIn('code', array_keys($records))
            ->pluck('id', 'code')
            ->all();
    }

    /**
     * Sync every customer account in the template.
     *
     * Rows without an account number or a resolvable zone, and rows
     * superseded by a later row for the same account, are logged as skipped
     * rather than failing the import.
     *
     * @param  list<array<string, mixed>>  $rows      Validated template rows.
     * @param  ImportProcess               $process   Import record that receives progress updates.
     * @param  ImportStatsRecorder         $recorder  Collector for this run's outcomes.
     * @param  array<string, int>          $zoneMap   Zone code to zone ID.
     */
    protected function importCustomers(
        array $rows,
        ImportProcess $process,
        ImportStatsRecorder $recorder,
        array $zoneMap
    ): void {
        $process->markProcessing(
            self::PROGRESS_CUSTOMERS_START,
            'Loading customer accounts',
            'Syncing customer accounts...'
        );

        $entity = ImportChange::ENTITY_CUSTOMER_ACCOUNT;
        $records = [];
        $rowNumbers = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + self::HEADING_ROW + 1;
            $account = $this->clean($row['account'] ?? null);
            $zoneCode = $this->clean($row['zone'] ?? null);

            $reason = match (true) {
                $account === null => ImportChange::REASON_MISSING_ACCOUNT,
                $zoneCode === null => ImportChange::REASON_MISSING_ZONE,
                !isset($zoneMap[$zoneCode]) => ImportChange::REASON_UNKNOWN_ZONE,
                default => null,
            };

            if ($reason !== null) {
                $recorder->skipped($entity, $account, $rowNumber, $reason);
                continue;
            }

            if (isset($rowNumbers[$account])) {
                $recorder->skipped($entity, $account, $rowNumbers[$account], ImportChange::REASON_SUPERSEDED);
            }

            $rowNumbers[$account] = $rowNumber;

            $records[$account] = [
                'account_number' => $account,
                'customer_name' => $this->clean($row['name'] ?? null) ?? '',
                'address' => $this->clean($row['address'] ?? null),
                'phone' => $this->clean($row['phone_number'] ?? null),
                'meter_number' => $this->clean($row['meter_number'] ?? null),
                'customer_category' => $this->clean($row['customer_category'] ?? null),
                'zone_id' => $zoneMap[$zoneCode],
                'account_status' => $this->clean($row['meter_status_erp'] ?? null) ?? '',
            ];
        }

        $recorder->flush();

        $total = count($records);
        $range = self::PROGRESS_CUSTOMERS_END - self::PROGRESS_CUSTOMERS_START;

        $this->syncInChunks(
            CustomerAccount::class,
            $entity,
            $records,
            'account_number',
            self::CUSTOMER_UPDATE_COLUMNS,
            $recorder,
            fn (int $done) => $process->markProcessing(
                (int) (self::PROGRESS_CUSTOMERS_START + ($done / max($total, 1)) * $range),
                'Loading customer accounts',
                sprintf('Processed %d of %d customer accounts.', $done, $total)
            )
        );
    }

    /**
     * Write new and changed records in fixed-size chunks and record each
     * outcome.
     *
     * Each chunk costs one read to load the matching existing records and,
     * when anything differs, one upsert. The upsert and the chunk's audit
     * rows commit in the same transaction, so the log never claims a change
     * that was rolled back.
     *
     * @param  class-string<Model>                  $model          Model to write to.
     * @param  string                               $entity         One of the ImportChange::ENTITY_* constants.
     * @param  array<string, array<string, mixed>>  $records        Records keyed by their unique key value.
     * @param  string                               $uniqueBy       Uniquely indexed column matching the array keys.
     * @param  list<string>                         $updateColumns  Columns compared and overwritten on existing records.
     * @param  ImportStatsRecorder                  $recorder       Collector for this run's outcomes.
     * @param  (callable(int): void)|null           $onChunk        Receives the running count of processed records.
     */
    protected function syncInChunks(
        string $model,
        string $entity,
        array $records,
        string $uniqueBy,
        array $updateColumns,
        ImportStatsRecorder $recorder,
        ?callable $onChunk = null
    ): void {
        $done = 0;

        foreach (array_chunk($records, $this->chunkSize, true) as $chunk) {
            $existing = $model::whereIn($uniqueBy, array_keys($chunk))
                ->get([$uniqueBy, ...$updateColumns])
                ->keyBy($uniqueBy);

            $writes = [];

            foreach ($chunk as $key => $record) {
                $key = (string) $key;
                $current = $existing->get($key);

                if ($current === null) {
                    $writes[] = $record;
                    $recorder->created($entity, $key);
                    continue;
                }

                $changes = $this->diff($current, $record, $updateColumns);

                if ($changes === []) {
                    $recorder->unchanged($entity);
                    continue;
                }

                $writes[] = $record;
                $recorder->updated($entity, $key, $changes);
            }

            if ($writes !== []) {
                DB::transaction(function () use ($model, $writes, $uniqueBy, $updateColumns, $recorder) {
                    $model::upsert($writes, [$uniqueBy], $updateColumns);
                    $recorder->flush();
                });
            }

            $done += count($chunk);

            if ($onChunk !== null) {
                $onChunk($done);
            }
        }
    }

    /**
     * Compare a stored record with incoming values.
     *
     * Values are compared as strings so that type differences between the
     * database driver and the spreadsheet (for example int 6 versus "6") are
     * not reported as changes. Raw attributes are used to skip casts and
     * accessors.
     *
     * @param  Model                 $current  Stored record.
     * @param  array<string, mixed>  $record   Incoming values.
     * @param  list<string>          $columns  Columns to compare.
     * @return array<string, array{old: mixed, new: mixed}> Changed columns only.
     */
    protected function diff(Model $current, array $record, array $columns): array
    {
        $changes = [];

        foreach ($columns as $column) {
            $old = $current->getRawOriginal($column);
            $new = $record[$column] ?? null;

            if ($this->comparable($old) !== $this->comparable($new)) {
                $changes[$column] = ['old' => $old, 'new' => $new];
            }
        }

        return $changes;
    }

    /**
     * @param  mixed  $value  Stored or incoming value.
     * @return string|null
     */
    protected function comparable(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }

    /**
     * Normalize a cell value to a trimmed string, or null when blank.
     *
     * Casting matters because spreadsheet readers return numeric-looking
     * cells as int or float, and untrimmed keys would create duplicates.
     *
     * @param  mixed  $value  Raw cell value.
     * @return string|null
     */
    protected function clean(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}

// TODO: Add a migration enforcing unique indexes on zones.code and customer_accounts.account_number (deduplicate existing rows first); upserts insert duplicates without them.
// TODO: Re-evaluate flags for imported customer accounts (for example with flags:evaluate-all after the run); upserts bypass the CustomerAccount observer.
// TODO: Use $billingCycle in exportRows() or remove the parameter; it is currently unused.
// TODO: Stream exportRows() with lazy()/cursor() once the account count makes loading every account into memory a concern.