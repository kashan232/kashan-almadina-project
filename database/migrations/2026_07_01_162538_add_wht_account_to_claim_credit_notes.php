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
        Schema::table('claim_credit_notes', function (Blueprint $table) {
            $table->unsignedBigInteger('wht_account_id')->nullable()->after('wht_percent');
            $table->string('wht_type', 50)->nullable()->default('percent')->after('wht_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('claim_credit_notes', function (Blueprint $table) {
            $table->dropColumn(['wht_account_id', 'wht_type']);
        });
    }
};
