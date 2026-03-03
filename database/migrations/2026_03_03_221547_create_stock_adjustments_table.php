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
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adj_id')->nullable(); // Voucher ID like GWN-001
            $table->date('date');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->text('remarks')->nullable();
            $table->string('status')->default('Unposted');
            $table->timestamps();
        });

        Schema::create('stock_adjustment_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_adjustment_id')->constrained('stock_adjustments')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('qty', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_details');
        Schema::dropIfExists('stock_adjustments');
    }
};
