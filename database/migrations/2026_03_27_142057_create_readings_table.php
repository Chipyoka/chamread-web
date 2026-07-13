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
        Schema::create('readings', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('account_id')
                ->constrained('customer_accounts')
                ->cascadeOnDelete();

            $table->foreignId('csa_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('billing_cycle_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Reading Data
            |--------------------------------------------------------------------------
            */
            $table->date('reading_date')->nullable();
            $table->decimal('previous_reading', 12, 3)->nullable();
            $table->decimal('current_reading', 12, 3)->nullable();

            $table->string('meter_status')->nullable();

            // Meter reading codes
            $table->string('this_month_code')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Reading Result
            |--------------------------------------------------------------------------
            */
            $table->enum('status', ['read', 'not_read']);

            $table->foreignId('meter_reading_code')
                ->nullable()
                ->constrained('meter_reading_codes')
                ->nullOnDelete();

            $table->text('comment')->nullable();

            // Calculated/Imported Consumption
            $table->decimal('consumption', 12, 3)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Field Evidence
            |--------------------------------------------------------------------------
            */
            $table->string('photo_path', 500)->nullable();

            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */
            $table->timestamp('reading_time')->useCurrent();
            $table->timestamp('synced_at')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Constraints
            |--------------------------------------------------------------------------
            */

            // One reading per account per billing cycle
            $table->unique(['account_id', 'billing_cycle_id']);

            $table->index(['csa_id', 'billing_cycle_id']);
            $table->index(['account_id', 'billing_cycle_id']);
            $table->index('status');
            $table->index('reading_date');
            $table->index('reading_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('readings');
    }
};