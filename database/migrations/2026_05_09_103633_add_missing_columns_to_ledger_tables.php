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
        $tables = ['customer_ledgers', 'sub_customer_ledgers'];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'date')) {
                        $table->date('date')->nullable()->after('admin_or_user_id');
                    }
                    if (!Schema::hasColumn($tableName, 'description')) {
                        $table->text('description')->nullable()->after('date');
                    }
                    if (!Schema::hasColumn($tableName, 'opening_balance')) {
                        $table->decimal('opening_balance', 12, 2)->default(0)->after('description');
                    }
                    if (!Schema::hasColumn($tableName, 'debit')) {
                        $table->decimal('debit', 12, 2)->default(0)->after('opening_balance');
                    }
                    if (!Schema::hasColumn($tableName, 'credit')) {
                        $table->decimal('credit', 12, 2)->default(0)->after('debit');
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
        $tables = ['customer_ledgers', 'sub_customer_ledgers'];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $columns = [];
                    if (Schema::hasColumn($tableName, 'date')) $columns[] = 'date';
                    if (Schema::hasColumn($tableName, 'description')) $columns[] = 'description';
                    if (Schema::hasColumn($tableName, 'opening_balance')) $columns[] = 'opening_balance';
                    if (Schema::hasColumn($tableName, 'debit')) $columns[] = 'debit';
                    if (Schema::hasColumn($tableName, 'credit')) $columns[] = 'credit';
                    
                    if (!empty($columns)) {
                        $table->dropColumn($columns);
                    }
                });
            }
        }
    }
};
