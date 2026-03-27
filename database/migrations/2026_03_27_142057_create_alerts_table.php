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
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('reading_id')->constrained()->cascadeOnDelete();
            $table->string('account_number');

            $table->enum('type', [
                'gps_mismatch',
                'abnormal_reading',
                'zero_consumption',
                'repeated_non_read'
            ]);

            $table->enum('severity',['low','medium','high'])->default('medium');

            $table->json('details')->nullable();

            $table->enum('status',['pending','investigating','resolved'])->default('pending');

            $table->foreignId('resolved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolved_notes')->nullable();

            $table->timestamp('created_at')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->index('status');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
