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
        Schema::table('customer_accounts', function (Blueprint $table) {
            // Pending update fields
            $table->string('new_phone')->nullable()->default(null)->after('phone');
            $table->string('new_meter_number')->nullable()->default(null)->after('meter_number');
            $table->text('new_address')->nullable()->default(null)->after('address');

            // Account status type
            $table->string('account_status')
                ->default('metered')
                ->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'new_phone',
                'new_meter_number',
                'new_address',
                'account_status',
            ]);
        });
    }
};