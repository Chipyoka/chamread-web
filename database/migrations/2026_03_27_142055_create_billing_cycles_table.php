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
        Schema::create('billing_cycles', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            $table->date('start_date');
            $table->date('end_date');
            $table->date('deadline')->nullable();
            $table->boolean('can_download')->default(false);
            $table->boolean('can_upload')->default(false);

            $table->enum('status', ['pending','active','locked','closed'])->default('active');
            $table->timestamps();

            $table->index('status');
            $table->index(['start_date','end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_cycles');
    }
};
