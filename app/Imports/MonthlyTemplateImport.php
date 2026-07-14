<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

/**
 * Handles reading the monthly ERP template.
 *
 * Converts spreadsheet rows into normalized arrays.
 */
class MonthlyTemplateImport implements ToCollection, WithHeadingRow
{
    protected Collection $rows;


    public function __construct()
    {
        $this->rows = collect();


        /*
        |--------------------------------------------------------------------------
        | Keep original headings but normalize them ourselves
        |--------------------------------------------------------------------------
        |
        | Example:
        |
        | "Meter number"
        | becomes
        | "meter_number"
        |
        */

        HeadingRowFormatter::extend(
            'custom',
            function ($value) {

                return strtolower(
                    str_replace(
                        ' ',
                        '_',
                        trim($value)
                    )
                );

            }
        );


        HeadingRowFormatter::default('custom');
    }


    public function collection(Collection $collection): void
    {
        $this->rows = $collection;
    }


    public function getRows(): Collection
    {
        return $this->rows;
    }
}