<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;

/**
 * Generates the monthly ERP template export file.
 *
 * Responsibilities:
 *
 * - Render prepared export rows.
 * - Apply spreadsheet formatting.
 * * This class intentionally contains no business logic.
 */
class MonthlyTemplateExport extends StringValueBinder implements
    FromCollection,
    WithHeadings,
    WithEvents,
    ShouldAutoSize,
    WithColumnFormatting,
    WithCustomValueBinder
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
     */
    public function collection(): Collection
    {
        return $this->rows;
    }


    /**
     * Force phone numbers to remain text.
     */
    public function bindValue($cell, $value)
    {
        // Column M = Phone Number
        if ($cell->getColumn() === 'M') {

            $cell->setValueExplicit(
                (string) $value,
                DataType::TYPE_STRING
            );

            return true;
        }

        return parent::bindValue($cell, $value);
    }


    /**
     * Column formatting.
     */
    public function columnFormats(): array
    {
        return [
            'M' => '@',
        ];
    }


    /**
     * ERP template headings.
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


    /**
     * Apply worksheet formatting.
     */
    public function registerEvents(): array
    {
        return [

            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                /*
                |--------------------------------------------------------------------------
                | Freeze Header Row
                |--------------------------------------------------------------------------
                */

                $sheet->freezePane('A2');


                /*
                |--------------------------------------------------------------------------
                | Header Styling
                |--------------------------------------------------------------------------
                */

                $sheet->getStyle('A1:X1')->applyFromArray([

                    'font' => [

                        'bold' => true,

                        'color' => [
                            'rgb' => 'FFFFFF',
                        ],

                    ],

                    'fill' => [

                        'fillType' => Fill::FILL_SOLID,

                        'startColor' => [
                            'rgb' => '00B050',
                        ],

                    ],

                    'alignment' => [

                        'horizontal' => Alignment::HORIZONTAL_CENTER,

                        'vertical' => Alignment::VERTICAL_CENTER,

                    ],

                   

                ]);


                /*
                |--------------------------------------------------------------------------
                | Header Height
                |--------------------------------------------------------------------------
                */

                $sheet->getRowDimension(1)->setRowHeight(24);

            },

        ];
    }
}