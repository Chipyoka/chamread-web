<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('district_user', function (Blueprint $table) {
            $table->id();

            $table->foreignId('district_id')
                  ->constrained('districts')
                  ->cascadeOnDelete();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            // Assignment metadata
            $table->enum('role', ['cra', 'cso', 'dm', 'ds','wqo', 'wos', 'other'])
                  ->default('cso');
            $table->enum('status', ['active', 'inactive'])
                  ->default('active');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Prevent duplicate assignments
            $table->unique(['district_id', 'user_id']);

            // Helpful indexes
            $table->index(['user_id', 'status']);
            $table->index(['district_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('district_user');
    }
};