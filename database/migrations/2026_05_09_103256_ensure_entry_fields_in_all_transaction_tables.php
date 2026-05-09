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
            'purchases',
            'sales',
            'vouchers',
            'receipts_vouchers',
            'payment_vouchers',
            'expense_vouchers',
            'income_vouchers',
            'adjustment_vouchers',
            'journal_vouchers',
            'stock_adjustments',
            'stock_holds',
            'stock_releases',
            'stock_transfers',
            'stock_wastages',
            'customer_claims',
            'customer_claim_releases',
            'claim_acceptances',
            'claim_item_receipts',
            'purchase_returns',
            'sale_returns',
            'productbookings',
            'stock_hold_vouchers',
            'stock_release_vouchers',
            'inward_gatepasses',
            'claim_credit_notes',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    // Handle entry_date
                    if (!Schema::hasColumn($tableName, 'entry_date')) {
                        $table->date('entry_date')->nullable();
                    } else {
                        // Ensure it's a date type in case it was created as something else
                        $table->date('entry_date')->nullable()->change();
                    }

                    // Handle entry_time
                    if (!Schema::hasColumn($tableName, 'entry_time')) {
                        $table->time('entry_time')->nullable();
                    } else {
                        // Ensure it's a time type (Fixes the "Incorrect date value" issue)
                        $table->time('entry_time')->nullable()->change();
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
            'purchases',
            'sales',
            'vouchers',
            'receipts_vouchers',
            'payment_vouchers',
            'expense_vouchers',
            'income_vouchers',
            'adjustment_vouchers',
            'journal_vouchers',
            'stock_adjustments',
            'stock_holds',
            'stock_releases',
            'stock_transfers',
            'stock_wastages',
            'customer_claims',
            'customer_claim_releases',
            'claim_acceptances',
            'claim_item_receipts',
            'purchase_returns',
            'sale_returns',
            'productbookings',
            'stock_hold_vouchers',
            'stock_release_vouchers',
            'inward_gatepasses',
            'claim_credit_notes',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (Schema::hasColumn($tableName, 'entry_date')) {
                        $table->dropColumn('entry_date');
                    }
                    if (Schema::hasColumn($tableName, 'entry_time')) {
                        $table->dropColumn('entry_time');
                    }
                });
            }
        }
    }
};
