<?php

namespace Database\Seeders;

use App\Models\District;
use Illuminate\Database\Seeder;

class DistrictSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $districts = [
            ['short_code' => 'KMH', 'name' => 'KASAMA MULENGA HILLS'],
            ['short_code' => 'KCT', 'name' => 'KASAMA CENTRAL TOWN'],
            ['short_code' => 'MPI', 'name' => 'MPIKA'],
            ['short_code' => 'NAK', 'name' => 'NAKONDE'],
            ['short_code' => 'ISO', 'name' => 'ISOKA'],
            ['short_code' => 'MPU', 'name' => 'MPULUNGU'],
            ['short_code' => 'MBA', 'name' => 'MBALA'],
            ['short_code' => 'CHN', 'name' => 'CHINSALI'],
            ['short_code' => 'CHL', 'name' => 'CHILUBI'],
            ['short_code' => 'MPO', 'name' => 'MPOROKOSO'],
            ['short_code' => 'KAP', 'name' => 'KAPUTA'],
            ['short_code' => 'LUW', 'name' => 'LUWINGU'],
            ['short_code' => 'MUN', 'name' => 'MUNGWI'],
        ];

        foreach ($districts as $district) {
            District::updateOrCreate(
                ['short_code' => $district['short_code']],
                [
                    'name'   => $district['name'],
                    'description'   => 'District of ' . $district['name'] . '.',
                    'status' => District::STATUS_ACTIVE,
                ]
            );
        }
    }
}