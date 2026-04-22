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
        Schema::table('claim_acceptance_items', function (Blueprint $table) {
            $table->string('btr_no')->nullable()->after('claim_acceptance_id');
        });
    }

    public function down(): void
    {
        Schema::table('claim_acceptance_items', function (Blueprint $table) {
            $table->dropColumn('btr_no');
        });
    }
};
