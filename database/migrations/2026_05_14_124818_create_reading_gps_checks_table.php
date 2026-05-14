<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reading_gps_checks', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('reading_id')->unique();

            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('billing_cycle_id')->nullable();

            $table->timestamp('processed_at');

            $table->enum('status', [
                'valid',
                'mismatch',
                'missing_coordinates',
                'invalid_gps',
                'skipped'
            ]);

            $table->decimal('distance_meters', 12, 2)->nullable();

            $table->unsignedInteger('allowed_radius_meters')->nullable();

            $table->unsignedBigInteger('exception_id')->nullable();

            $table->timestamps();

            $table->index('account_id');
            $table->index('billing_cycle_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_gps_checks');
    }
};