<?php

namespace App\Exports;

use App\Models\CustomerAccountIssue;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CustomerAccountIssuesExport extends StringValueBinder implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithEvents,
    WithStrictNullComparison,
    WithCustomValueBinder
{
    /**
     * @var int
     */
    protected int $rowNumber = 0;

    public function collection(): Collection
    {
        return CustomerAccountIssue::with([
                'zone',
                'reporter',
                'resolver'
            ])
            ->orderByDesc('created_at')
            ->get();
    }

    public function headings(): array
    {
        return [
            'NO',
            'ACCOUNT NUMBER',
            'CUSTOMER NAME',
            'METER NUMBER',
            'PHONE',
            'ZONE',
            'ISSUE',
            'COMMENT',
            'STATUS',
            'REPORTED BY',
            'RESOLVED BY',
            'RESOLVED AT',
            'LATITUDE',
            'LONGITUDE',
            'CREATED AT',
        ];
    }

    public function map($issue): array
    {
        return [
            ++$this->rowNumber,
            $issue->account_number,
            $issue->customer_name,
            $issue->meter_number,
            $issue->phone,
            $issue->zone?->name,
            $issue->issue,
            $issue->comment,
            strtoupper($issue->status),
            $issue->reporter?->name,
            $issue->resolver?->name,
            optional($issue->resolved_at)?->format('d M Y H:i'),
            $issue->gps_latitude,
            $issue->gps_longitude,
            optional($issue->created_at)->format('d M Y H:i'),
        ];
    }

    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet;

                // Header styling
                $sheet->getStyle('A1:O1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 11,
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
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);

                // All cells
                $lastRow = $sheet->getHighestRow();

                $sheet->getStyle("A1:O{$lastRow}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->getStyle("A1:O{$lastRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                // Freeze header row
                $sheet->freezePane('A2');

                // Autofilter
                $sheet->setAutoFilter("A1:O{$lastRow}");
            },
        ];
    }
}