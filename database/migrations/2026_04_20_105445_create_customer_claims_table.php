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
        Schema::create('customer_claims', function (Blueprint $table) {
            $table->id();
            $table->string('claim_no')->unique();
            $table->date('claim_date');
            $table->string('claim_type'); // item_return, credit_note, claim_hold
            $table->string('party_type'); // customer, vendor, walkin
            $table->unsignedBigInteger('party_id');
            $table->unsignedBigInteger('product_id');
            $table->string('mfg_date')->nullable();
            $table->decimal('sales_price', 15, 2)->default(0);
            $table->string('card_no')->nullable();
            $table->date('bill_date')->nullable();
            $table->unsignedBigInteger('original_warehouse_id')->nullable(); // Deliver From
            $table->unsignedBigInteger('claim_warehouse_id')->nullable(); // Claim WH (To)
            $table->decimal('claim_income', 15, 2)->default(0);
            $table->text('fault_found')->nullable();
            $table->text('remarks')->nullable();
            
            // Replacement Fields
            $table->unsignedBigInteger('replacement_product_id')->nullable();
            $table->decimal('replacement_sales_price', 15, 2)->default(0);
            $table->unsignedBigInteger('replacement_from_warehouse_id')->nullable();
            $table->unsignedBigInteger('replacement_to_warehouse_id')->nullable();

            $table->string('status')->default('Draft'); // Draft, Posted, Cancelled
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_claims');
    }
};
