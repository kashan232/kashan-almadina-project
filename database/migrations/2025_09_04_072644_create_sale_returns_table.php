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
        Schema::create('sale_returns', function (Blueprint $table) {
        $table->id();
            $table->string('invoice_no')->unique();
            $table->string('manual_invoice')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('sub_customer')->nullable();
            $table->string('filer_type')->nullable();
            $table->text('address')->nullable();
            $table->string('tel')->nullable();
            $table->text('remarks')->nullable();
            $table->text('quantity')->nullable();
            $table->decimal('sub_total1', 12, 2)->default(0);
            $table->decimal('sub_total2', 12, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('previous_balance', 12, 2)->default(0);
            $table->decimal('total_balance', 12, 2)->default(0);
            $table->decimal('receipt1', 12, 2)->default(0);
            $table->decimal('receipt2', 12, 2)->default(0);
            $table->decimal('final_balance1', 12, 2)->default(0);
            $table->decimal('final_balance2', 12, 2)->default(0);
            $table->text('weight')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_returns');
    }
};
