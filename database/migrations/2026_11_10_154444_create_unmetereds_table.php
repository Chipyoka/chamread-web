<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unmetered', function (Blueprint $table) {
            $table->id();

            $table->foreignId('account_id')->constrained('customer_accounts');
            $table->foreignId('csa_id')->constrained('users');
            $table->foreignId('billing_cycle_id')->constrained('billing_cycles');

            // Set when the app sent the previous cycle's id and the server
            // moved the record into the current cycle (same idea as readings).
            $table->unsignedBigInteger('remapped_from_cycle_id')->nullable();

            // active | inactive — see Unmetered::STATUSES
            $table->string('connection_status', 20)->nullable();

            // The selected comment: a meter_reading_codes row, limited by the
            // API to Unmetered::ALLOWED_CODES. Named like readings.meter_reading_code.
            $table->foreignId('meter_reading_code')->constrained('meter_reading_codes');

            // Required by the API, nullable here so the policy can change
            // without another migration.
            $table->string('photo_path')->nullable();
            $table->string('comment')->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->dateTime('captured_at');           // captured on the device
            $table->dateTime('synced_at')->nullable(); // received by the server

            $table->timestamps();

            // One record per account per cycle. This is also what makes the
            // sync endpoint safe against double submissions.
            $table->unique(['account_id', 'billing_cycle_id']);

            $table->index(['csa_id', 'billing_cycle_id']);
            $table->index(['billing_cycle_id', 'captured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unmetered');
    }
};