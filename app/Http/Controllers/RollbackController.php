<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Productbooking;
use App\Models\ProductBookingItem;
use App\Models\Product;
use App\Models\WarehouseStock;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\InwardGatepass;
use App\Models\VendorLedger;
use App\Models\CustomerLedger;
use App\Models\Voucher;
use App\Models\Account;
use App\Models\StockHold;
use App\Models\StockHoldVoucher;
use App\Models\StockRelease;
use App\Models\StockTransfer;
use App\Models\StockWastage;
use App\Models\CustomerClaim;
use App\Models\ClaimAcceptance;
use App\Models\ClaimItemReceipt;
use App\Models\ClaimCreditNote;
use App\Models\ReceiptsVoucher;
use App\Models\PaymentVoucher;
use App\Models\ExpenseVoucher;
use App\Models\IncomeVoucher;
use App\Models\JournalVoucher;
use App\Models\AdjustmentVoucher;

class RollbackController extends Controller
{
    public function index()
    {
        $modules = [
            'purchase' => 'Purchase',
            'purchase_return' => 'Purchase Return',
            'inward_gatepass' => 'Inward Gatepass',
            'sale' => 'Sale / Invoice',
            'sale_return' => 'Sale Return',
            'stock_hold' => 'Stock Hold',
            'stock_release' => 'Stock Release',
            'stock_transfer' => 'Stock Transfer',
            'stock_wastage' => 'Stock Wastage',
            'warehouse_stock' => 'Warehouse Stock (Manual)',
            'customer_claim' => 'Customer Claim',
            'claim_acceptance' => 'Claim Acceptance',
            'claim_receipt' => 'Claim Receipt/Credits',
            'receipt_voucher' => 'Receipt Voucher',
            'payment_voucher' => 'Payment Voucher',
            'expense_voucher' => 'Expense Voucher',
            'income_voucher' => 'Income Voucher',
            'journal_voucher' => 'Journal Voucher',
            'adjustment_voucher' => 'Adjustment Voucher',
        ];

        return view('admin_panel.rollback.index', compact('modules'));
    }

    public function process(Request $request)
    {
        $request->validate([
            'module' => 'required',
            'invoice_no' => 'required',
        ]);

        $module = $request->module;
        // Explode by comma, trim whitespace, and remove empty values
        $invoiceNos = array_filter(array_map('trim', explode(',', $request->invoice_no)));

        if (empty($invoiceNos)) {
            return back()->with('error', 'Please provide at least one valid invoice number.');
        }

        try {
            return DB::transaction(function () use ($module, $invoiceNos) {
                foreach ($invoiceNos as $invoiceNo) {
                    switch ($module) {
                        case 'sale':
                            $this->rollbackSale($invoiceNo);
                            break;
                        case 'purchase':
                            $this->rollbackPurchase($invoiceNo);
                            break;
                        case 'purchase_return':
                            $this->rollbackPurchaseReturn($invoiceNo);
                            break;
                        case 'sale_return':
                            $this->rollbackSaleReturn($invoiceNo);
                            break;
                        case 'inward_gatepass':
                            $this->rollbackInward($invoiceNo);
                            break;
                        case 'stock_hold':
                            $this->rollbackStockHold($invoiceNo);
                            break;
                        case 'stock_release':
                            $this->rollbackStockRelease($invoiceNo);
                            break;
                        case 'stock_transfer':
                            $this->rollbackStockTransfer($invoiceNo);
                            break;
                        case 'stock_wastage':
                            $this->rollbackStockWastage($invoiceNo);
                            break;
                        case 'warehouse_stock':
                            $this->rollbackWarehouseStock($invoiceNo);
                            break;
                        case 'customer_claim':
                            $this->rollbackCustomerClaim($invoiceNo);
                            break;
                        case 'claim_acceptance':
                            $this->rollbackClaimAcceptance($invoiceNo);
                            break;
                        case 'claim_receipt':
                            $this->rollbackClaimReceipt($invoiceNo);
                            break;
                        case 'receipt_voucher':
                            $this->rollbackVoucher(ReceiptsVoucher::class, 'rvid', $invoiceNo, 'Receipt Voucher');
                            break;
                        case 'payment_voucher':
                            $this->rollbackVoucher(PaymentVoucher::class, 'pvid', $invoiceNo, 'Payment Voucher');
                            break;
                        case 'expense_voucher':
                            $this->rollbackVoucher(ExpenseVoucher::class, 'evid', $invoiceNo, 'Expense Voucher');
                            break;
                        case 'income_voucher':
                            $this->rollbackVoucher(IncomeVoucher::class, 'ivid', $invoiceNo, 'Income Voucher');
                            break;
                        case 'journal_voucher':
                            $this->rollbackVoucher(JournalVoucher::class, 'jvid', $invoiceNo, 'Journal Voucher');
                            break;
                        case 'adjustment_voucher':
                            $this->rollbackVoucher(AdjustmentVoucher::class, 'avid', $invoiceNo, 'Adjustment Voucher');
                            break;
                        default:
                            throw new \Exception("Rollback for module '$module' is not yet implemented.");
                    }
                }
                $joined = implode(', ', $invoiceNos);
                return back()->with('success', "Successfully rolled back records: $joined");
            });
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function findRecord($model, $field, $input)
    {
        // 1. Try Exact match
        $record = $model::where($field, $input)->first();
        if ($record) return $record;

        // 2. Try numeric match (handling prefixes like INVSLE- or leading zeros like 001)
        $inputNum = (int)preg_replace('/[^0-9]/', '', $input);
        if ($inputNum > 0) {
            // Search by LIKE to narrow down candidates efficiently
            $candidates = $model::where($field, 'LIKE', '%' . $inputNum . '%')->get();
            foreach ($candidates as $candidate) {
                $dbNum = (int)preg_replace('/[^0-9]/', '', $candidate->$field);
                if ($dbNum === $inputNum) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    private function rollbackSale($invoiceNo)
    {
        $sale = $this->findRecord(Sale::class, 'invoice_no', $invoiceNo);
        if (!$sale) throw new \Exception("Sale $invoiceNo not found.");

        $this->validateRollbackDate($sale);

        $sale->load('items');

        // 1. Reverse Stock Impact
        foreach ($sale->items as $item) {
            $this->adjustStock($item->product_id, $item->warehouse_id, $item->sales_qty, 'add');
        }

        // 2. Reverse Ledger Impact
        $saleAmount = (float)($sale->sub_total2 ?? 0);
        $orderDiscount = (float)($sale->discount_amount ?? 0);
        $receiptAmount = (float)($sale->receipt1 + $sale->receipt2);
        $impact = $saleAmount - ($orderDiscount + $receiptAmount);
        
        $this->adjustLedger($sale->partyType, $sale->customer_id, $impact, 'subtract');

        // 3. Delete Auto-generated Vouchers and reverse Account impacts
        $rvs = ReceiptsVoucher::where('remarks', 'LIKE', '%Auto-generated from Sale: ' . $sale->invoice_no . '%')->get();
        foreach ($rvs as $rv) {
            $rowAccs = json_decode($rv->row_account_id, true);
            $rowAmts = json_decode($rv->amount, true);
            if (is_array($rowAccs)) {
                foreach ($rowAccs as $idx => $accId) {
                    $rowAmt = (float)($rowAmts[$idx] ?? 0);
                    $this->adjustAccount($accId, $rowAmt, 'subtract');
                }
            }
            $rv->delete();
        }

        $discountVouchers = Voucher::where('narration', 'LIKE', '%Discount on Sale: ' . $sale->invoice_no . '%')->get();
        foreach ($discountVouchers as $dv) {
            $dv->delete();
        }

        // Reverse JournalVoucher for Sale Discount
        $discountJVs = \App\Models\JournalVoucher::where('jvid', 'SJ-DISC-' . $sale->invoice_no)->get();
        foreach ($discountJVs as $jv) {
            // Deduct from Account opening_balance
            if ($sale->discount_account_id) {
                $this->adjustAccount($sale->discount_account_id, $sale->discount_amount, 'subtract');
            }
            $jv->delete();
        }

        // 4. Convert back to Booking (Draft)
        $bookingData = $sale->toArray();
        unset($bookingData['id']); 
        if (isset($bookingData['partyType'])) {
            $bookingData['party_type'] = $bookingData['partyType'];
            unset($bookingData['partyType']);
        }
        
        $booking = Productbooking::create($bookingData);
        foreach ($sale->items as $item) {
            $itemData = $item->toArray();
            unset($itemData['id']);
            $itemData['booking_id'] = $booking->id;
            ProductBookingItem::create($itemData);
        }

        // 5. Delete Sale
        $sale->items()->delete();
        $sale->delete();
        
        return back()->with('success', "Sale #$invoiceNo rolled back to Draft.");
    }

    private function rollbackPurchase($invoiceNo)
    {
        $purchase = $this->findRecord(Purchase::class, 'invoice_no', $invoiceNo);
        if (!$purchase) throw new \Exception("Purchase $invoiceNo not found.");
        
        $this->validateRollbackDate($purchase);
        
        $purchase->load(['items', 'accountAllocations']);
        
        if ($purchase->status !== 'Posted') throw new \Exception("Purchase $invoiceNo is not Posted.");

        // 1. Reverse Stock Impact
        foreach ($purchase->items as $item) {
            $this->adjustStock($item->product_id, $purchase->warehouse_id, $item->qty, 'subtract');
        }

        // 2. Reverse Ledger Impact (Consolidated)
        $this->adjustLedger($purchase->purchasable_type, $purchase->purchasable_id, $purchase->net_amount, 'subtract');
        
        // 3. Reverse WHT Account Impact
        if ($purchase->wht > 0 && $purchase->wht_account_id) {
            $this->adjustAccount($purchase->wht_account_id, $purchase->wht, 'subtract');
        }

        // 4. Reverse Allocation Account Impacts
        foreach ($purchase->accountAllocations as $allocation) {
            $this->adjustAccount($allocation->account_id, $allocation->amount, 'add'); // Post subtracted, so rollback adds
        }

        // 5. Delete Related Journal Vouchers
        JournalVoucher::where('jvid', 'PJ-WHT-' . $purchase->invoice_no)->delete();
        JournalVoucher::where('jvid', 'PJ-ALLOC-' . $purchase->invoice_no)->delete();

        // 6. Update Purchase Status
        $purchase->update(['status' => 'Unposted']);
        
        // 7. Reset Inward Gatepass Status
        if ($purchase->inward_id) {
            InwardGatepass::where('id', $purchase->inward_id)->update(['status' => 'pending']);
        }

        return back()->with('success', "Purchase #$invoiceNo set to Unposted.");
    }

    private function rollbackPurchaseReturn($invoiceNo)
    {
        $ret = $this->findRecord(PurchaseReturn::class, 'invoice_no', $invoiceNo);
        if (!$ret) throw new \Exception("Purchase Return $invoiceNo not found.");
        
        $this->validateRollbackDate($ret);
        
        $ret->load('items');
        if ($ret->status !== 'Posted') throw new \Exception("Return $invoiceNo is not Posted.");

        foreach ($ret->items as $item) {
            $this->adjustStock($item->product_id, $ret->warehouse_id, $item->qty, 'add');
        }

        $this->adjustLedger($ret->party_type, $ret->party_id, $ret->total_balance, 'add');
        $ret->update(['status' => 'Unposted']);
        return back()->with('success', "Purchase Return #$invoiceNo set to Unposted.");
    }

    private function rollbackSaleReturn($invoiceNo)
    {
        $ret = $this->findRecord(SaleReturn::class, 'invoice_no', $invoiceNo);
        if (!$ret) throw new \Exception("Sale Return $invoiceNo not found.");
        
        $this->validateRollbackDate($ret);
        
        $ret->load('items');
        if ($ret->status !== 'Posted') throw new \Exception("Return $invoiceNo is not Posted.");

        foreach ($ret->items as $item) {
            $this->adjustStock($item->product_id, $item->warehouse_id, $item->sales_qty, 'subtract');
        }

        $this->adjustLedger($ret->party_type, $ret->customer_id, $ret->total_balance, 'add');
        $ret->update(['status' => 'Unposted']);
        return back()->with('success', "Sale Return #$invoiceNo set to Unposted.");
    }

    private function rollbackInward($invoiceNo)
    {
        $inward = $this->findRecord(InwardGatepass::class, 'invoice_no', $invoiceNo);
        if (!$inward) throw new \Exception("Inward $invoiceNo not found.");
        
        $this->validateRollbackDate($inward);
        
        $inward->update(['status' => 'pending']);
        return back()->with('success', "Inward #$invoiceNo set to Pending.");
    }

    private function rollbackStockHold($invoiceNo)
    {
        $hold = $this->findRecord(StockHoldVoucher::class, 'voucher_no', $invoiceNo);
        if (!$hold) throw new \Exception("Stock Hold Voucher $invoiceNo not found.");
        
        $this->validateRollbackDate($hold);
        
        if ($hold->status !== 'Posted') throw new \Exception("Hold $invoiceNo is not Posted.");

        $hold->load('items');
        foreach ($hold->items as $item) {
            $this->adjustStock($item->product_id, $item->warehouse_id, $item->hold_qty, 'subtract');
        }

        $hold->update(['status' => 'Unposted']);
        return back()->with('success', "Stock Hold #$invoiceNo set to Unposted.");
    }

    private function rollbackStockRelease($invoiceNo)
    {
        $rel = $this->findRecord(\App\Models\StockReleaseVoucher::class, 'voucher_no', $invoiceNo);
        if (!$rel) throw new \Exception("Stock Release Voucher $invoiceNo not found.");
        
        $this->validateRollbackDate($rel);
        
        if ($rel->status !== 'Posted') throw new \Exception("Release $invoiceNo is not Posted.");

        $rel->load('items.hold');

        foreach ($rel->items as $item) {
            // 1. Add back physical stock that was deducted during release
            $this->adjustStock($item->product_id, $item->warehouse_id, $item->release_qty, 'add');

            // 2. Restore hold_qty in the original StockHold
            if ($item->hold) {
                $item->hold->hold_qty += $item->release_qty;
                $item->hold->status = 0; // Set back to active hold
                $item->hold->save();
            }

            $item->update(['status' => 'Unposted']);
        }

        $rel->update(['status' => 'Unposted']);
        return back()->with('success', "Stock Release #$invoiceNo set to Unposted.");
    }

    private function rollbackStockTransfer($invoiceNo)
    {
        // FIX: StockTransfer uses 'id' if 'transfer_no' is not present
        $trans = $this->findRecord(StockTransfer::class, 'id', $invoiceNo);
        if (!$trans) throw new \Exception("Stock Transfer $invoiceNo not found.");
        
        $this->validateRollbackDate($trans);
        
        $trans->load('items');
        if ($trans->status !== 'Posted') throw new \Exception("Transfer $invoiceNo is not Posted.");

        foreach ($trans->items as $item) {
            $this->adjustStock($item->product_id, $trans->from_warehouse, $item->qty, 'add');
            $this->adjustStock($item->product_id, $trans->to_warehouse, $item->qty, 'subtract');
        }

        $trans->update(['status' => 'Unposted']);
        return back()->with('success', "Stock Transfer #$invoiceNo set to Unposted.");
    }

    private function rollbackStockWastage($invoiceNo)
    {
        $wastage = $this->findRecord(StockWastage::class, 'gwn_id', $invoiceNo);
        if (!$wastage) throw new \Exception("Stock Wastage $invoiceNo not found.");
        
        $this->validateRollbackDate($wastage);
        
        $wastage->load('items');
        if ($wastage->status !== 'Posted') throw new \Exception("Wastage $invoiceNo is not Posted.");

        foreach ($wastage->items as $item) {
            $this->adjustStock($item->product_id, $wastage->warehouse_id, $item->qty, 'add');
        }

        $wastage->update(['status' => 'Unposted']);
        return back()->with('success', "Stock Wastage #$invoiceNo set to Unposted.");
    }

    private function rollbackWarehouseStock($invoiceNo)
    {
        $ws = WarehouseStock::find($invoiceNo);
        if (!$ws) throw new \Exception("Warehouse Stock record $invoiceNo not found.");
        $ws->update(['status' => 'Unposted']);
        return back()->with('success', "Warehouse Stock record #$invoiceNo set to Unposted.");
    }

    private function rollbackCustomerClaim($invoiceNo)
    {
        $claim = $this->findRecord(CustomerClaim::class, 'claim_no', $invoiceNo);
        if (!$claim) throw new \Exception("Claim $invoiceNo not found.");
        $claim->update(['status' => 'Unposted']);
        return back()->with('success', "Customer Claim #$invoiceNo set to Unposted.");
    }

    private function rollbackClaimAcceptance($invoiceNo)
    {
        $acc = $this->findRecord(ClaimAcceptance::class, 'invoice_no', $invoiceNo);
        if (!$acc) throw new \Exception("Acceptance $invoiceNo not found.");
        $acc->update(['status' => 'Unposted']);
        return back()->with('success', "Claim Acceptance #$invoiceNo set to Unposted.");
    }

    private function rollbackClaimReceipt($invoiceNo)
    {
        $rec = $this->findRecord(ClaimItemReceipt::class, 'invoice_no', $invoiceNo);
        if (!$rec) throw new \Exception("Receipt $invoiceNo not found.");
        $rec->update(['status' => 'Unposted']);
        return back()->with('success', "Claim Receipt #$invoiceNo set to Unposted.");
    }

    private function rollbackVoucher($model, $field, $invoiceNo, $name)
    {
        $v = $this->findRecord($model, $field, $invoiceNo);
        if (!$v) throw new \Exception("$name $invoiceNo not found.");
        
        $this->validateRollbackDate($v);
        
        if ($v->status !== 'posted') throw new \Exception("$name $invoiceNo is not posted.");

        $amount = (float)$v->total_amount;
        
        // Reverse Header Party
        $this->adjustLedger($v->type, $v->party_id, $amount, $model === ReceiptsVoucher::class ? 'add' : 'subtract');

        // Reverse Row Accounts
        $rowAccs = json_decode($v->row_account_id, true);
        $rowAmts = json_decode($v->amount, true);
        if (is_array($rowAccs)) {
            foreach ($rowAccs as $idx => $accId) {
                $rowAmt = (float)($rowAmts[$idx] ?? 0);
                $this->adjustAccount($accId, $rowAmt, $model === ReceiptsVoucher::class ? 'subtract' : 'add');
            }
        }

        $v->update(['status' => 'draft']);
        return back()->with('success', "$name #$invoiceNo set to Draft.");
    }

    // Helper: Adjust Stock
    private function adjustStock($pid, $wid, $qty, $action)
    {
        $qty = (float)$qty;
        if ($wid == 0) {
            $p = Product::find($pid);
            if ($p) {
                $p->stock = $action === 'add' ? ($p->stock + $qty) : ($p->stock - $qty);
                $p->save();
            }
        } else {
            $ws = WarehouseStock::where('warehouse_id', $wid)->where('product_id', $pid)->first();
            if ($ws) {
                $ws->quantity = $action === 'add' ? ($ws->quantity + $qty) : ($ws->quantity - $qty);
                $ws->save();
            }
        }
    }

    // Helper: Adjust Ledger
    private function adjustLedger($type, $id, $amount, $action)
    {
        $amount = (float)$amount;
        $type = strtolower(class_basename($type));
        
        if ($type === 'vendor') {
            $ledger = VendorLedger::where('vendor_id', $id)->latest('id')->first();
            if ($ledger) {
                $ledger->previous_balance = $ledger->closing_balance;
                $ledger->closing_balance = $action === 'add' ? ($ledger->closing_balance + $amount) : ($ledger->closing_balance - $amount);
                $ledger->save();
            }
        } elseif (in_array($type, ['customer', 'walkin', 'walking'])) {
            $ledger = CustomerLedger::where('customer_id', $id)->latest('id')->first();
            if ($ledger) {
                $ledger->previous_balance = $ledger->closing_balance;
                $ledger->closing_balance = $action === 'add' ? ($ledger->closing_balance + $amount) : ($ledger->closing_balance - $amount);
                $ledger->save();
            }
        } elseif ($type === 'subcustomer') {
            $ledger = \App\Models\SubCustomerLedger::where('sub_customer_id', $id)->latest('id')->first();
            if ($ledger) {
                $ledger->previous_balance = $ledger->closing_balance;
                $ledger->closing_balance = $action === 'add' ? ($ledger->closing_balance + $amount) : ($ledger->closing_balance - $amount);
                $ledger->save();
            }
        } else {
            $this->adjustAccount($id, $amount, $action);
        }
    }

    // Helper: Adjust Account
    private function adjustAccount($id, $amount, $action)
    {
        $acc = Account::find($id);
        if ($acc) {
            $acc->opening_balance = $action === 'add' ? (($acc->opening_balance ?? 0) + $amount) : (($acc->opening_balance ?? 0) - $amount);
            $acc->save();
        }
    }

    private function validateRollbackDate($record)
    {
        if (auth()->user()->hasRole('Admin') || auth()->user()->usertype === 'admin') {
            return;
        }

        $today = date('Y-m-d');
        
        // Try to find the date field
        $date = null;
        if (isset($record->entry_date)) {
            $date = $record->entry_date;
        } elseif (isset($record->date)) {
            $date = $record->date;
        } elseif (isset($record->current_date)) {
            $date = $record->current_date;
        } elseif (isset($record->created_at)) {
            // Ensure we handle carbon objects
            $date = is_object($record->created_at) ? $record->created_at->format('Y-m-d') : $record->created_at;
        }

        if ($date) {
            $formattedDate = date('Y-m-d', strtotime($date));
            if ($formattedDate !== $today) {
                throw new \Exception("Access Denied: Standard users can only rollback transactions from today ($today). This record is from $formattedDate. Please contact an Admin for historical rollbacks.");
            }
        }
    }
}
