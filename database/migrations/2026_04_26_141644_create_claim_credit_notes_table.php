<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claim_credit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_no')->unique();
            $table->date('date');
            $table->string('party_type'); // vendor or customer
            $table->unsignedBigInteger('party_id');
            $table->unsignedBigInteger('from_warehouse_id'); // Deduct From (-) Cr
            $table->unsignedBigInteger('to_warehouse_id');   // Add To (+) Dr
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('total_discount', 15, 2)->default(0);
            $table->decimal('wht_percent', 10, 2)->default(0);
            $table->decimal('wht_amount', 15, 2)->default(0);
            $table->decimal('net_total', 15, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->string('status')->default('Draft');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_credit_notes');
    }
};
