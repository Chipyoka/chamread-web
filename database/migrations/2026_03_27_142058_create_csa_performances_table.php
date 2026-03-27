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
        Schema::create('csa_performance', function (Blueprint $table) {
            $table->id();

            $table->foreignId('csa_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('billing_cycle_id')->constrained()->cascadeOnDelete();

            $table->integer('total_assigned')->default(0);
            $table->integer('total_read')->default(0);
            $table->integer('total_not_read')->default(0);

            $table->integer('photo_compliance')->default(0);
            $table->integer('gps_compliance')->default(0);

            $table->decimal('completion_rate',5,2)->nullable();

            $table->timestamp('calculated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['csa_id','billing_cycle_id']);
            $table->index('completion_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('csa_performances');
    }
};
