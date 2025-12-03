<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('stock_releases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hold_id')->nullable()->constrained('stock_holds')->onDelete('set null');
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('party_type')->nullable();
            $table->unsignedBigInteger('party_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('item_id')->nullable();
            $table->decimal('sale_qty', 16, 4)->nullable();
            $table->decimal('release_qty', 16, 4)->default(0);
            $table->text('remarks')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_releases');
    }
};
