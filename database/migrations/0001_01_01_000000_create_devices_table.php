<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();

            // Basic device information
            $table->string('name');
            $table->enum('type', [
                'phone',
                'tablet',
                'laptop',
                'desktop',
                'other'
            ]);

            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable()->unique();

            // Mobile device identifiers
            $table->string('imei')->nullable()->unique();
            $table->string('imei_2')->nullable()->unique();
            $table->string('sim_serial_number')->nullable();
            $table->string('phone_number')->nullable();

            // Operating system details
            $table->string('operating_system')->nullable(); // Android, Windows, iOS, Linux
            $table->string('os_version')->nullable();

            // Hardware details
            $table->string('processor')->nullable();
            $table->string('ram')->nullable();
            $table->string('storage_capacity')->nullable();

            // Network information
            $table->string('mac_address')->nullable()->unique();
            $table->string('ip_address')->nullable();

            // Device management
            $table->enum('status', [
                'active',
                'inactive',
                'lost',
                'damaged',
                'returned'
            ])->default('active');

            $table->date('assigned_at')->nullable();
            $table->date('returned_at')->nullable();

            // Additional metadata
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};