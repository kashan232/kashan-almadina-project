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
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('sales_rate', 12, 2)->default(0)->after('retail_price');
        });

        Schema::table('product_booking_items', function (Blueprint $table) {
            $table->decimal('sales_rate', 12, 2)->default(0)->after('retail_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('sales_rate');
        });

        Schema::table('product_booking_items', function (Blueprint $table) {
            $table->dropColumn('sales_rate');
        });
    }
};
