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
        Schema::table('sales', function (Blueprint $table) {
            $table->string('discount_head')->nullable()->after('discount_amount');
            $table->unsignedBigInteger('discount_account_id')->nullable()->after('discount_head');
        });
        
        Schema::table('productbookings', function (Blueprint $table) {
            $table->string('discount_head')->nullable()->after('discount_amount');
            $table->unsignedBigInteger('discount_account_id')->nullable()->after('discount_head');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['discount_head', 'discount_account_id']);
        });
        
        Schema::table('productbookings', function (Blueprint $table) {
            $table->dropColumn(['discount_head', 'discount_account_id']);
        });
    }
};
