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
        Schema::table('sale_returns', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_returns', 'sale_id')) {
                $table->unsignedBigInteger('sale_id')->nullable()->after('invoice_no');
            }
            if (!Schema::hasColumn('sale_returns', 'current_date')) {
                $table->date('current_date')->nullable()->after('sale_id');
            }
            if (!Schema::hasColumn('sale_returns', 'party_type')) {
                $table->string('party_type')->nullable()->after('manual_invoice');
            }
            if (!Schema::hasColumn('sale_returns', 'status')) {
                $table->string('status')->default('Unposted')->after('weight');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_returns', function (Blueprint $table) {
            $table->dropColumn(['sale_id', 'current_date', 'party_type', 'status']);
        });
    }
};
