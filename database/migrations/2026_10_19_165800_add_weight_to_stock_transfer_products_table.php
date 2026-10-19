<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transfer_products', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_transfer_products', 'weight')) {
                $table->decimal('weight', 12, 2)->nullable()->default(0)->after('quantity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_transfer_products', function (Blueprint $table) {
            if (Schema::hasColumn('stock_transfer_products', 'weight')) {
                $table->dropColumn('weight');
            }
        });
    }
};
