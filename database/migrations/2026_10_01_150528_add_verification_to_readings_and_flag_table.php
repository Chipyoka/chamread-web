<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('readings', function (Blueprint $table) {
            // pending = saved, not yet checked | auto = passed all rules
            // flagged = held for a supervisor  | manual = supervisor verified
            // failed  = the engine errored while checking (retry with readings:verify)
            $table->enum('verification_status', ['pending', 'auto', 'flagged', 'manual', 'failed'])
                ->default('pending')
                ->after('consumption');

            $table->foreignId('verified_by')
                ->nullable()
                ->after('verification_status')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('verified_at')->nullable()->after('verified_by');

            // Supervisor dashboard: current cycle + flagged
            $table->index(['billing_cycle_id', 'verification_status']);
        });

        Schema::table('flags', function (Blueprint $table) {
            // Only flags with this set to true hold a reading for verification
            $table->boolean('requires_verification')->default(false)->after('active');
        });
    }

    public function down(): void
    {
        Schema::table('readings', function (Blueprint $table) {
            $table->dropIndex(['billing_cycle_id', 'verification_status']);
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn(['verification_status', 'verified_at']);
        });

        Schema::table('flags', function (Blueprint $table) {
            $table->dropColumn('requires_verification');
        });
    }
};