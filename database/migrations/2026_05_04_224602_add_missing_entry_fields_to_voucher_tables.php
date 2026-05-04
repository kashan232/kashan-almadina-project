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
            'vouchers'             => ['after' => 'date'],
            'receipts_vouchers'    => ['after' => 'entry_date'],
            'payment_vouchers'     => ['after' => 'entry_date'],
            'inward_gatepasses'    => ['after' => 'gatepass_date'],
            'stock_adjustments'    => ['after' => 'date'],
            'stock_holds'          => ['after' => 'entry_date'],
            'stock_releases'       => ['after' => 'release_no'],
            'customer_claims'      => ['after' => 'claim_no'],
            'claim_acceptances'    => ['after' => 'acceptance_no'],
            'claim_item_receipts'  => ['after' => 'receipt_no'],
            'sale_returns'         => ['after' => 'current_date'],
            'purchase_returns'     => ['after' => 'current_date'],
        ];

        foreach ($tables as $tableName => $config) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName, $config) {
                    if (!Schema::hasColumn($tableName, 'entry_date')) {
                        $table->date('entry_date')->nullable()->after($config['after']);
                    }
                    if (!Schema::hasColumn($tableName, 'entry_time')) {
                        // Use entry_date as anchor if it exists or was just added
                        $table->time('entry_time')->nullable()->after('entry_date');
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
            'vouchers', 'receipts_vouchers', 'payment_vouchers', 'inward_gatepasses',
            'stock_adjustments', 'stock_holds', 'stock_releases', 'customer_claims',
            'claim_acceptances', 'claim_item_receipts', 'sale_returns', 'purchase_returns'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $columns = [];
                    if (Schema::hasColumn($tableName, 'entry_date')) $columns[] = 'entry_date';
                    if (Schema::hasColumn($tableName, 'entry_time')) $columns[] = 'entry_time';
                    if (!empty($columns)) {
                        $table->dropColumn($columns);
                    }
                });
            }
        }
    }
};
