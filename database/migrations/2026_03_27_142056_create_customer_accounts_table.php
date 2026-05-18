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

            $table->string('account_number');
            $table->string('meter_number')->nullable();

            $table->string('name')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();

            $table->foreignId('zone_id')->constrained();
            $table->foreignId('dma_id')->constrained();
            $table->string('billing_area')->nullable();
            $table->string('status')->default('active');

            $table->timestamps();

            $table->unique(['account_number']);
            $table->index(['zone_id','dma_id']);
            $table->index('account_number');
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
