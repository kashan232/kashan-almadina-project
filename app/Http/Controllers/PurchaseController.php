<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountHead;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\InwardGatepass;
use App\Models\Stock;
use App\Models\Vendor;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseAccountAllocaations;
use App\Models\Warehouse;
use App\Models\PurchaseItem;
use App\Models\VendorLedger;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index()
    {
        $Purchase = Purchase::with(['vendor', 'warehouse'])->get();
        return view("admin_panel.purchase.index", compact('Purchase'));
    }
    public function add_purchase()
    {
        // $userId = Auth::id();
        $Purchase = Purchase::get();
        $Vendor = Vendor::get();
        $Warehouse = Warehouse::get();
        $AccountHeads = AccountHead::get();
        $customers = Customer::all();
        return view('admin_panel.purchase.add_purchase', compact('Vendor', "Warehouse", 'Purchase', 'AccountHeads', 'customers'));
    }

    public function store(Request $request)
    {
        DB::transaction(function () use ($request) {
            $typeMap = [
                'Vendor'       => \App\Models\Vendor::class,
                'Customer'     => \App\Models\Customer::class,
                'SubCustomer'  => \App\Models\SubCustomer::class,
            ];
            $invoiceNo = Purchase::generateInvoiceNo();
            $typeKey = ucfirst(strtolower($request['vendor_type']));

            // 1️⃣ Save main Purchase
            $purchase = Purchase::create([
                'invoice_no'    => $invoiceNo,
                'warehouse_id'    => $request['warehouse_id'],
                'vendor_id'       => $request['vendor_id'],
                'purchasable_type' => $typeMap[$typeKey],
                'purchasable_id'  => $request['vendor_id'],
                'current_date'    => $request['current_date'] ?? now(),
                'dc_date'         => $request['dc_date'] ?? null,
                'note'            => $request['remarks'] ?? null,
                'subtotal'        => $request->subtotal,
                'discount'        => $request->discount,
                'wht'             => $request->wht,
                'net_amount'      => $request->net_amount,
                'branch_id'       => auth()->user()->branch_id ?? 1,
            ]);

            $subtotal = 0;

            // 2️⃣ Loop purchase items
            foreach ($request['product_id'] as $index => $productId) {
                if ($productId === null) {
                    continue;
                }

                $qty       = $request['qty'][$index];
                $price     = $request['price'][$index];
                $disc      = $request['item_disc'][$index] ?? 0;
                $lineTotal = ($price * $qty) - $disc;

                // Save purchase item
                PurchaseItem::create([
                    'purchase_id'   => $purchase->id,
                    'product_id'    => $productId,
                    'price'         => $price,
                    'item_discount' => $disc,
                    'qty'           => $qty,
                    'line_total'    => $lineTotal,
                ]);

                $subtotal += $lineTotal;

                // 3️⃣ Update product stock
                $product = Product::find($productId);
                $product->stock += $qty;
                $product->save();
            }

            // 4️⃣ Update totals in purchase
            $purchase->update([
                'subtotal'    => $subtotal,
                'net_amount'  => $request->net_amount,
                'due_amount'  => $request->net_amount,
            ]);

            // 5️⃣ Ledger Update based on type
            $type = strtolower($request->vendor_type);
            $amount = $request->net_amount;

            if ($type === 'vendor') {
                $vendor_id = $request->vendor_id;

                $ledger = VendorLedger::where('vendor_id', $vendor_id)
                    ->latest('id')
                    ->first();

                if ($ledger) {
                    $ledger->previous_balance = $ledger->closing_balance;
                    $ledger->closing_balance  = $ledger->closing_balance + $amount;
                    $ledger->save();
                } else {
                    VendorLedger::create([
                        'vendor_id'        => $vendor_id,
                        'admin_or_user_id' => auth()->id(),
                        'date'             => $request['current_date'],
                        'description'      => 'Purchase ID: ' . $purchase->id,
                        'previous_balance' => 0,
                        'closing_balance'  => $amount,
                        'opening_balance'  => $amount,
                    ]);
                }
            } elseif ($type === 'customer') {
                $customer_id = $request->vendor_id;

                $ledger = CustomerLedger::where('customer_id', $customer_id)
                    ->latest('id')
                    ->first();

                if ($ledger) {
                    $ledger->previous_balance = $ledger->closing_balance;
                    $ledger->closing_balance  = $ledger->closing_balance + $amount;
                    $ledger->save();
                } else {
                    CustomerLedger::create([
                        'customer_id'      => $customer_id,
                        'admin_or_user_id' => auth()->id(),
                        'date'             => $request['current_date'],
                        'description'      => 'Purchase ID: ' . $purchase->id,
                        'previous_balance' => 0,
                        'closing_balance'  => $amount,
                        'opening_balance'  => $amount,
                    ]);
                }
            }



            if ($request->has('account_head_id')) {
                foreach ($request->account_head_id as $index => $headId) {
                    $accountId = $request->account_id[$index] ?? null;
                    $amount    = $request->account_amount[$index] ?? 0;

                    if ($headId && $accountId && $amount > 0) {
                        // Save Allocation
                        PurchaseAccountAllocaations::create([
                            'purchase_id'     => $purchase->id,
                            'account_head_id' => $headId,
                            'account_id'      => $accountId,
                            'amount'          => $amount,
                        ]);

                        // 🔥 Update Account Balance
                        $account = Account::find($accountId);
                        if ($account) {
                            $account->opening_balance = $account->opening_balance + $amount;
                            $account->save();
                        }
                    }
                }
            }


            // 6️⃣ Vouchers
            if ($request['discount'] > 0) {
                Voucher::create([
                    'voucher_type'    => 'Discount voucher',
                    'date'            => now(),
                    'sales_officer'   => auth()->user()->name,
                    'type'            => 'Credit',
                    'person'          => $purchase->vendor_id,
                    'sub_head'        => 'Purchase Discount',
                    'narration'       => 'Discount applied on Purchase ID: ' . $purchase->id,
                    'amount'          => $request['discount']
                ]);
            }

            if ($request['wht'] > 0) {
                Voucher::create([
                    'voucher_type'    => 'Wht voucher',
                    'date'            => now(),
                    'sales_officer'   => auth()->user()->name,
                    'type'            => 'Credit',
                    'person'          => $purchase->vendor_id,
                    'sub_head'        => 'WHT',
                    'narration'       => 'WHT applied on Purchase ID: ' . $purchase->id,
                    'amount'          => $request['wht']
                ]);
            }
        });

        return redirect()->back()->with('success', 'Purchase saved successfully!');
    }


    public function store_inwrd_purchse(Request $request)
    {
        DB::transaction(function () use ($request) {
            $typeMap = [
                'Vendor'      => \App\Models\Vendor::class,
                'Customer'    => \App\Models\Customer::class,
                'SubCustomer' => \App\Models\SubCustomer::class,
            ];

            $invoiceNo = Purchase::generateInvoiceNo();
            $typeKey = ucfirst(strtolower($request['vendor_type']));

            // 1️⃣ Save Purchase (with inward_id)
            $purchase = Purchase::create([
                'invoice_no'      => $invoiceNo,
                'warehouse_id'    => $request['warehouse_id'],
                'vendor_id'       => $request['vendor_id'],
                'purchasable_type' => $typeMap[$typeKey],
                'purchasable_id'  => $request['vendor_id'],
                'current_date'    => $request['current_date'] ?? now(),
                'dc_date'         => $request['dc_date'] ?? null,
                'note'            => $request['remarks'] ?? null,
                'subtotal'        => $request->subtotal,
                'discount'        => $request->discount,
                'wht'             => $request->wht,
                'net_amount'      => $request->net_amount,
                'branch_id'       => auth()->user()->branch_id ?? 1,
                'inward_id'       => $request->inward_id, // link inward
            ]);

            $subtotal = 0;
            // 2️⃣ Save Purchase Items + Stock Update
            foreach ($request['product_id'] as $index => $productId) {
                if (!$productId) continue;

                $qty       = $request['qty'][$index];
                $price     = $request['purchase_retail_price'][$index]; // ✅ retail
                $disc      = $request['item_disc'][$index] ?? 0;
                $discAmt   = $request['item_disc_amount'][$index] ?? 0;
                $lineTotal = ($price * $qty) - $discAmt;

                PurchaseItem::create([
                    'purchase_id'   => $purchase->id,
                    'product_id'    => $productId,
                    'price'         => $price,
                    'item_discount' => $discAmt,
                    'qty'           => $qty,
                    'line_total'    => $lineTotal,
                ]);

                $subtotal += $lineTotal;

                // 🔥 Stock Update
                $product = Product::find($productId);
                $product->stock += $qty;
                $product->save();
            }

            // 3️⃣ Update Purchase Totals
            $purchase->update([
                'subtotal'   => $subtotal,
                'net_amount' => $request->net_amount,
                'due_amount' => $request->net_amount,
            ]);

            // 4️⃣ Ledger Updates
            if (strtolower($request->vendor_type) === 'vendor') {
                // Vendor Ledger
                $ledger = VendorLedger::where('vendor_id', $request->vendor_id)->latest('id')->first();
                if ($ledger) {
                    $ledger->previous_balance = $ledger->closing_balance;
                    $ledger->closing_balance  = $ledger->closing_balance + $request->net_amount;
                    $ledger->save();
                } else {
                    VendorLedger::create([
                        'vendor_id'        => $request->vendor_id,
                        'admin_or_user_id' => auth()->id(),
                        'date'             => $request['current_date'],
                        'description'      => 'Inward Purchase ID: ' . $purchase->id,
                        'previous_balance' => 0,
                        'closing_balance'  => $request->net_amount,
                        'opening_balance'  => $request->net_amount,
                    ]);
                }
            } elseif (strtolower($request->vendor_type) === 'customer') {
                // Customer Ledger
                $ledger = CustomerLedger::where('customer_id', $request->vendor_id)->latest('id')->first();
                if ($ledger) {
                    $ledger->previous_balance = $ledger->closing_balance;
                    $ledger->closing_balance  = $ledger->closing_balance + $request->net_amount;
                    $ledger->save();
                } else {
                    CustomerLedger::create([
                        'customer_id'      => $request->vendor_id,
                        'admin_or_user_id' => auth()->id(),
                        'previous_balance' => 0,
                        'closing_balance'  => $request->net_amount,
                        'opening_balance'  => $request->net_amount,
                    ]);
                }
            }

            // 5️⃣ Account Allocations
            if ($request->has('account_head_id')) {
                foreach ($request->account_head_id as $index => $headId) {
                    $accountId = $request->account_id[$index] ?? null;
                    $amount    = $request->account_amount[$index] ?? 0;

                    if ($headId && $accountId && $amount > 0) {
                        PurchaseAccountAllocaations::create([
                            'purchase_id'     => $purchase->id,
                            'account_head_id' => $headId,
                            'account_id'      => $accountId,
                            'amount'          => $amount,
                        ]);

                        $account = Account::find($accountId);
                        if ($account) {
                            $account->opening_balance += $amount;
                            $account->save();
                        }
                    }
                }
            }

            // 6️⃣ Vouchers
            if ($request['discount'] > 0) {
                Voucher::create([
                    'voucher_type'  => 'Discount voucher',
                    'date'          => now(),
                    'sales_officer' => auth()->user()->name,
                    'type'          => 'Credit',
                    'person'        => $purchase->vendor_id,
                    'sub_head'      => 'Purchase Discount',
                    'narration'     => 'Discount applied on Inward Purchase ID: ' . $purchase->id,
                    'amount'        => $request['discount']
                ]);
            }

            if ($request['wht'] > 0) {
                Voucher::create([
                    'voucher_type'  => 'Wht voucher',
                    'date'          => now(),
                    'sales_officer' => auth()->user()->name,
                    'type'          => 'Credit',
                    'person'        => $purchase->vendor_id,
                    'sub_head'      => 'WHT',
                    'narration'     => 'WHT applied on Inward Purchase ID: ' . $purchase->id,
                    'amount'        => $request['wht']
                ]);
            }

            // 7️⃣ Inward Status Update
            InwardGatepass::where('id', $request->inward_id)->update(['status' => 'linked']);
        });

        return redirect()->back()->with('success', 'Inward Purchase confirmed and saved successfully!');
    }


    public function edit($id)
    {
        $purchase   = Purchase::findOrFail($id);
        $Vendor     = Vendor::all();
        $Warehouse  = Warehouse::all();
        //   $Transport  = Transport::all();
        return view('admin_panel.purchase.edit', compact('purchase', 'Vendor', 'Warehouse'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'invoice_no' => 'nullable',
            'supplier' => 'nullable',
            'purchase_date' => 'nullable',
            'warehouse_id' => 'nullable',
            'item_category' => 'nullable',
            'item_name' => 'nullable|array',
            'quantity' => 'nullable|array',
            'price' => 'nullable|array',
            'unit' => 'nullable|array',
            'total' => 'nullable|array',
            'note' => 'nullable',
            'total_price' => 'nullable',
            'discount' => 'nullable',
            'Payable_amount' => 'nullable',
            'paid_amount' => 'nullable',
            'due_amount' => 'nullable',
            'status' => 'nullable',
            'is_return' => 'nullable',
        ]);

        $purchase = Purchase::findOrFail($id);

        $purchase->update([
            'invoice_no' => $validated['invoice_no'] ?? null,
            'supplier' => $validated['supplier'] ?? null,
            'purchase_date' => $validated['purchase_date'] ?? null,
            'warehouse_id' => $validated['warehouse_id'] ?? null,
            'item_category' => $validated['item_category'] ?? null,

            'item_name' => json_encode($validated['item_name'] ?? []),
            'quantity' => json_encode($validated['quantity'] ?? []),
            'price' => json_encode($validated['price'] ?? []),
            'unit' => json_encode($validated['unit'] ?? []),
            'total' => json_encode($validated['total'] ?? []),

            'note' => $validated['note'] ?? null,
            'total_price' => $validated['total_price'] ?? null,
            'discount' => $validated['discount'] ?? null,
            'Payable_amount' => $validated['Payable_amount'] ?? null,
            'paid_amount' => $validated['paid_amount'] ?? null,
            'due_amount' => $validated['due_amount'] ?? null,
            'status' => $validated['status'] ?? null,
            'is_return' => $validated['is_return'] ?? null,
        ]);

        return redirect()->route('Purchase.home')->with('success', 'Purchase updated successfully!');
    }

    public function destroy($id)
    {
        $purchase = Purchase::findOrFail($id);
        $purchase->delete();

        return redirect()->back()->with('success', 'Purchase deleted successfully.');
    }

    public function addBill($gatepassId)
    {
        $gatepass = InwardGatepass::with([
            'items.product.brand',
            'items.product.latestPrice'
        ])->findOrFail($gatepassId);

        $Purchase = Purchase::get();
        $Vendor = Vendor::get();
        $Warehouse = Warehouse::get();
        $AccountHeads = AccountHead::get();

        return view('admin_panel.inward.add_bill', compact('gatepass', 'Vendor', "Warehouse", 'Purchase', 'AccountHeads'));
    }

    public function Invoice($id)
    {
        $purchase   = Purchase::with(['vendor', 'warehouse', 'items.product'])->findOrFail($id);
        return view('admin_panel.purchase.Invoice', compact('purchase'));
    }

    public function getAccountsByHead($headId)
    {
        $accounts = Account::where('head_id', $headId)->where('status', 1)->get();
        return response()->json($accounts);
    }

    public function getPartyList(Request $request)
    {
        $type = strtolower($request->query('type', 'vendor'));

        if ($type === 'vendor') {
            $vendors = Vendor::select('id', 'name as text')->get();
            return response()->json($vendors);
        } elseif ($type === 'customer') {
            $customers = Customer::where('customer_type', 'Main Customer')
                ->select('id', 'customer_name as text')
                ->get();
            return response()->json($customers);
        } elseif ($type === 'walkin') {
            $walkins = Customer::where('customer_type', 'Walking Customer')
                ->select('id', 'customer_name as text')
                ->get();
            return response()->json($walkins);
        }

        return response()->json([]);
    }
}
