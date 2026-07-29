<?php

namespace App\Exports;

use App\Models\Reading;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MeterReadingsExport extends StringValueBinder implements 
    FromCollection, 
    WithHeadings, 
    WithMapping, 
    ShouldAutoSize, 
    WithEvents,
    WithStrictNullComparison,
    WithCustomValueBinder
{
    protected $billingCycleId;
    protected $duration;
    protected $search;
    protected $zoneId;
    protected $district;

    public function __construct($billingCycleId, $duration = null, $search = null, $zoneId = null, $district = null)
    {
        $this->billingCycleId = $billingCycleId;
        $this->duration = $duration;
        $this->search = $search;
        $this->zoneId = $zoneId;
        $this->district = $district;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = Reading::with(['account', 'account.zone', 'csa', 'billingCycle'])
            ->where('billing_cycle_id', $this->billingCycleId);

        // Apply duration filter
        if ($this->duration === 'today') {
            $query->whereDate('reading_time', today());
        } elseif ($this->duration === 'this_week') {
            $query->whereBetween('reading_time', [now()->startOfWeek(), now()->endOfWeek()]);
        }

        // Apply search filter by account number
        if ($this->search) {
            $query->whereHas('account', function ($q) {
                $q->where('account_number', 'like', '%' . $this->search . '%');
            });
        }

        // Apply zone filter
        if ($this->zoneId) {
            $query->whereHas('account', function ($q) {
                $q->where('zone_id', $this->zoneId);
            });
        }

        // Apply district filter (from zone relation)
        if ($this->district) {
            $query->whereHas('account.zone', function ($q) {
                $q->where('district', $this->district);
            });
        }

        return $query->orderBy('reading_time', 'desc')->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ACCOUNT NUMBER',
            'CUSTOMER NAME',
            'PHONE',
            'ADDRESS',
            'ZONE',
            'DISTRICT',
            'PREVIOUS READING (M3)',
            'CURRENT READING (M3)',
            'CONSUMPTION (M3)',
            'STATUS',
            'METER STATUS',
            'THIS MONTH CODE',
            'COMMENT',
            'READING DATE',
            'READING TIME',
            'CSA NAME',
            'BILLING CYCLE',
            'CREATED AT',
            'UPDATED AT'
        ];
    }

    /**
     * @param mixed $reading
     * @return array
     */
    public function map($reading): array
    {
        $consumption = ($reading->current_reading ?? 0) - ($reading->previous_reading ?? 0);

        return [
            $reading->account->account_number ?? '',
            $reading->account->customer_name ?? '',
            $reading->account->phone ?? '',
            $reading->account->address ?? '',
            $reading->account->zone->name ?? '',
            $reading->account->zone->district ?? '',
            number_format((float) ($reading->previous_reading ?: 0), 3, '.', ''),
            number_format((float) ($reading->current_reading ?: 0), 3, '.', ''),
            number_format($consumption, 3, '.', ''),
            ucfirst($reading->status),
            $reading->meter_status ?? '',
            $reading->this_month_code ?? '',
            $reading->comment ?? '',
            $reading->reading_date ? $reading->reading_date : '',
            $reading->reading_time ? $reading->reading_time : '',
            $reading->csa->name ?? '',
            $reading->billingCycle->name ?? '',
            $reading->created_at ? $reading->created_at->format('Y-m-d H:i:s') : '',
            $reading->updated_at ? $reading->updated_at->format('Y-m-d H:i:s') : '',
        ];
    }

    /**
     * Custom value binder to force phone numbers as text
     */
    public function bindValue(Cell $cell, $value)
    {
        $columnIndex = $cell->getColumn();
        
        // Column C is the PHONE column (A=1, B=2, C=3)
        if ($columnIndex === 'C' && $value !== null && $value !== '') {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }

        // For all other columns, use the default binding
        return parent::bindValue($cell, $value);
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $lastColumn = $sheet->getHighestColumn();

                // Style the header row
                $headerRange = 'A1:' . $lastColumn . '1';
                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 11,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '00B050'], // Dark Green
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Apply borders to all cells
                $range = 'A1:' . $lastColumn . $lastRow;
                $sheet->getStyle($range)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'D3D3D3'],
                        ],
                    ],
                ]);

                // Style the data rows
                $dataRange = 'A2:' . $lastColumn . $lastRow;
                $sheet->getStyle($dataRange)->applyFromArray([
                    'font' => [
                        'size' => 10,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Set specific column widths manually
                $sheet->getColumnDimension('B')->setWidth(30); // Customer Name
                $sheet->getColumnDimension('C')->setWidth(20); // Phone
                $sheet->getColumnDimension('D')->setWidth(40); // Address
                $sheet->getColumnDimension('F')->setWidth(20); // District
                $sheet->getColumnDimension('G')->setWidth(22); // Previous Reading
                $sheet->getColumnDimension('H')->setWidth(22); // Current Reading
                $sheet->getColumnDimension('I')->setWidth(22); // Consumption
                $sheet->getColumnDimension('N')->setWidth(15); // Reading Date
                $sheet->getColumnDimension('O')->setWidth(20); // Reading Time
                $sheet->getColumnDimension('P')->setWidth(25); // CSA Name
                $sheet->getColumnDimension('Q')->setWidth(20); // Billing Cycle
                $sheet->getColumnDimension('R')->setWidth(20); // Created At
                $sheet->getColumnDimension('S')->setWidth(20); // Updated At

                // Freeze the header row
                $sheet->freezePane('A2');

                // Set row height for header
                $sheet->getRowDimension(1)->setRowHeight(25);
            },
        ];
    }
}