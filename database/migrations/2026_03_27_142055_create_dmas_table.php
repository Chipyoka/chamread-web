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
        Schema::create('dmas', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');

            $table->foreignId('zone_id')->constrained()->restrictOnDelete();

            $table->enum('status', ['active','inactive'])->default('active');
            $table->timestamps();

            $table->index('zone_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dmas');
    }
};
