<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stock_holds', function (Blueprint $table) {
            $table->id();

            // Basic references
            $table->unsignedBigInteger('sale_id')->nullable();        // optional link to sale
            $table->unsignedBigInteger('invoice_id')->nullable();     // invoice/booking id
            $table->string('party_type')->nullable();                 // 'customer'|'vendor'|'walkin'
            $table->unsignedBigInteger('party_id')->nullable();       // id of customer/vendor

            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('item_id')->nullable();        // sale_item id or product booking item id

            // Quantities & pricing (all nullable)
            $table->decimal('sale_qty', 18, 3)->nullable();
            $table->decimal('hold_qty', 18, 3)->nullable();

            // metadata / texts
            $table->text('remarks')->nullable();
            $table->json('meta')->nullable(); // any extra JSON data

            // status: 0 = hold, 1 = claimed/converted (nullable default 0)
            $table->tinyInteger('status')->nullable()->default(0);

            $table->date('entry_date')->nullable();

            $table->timestamps();

            // Indexes for faster lookups
            $table->index(['invoice_id']);
            $table->index(['product_id']);
            $table->index(['party_id', 'party_type']);
            $table->index(['status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_holds');
    }
};
