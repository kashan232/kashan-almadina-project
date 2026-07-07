<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['stock_hold_vouchers', 'stock_release_vouchers'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('hold_account_head_id')->nullable()->after('remarks');
                $table->unsignedBigInteger('hold_account_id')->nullable()->after('hold_account_head_id');
                $table->unsignedBigInteger('warehouse_account_head_id')->nullable()->after('hold_account_id');
                $table->unsignedBigInteger('warehouse_account_id')->nullable()->after('warehouse_account_head_id');
            });
        }
    }

    public function down(): void
    {
        foreach (['stock_hold_vouchers', 'stock_release_vouchers'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn([
                    'hold_account_head_id',
                    'hold_account_id',
                    'warehouse_account_head_id',
                    'warehouse_account_id',
                ]);
            });
        }
    }
};
