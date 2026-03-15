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
        Schema::table('productbookings', function (Blueprint $table) {
            $table->text('receipt_heads')->nullable();
            $table->text('receipt_accounts')->nullable();
            $table->text('receipt_narrations')->nullable();
            $table->text('receipt_amounts_json')->nullable();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->text('receipt_heads')->nullable();
            $table->text('receipt_accounts')->nullable();
            $table->text('receipt_narrations')->nullable();
            $table->text('receipt_amounts_json')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productbookings', function (Blueprint $table) {
            $table->dropColumn(['receipt_heads', 'receipt_accounts', 'receipt_narrations', 'receipt_amounts_json']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['receipt_heads', 'receipt_accounts', 'receipt_narrations', 'receipt_amounts_json']);
        });
    }
};
