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
        Schema::table('adjustment_vouchers', function (Blueprint $table) {
            $table->text('qty')->nullable()->after('reference_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('adjustment_vouchers', function (Blueprint $table) {
            $table->dropColumn('qty');
        });
    }
};
