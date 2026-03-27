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
        Schema::create('csa_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('csa_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('zone_id')->constrained();
            $table->foreignId('dma_id')->nullable()->constrained();
            $table->foreignId('billing_cycle_id')->constrained();

            $table->enum('status', ['active','reassigned'])->default('active');
            $table->timestamp('assigned_at')->nullable();

            $table->timestamps();

            $table->unique(['csa_id','zone_id','dma_id','billing_cycle_id']);
            $table->index(['csa_id','billing_cycle_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('csa_assignments');
    }
};
