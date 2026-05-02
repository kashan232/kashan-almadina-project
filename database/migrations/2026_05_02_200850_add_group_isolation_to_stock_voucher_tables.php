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
        $tables = [
            'stock_hold_vouchers',
            'stock_release_vouchers',
            'customer_claim_releases'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) {
                    if (!Schema::hasColumn($table->getTable(), 'created_by')) {
                        $table->unsignedBigInteger('created_by')->nullable()->after('id');
                    }
                    if (!Schema::hasColumn($table->getTable(), 'user_group_ids')) {
                        $table->json('user_group_ids')->nullable()->after('created_by');
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'stock_hold_vouchers',
            'stock_release_vouchers',
            'customer_claim_releases'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn(['created_by', 'user_group_ids']);
                });
            }
        }
    }
};
