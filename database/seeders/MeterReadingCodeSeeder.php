<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MeterReadingCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $codes = [
            ['code' => '01', 'name' => 'Working Meter', 'description' => 'Working Meter'],
            ['code' => '02', 'name' => 'Unmetered Active', 'description' => 'Unmetered Active'],
            ['code' => '03', 'name' => 'Disconnected', 'description' => 'Disconnected'],
            ['code' => '04', 'name' => 'Borehole or Own Supply', 'description' => 'Borehole or Own Supply'],
            ['code' => '05', 'name' => 'No access to the Property', 'description' => 'No access to the Property'],
            ['code' => '06', 'name' => 'Stuck Meter', 'description' => 'Stuck Meter'],
            ['code' => '07', 'name' => 'Damaged Meter', 'description' => 'Damaged Meter'],
            ['code' => '08', 'name' => 'Unclear Meter', 'description' => 'Unclear Meter'],
            ['code' => '09', 'name' => 'Uprooted Connection', 'description' => 'Uprooted Connection'],
            ['code' => '10', 'name' => 'No Water', 'description' => 'No Water'],
            ['code' => '11', 'name' => 'Meter Placement', 'description' => 'Meter Placement'],
            ['code' => '12', 'name' => 'Meter replacement', 'description' => 'Meter replacement'],
            ['code' => '13', 'name' => 'Unlocated Meter', 'description' => 'Unlocated Meter'],
            ['code' => '14', 'name' => 'Suspiscious Activity', 'description' => 'Suspiscious Activity'],
            ['code' => '15', 'name' => 'Sewer Only', 'description' => 'Sewer Only'],
            ['code' => '16', 'name' => 'New Account', 'description' => 'New Account'],
            ['code' => '17', 'name' => 'Multiple Houses', 'description' => 'Multiple Houses'],
            ['code' => '18', 'name' => 'Access Denied', 'description' => 'Access Denied'],
            ['code' => '19', 'name' => 'Meter Inaccessible', 'description' => 'Meter Inaccessible'],
            ['code' => '20', 'name' => 'Leaking Meter', 'description' => 'Leaking Meter'],
            ['code' => '21', 'name' => 'Meter Clocked Over', 'description' => 'Meter Clocked Over'],
            ['code' => '22', 'name' => 'Undeveloped Property', 'description' => 'Undeveloped Property'],
            ['code' => '23', 'name' => 'Vacant Property', 'description' => 'Vacant Property'],
            ['code' => '24', 'name' => 'Duplicate Account', 'description' => 'Duplicate Account'],
            ['code' => '25', 'name' => 'Reversing Meter', 'description' => 'Reversing Meter'],
        ];

        foreach ($codes as $codeData) {
            DB::table('meter_reading_codes')->insert([
                'code' => $codeData['code'],
                'name' => $codeData['name'],
                'description' => $codeData['description'],
                'type' => 'reading', // Default type
                'status' => 'active', // Default status
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}