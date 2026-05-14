<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exceptions', function (Blueprint $table) {
            $table->id();

            $table->string('type');

            $table->unsignedBigInteger('account_id')->nullable();
            $table->unsignedBigInteger('reading_id')->nullable();
            $table->unsignedBigInteger('billing_cycle_id')->nullable();

            $table->enum('severity', [
                'low',
                'medium',
                'high',
                'critical'
            ]);

            $table->enum('status', [
                'open',
                'investigating',
                'resolved',
                'dismissed'
            ])->default('open');

            $table->string('title');

            $table->text('description')->nullable();

            $table->timestamp('detected_at');

            $table->timestamp('resolved_at')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('type');
            $table->index('status');
            $table->index('account_id');
            $table->index('billing_cycle_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exceptions');
    }
};