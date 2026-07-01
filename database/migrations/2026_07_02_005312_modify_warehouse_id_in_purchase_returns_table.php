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
        // Drop the foreign key constraint safely if it exists
        try {
            Schema::table('purchase_returns', function (Blueprint $table) {
                $table->dropForeign('purchase_returns_warehouse_id_foreign');
            });
        } catch (\Exception $e) {
            // Ignore if it doesn't exist
        }

        Schema::table('purchase_returns', function (Blueprint $table) {
            // Make the column nullable
            $table->unsignedBigInteger('warehouse_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_returns', function (Blueprint $table) {
            // Revert back to not null and add foreign key
            $table->unsignedBigInteger('warehouse_id')->nullable(false)->change();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
        });
    }
};
