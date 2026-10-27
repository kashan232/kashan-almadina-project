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

class PurgeZaidData extends Command
{
    protected $signature = 'cleanup:purge-zaid 
                            {--execute : Actually perform the deletion and resync. Without this flag, only a dry-run preview is shown}
                            {--user-id= : Custom User ID if different}';

    protected $description = 'Safely reverse, purge and resync all transactions created by user ZAID LODHI';

    public function handle()
    {
        $this->info("========================================================");
        $this->info("       PURGE & REVERSAL TOOL FOR ZAID LODHI");
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

        $this->info("Target User Identified:");
        $this->line(" - ID:    {$user->id}");
        $this->line(" - Name:  {$user->name}");
        $this->line(" - Email: {$user->email}");
        $this->line(" - Mode:  " . ($isLive ? "<fg=red;options=bold>*** LIVE EXECUTION (CHANGES WILL BE COMMITTED) ***</>" : "<fg=yellow;options=bold>DRY RUN (Preview only, no data will be changed)</>"));
        $this->newLine();

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
                ['Sales (sales table)', count($saleIds), 'Delete'],
                ['Sale Items (sale_items table)', $saleItemsCount, 'Delete & Auto-restore stock'],
                ['Receipts Vouchers (receipts_vouchers)', count($allReceiptIds), 'Delete (includes auto-receipts from sales)'],
                ['Purchases (purchases table)', count($purchaseIds), 'Delete'],
                ['Purchase Items (purchase_items table)', $purchaseItemsCount, 'Delete & Auto-restore stock'],
                ['Customer Claims (customer_claims table)', count($claimIds), 'Delete & Reverse stock'],
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
            $this->info("To perform actual deletion on live server, run:");
            $this->comment("  php artisan cleanup:purge-zaid --execute");
            return 0;
        }

        $this->warn("Starting live deletion within database transaction...");

        DB::beginTransaction();

        try {
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
            $this->info("Transaction committed successfully. All Zaid Lodhi entries deleted.");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Failed to delete records: " . $e->getMessage());
            return 1;
        }

        // Re-sync customer ledgers
        $this->info("Re-syncing Customer Ledgers from remaining transactions...");
        $this->resyncCustomerLedgers();
        $this->info("Customer Ledgers resynchronized successfully!");

        $this->newLine();
        $this->info("<bg=green;fg=white;options=bold> ALL TASKS COMPLETED SUCCESSFULLY! SYSTEM IS CLEAN. </>");
        return 0;
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
