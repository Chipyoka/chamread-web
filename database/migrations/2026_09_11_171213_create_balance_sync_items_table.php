<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('balance_sync_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('balance_sync_runs')->cascadeOnDelete();
            $table->string('account_number', 64);
            $table->enum('status', ['pending', 'success', 'failed', 'not_attempted'])->default('pending');
            $table->decimal('balance', 15, 2)->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('last_error', 255)->nullable();
            $table->timestamps();

            $table->index(['run_id', 'status']);
            $table->index(['run_id', 'account_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('balance_sync_items');
    }
};