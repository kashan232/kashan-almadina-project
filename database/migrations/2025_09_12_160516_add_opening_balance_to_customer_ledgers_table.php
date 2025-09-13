<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOpeningBalanceToCustomerLedgersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customer_ledgers', function (Blueprint $table) {
            $table->decimal('opening_balance', 15, 2)->nullable()->after('previous_balance'); // Add the opening_balance column
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customer_ledgers', function (Blueprint $table) {
            $table->dropColumn('opening_balance'); // Drop the opening_balance column if the migration is rolled back
        });
    }
}
