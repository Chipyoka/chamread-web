<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NonReadReasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('non_read_reasons')->insert([
            ['code'=>'locked_gate','name'=>'Locked Gate','sort_order'=>1],
            ['code'=>'dog','name'=>'Dog on Premises','sort_order'=>2],
            ['code'=>'no_meter','name'=>'No Meter Found','sort_order'=>3],
            ['code'=>'faulty','name'=>'Meter Faulty','sort_order'=>4],
            ['code'=>'refused','name'=>'Customer Refused','sort_order'=>5],
            ['code'=>'no_access','name'=>'No Access','sort_order'=>6],
            ['code'=>'vacant','name'=>'Property Vacant','sort_order'=>7],
            ['code'=>'other','name'=>'Other','sort_order'=>8],
        ]);
    }
}
