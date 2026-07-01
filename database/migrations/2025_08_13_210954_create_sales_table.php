<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Main sales table
        Schema::create('sales', function (Blueprint $table) {
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
            $table->decimal('discount_percent', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('previous_balance', 12, 2)->default(0);
            $table->decimal('total_balance', 12, 2)->default(0);
            $table->decimal('receipt1', 12, 2)->default(0);
            $table->decimal('receipt2', 12, 2)->default(0);
            $table->decimal('final_balance1', 12, 2)->default(0);
            $table->decimal('final_balance2', 12, 2)->default(0);
            $table->text('weight')->nullable();

            $table->timestamps();
        });

        // Sales items table
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id'); // FK to sales table
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->decimal('stock', 12, 2)->default(0);
            $table->decimal('price_level', 12, 2)->default(0);
            $table->decimal('sales_price', 12, 2)->default(0);
            $table->decimal('sales_qty', 12, 2)->default(0);
            $table->decimal('retail_price', 12, 2)->default(0);
            $table->decimal('discount_percent', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);

            $table->timestamps();

            // Foreign key
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
