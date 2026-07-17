<?php
// database/migrations/[timestamp]_create_flags_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flags', function (Blueprint $table) {
            $table->id();
            
            // Core identification
            $table->string('name');                    // Human-readable: "High Consumption"
            $table->string('code')->unique();          // Machine-readable: "HIGH_CONSUMPTION"
            $table->text('description')->nullable();   // What this flag means
            
            // Target entity - we're focusing on these three
            $table->enum('applies_to', [
                'account',
                'reading', 
                'meter_reader'
            ]);
            
            // UI hints
            $table->string('color')->nullable();       // Hex color for badges: #FF0000
            $table->string('icon')->nullable();        // Icon class or identifier
            
            // System flags can't be deleted by users
            $table->boolean('is_system')->default(false);
            $table->boolean('active')->default(true);
            
            $table->timestamps();
            
            // Indexes for common queries
            $table->index(['applies_to', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flags');
    }
};