<?php
// database/migrations/[timestamp]_create_flag_rules_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flag_rules', function (Blueprint $table) {
            $table->id();
            
            // Relationship to flag
            $table->foreignId('flag_id')
                  ->constrained('flags')
                  ->cascadeOnDelete();
            
            // Rule definition - simple expression engine
            // Format: field, operator, value
            // Example: consumption > 100
            $table->string('field');        // Model attribute: consumption, status, etc.
            $table->string('operator');     // Comparison: >, <, =, !=, >=, <=, contains, is_null, is_not_null
            $table->string('value')->nullable(); // Threshold: 100, "not_read", null for is_null checks
            
            // Logical grouping (for future AND/OR support)
            $table->string('group_key')->nullable();    // Group multiple rules together
            $table->integer('order')->default(0);       // Evaluation order within group
            
            // Execution control
            $table->boolean('active')->default(true);
            $table->enum('evaluation_type', ['on_save', 'scheduled', 'both'])->default('on_save');
            $table->text('description')->nullable();    // Human explanation of the rule
            
            $table->timestamps();
            
            // Indexes
            $table->index(['flag_id', 'active']);
            $table->index('evaluation_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flag_rules');
    }
};