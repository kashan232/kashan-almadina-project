<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Http\Controllers\GeneralLedgerController;
use App\Services\StockService;

class PurgeZaidData extends Command
{
    protected $signature = 'cleanup:purge-zaid 
                            {--execute : Actually commit changes. Without this flag, only a dry-run preview is shown}
                            {--restore-stock-only : Only reverse and restore physical stock balances without deleting vouchers or ledgers}
                            {--user-id= : Custom User ID if different}';

    protected $description = 'Safely reverse physical stock, purge transactions, and resync ledgers for ZAID LODHI';

    public function handle()
    {
        $this->info("========================================================");
        $this->info("       PURGE & STOCK REVERSAL TOOL FOR ZAID LODHI");
        $this->info("========================================================");

        $userId = $this->option('user-id');
        $user = null;

        if ($userId) {
            $user = User::find((int) $userId);
        }

        if (!$user) {
            $user = User::where('email', 'zaid@gmail.com')
                ->orWhere('name', 'like', '%ZAID%')
                ->first();
        }

        if (!$user) {
            $this->error("Target user (ZAID LODHI) not found in users table!");
            return 1;
        }

        $userId = $user->id;
        $isLive = $this->option('execute');
        $restoreStockOnly = $this->option('restore-stock-only');

        $this->info("Target User Identified:");
        $this->line(" - ID:    {$user->id}");
        $this->line(" - Name:  {$user->name}");
        $this->line(" - Email: {$user->email}");
        $this->line(" - Mode:  " . ($isLive ? "<fg=red;options=bold>*** LIVE EXECUTION (CHANGES WILL BE COMMITTED) ***</>" : "<fg=yellow;options=bold>DRY RUN (Preview only, no data will be changed)</>"));
        if ($restoreStockOnly) {
            $this->line(" - Scope: <fg=cyan;options=bold>RESTORE STOCK ONLY</>");
        }
        $this->newLine();

        // ----------------------------------------------------
        // Compute Stock Reversals (Sales, Purchases, Returns, Claims)
        // ----------------------------------------------------
        $stockAdjustments = $this->collectStockAdjustments($userId);
        $totalStockUnits = array_sum(array_column($stockAdjustments, 'net_delta'));
        $distinctStockCombinations = count($stockAdjustments);

        $this->info("Stock Impact Analysis:");
        $this->line(" - Distinct (Product, Warehouse) Stock lines to adjust: <fg=cyan>{$distinctStockCombinations}</>");
        $this->line(" - Net Physical Stock to Restore across Warehouses:     <fg=green;options=bold>" . ($totalStockUnits >= 0 ? "+{$totalStockUnits}" : $totalStockUnits) . " units</>");
        $this->newLine();

        if ($restoreStockOnly) {
            if (!$isLive) {
                $this->warn("DRY-RUN COMPLETE: Stock adjustments were NOT applied.");
                $this->info("To apply stock restorations to database, run:");
                $this->comment("  php artisan cleanup:purge-zaid --restore-stock-only --execute");
                return 0;
            }

            $this->warn("Applying physical stock adjustments via StockService...");
            $this->applyStockAdjustments($stockAdjustments);
            $this->info("<bg=green;fg=white;options=bold> SUCCESS: Physical stock has been restored successfully! </>");
            return 0;
        }

        // 1. Sales & Sale Items
        $zaidSales = DB::table('sales')->where('created_by', $userId)->get();
        $saleIds = $zaidSales->pluck('id')->toArray();
        $saleInvoices = $zaidSales->pluck('invoice_no')->filter()->toArray();
        $saleItemsCount = count($saleIds) ? DB::table('sale_items')->whereIn('sale_id', $saleIds)->count() : 0;

        // 2. Auto-generated receipts from Zaid sales (regardless of created_by)
        $autoReceiptIds = [];
        if (!empty($saleInvoices)) {
            $autoReceipts = DB::table('receipts_vouchers')->where('remarks', 'like', 'Auto-generated from Sale%')->get();
            foreach ($autoReceipts as $r) {
                preg_match('/Sale:\s*(\w+)/', $r->remarks, $m);
                if (!empty($m[1]) && in_array($m[1], $saleInvoices)) {
                    $autoReceiptIds[] = $r->id;
                }
            }
        }
        $allReceiptIds = DB::table('receipts_vouchers')
            ->where('created_by', $userId)
            ->orWhereIn('id', $autoReceiptIds)
            ->pluck('id')
            ->unique()
            ->toArray();

        // 3. Purchases & Items
        $purchaseIds = DB::table('purchases')->where('created_by', $userId)->pluck('id')->toArray();
        $purchaseItemsCount = count($purchaseIds) ? DB::table('purchase_items')->whereIn('purchase_id', $purchaseIds)->count() : 0;

        // 4. Customer Claims
        $claimIds = DB::table('customer_claims')->where('created_by', $userId)->pluck('id')->toArray();

        // 5. Sale Returns & Items
        $saleReturnIds = DB::table('sale_returns')->where('created_by', $userId)->pluck('id')->toArray();
        $srItemsCount = (count($saleReturnIds) && Schema::hasTable('sale_return_items')) 
            ? DB::table('sale_return_items')->whereIn('sale_return_id', $saleReturnIds)->count() 
            : 0;

        // 6. Product Bookings & Items
        $bookingIds = DB::table('productbookings')->where('created_by', $userId)->pluck('id')->toArray();
        $bItemsCount = (count($bookingIds) && Schema::hasTable('productbooking_items'))
            ? DB::table('productbooking_items')->whereIn('productbooking_id', $bookingIds)->count()
            : 0;

        // 7. Other Vouchers
        $paymentCount = DB::table('payment_vouchers')->where('created_by', $userId)->count();
        $journalCount = DB::table('journal_vouchers')->where('created_by', $userId)->count();
        $adjustmentCount = DB::table('adjustment_vouchers')->where('created_by', $userId)->count();
        $expenseCount = DB::table('expense_vouchers')->where('created_by', $userId)->count();
        $incomeCount = DB::table('income_vouchers')->where('created_by', $userId)->count();

        // 8. Customer Ledgers
        $custLedgerDirect = DB::table('customer_ledgers')->where('admin_or_user_id', $userId)->count();
        $custLedgerSaleInvs = 0;
        if (!empty($saleInvoices)) {
            $custLedgerSaleInvs = DB::table('customer_ledgers')
                ->where('admin_or_user_id', '!=', $userId)
                ->where(function($q) use ($saleInvoices) {
                    foreach ($saleInvoices as $inv) {
                        $q->orWhere('description', 'like', '%Sale: ' . $inv . '%');
                    }
                })->count();
        }

        // 9. Customers created by Zaid
        $zaidCustomerIds = DB::table('customers')->where('created_by', $userId)->pluck('id')->toArray();
        $zaidCustLedgersCount = count($zaidCustomerIds) ? DB::table('customer_ledgers')->whereIn('customer_id', $zaidCustomerIds)->count() : 0;

        // Summary table
        $this->table(
            ['Module / Table', 'Record Count', 'Action'],
            [
                ['Physical Stock Reversals', $distinctStockCombinations . ' combinations', 'Adjust physical stock (+' . $totalStockUnits . ' net units)'],
                ['Sales (sales table)', count($saleIds), 'Delete'],
                ['Sale Items (sale_items table)', $saleItemsCount, 'Delete'],
                ['Receipts Vouchers (receipts_vouchers)', count($allReceiptIds), 'Delete (includes auto-receipts from sales)'],
                ['Purchases (purchases table)', count($purchaseIds), 'Delete'],
                ['Purchase Items (purchase_items table)', $purchaseItemsCount, 'Delete'],
                ['Customer Claims (customer_claims table)', count($claimIds), 'Delete'],
                ['Sale Returns (sale_returns table)', count($saleReturnIds), 'Delete'],
                ['Sale Return Items (sale_return_items)', $srItemsCount, 'Delete'],
                ['Product Bookings (productbookings table)', count($bookingIds), 'Delete'],
                ['Product Booking Items (productbooking_items)', $bItemsCount, 'Delete'],
                ['Payment Vouchers (payment_vouchers)', $paymentCount, 'Delete'],
                ['Journal Vouchers (journal_vouchers)', $journalCount, 'Delete'],
                ['Adjustment Vouchers (adjustment_vouchers)', $adjustmentCount, 'Delete'],
                ['Expense Vouchers (expense_vouchers)', $expenseCount, 'Delete'],
                ['Income Vouchers (income_vouchers)', $incomeCount, 'Delete'],
                ['Customer Ledgers (customer_ledgers)', ($custLedgerDirect + $custLedgerSaleInvs + $zaidCustLedgersCount), 'Delete & Full Auto-Resync'],
                ['Customers (customers table)', count($zaidCustomerIds), 'Delete customers created by Zaid & their opening ledgers'],
                ['User Account Status', 1, 'Scramble password, remove roles & user groups'],
            ]
        );

        if (!$isLive) {
            $this->warn("DRY-RUN COMPLETE: No changes were made to the database.");
            $this->info("To perform actual deletion and stock reversal, run:");
            $this->comment("  php artisan cleanup:purge-zaid --execute");
            return 0;
        }

        $this->warn("Starting live execution within database transaction...");

        DB::beginTransaction();

        try {
            // First: Apply stock adjustments
            $this->info("Restoring physical stock balances across warehouses...");
            $this->applyStockAdjustments($stockAdjustments);

            // Delete sales & items
            if (count($saleIds)) {
                DB::table('sale_items')->whereIn('sale_id', $saleIds)->delete();
                DB::table('sales')->whereIn('id', $saleIds)->delete();
            }

            // Delete receipts
            if (count($allReceiptIds)) {
                DB::table('receipts_vouchers')->whereIn('id', $allReceiptIds)->delete();
            }

            // Delete purchases & items
            if (count($purchaseIds)) {
                DB::table('purchase_items')->whereIn('purchase_id', $purchaseIds)->delete();
                DB::table('purchases')->whereIn('id', $purchaseIds)->delete();
            }

            // Clean any orphan purchase items
            if (Schema::hasTable('purchase_items')) {
                DB::table('purchase_items')->whereNotIn('purchase_id', DB::table('purchases')->pluck('id'))->delete();
            }

            // Delete customer claims
            if (count($claimIds)) {
                DB::table('customer_claims')->whereIn('id', $claimIds)->delete();
            }

            // Delete sale returns
            if (count($saleReturnIds)) {
                if (Schema::hasTable('sale_return_items')) {
                    DB::table('sale_return_items')->whereIn('sale_return_id', $saleReturnIds)->delete();
                }
                DB::table('sale_returns')->whereIn('id', $saleReturnIds)->delete();
            }

            // Delete product bookings
            if (count($bookingIds)) {
                if (Schema::hasTable('productbooking_items')) {
                    DB::table('productbooking_items')->whereIn('productbooking_id', $bookingIds)->delete();
                }
                DB::table('productbookings')->whereIn('id', $bookingIds)->delete();
            }

            // Delete other vouchers
            DB::table('payment_vouchers')->where('created_by', $userId)->delete();
            DB::table('journal_vouchers')->where('created_by', $userId)->delete();
            DB::table('adjustment_vouchers')->where('created_by', $userId)->delete();
            DB::table('expense_vouchers')->where('created_by', $userId)->delete();
            DB::table('income_vouchers')->where('created_by', $userId)->delete();

            // Delete customer ledgers
            DB::table('customer_ledgers')->where('admin_or_user_id', $userId)->delete();
            if (!empty($saleInvoices)) {
                DB::table('customer_ledgers')->where(function($q) use ($saleInvoices) {
                    foreach ($saleInvoices as $inv) {
                        $q->orWhere('description', 'like', '%Sale: ' . $inv . '%');
                    }
                })->delete();
            }

            // Delete customers created by Zaid and all their associated records
            if (count($zaidCustomerIds)) {
                DB::table('customer_claims')->whereIn('party_id', $zaidCustomerIds)->delete();
                DB::table('customer_ledgers')->whereIn('customer_id', $zaidCustomerIds)->delete();
                if (Schema::hasTable('customer_payments')) {
                    DB::table('customer_payments')->whereIn('customer_id', $zaidCustomerIds)->delete();
                }
                if (Schema::hasTable('outstanding_losses')) {
                    DB::table('outstanding_losses')->whereIn('customer_id', $zaidCustomerIds)->delete();
                }
                DB::table('customers')->whereIn('id', $zaidCustomerIds)->delete();
            }

            // Deactivate Zaid user
            $user->password = bcrypt(Str::random(32));
            $user->name = 'ZAID LODHI [DISABLED]';
            $user->usertype = 'disabled';
            $user->save();

            if (method_exists($user, 'syncRoles')) {
                $user->syncRoles([]);
            }
            DB::table('user_group_assignments')->where('user_id', $userId)->delete();

            DB::commit();
            $this->info("Transaction committed successfully. All Zaid Lodhi entries purged and stock restored.");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Failed during execution: " . $e->getMessage());
            return 1;
        }

        // Re-sync customer ledgers
        $this->info("Re-syncing Customer Ledgers from remaining transactions...");
        $this->resyncCustomerLedgers();
        $this->info("Customer Ledgers resynchronized successfully!");

        $this->newLine();
        $this->info("<bg=green;fg=white;options=bold> ALL TASKS COMPLETED SUCCESSFULLY! SYSTEM AND STOCK ARE RESTORED. </>");
        return 0;
    }

    private function collectStockAdjustments($userId): array
    {
        $netAdjustments = [];

        $addDelta = function($pid, $wid, $delta, $type) use (&$netAdjustments) {
            if ($delta == 0 || $pid <= 0) return;
            $wid = app(StockService::class)->normalizeWarehouseId($wid);
            $key = "{$pid}-{$wid}";
            if (!isset($netAdjustments[$key])) {
                $netAdjustments[$key] = [
                    'product_id' => (int) $pid,
                    'warehouse_id' => $wid,
                    'sales_qty_restored' => 0.0,
                    'purchases_qty_deducted' => 0.0,
                    'returns_qty_deducted' => 0.0,
                    'claims_qty_net' => 0.0,
                    'net_delta' => 0.0,
                ];
            }
            if ($type === 'sale') {
                $netAdjustments[$key]['sales_qty_restored'] += $delta;
            } elseif ($type === 'purchase') {
                $netAdjustments[$key]['purchases_qty_deducted'] += abs($delta);
            } elseif ($type === 'return') {
                $netAdjustments[$key]['returns_qty_deducted'] += abs($delta);
            } elseif ($type === 'claim') {
                $netAdjustments[$key]['claims_qty_net'] += $delta;
            }
            $netAdjustments[$key]['net_delta'] += $delta;
        };

        // 1. Sales
        $saleItems = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.created_by', $userId)
            ->select('sale_items.product_id', 'sale_items.warehouse_id', 'sale_items.sales_qty')
            ->get();
        foreach ($saleItems as $si) {
            $addDelta($si->product_id, $si->warehouse_id, +(float)$si->sales_qty, 'sale');
        }

        // 2. Purchases
        $purchaseItems = DB::table('purchase_items')
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->where('purchases.created_by', $userId)
            ->select('purchase_items.product_id', 'purchases.warehouse_id', 'purchase_items.qty')
            ->get();
        foreach ($purchaseItems as $pi) {
            $addDelta($pi->product_id, $pi->warehouse_id, -(float)$pi->qty, 'purchase');
        }

        // 3. Sale Returns
        if (Schema::hasTable('sale_return_items')) {
            $srItems = DB::table('sale_return_items')
                ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                ->where('sale_returns.created_by', $userId)
                ->select('sale_return_items.product_id', 'sale_return_items.warehouse_id', 'sale_return_items.sales_qty')
                ->get();
            foreach ($srItems as $sri) {
                $addDelta($sri->product_id, $sri->warehouse_id, -(float)$sri->sales_qty, 'return');
            }
        }

        // 4. Customer Claims
        $claims = DB::table('customer_claims')->where('created_by', $userId)->get();
        foreach ($claims as $c) {
            if (!empty($c->claim_warehouse_id)) {
                $addDelta($c->product_id, $c->claim_warehouse_id, -1.0, 'claim');
            }
            if ($c->claim_type === 'item_return' && !empty($c->original_warehouse_id)) {
                $addDelta($c->product_id, $c->original_warehouse_id, +1.0, 'claim');
            } elseif ($c->claim_type === 'credit_note' && !empty($c->replacement_product_id) && !empty($c->replacement_from_warehouse_id)) {
                $addDelta($c->replacement_product_id, $c->replacement_from_warehouse_id, +1.0, 'claim');
            }
        }

        // If no records in database (already purged), fallback to saved verified audit snapshot
        if (empty($netAdjustments)) {
            $snapshotPath = database_path('zaid_stock_reversals.json');
            if (file_exists($snapshotPath)) {
                $loaded = json_decode(file_get_contents($snapshotPath), true);
                if (is_array($loaded)) {
                    foreach ($loaded as $row) {
                        $wid = app(StockService::class)->normalizeWarehouseId($row['warehouse_id'] ?? 0);
                        $pid = (int) ($row['product_id'] ?? 0);
                        $key = "{$pid}-{$wid}";
                        $netAdjustments[$key] = [
                            'product_id' => $pid,
                            'warehouse_id' => $wid,
                            'sales_qty_restored' => (float)($row['sales_qty_restored'] ?? 0),
                            'purchases_qty_deducted' => (float)($row['purchases_qty_deducted'] ?? 0),
                            'returns_qty_deducted' => (float)($row['returns_qty_deducted'] ?? 0),
                            'claims_qty_net' => (float)($row['claims_qty_net'] ?? 0),
                            'net_delta' => (float)($row['net_delta'] ?? 0),
                        ];
                    }
                }
            }
        }

        return array_values($netAdjustments);
    }

    private function applyStockAdjustments(array $adjustments): void
    {
        $stockService = app(StockService::class);
        $bar = $this->output->createProgressBar(count($adjustments));
        $bar->start();

        foreach ($adjustments as $adj) {
            $stockService->adjust(
                (int) $adj['product_id'],
                $adj['warehouse_id'],
                (float) $adj['net_delta'],
                true
            );
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function resyncCustomerLedgers()
    {
        $gl = app(GeneralLedgerController::class);
        $customers = Customer::all();
        $total = $customers->count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($customers as $c) {
            try {
                $trueBal = round((float)$gl->calculateOpeningBalance('customer', $c->id, '2099-12-31'), 2);
                $latestLedger = CustomerLedger::where('customer_id', $c->id)->latest('id')->first();

                if (!$latestLedger) {
                    if ($trueBal != 0.0) {
                        CustomerLedger::create([
                            'customer_id' => $c->id,
                            'admin_or_user_id' => 1,
                            'date' => date('Y-m-d'),
                            'description' => 'Ledger Auto-Sync Baseline',
                            'opening_balance' => (float)($c->opening_balance ?? 0),
                            'previous_balance' => (float)($c->opening_balance ?? 0),
                            'debit' => $trueBal > 0 ? $trueBal : 0,
                            'credit' => $trueBal < 0 ? abs($trueBal) : 0,
                            'closing_balance' => $trueBal,
                        ]);
                    }
                } else {
                    $stored = round((float)$latestLedger->closing_balance, 2);
                    if (abs($trueBal - $stored) > 0.01) {
                        $latestLedger->closing_balance = $trueBal;
                        $latestLedger->save();
                    }
                }
            } catch (\Exception $e) {
                // Ignore single-customer error during batch
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }
}
