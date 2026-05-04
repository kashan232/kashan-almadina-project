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
        Schema::table('customer_claim_releases', function (Blueprint $table) {
            $table->date('entry_date')->nullable()->after('release_no');
            $table->time('entry_time')->nullable()->after('entry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_claim_releases', function (Blueprint $table) {
            $table->dropColumn(['entry_date', 'entry_time']);
        });
    }
};
