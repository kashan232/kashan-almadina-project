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
        if (Schema::hasTable('sales') && !Schema::hasColumn('sales', 'order_discount_mode')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->string('order_discount_mode', 20)->default('percent')->nullable()->after('discount_amount');
            });
        }

        if (Schema::hasTable('productbookings') && !Schema::hasColumn('productbookings', 'order_discount_mode')) {
            Schema::table('productbookings', function (Blueprint $table) {
                $table->string('order_discount_mode', 20)->default('percent')->nullable()->after('discount_amount');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sales') && Schema::hasColumn('sales', 'order_discount_mode')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropColumn('order_discount_mode');
            });
        }

        if (Schema::hasTable('productbookings') && Schema::hasColumn('productbookings', 'order_discount_mode')) {
            Schema::table('productbookings', function (Blueprint $table) {
                $table->dropColumn('order_discount_mode');
            });
        }
    }
};
