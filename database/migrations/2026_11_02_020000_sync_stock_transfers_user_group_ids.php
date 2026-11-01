<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\StockTransfer;
use App\Support\GroupContext;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $transfers = StockTransfer::withoutGlobalScopes()->get();
        foreach ($transfers as $tr) {
            $current = GroupContext::normalizeIds($tr->user_group_ids ?? []);
            $resolved = GroupContext::resolveForModel($tr);
            $combined = GroupContext::normalizeIds(array_merge($current, $resolved));

            sort($current);
            sort($combined);

            if ($current !== $combined) {
                $tr->user_group_ids = $combined;
                $tr->saveQuietly();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
