<?php

namespace App\Console\Commands;

use App\Models\Account;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DedupeAccounts extends Command
{
    protected $signature = 'accounts:dedupe
        {--force : Actually merge groups and delete unreferenced duplicate accounts}';

    protected $description = 'Find duplicate chart-of-accounts (same head + title), merge their user groups into the original, and delete unreferenced copies created by the old group-assignment bug.';

    /**
     * Tables/columns that reference an account by its id.
     * type: exact = integer FK column; json = id embedded in a JSON/text list.
     */
    private array $referenceMap = [
        ['table' => 'purchase_account_allocaations', 'column' => 'account_id', 'type' => 'exact'],
        ['table' => 'stock_wastages',                'column' => 'account_id', 'type' => 'exact'],
        ['table' => 'purchases',                     'column' => 'wht_account_id', 'type' => 'exact'],
        ['table' => 'purchase_returns',              'column' => 'wht_account_id', 'type' => 'exact'],
        ['table' => 'claim_credit_notes',            'column' => 'wht_account_id', 'type' => 'exact'],
        ['table' => 'sales',                         'column' => 'discount_account_id', 'type' => 'exact'],
        ['table' => 'productbookings',               'column' => 'discount_account_id', 'type' => 'exact'],
        ['table' => 'bookings',                      'column' => 'discount_account_id', 'type' => 'exact'],
        ['table' => 'sale_returns',                  'column' => 'discount_account_id', 'type' => 'exact'],
        ['table' => 'income_vouchers',               'column' => 'account_id', 'type' => 'json'],
        ['table' => 'journal_vouchers',              'column' => 'account_id', 'type' => 'json'],
        ['table' => 'adjustment_vouchers',           'column' => 'account_id', 'type' => 'json'],
        ['table' => 'receipts_vouchers',             'column' => 'row_account_id', 'type' => 'json'],
        ['table' => 'payment_vouchers',              'column' => 'row_account_id', 'type' => 'json'],
        ['table' => 'expense_vouchers',              'column' => 'row_account_id', 'type' => 'json'],
        ['table' => 'receipts_vouchers',             'column' => 'discount_account_id', 'type' => 'json'],
        ['table' => 'payment_vouchers',              'column' => 'discount_account_id', 'type' => 'json'],
        ['table' => 'stock_hold_vouchers',           'column' => 'hold_account_id', 'type' => 'exact'],
        ['table' => 'stock_hold_vouchers',           'column' => 'warehouse_account_id', 'type' => 'exact'],
        ['table' => 'stock_release_vouchers',        'column' => 'hold_account_id', 'type' => 'exact'],
        ['table' => 'stock_release_vouchers',        'column' => 'warehouse_account_id', 'type' => 'exact'],
    ];

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        $this->info($force
            ? 'Running duplicate cleanup (FORCE mode: groups will be merged, unreferenced copies deleted).'
            : 'Running duplicate scan (DRY-RUN: nothing will be changed). Add --force to apply.');
        $this->newLine();

        // All accounts, ignoring active/group scopes.
        $accounts = Account::withoutGlobalScopes()
            ->orderBy('id')
            ->get(['id', 'head_id', 'title', 'account_code', 'user_group_ids', 'status']);

        // Group by head_id + normalized title.
        $groups = $accounts->groupBy(function ($acc) {
            return $acc->head_id . '||' . mb_strtolower(trim((string) $acc->title));
        })->filter(fn ($group) => $group->count() > 1);

        if ($groups->isEmpty()) {
            $this->info('No duplicate accounts found. Nothing to clean.');
            return self::SUCCESS;
        }

        $this->warn($groups->count() . ' duplicate title group(s) found.');
        $this->newLine();

        $deleted = 0;
        $skippedReferenced = 0;
        $mergedGroups = 0;

        foreach ($groups as $key => $group) {
            // Primary = lowest id (the original account).
            $primary = $group->sortBy('id')->first();
            $duplicates = $group->where('id', '!=', $primary->id);

            $this->line("<fg=cyan>Title:</> {$primary->title}  (Head #{$primary->head_id})");
            $this->line("  Keep : #{$primary->id} [code {$primary->account_code}]");

            // Collect merged group ids from primary + all duplicates.
            $mergedIds = collect($this->normalizeGroupIds($primary->user_group_ids));

            foreach ($duplicates as $dup) {
                $refs = $this->findReferences((int) $dup->id);
                $mergedIds = $mergedIds->merge($this->normalizeGroupIds($dup->user_group_ids));

                if (empty($refs)) {
                    $this->line("  Dup  : #{$dup->id} [code {$dup->account_code}] <fg=green>-> deletable (no references)</>");
                    if ($force) {
                        Account::withoutGlobalScopes()->whereKey($dup->id)->delete();
                        $deleted++;
                    }
                } else {
                    $skippedReferenced++;
                    $this->line("  Dup  : #{$dup->id} [code {$dup->account_code}] <fg=yellow>-> KEPT (used in: " . implode(', ', $refs) . ")</>");
                }
            }

            // Merge all discovered group ids into the primary.
            $finalIds = $mergedIds->filter()->unique()->values()->all();
            $currentIds = $this->normalizeGroupIds($primary->user_group_ids);

            if ($this->groupsDiffer($currentIds, $finalIds)) {
                $this->line('  Groups merged into primary: [' . implode(', ', $finalIds) . ']');
                if ($force) {
                    Account::withoutGlobalScopes()->whereKey($primary->id)->update([
                        'user_group_ids' => !empty($finalIds) ? json_encode($finalIds) : null,
                    ]);
                    $mergedGroups++;
                }
            }

            $this->newLine();
        }

        $this->info('Summary:');
        $this->line("  Duplicate groups        : {$groups->count()}");
        if ($force) {
            $this->line("  Deleted duplicates      : {$deleted}");
            $this->line("  Primaries group-merged  : {$mergedGroups}");
            $this->line("  Kept (referenced)       : {$skippedReferenced}");
            $this->newLine();
            $this->info('Cleanup complete.');
        } else {
            $this->line("  Referenced (would keep) : {$skippedReferenced}");
            $this->newLine();
            $this->warn('This was a dry-run. Re-run with --force to apply changes.');
        }

        return self::SUCCESS;
    }

    private function normalizeGroupIds($value): array
    {
        if (is_array($value)) {
            $ids = $value;
        } elseif (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            $ids = is_array($decoded) ? $decoded : [];
        } else {
            $ids = [];
        }

        return collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function groupsDiffer(array $a, array $b): bool
    {
        sort($a);
        sort($b);
        return $a !== $b;
    }

    /**
     * Return a list of "table.column" locations that reference the given account id.
     */
    private function findReferences(int $accountId): array
    {
        $hits = [];

        foreach ($this->referenceMap as $ref) {
            $table = $ref['table'];
            $column = $ref['column'];

            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                continue;
            }

            try {
                if ($ref['type'] === 'exact') {
                    $exists = DB::table($table)->where($column, $accountId)->exists();
                } else {
                    $exists = $this->jsonReferenceExists($table, $column, $accountId);
                }
            } catch (\Throwable $e) {
                // If a check fails, err on the side of caution: treat as referenced.
                $exists = true;
            }

            if ($exists) {
                $hits[] = $table . '.' . $column;
            }
        }

        return $hits;
    }

    /**
     * Precise-ish token match for an id embedded in a JSON/CSV list column.
     */
    private function jsonReferenceExists(string $table, string $column, int $id): bool
    {
        $patterns = [
            '%"' . $id . '"%',   // "50001"
            '%[' . $id . ']%',   // [50001]
            '%[' . $id . ',%',   // [50001,...
            '%,' . $id . ',%',   // ...,50001,...
            '%,' . $id . ']%',   // ...,50001]
            '%: ' . $id . '%',   // "account_id": 50001
            '%:' . $id . ',%',
            '%:' . $id . '}%',
        ];

        $query = DB::table($table)->where(function ($q) use ($column, $patterns, $id) {
            // Plain single-value equality (column may hold just "50001").
            $q->where($column, (string) $id);
            foreach ($patterns as $p) {
                $q->orWhere($column, 'like', $p);
            }
        });

        return $query->exists();
    }
}
