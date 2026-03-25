<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if admin already exists
        $existing = DB::table('users')->where('email', 'admin@example.com')->first();
        if ($existing) {
            $this->command->info('Admin user already exists. Skipping...');
            return;
        }

        DB::table('users')->insert([
            'name' => 'Administrator',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'photo_url' => '/images/default-avatar.png', // optional default photo
            'role' => 'admin',
            'email_verified_at' => Carbon::now(),
            'password' => Hash::make('Admin@123'), // choose a secure default
            'remember_token' => Str::random(10),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $this->command->info('Admin user created successfully.');
    }
}