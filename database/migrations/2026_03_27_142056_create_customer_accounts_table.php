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
        Schema::create('customer_accounts', function (Blueprint $table) {
            $table->id();

            // Customer Details
            $table->string('account_number')->unique();
            $table->string('customer_name');
            $table->text('address')->nullable();
            $table->string('phone')->nullable();

            // Meter Information
            $table->string('meter_number')->nullable()->index();
            $table->string('customer_category')->nullable();

            // Location
            $table->foreignId('zone_id')->constrained()->cascadeOnDelete();

            // Account Status
            $table->string('status')->default('active');

            $table->timestamps();

            // Indexes
            $table->index('account_number');
            $table->index('phone');
            $table->index(['zone_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_accounts');
    }
};