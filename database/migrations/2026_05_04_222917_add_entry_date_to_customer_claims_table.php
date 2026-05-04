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
            $table->date('entry_date')->nullable()->after('claim_no');
            $table->time('entry_time')->nullable()->after('entry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_claims', function (Blueprint $table) {
            $table->dropColumn(['entry_date', 'entry_time']);
        });
    }
};
