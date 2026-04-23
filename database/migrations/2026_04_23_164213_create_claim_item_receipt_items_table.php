<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claim_item_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('claim_item_receipt_id');
            $table->unsignedBigInteger('product_id');
            $table->string('btr_no')->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->string('status')->default('Draft');
            $table->timestamps();

            $table->foreign('claim_item_receipt_id', 'cir_id_foreign')->references('id')->on('claim_item_receipts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_item_receipt_items');
    }
};
