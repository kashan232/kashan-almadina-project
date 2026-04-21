<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_release_vouchers', function (Blueprint $row) {
            $row->string('release_type')->default('stock')->after('voucher_no');
            $row->unsignedBigInteger('claim_id')->nullable()->after('hold_voucher_id');
        });
    }

    public function down(): void
    {
        Schema::table('stock_release_vouchers', function (Blueprint $row) {
            $row->dropColumn(['release_type', 'claim_id']);
        });
    }
};
