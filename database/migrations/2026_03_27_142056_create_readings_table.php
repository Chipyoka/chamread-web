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

            $table->string('account_number');

            $table->foreignId('csa_id')->constrained('users');
            $table->foreignId('billing_cycle_id')->constrained();

            $table->foreignId('zone_id')->constrained();
            $table->foreignId('dma_id')->constrained();

            $table->decimal('previous_reading',12,3)->nullable();
            $table->decimal('current_reading',12,3)->nullable();

            $table->enum('status',['read','not_read']);
            $table->string('reason_code')->nullable();

            $table->string('photo_path',500)->nullable();
            $table->decimal('latitude',10,8)->nullable();
            $table->decimal('longitude',11,8)->nullable();

            $table->timestamp('reading_time')->useCurrent();
            $table->timestamp('synced_at')->nullable();

            $table->timestamps();

            $table->unique(['account_number','billing_cycle_id']);

            $table->index(['csa_id','billing_cycle_id']);
            $table->index(['account_number','billing_cycle_id']);
            $table->index(['zone_id','dma_id']);
            $table->index('status');
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
