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
      
            Schema::create('productbookings', function (Blueprint $table) {
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

            $table->timestamps();
        });

        // Sales items table
        Schema::create('product_booking_items', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('booking_id'); // FK to bookings table
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
    $table->foreign('booking_id')->references('id')->on('productbookings')->onDelete('cascade');
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productbookings');
    }
};
