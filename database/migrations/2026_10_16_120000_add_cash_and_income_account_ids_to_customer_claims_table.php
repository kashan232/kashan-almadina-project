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
        Schema::table('customer_claims', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_claims', 'cash_account_id')) {
                $table->unsignedBigInteger('cash_account_id')->nullable()->after('claim_income');
            }
            if (!Schema::hasColumn('customer_claims', 'income_account_id')) {
                $table->unsignedBigInteger('income_account_id')->nullable()->after('cash_account_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_claims', function (Blueprint $table) {
            if (Schema::hasColumn('customer_claims', 'cash_account_id')) {
                $table->dropColumn('cash_account_id');
            }
            if (Schema::hasColumn('customer_claims', 'income_account_id')) {
                $table->dropColumn('income_account_id');
            }
        });
    }
};
