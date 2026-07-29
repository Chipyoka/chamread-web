<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use App\Models\CustomerAccount;
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

class CustomerAccountsExport extends StringValueBinder implements 
    FromCollection, 
    WithHeadings, 
    WithMapping, 
    ShouldAutoSize, 
    WithEvents,
    WithStrictNullComparison,
    WithCustomValueBinder
{
    protected $zoneId;
    protected $search;
    protected $category;

    public function __construct($zoneId = null, $search = null, $category = null)
    {
        $this->zoneId = $zoneId;
        $this->search = $search;
        $this->category = $category;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = CustomerAccount::with('zone');

        // Apply zone filter if provided
        if ($this->zoneId) {
            $query->where('zone_id', $this->zoneId);
        }

        // Apply category filter if provided
        if ($this->category) {
            $query->where('customer_category', 'like', '%' . $this->category . '%');
        }

        // Apply search filter if provided
        if ($this->search) {
            $query->where('account_number', 'like', '%' . $this->search . '%');
        }

        return $query->orderBy('account_number')->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ACCOUNT_NUMBER',
            'CUSTOMER_NAME',
            'ADDRESS',
            'PHONE',
            'METER_NUMBER',
            'CUSTOMER_CATEGORY',
            'ZONE',
            'STATUS',
            'CREATED_AT',
            'UPDATED_AT'
        ];
    }

    /**
     * @param mixed $account
     * @return array
     */
    public function map($account): array
    {
        return [
            $account->account_number,
            $account->customer_name,
            $account->address ?? '',
            $account->phone ?? '',
            $account->meter_number ?? '',
            $account->customer_category ?? '',
            $account->zone->name ?? '',
            ucfirst($account->status),
            $account->created_at ? $account->created_at->format('Y-m-d H:i:s') : '',
            $account->updated_at ? $account->updated_at->format('Y-m-d H:i:s') : '',
        ];
    }

    /**
     * Custom value binder to force phone number as text
     */
    public function bindValue(Cell $cell, $value)
    {
        $columnIndex = $cell->getColumn();
        
        // Column D is the PHONE column (A=1, B=2, C=3, D=4)
        if ($columnIndex === 'D' && $value !== null && $value !== '') {
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

                // Set specific column widths manually if needed
                $sheet->getColumnDimension('C')->setWidth(40); // Address
                $sheet->getColumnDimension('D')->setWidth(20); // Phone (set width for better visibility)
                $sheet->getColumnDimension('I')->setWidth(20); // Created At
                $sheet->getColumnDimension('J')->setWidth(20); // Updated At

                // Freeze the header row
                $sheet->freezePane('A2');

                // Set row height for header
                $sheet->getRowDimension(1)->setRowHeight(25);
            },
        ];
    }
}