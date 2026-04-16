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
        Schema::table('warehouses', function (Blueprint $table) {
            $table->text('user_group_ids')->nullable()->after('warehouse_name');
            $table->unsignedBigInteger('created_by')->nullable()->after('user_group_ids');
        });
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropColumn(['user_group_ids', 'created_by']);
        });
    }
};
