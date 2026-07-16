<?php

namespace App\Imports;

use App\Models\Zone;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Validators\Failure;
use Illuminate\Support\Facades\Log;

class ZonesImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure
{
    use SkipsErrors, SkipsFailures;

    private $successCount = 0;
    private $errorCount = 0;
    // Remove this line - it conflicts with SkipsErrors trait
    // private $errors = [];

    public function model(array $row)
    {
        // Check if zone already exists
        $existingZone = Zone::where('code', $row['zone'])->first();
        
        if ($existingZone) {
            // Update existing zone
            $existingZone->update([
                'name' => $row['zone'], // Using code as name if not provided
                'district' => $row['district'],
                'province' => $row['province'],
                'status' => 'active',
            ]);
            $this->successCount++;
            return null; // Don't create new record
        }

        // Create new zone
        $this->successCount++;
        return new Zone([
            'code' => $row['zone'],
            'name' => $row['zone'], // Using code as name if not provided
            'district' => $row['district'],
            'province' => $row['province'],
            'status' => 'active',
        ]);
    }

    public function rules(): array
    {
        return [
            'province' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'zone' => 'required|string|max:255',
        ];
    }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $row = $failure->row();
            $errors = implode(', ', $failure->errors());
            // Use the trait's error collection method
            $this->errors[] = "Row {$row}: {$errors}";
            $this->errorCount++;
        }
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function getErrorCount(): int
    {
        return $this->errorCount;
    }

    public function getErrors(): array
    {
        // The SkipsErrors trait provides $this->errors
        return $this->errors ?? [];
    }
}