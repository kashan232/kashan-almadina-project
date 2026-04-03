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
        Schema::table('productbookings', function (Blueprint $table) {
            $table->boolean('is_sale_order')->default(0)->after('invoice_no');
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('is_sale_order')->default(0)->after('invoice_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productbookings', function (Blueprint $table) {
            $table->dropColumn('is_sale_order');
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('is_sale_order');
        });
    }
};
