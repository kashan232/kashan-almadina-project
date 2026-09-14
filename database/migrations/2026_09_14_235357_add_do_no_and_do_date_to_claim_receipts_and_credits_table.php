<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('claim_item_receipts', function (Blueprint $table) {
            $table->string('do_no')->nullable()->after('voucher_no');
            $table->date('do_date')->nullable()->after('do_no');
        });

        Schema::table('claim_credit_notes', function (Blueprint $table) {
            $table->string('do_no')->nullable()->after('voucher_no');
            $table->date('do_date')->nullable()->after('do_no');
        });
    }

    public function down(): void
    {
        Schema::table('claim_item_receipts', function (Blueprint $table) {
            $table->dropColumn(['do_no', 'do_date']);
        });

        Schema::table('claim_credit_notes', function (Blueprint $table) {
            $table->dropColumn(['do_no', 'do_date']);
        });
    }
};
