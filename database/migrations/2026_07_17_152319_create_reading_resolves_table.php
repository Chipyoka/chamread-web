<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reading_resolves', function (Blueprint $table) {

            $table->id();

            $table->foreignId('reading_id')
                ->constrained('readings')
                ->cascadeOnDelete();

            $table->foreignId('resolved_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('billing_cycle_id')
                ->constrained('billing_cycles')
                ->cascadeOnDelete();

            $table->timestamps();


            $table->unique([
                'reading_id',
                'billing_cycle_id'
            ]);

        });
    }


    public function down(): void
    {
        Schema::dropIfExists('reading_resolves');
    }
};