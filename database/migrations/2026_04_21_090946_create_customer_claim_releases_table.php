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
        Schema::create('customer_claim_releases', function (Blueprint $table) {
            $table->id();
            $table->string('release_no')->unique();
            $table->date('release_date');
            $table->unsignedBigInteger('claim_id');
            $table->string('party_type')->nullable();
            $table->unsignedBigInteger('party_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->decimal('release_qty', 15, 2)->default(1);
            $table->string('status')->default('Draft'); // Draft, Posted
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_claim_releases');
    }
};
