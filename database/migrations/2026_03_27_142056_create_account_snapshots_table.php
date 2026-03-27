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
        Schema::create('accounts_snapshot', function (Blueprint $table) {
            $table->id();

            $table->string('account_number');
            $table->string('meter_number')->nullable();

            $table->string('name')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();

            $table->foreignId('zone_id')->constrained();
            $table->foreignId('dma_id')->constrained();
            $table->string('billing_area')->nullable();

            $table->foreignId('billing_cycle_id')->constrained();

            $table->decimal('previous_reading', 12, 3)->nullable();

            $table->timestamp('created_at')->nullable();

            $table->unique(['account_number','billing_cycle_id']);
            $table->index(['zone_id','dma_id']);
            $table->index('account_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_snapshots');
    }
};
