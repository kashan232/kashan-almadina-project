<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_returns', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_returns', 'discount_head')) {
                $table->string('discount_head')->nullable()->after('discount_amount');
            }
            if (!Schema::hasColumn('sale_returns', 'discount_account_id')) {
                $table->unsignedBigInteger('discount_account_id')->nullable()->after('discount_head');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_returns', function (Blueprint $table) {
            if (Schema::hasColumn('sale_returns', 'discount_account_id')) {
                $table->dropColumn('discount_account_id');
            }
            if (Schema::hasColumn('sale_returns', 'discount_head')) {
                $table->dropColumn('discount_head');
            }
        });
    }
};
