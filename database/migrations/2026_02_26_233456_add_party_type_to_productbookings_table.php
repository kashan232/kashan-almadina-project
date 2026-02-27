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
        Schema::table('productbookings', function (Blueprint $table) {
            $table->string('party_type')->nullable()->after('manual_invoice');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productbookings', function (Blueprint $table) {
            $table->dropColumn('party_type');
        });
    }
};
