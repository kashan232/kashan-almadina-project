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
        if (!Schema::hasColumn('accounts', 'current_balance')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->decimal('current_balance', 15, 2)->default(0)->after('opening_balance');
            });

            \Illuminate\Support\Facades\DB::statement('UPDATE accounts SET current_balance = opening_balance');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('accounts', 'current_balance')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->dropColumn('current_balance');
            });
        }
    }
};
