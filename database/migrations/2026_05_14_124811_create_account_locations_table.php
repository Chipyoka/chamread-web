<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_locations', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('account_id')->unique();

            $table->text('address_snapshot')->nullable();
            $table->string('address_hash')->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->string('geocode_provider')->nullable();
            $table->decimal('geocode_confidence', 5, 2)->nullable();

            $table->timestamp('geocoded_at')->nullable();

            $table->enum('status', [
                'pending',
                'success',
                'failed',
                'ambiguous'
            ])->default('pending');

            $table->unsignedInteger('retry_count')->default(0);

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_locations');
    }
};