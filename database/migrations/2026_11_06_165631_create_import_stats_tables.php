<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the import audit tables.
 *
 * - import_summaries: one row per (import process, entity) with outcome counts.
 * - import_changes: append-only log of created, updated and skipped records.
 *   Unchanged records are counted in the summary but not logged, which keeps
 *   routine monthly re-imports small.
 *
 * Both tables cascade-delete with their import process.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('import_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_process_id')->constrained()->cascadeOnDelete();
            $table->string('entity', 32);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('unchanged_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->timestamps();

            $table->unique(['import_process_id', 'entity']);
        });

        Schema::create('import_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_process_id')->constrained()->cascadeOnDelete();
            $table->string('entity', 32);
            $table->string('action', 16);
            $table->string('record_key', 64)->nullable();
            $table->unsignedInteger('row_number')->nullable();
            $table->json('changes')->nullable();
            $table->string('reason', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['import_process_id', 'entity', 'action']);
            $table->index(['entity', 'record_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_changes');
        Schema::dropIfExists('import_summaries');
    }
};

// TODO: None