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
         Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id');
            $table->decimal('purchase_retail_price', 10, 2);
            $table->decimal('purchase_tax_percent', 15, 2);
            $table->decimal('purchase_tax_amount', 15, 2);
            $table->decimal('purchase_discount_percent', 15, 2);
            $table->decimal('purchase_discount_amount', 15, 2);
            $table->decimal('purchase_net_amount', 15, 2);
            $table->decimal('sale_retail_price', 15, 2);
            $table->decimal('sale_tax_percent', 15, 2);
            $table->decimal('sale_tax_amount', 15, 2);
            $table->decimal('sale_wht_percent', 15, 2);
            $table->decimal('sale_discount_percent', 15, 2);
            $table->decimal('sale_discount_amount', 15, 2);
            $table->decimal('sale_net_amount', 15, 2);
            // $table->date('effective_date');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            // $table->decimal('price', 10, 2);
            // $table->decimal('tax_percent', 5, 2);
            // $table->decimal('wht_percent', 5, 2);
            // $table->decimal('discount_percent', 5, 2);
            // $table->date('effective_date');
            // $table->decimal('retail_price', 10, 2)->nullable();
            // $table->decimal('tax_percent', 5, 2)->nullable();
            // $table->decimal('tax_amount', 10, 2)->nullable();
            // $table->decimal('discount_percent', 5, 2)->nullable();
            // $table->decimal('discount_amount', 10, 2)->nullable();
            // $table->decimal('net_amount', 10, 2)->nullable();
            // $table->decimal('wht_percent', 5, 2)->nullable();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
