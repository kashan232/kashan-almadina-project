<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Support\ModuleIdSequence;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'customers' => ModuleIdSequence::CUSTOMER_MAIN_MIN,
            'vendors' => ModuleIdSequence::VENDOR_MIN,
            'products' => ModuleIdSequence::PRODUCT_MIN,
            'account_heads' => ModuleIdSequence::ACCOUNT_HEAD_MIN,
            'accounts' => ModuleIdSequence::SUB_HEAD_MIN,
        ];

        foreach ($tables as $table => $floor) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $maxId = (int) (DB::table($table)->max('id') ?? 0);
            $next = max($floor, $maxId + 1);

            DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = {$next}");
        }
    }

    public function down(): void
    {
        // No rollback — ID floors are business rules.
    }
};
