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
        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_return_id'); // FK to sales table
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->decimal('stock', 12, 2)->default(0);
            $table->decimal('price_level', 12, 2)->default(0);
            $table->decimal('sales_price', 12, 2)->default(0);
            $table->decimal('sales_qty', 12, 2)->default(0);
            $table->decimal('retail_price', 12, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);

            $table->timestamps();

            // Foreign key
            $table->foreign('sale_return_id')->references('id')->on('sale_returns')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_return_items');
    }
};
