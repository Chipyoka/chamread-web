<?php

namespace App\Services;

use App\Imports\MonthlyTemplateImport;
use App\Models\CustomerAccount;
use App\Models\ImportProcess;
use App\Models\Zone;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\BillingCycle;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Handles importing monthly ERP customer templates.
 *
 * The import process is responsible for synchronizing:
 *
 * - Zones
 * - Customer accounts
 *
 * The following template data is intentionally ignored:
 *
 * - Current readings
 * - Historical readings
 * - Reading codes
 * - Consumption data
 *
 * These are managed internally by the application.
 */
class MonthlyTemplateService
{
    protected array $requiredColumns = [
        'account',
        'name',
        'address',
        'meter_number',
        'customer_category',
        'phone_number',
        'district',
        'zone',
        'province',
    ];

    /**
     * Rows per progress update. Prevents progress writes from
     * becoming their own N+1 bottleneck.
     */
    protected int $progressStep = 250;

    /**
     * Rows per bulk insert chunk.
     */
    protected int $insertChunkSize = 500;

    /**
     * Process uploaded monthly template.
     */
    public function process(
        ImportProcess $process,
        string $filePath
    ): void {

        $rows = $this->extractRows($process, $filePath);

        $this->validateTemplate($rows);

        $process->markProcessing(
            25,
            'Loading zones',
            'Creating missing zone records...'
        );

        $zoneMap = DB::transaction(
            fn () => $this->importZones($rows, $process)
        );

        $process->markProcessing(
            60,
            'Loading customer accounts',
            'Creating missing customer accounts...'
        );

        DB::transaction(
            fn () => $this->importCustomers($rows, $process, $zoneMap)
        );

        $process->markProcessing(
            95,
            'Finalizing import',
            'Import preparation completed.'
        );
    }

    /**
     * Extract spreadsheet rows using Laravel Excel import.
     */
    protected function extractRows(
        ImportProcess $process,
        string $filePath
    ): array {

        $process->markProcessing(
            10,
            'Extracting file',
            'Reading spreadsheet contents...'
        );

        $fullPath = Storage::disk('local')->path($filePath);

        $import = new MonthlyTemplateImport();

        Excel::import($import, $fullPath);

        return $import->getRows()->toArray();
    }

    /**
     * Validate uploaded template structure.
     */
    protected function validateTemplate(array $rows): void
    {
        if (empty($rows)) {
            throw new \Exception('The uploaded template is empty.');
        }

        $headers = array_keys($rows[0]);

        foreach ($this->requiredColumns as $column) {
            if (!in_array($column, $headers)) {
                throw new \Exception("Missing required column: {$column}");
            }
        }
    }

    /**
     * Import zones.
     *
     * Existing zones are preserved. Only genuinely new zone codes
     * are inserted, in bulk, instead of one firstOrCreate() per row.
     *
     * Returns a [code => id] map so importCustomers() never has to
     * hit the database to resolve a zone again.
     */
    protected function importZones(
        array $rows,
        ImportProcess $process
    ): array {

        // Collapse to unique, non-empty zone codes with their first-seen
        // district/province (mirrors firstOrCreate's "create once" behavior).
        $uniqueZones = [];
        foreach ($rows as $row) {
            $code = $row['zone'] ?? null;
            if (empty($code) || isset($uniqueZones[$code])) {
                continue;
            }
            $uniqueZones[$code] = [
                'code' => $code,
                'name' => $code,
                'district' => $row['district'] ?? '',
                'province' => $row['province'] ?? '',
            ];
        }

        // One query to find out which of those already exist.
        $existingCodes = Zone::whereIn('code', array_keys($uniqueZones))
            ->pluck('id', 'code');

        $now = Carbon::now();
        $toInsert = [];
        foreach ($uniqueZones as $code => $data) {
            if (!$existingCodes->has($code)) {
                $toInsert[] = $data + [
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Bulk insert only what's missing, in manageable chunks.
        foreach (array_chunk($toInsert, $this->insertChunkSize) as $chunk) {
            Zone::insert($chunk);
        }

        $total = count($rows);
        $processed = 0;
        $nextReport = $this->progressStep;

        foreach ($rows as $index => $row) {
            $processed++;
            if ($processed >= $nextReport || $processed === $total) {
                $progress = 25 + ($processed / $total) * 30;
                $process->markProcessing(
                    (int) $progress,
                    'Loading zones',
                    sprintf('Processed %s of %s zones.', $processed, $total)
                );
                $nextReport += $this->progressStep;
            }
        }

        // Return the full code => id map (existing + newly inserted).
        return Zone::whereIn('code', array_keys($uniqueZones))
            ->pluck('id', 'code')
            ->toArray();
    }

    /**
     * Import customer accounts.
     *
     * Existing accounts are preserved. New accounts are bulk-inserted;
     * zone lookups use the in-memory map built in importZones() instead
     * of querying per row.
     */
    protected function importCustomers(
        array $rows,
        ImportProcess $process,
        array $zoneMap
    ): void {

        $total = count($rows);

        // Which accounts already exist? One query for all of them.
        $accountNumbers = array_values(array_filter(
            array_unique(array_column($rows, 'account'))
        ));

        $existingAccounts = CustomerAccount::whereIn('account_number', $accountNumbers)
            ->pluck('id', 'account_number');

        $now = Carbon::now();
        $toInsert = [];
        $seen = [];

        foreach ($rows as $row) {
            $account = $row['account'] ?? null;

            if (empty($account) || $existingAccounts->has($account) || isset($seen[$account])) {
                continue;
            }

            $zoneId = $zoneMap[$row['zone'] ?? null] ?? null;
            if (!$zoneId) {
                continue;
            }

            $seen[$account] = true;

            $toInsert[] = [
                'account_number' => $account,
                'customer_name' => $row['name'] ?? '',
                'address' => $row['address'] ?? null,
                'phone' => $row['phone_number'] ?? null,
                'meter_number' => $row['meter_number'] ?? null,
                'customer_category' => $row['customer_category'] ?? null,
                'zone_id' => $zoneId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($toInsert, $this->insertChunkSize) as $chunk) {
            CustomerAccount::insert($chunk);
        }

        $processed = 0;
        $nextReport = $this->progressStep;

        foreach ($rows as $row) {
            $processed++;
            if ($processed >= $nextReport || $processed === $total) {
                $progress = 60 + ($processed / $total) * 35;
                $process->markProcessing(
                    (int) $progress,
                    'Loading customer accounts',
                    sprintf('Processed %s of %s customer accounts.', $processed, $total)
                );
                $nextReport += $this->progressStep;
            }
        }
    }

    /**
     * Build the monthly ERP export rows. (Unchanged.)
     */
    public function exportRows(BillingCycle $billingCycle): Collection
    {
        return CustomerAccount::query()
            ->with('zone')
            ->orderBy('account_number')
            ->get()
            ->map(function (CustomerAccount $account) {
                return [
                    'Account' => $account->account_number,
                    'Name' => $account->customer_name,
                    'Address' => $account->address,
                    'Meter number' => $account->meter_number,
                    'Customer Category' => $account->customer_category,
                    'Current Reading Date' => '',
                    'Current Reading' => '',
                    'Meter reading ERP (incl estimates)' => '',
                    'Meter Status ERP' => '',
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
                ];
            });
    }
}