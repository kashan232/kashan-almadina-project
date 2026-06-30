<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('purchase_returns', 'wht_account_id')) {
            Schema::table('purchase_returns', function (Blueprint $table) {
                $table->unsignedBigInteger('wht_account_id')->nullable()->after('wht_type');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('purchase_returns', 'wht_account_id')) {
            Schema::table('purchase_returns', function (Blueprint $table) {
                $table->dropColumn('wht_account_id');
            });
        }
    }
};
