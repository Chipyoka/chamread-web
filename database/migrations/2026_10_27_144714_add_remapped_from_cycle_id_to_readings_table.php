<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit column for readings whose billing_cycle_id was corrected server-side
 * because the mobile client uploaded them with a stale (previous) cycle id.
 *
 * NULL            => the client's cycle id was used as-is.
 * <previous id>   => the client sent this id; the reading was filed under the
 *                    current cycle instead (see ReadingController::reconcileStaleCycle).
 *
 * Audit query:  Reading::whereNotNull('remapped_from_cycle_id')
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('readings', function (Blueprint $table) {
            $table->unsignedBigInteger('remapped_from_cycle_id')
                ->nullable()
                ->after('billing_cycle_id')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('readings', function (Blueprint $table) {
            $table->dropIndex(['remapped_from_cycle_id']);
            $table->dropColumn('remapped_from_cycle_id');
        });
    }
};