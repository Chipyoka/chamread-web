<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

/**
 * Generates the monthly ERP template export file.
 *
 * This class is responsible only for formatting
 * prepared data into the required spreadsheet structure.
 *
 * Data retrieval and transformation should happen
 * before reaching this exporter.
 */
class MonthlyTemplateExport implements FromCollection, WithHeadings
{
    /**
     * Export rows.
     */
    protected Collection $rows;


    /**
     * Create a new export instance.
     *
     * @param Collection|array $rows
     */
    public function __construct(
        Collection|array $rows
    ) {

        $this->rows = collect($rows);

    }


    /**
     * Return rows for Excel generation.
     *
     * @return Collection
     */
    public function collection(): Collection
    {
        return $this->rows;
    }


    /**
     * Define the ERP template columns.
     *
     * These must always remain in the same order
     * as the original uploaded template.
     *
     * @return array<int,string>
     */
    public function headings(): array
    {
        return [

            'Account',

            'Name',

            'Address',

            'Meter number',

            'Customer Category',

            'Current Reading Date',

            'Current Reading',

            'Meter reading ERP (incl estimates)',

            'Meter Status ERP',

            'Optional Comment',

            'MR: This month Code',

            'MR: Last month Code',

            'Phone number',

            'Previous Date',

            'Previous2 Meter Code',

            'Previous2 Reading',

            'Previous2 Date',

            'Previous3 Meter Code',

            'Previous3 Reading',

            'Previous3 Date',

            'District',

            'Zone',

            'Consumption',

            'Province',

        ];
    }
}