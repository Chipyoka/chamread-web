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
            $table->decimal('balance', 10, 2)
                ->nullable()
                ->default(0.00)
                ->after('account_status');

            $table->date('checked_at')
                ->nullable()
                ->default(null)
                ->after('balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'balance',
                'checked_at',
            ]);
        });
    }
};