<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claim_credit_note_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('claim_credit_note_id');
            $table->unsignedBigInteger('product_id');
            $table->string('btr_no')->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('retail_price', 15, 2)->default(0);
            $table->decimal('discount_percent', 10, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0); // qty * price
            $table->decimal('line_total', 15, 2)->default(0); // amount - discount
            $table->string('status')->default('Draft');
            $table->timestamps();

            $table->foreign('claim_credit_note_id', 'ccni_id_foreign')->references('id')->on('claim_credit_notes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_credit_note_items');
    }
};
