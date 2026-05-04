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
        // 1. vouchers
        Schema::table('vouchers', function (Blueprint $table) {
            if (!Schema::hasColumn('vouchers', 'entry_date')) {
                $table->date('entry_date')->nullable()->after('date');
            }
            if (!Schema::hasColumn('vouchers', 'entry_time')) {
                $table->time('entry_time')->nullable()->after('entry_date');
            }
        });

        // 2. receipts_vouchers
        Schema::table('receipts_vouchers', function (Blueprint $table) {
            if (!Schema::hasColumn('receipts_vouchers', 'entry_time')) {
                $table->time('entry_time')->nullable()->after('entry_date');
            }
        });

        // 3. inward_gatepasses
        Schema::table('inward_gatepasses', function (Blueprint $table) {
            if (!Schema::hasColumn('inward_gatepasses', 'entry_date')) {
                $table->date('entry_date')->nullable()->after('gatepass_date');
            }
            if (!Schema::hasColumn('inward_gatepasses', 'entry_time')) {
                $table->time('entry_time')->nullable()->after('entry_date');
            }
        });

        // 4. stock_adjustments
        Schema::table('stock_adjustments', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_adjustments', 'entry_date')) {
                $table->date('entry_date')->nullable()->after('date');
            }
            if (!Schema::hasColumn('stock_adjustments', 'entry_time')) {
                $table->time('entry_time')->nullable()->after('entry_date');
            }
        });

        // 5. stock_holds
        Schema::table('stock_holds', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_holds', 'entry_time')) {
                $table->time('entry_time')->nullable()->after('entry_date');
            }
        });

        // 6. stock_releases
        Schema::table('stock_releases', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_releases', 'entry_date')) {
                $table->date('entry_date')->nullable()->after('release_no');
            }
            if (!Schema::hasColumn('stock_releases', 'entry_time')) {
                $table->time('entry_time')->nullable()->after('entry_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) { $table->dropColumn(['entry_date', 'entry_time']); });
        Schema::table('receipts_vouchers', function (Blueprint $table) { $table->dropColumn(['entry_time']); });
        Schema::table('inward_gatepasses', function (Blueprint $table) { $table->dropColumn(['entry_date', 'entry_time']); });
        Schema::table('stock_adjustments', function (Blueprint $table) { $table->dropColumn(['entry_date', 'entry_time']); });
        Schema::table('stock_holds', function (Blueprint $table) { $table->dropColumn(['entry_time']); });
        Schema::table('stock_releases', function (Blueprint $table) { $table->dropColumn(['entry_date', 'entry_time']); });
    }
};
