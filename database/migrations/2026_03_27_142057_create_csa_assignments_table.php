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

            $table->unsignedInteger('target')->default(1000);
            $table->enum('assignment_type', ['primary', 'secondary'])->default('primary');
            $table->foreignId('covered_csa_id')->nullable()->constrained('users');
            $table->string('covering_reason')->nullable();

            $table->enum('status', ['active','reassigned', 'inactive', 'other'])->default('active');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('end_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['csa_id','zone_id','dma_id','billing_cycle_id','assignment_type'],
                'csa_assignments_unique'
            );
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
