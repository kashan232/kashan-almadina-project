<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claim_item_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_no')->unique();
            $table->date('date');
            $table->string('party_type')->nullable(); 
            $table->unsignedBigInteger('party_id')->nullable();
            $table->unsignedBigInteger('from_warehouse_id')->nullable(); // Company Claim Stock (-)
            $table->unsignedBigInteger('to_warehouse_id')->nullable();   // Selected Warehouse (+)
            $table->text('remarks')->nullable();
            $table->string('status')->default('Draft');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_item_receipts');
    }
};
