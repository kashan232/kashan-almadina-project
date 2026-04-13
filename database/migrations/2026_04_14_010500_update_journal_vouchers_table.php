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
        Schema::table('journal_vouchers', function (Blueprint $table) {
            // Change header party fields to text to support JSON arrays (row-wise)
            $table->text('party_type')->nullable()->change();
            $table->text('party_id')->nullable()->change();
            
            // Add DR/CR column for rows
            $table->text('dr_cr')->after('account_id')->nullable(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_vouchers', function (Blueprint $table) {
            $table->string('party_type')->nullable()->change();
            $table->string('party_id')->nullable()->change();
            $table->dropColumn('dr_cr');
        });
    }
};
