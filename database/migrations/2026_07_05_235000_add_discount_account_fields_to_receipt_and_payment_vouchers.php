<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['receipts_vouchers', 'payment_vouchers'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'discount_head')) {
                    $table->text('discount_head')->nullable()->after('discount_value');
                }
                if (!Schema::hasColumn($tableName, 'discount_account_id')) {
                    $table->text('discount_account_id')->nullable()->after('discount_head');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['receipts_vouchers', 'payment_vouchers'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'discount_account_id')) {
                    $table->dropColumn('discount_account_id');
                }
                if (Schema::hasColumn($tableName, 'discount_head')) {
                    $table->dropColumn('discount_head');
                }
            });
        }
    }
};
