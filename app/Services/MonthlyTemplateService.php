<?php

namespace App\Services;

use App\Imports\MonthlyTemplateImport;
use App\Models\CustomerAccount;
use App\Models\ImportProcess;
use App\Models\Zone;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
    /**
     * Required normalized Excel columns.
     *
     * These match Laravel Excel WithHeadingRow output.
     */
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
     * Process uploaded monthly template.
     */
    public function process(
        ImportProcess $process,
        string $filePath
    ): void {

        $rows = $this->extractRows(
            $process,
            $filePath
        );


        $this->validateTemplate(
            $rows
        );


        $process->markProcessing(
            25,
            'Loading zones',
            'Creating missing zone records...'
        );


        $this->importZones(
            $rows,
            $process
        );


        $process->markProcessing(
            60,
            'Loading customer accounts',
            'Creating missing customer accounts...'
        );


        $this->importCustomers(
            $rows,
            $process
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


        Excel::import(
            $import,
            $fullPath
        );


        return $import
            ->getRows()
            ->toArray();

    }


    /**
     * Validate uploaded template structure.
     */
    protected function validateTemplate(
        array $rows
    ): void {

        if (empty($rows)) {

            throw new \Exception(
                'The uploaded template is empty.'
            );

        }


        logger()->info('First imported row', [
            'row' => $rows[0],
        ]);


        logger()->info('Imported headers', [
            'headers' => array_keys($rows[0]),
        ]);


        $headers = array_keys($rows[0]);


        foreach ($this->requiredColumns as $column) {

            if (!in_array($column, $headers)) {

                throw new \Exception(
                    "Missing required column: {$column}"
                );

            }

        }

    }


    /**
     * Import zones.
     *
     * Existing zones are preserved.
     */
    protected function importZones(
        array $rows,
        ImportProcess $process
    ): void {

        $total = count($rows);

        foreach ($rows as $index => $row) {


            if (empty($row['zone'])) {
                continue;
            }

            logger()->info('Zone row debug', [
                'zone_row' => $rows[0],
            ]);

            Zone::firstOrCreate(
                [
                    'code' => $row['zone'],
                ],
                [
                    'name' => $row['zone'],
                    'district' => $row['district'] ?? '',
                    'province' => $row['province'] ?? '',
                ]
            );


            $progress = 25 + (($index + 1) / $total) * 30;


            $process->markProcessing(
                (int) $progress,
                'Loading zones',
                sprintf(
                    'Processed %s of %s zones.',
                    $index + 1,
                    $total
                )
            );

        }

    }


    /**
     * Import customer accounts.
     *
     * Existing accounts are preserved.
     */
    protected function importCustomers(
        array $rows,
        ImportProcess $process
    ): void {

        $total = count($rows);


        foreach ($rows as $index => $row) {


            if (empty($row['account'])) {
                continue;
            }


            $zone = Zone::where(
                'code',
                $row['zone']
            )->first();


            if (!$zone) {
                continue;
            }


            CustomerAccount::firstOrCreate(
                [
                    'account_number' =>
                        $row['account'],
                ],
                [
                    'customer_name' =>
                        $row['name'] ?? '',

                    'address' =>
                        $row['address'] ?? null,

                    'phone' =>
                        $row['phone_number'] ?? null,

                    'meter_number' =>
                        $row['meter_number'] ?? null,

                    'customer_category' =>
                        $row['customer_category'] ?? null,

                    'zone_id' =>
                        $zone->id,
                ]
            );


            $progress = 60 + (($index + 1) / $total) * 35;


            $process->markProcessing(
                (int) $progress,
                'Loading customer accounts',
                sprintf(
                    'Processed %s of %s customer accounts.',
                    $index + 1,
                    $total
                )
            );

        }

    }
}