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
        Schema::create('stock_wastages', function (Blueprint $table) {
            $table->id();
            $table->string('gwn_id')->nullable(); 
            $table->date('date');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('account_head_id')->nullable()->constrained('account_heads');
            $table->foreignId('account_id')->nullable()->constrained('accounts');
            $table->string('ref_no')->nullable();
            $table->text('remarks')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('status')->default('Unposted'); // Unposted, Posted
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('stock_wastage_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_wastage_id')->constrained('stock_wastages')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('qty', 10, 2)->default(0);
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_wastages');
    }
};
