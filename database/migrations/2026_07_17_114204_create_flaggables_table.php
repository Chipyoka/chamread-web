<?php
// database/migrations/[timestamp]_create_flaggables_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flaggables', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to flags
            $table->foreignId('flag_id')
                  ->constrained('flags')
                  ->cascadeOnDelete();
            
            // Polymorphic relationship
            $table->morphs('flaggable');
            
            // Audit trail - how was this flag applied?
            $table->enum('source', ['manual', 'rule', 'system'])->default('manual');
            $table->foreignId('created_by')->nullable()->constrained('users');
            
            // Context - what triggered this flag?
            // Stores the actual values that matched the rule
            $table->json('context')->nullable();
            
            // When does this flag expire? Null = never
            $table->timestamp('expires_at')->nullable();
            
            $table->timestamps();
            
            // Prevent duplicate flags on same entity
            $table->unique(['flag_id', 'flaggable_type', 'flaggable_id'], 
                          'flaggable_unique_flag');
            
            // Indexes for common queries
            $table->index(['source', 'created_at']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flaggables');
    }
};