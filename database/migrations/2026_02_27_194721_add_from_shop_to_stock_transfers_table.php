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
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->boolean('from_shop')->default(0)->after('from_warehouse_id');
            $table->unsignedBigInteger('from_warehouse_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropColumn('from_shop');
            $table->unsignedBigInteger('from_warehouse_id')->nullable(false)->change();
        });
    }
};
