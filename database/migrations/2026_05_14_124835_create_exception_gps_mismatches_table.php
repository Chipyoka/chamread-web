<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exception_gps_mismatches', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('exception_id');

            $table->decimal('expected_latitude', 10, 7);
            $table->decimal('expected_longitude', 10, 7);

            $table->decimal('actual_latitude', 10, 7);
            $table->decimal('actual_longitude', 10, 7);

            $table->decimal('distance_meters', 12, 2);

            $table->unsignedInteger('allowed_radius_meters');
            
            $table->string('comment')->nullable();

            $table->timestamps();

            $table->index('exception_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exception_gps_mismatches');
    }
};