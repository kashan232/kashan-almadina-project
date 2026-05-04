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
            'sale_returns',
            'purchases',
            'purchase_returns',
            'stock_transfers',
            'stock_wastages',
            'expense_vouchers',
            'payment_vouchers',
            'income_vouchers',
            'journal_vouchers',
            'adjustment_vouchers',
            'claim_credit_notes',
            'claim_acceptances',
            'claim_item_receipts',
            'stock_hold_vouchers',
            'stock_release_vouchers'
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'entry_date')) {
                    $table->date('entry_date')->nullable();
                }
                if (!Schema::hasColumn($tableName, 'entry_time')) {
                    $table->time('entry_time')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'sale_returns',
            'purchases',
            'purchase_returns',
            'stock_transfers',
            'stock_wastages',
            'expense_vouchers',
            'payment_vouchers',
            'income_vouchers',
            'journal_vouchers',
            'adjustment_vouchers',
            'claim_credit_notes',
            'claim_acceptances',
            'claim_item_receipts',
            'stock_hold_vouchers',
            'stock_release_vouchers'
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['entry_date', 'entry_time']);
            });
        }
    }
};
