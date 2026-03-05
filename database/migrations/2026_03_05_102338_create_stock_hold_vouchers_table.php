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
        Schema::create('stock_hold_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_no')->unique();
            $table->date('date');
            $table->string('party_type')->nullable(); // customer, vendor, walkin
            $table->unsignedBigInteger('party_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->string('hold_type')->nullable(); // hold, claim
            $table->text('remarks')->nullable();
            $table->string('status')->default('Unposted'); // Unposted, Posted
            $table->timestamps();
        });

        Schema::table('stock_holds', function (Blueprint $table) {
            $table->unsignedBigInteger('stock_hold_voucher_id')->nullable()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('stock_holds', function (Blueprint $table) {
            $table->dropColumn('stock_hold_voucher_id');
        });
        Schema::dropIfExists('stock_hold_vouchers');
    }
};
