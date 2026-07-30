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
        Schema::create('customer_account_issues', function (Blueprint $table) {
            $table->id();

            $table->foreignId('zone_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('reported_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('resolved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('account_number')->nullable()->index();
            $table->string('customer_name')->nullable();
            $table->string('meter_number')->nullable()->index();
            $table->string('phone', 20)->nullable();

            $table->string('issue');
            $table->longText('comment')->nullable();

            $table->enum('status', [
                'pending',
                'completed',
                'cancelled',
            ])->default('pending')->index();

            $table->timestamp('resolved_at')->nullable();

            // Optional evidence
            $table->string('photo')->nullable();

            // GPS coordinates
            $table->decimal('gps_latitude', 10, 7)->nullable();
            $table->decimal('gps_longitude', 10, 7)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_account_issues');
    }
};