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
        Schema::table('claim_acceptances', function (Blueprint $row) {
            $row->unsignedBigInteger('from_warehouse_id')->nullable()->after('date');
            $row->unsignedBigInteger('to_warehouse_id')->nullable()->after('from_warehouse_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('claim_acceptances', function (Blueprint $row) {
            $row->dropColumn(['from_warehouse_id', 'to_warehouse_id']);
        });
    }
};
