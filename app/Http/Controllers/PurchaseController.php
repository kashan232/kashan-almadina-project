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
use Illuminate\Support\Facades\Validator;


class PurchaseController extends Controller
{
    public function index()
    {
        $Purchase = Purchase::with(['vendor', 'warehouse'])->get();
        return view("admin_panel.purchase.index", compact('Purchase'));
    }
    public function add_purchase()
    {
        $Vendor = Vendor::get();
        $Warehouse = Warehouse::get();
        $AccountHeads = AccountHead::get();
        $customers = Customer::all();

        // ---------- GET LAST INVOICE ----------
        $lastPurchase = Purchase::orderBy('id', 'DESC')->first();

        if ($lastPurchase && !empty($lastPurchase->invoice_no)) {

            // Extract number from format "PUR-006"
            $lastNumber = intval(str_replace('PUR-', '', $lastPurchase->invoice_no));

            // Increment
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1; // First ever invoice
        }

        // Format into PUR-XXX
        $nextInvoice = 'PUR-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        // Pass this to the view
        return view(
            'admin_panel.purchase.add_purchase',
            compact('Vendor', "Warehouse", 'AccountHeads', 'customers', 'nextInvoice')
        );
    }



    public function store(Request $request)
    {
        // 1) Server-side validation (accounts removed from required rules)
        $rules = [
            'vendor_type'         => 'required|string',
            'vendor_id'           => 'required|integer',
            'warehouse_id'        => 'required|integer|exists:warehouses,id',
            'current_date'        => 'nullable|date',
            // products
            'product_id'          => 'required|array|min:1',
            'product_id.*'        => 'required|integer|exists:products,id',
            'qty'                 => 'required|array',
            'qty.*'               => 'required|numeric|min:1',
            'price'               => 'nullable|array',
            'price.*'             => 'nullable|numeric|min:0',
            // totals
            'subtotal'            => 'required|numeric|min:0',
            'net_amount'          => 'required|numeric|min:0',
        ];

        $messages = [
            'warehouse_id.required' => 'Please select Warehouse.',
            'warehouse_id.exists'   => 'Selected Warehouse is invalid.',
            'product_id.required'   => 'Please add at least one Item.',
            'product_id.*.exists'   => 'One or more selected products are invalid.',
            'qty.*.required'        => 'Please provide quantity for each item.',
            'qty.*.min'             => 'Quantity must be at least 1.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Clean product rows: keep only rows with valid product id and qty>0
        $productIds = $request->input('product_id', []);
        $qtys       = $request->input('qty', []);
        $prices     = $request->input('price', []);
        $item_discs = $request->input('item_disc', []);
        $disc_amounts = $request->input('item_disc_amount', []);
        $purchase_retail = $request->input('purchase_retail_price', []);
        $purchase_net = $request->input('purchase_net_amount', []);
        $amounts    = $request->input('total', []);

        $cleanProductIds = [];
        $cleanQtys = [];
        $cleanPrices = [];
        $cleanItemDiscs = [];
        $cleanDiscAmounts = [];
        $cleanPurchaseRetail = [];
        $cleanPurchaseNet = [];
        $cleanAmounts = [];

        $max = max(count($productIds), count($qtys), count($prices));
        for ($i = 0; $i < $max; $i++) {
            $pid = $productIds[$i] ?? null;
            $q = $qtys[$i] ?? null;
            if (!empty($pid) && is_numeric($q) && floatval($q) > 0) {
                $cleanProductIds[] = $pid;
                $cleanQtys[] = $q;
                $cleanPrices[] = $prices[$i] ?? 0;
                $cleanItemDiscs[] = $item_discs[$i] ?? 0;
                $cleanDiscAmounts[] = $disc_amounts[$i] ?? 0;
                $cleanPurchaseRetail[] = $purchase_retail[$i] ?? 0;
                $cleanPurchaseNet[] = $purchase_net[$i] ?? 0;
                $cleanAmounts[] = $amounts[$i] ?? 0;
            }
        }

        // Merge cleaned arrays back to request so saving logic uses them
        $request->merge([
            'product_id' => $cleanProductIds,
            'qty' => $cleanQtys,
            'price' => $cleanPrices,
            'item_disc' => $cleanItemDiscs,
            'item_disc_amount' => $cleanDiscAmounts,
            'purchase_retail_price' => $cleanPurchaseRetail,
            'purchase_net_amount' => $cleanPurchaseNet,
            'total' => $cleanAmounts,
        ]);

        // Now perform DB transaction and handle exceptions explicitly
        try {
            DB::transaction(function () use ($request) {
                $typeMap = [
                    'Vendor'       => \App\Models\Vendor::class,
                    'Customer'     => \App\Models\Customer::class,
                    'SubCustomer'  => \App\Models\SubCustomer::class,
                ];

                $invoiceNo = \App\Models\Purchase::generateInvoiceNo();
                $typeKey = ucfirst(strtolower($request['vendor_type']));

                $purchase = \App\Models\Purchase::create([
                    'invoice_no'       => $invoiceNo,
                    'warehouse_id'     => $request['warehouse_id'],
                    'vendor_id'        => $request['vendor_id'],
                    'purchasable_type' => $typeMap[$typeKey] ?? null,
                    'purchasable_id'   => $request['vendor_id'],
                    'current_date'     => $request['current_date'] ?? now(),
                    'dc_date'          => $request['dc_date'] ?? null,
                    'note'             => $request['remarks'] ?? null,
                    'subtotal'         => $request->subtotal,
                    'discount'         => $request->discount,
                    'wht'              => $request->wht,
                    'net_amount'       => $request->net_amount,
                    'branch_id'        => auth()->user()->branch_id ?? 1,
                ]);

                $subtotal = 0;

                // Purchase items
                foreach ($request->product_id as $index => $productId) {
                    if (empty($productId)) continue;

                    $qty  = $request->qty[$index] ?? 0;
                    $price = $request->price[$index] ?? 0;
                    $disc = $request->item_disc[$index] ?? 0;
                    // line total calculation — adjust if your UI stores differently
                    $lineTotal = ($price * $qty) - ($request->item_disc_amount[$index] ?? 0);

                    \App\Models\PurchaseItem::create([
                        'purchase_id'   => $purchase->id,
                        'product_id'    => $productId,
                        'price'         => $price,
                        'item_discount' => $disc,
                        'qty'           => $qty,
                        'line_total'    => $lineTotal,
                    ]);

                    $subtotal += $lineTotal;

                    // Update product stock
                    $product = \App\Models\Product::find($productId);
                    if ($product) {
                        $product->stock = ($product->stock ?? 0) + $qty;
                        $product->save();
                    }
                }

                $purchase->update([
                    'subtotal'   => $subtotal,
                    'net_amount' => $request->net_amount,
                    'due_amount' => $request->net_amount,
                ]);

                // Ledger update (vendor/customer)
                $type = strtolower($request->vendor_type);
                $amount = $request->net_amount;

                if ($type === 'vendor') {
                    // vendor ledger update code (same as your existing)
                    $vendor_id = $request->vendor_id;
                    $ledger = \App\Models\VendorLedger::where('vendor_id', $vendor_id)->latest('id')->first();
                    if ($ledger) {
                        $ledger->previous_balance = $ledger->closing_balance;
                        $ledger->closing_balance  = $ledger->closing_balance + $amount;
                        $ledger->save();
                    } else {
                        \App\Models\VendorLedger::create([
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
                    // customer ledger update
                    $customer_id = $request->vendor_id;
                    $ledger = \App\Models\CustomerLedger::where('customer_id', $customer_id)->latest('id')->first();
                    if ($ledger) {
                        $ledger->previous_balance = $ledger->closing_balance;
                        $ledger->closing_balance  = $ledger->closing_balance + $amount;
                        $ledger->save();
                    } else {
                        \App\Models\CustomerLedger::create([
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

                // Save account allocations IF provided and complete (optional)
                $heads = $request->input('account_head_id', []);
                $accs  = $request->input('account_id', []);
                $amts  = $request->input('account_amount', []);

                $maxAlloc = max(count($heads), count($accs), count($amts));
                for ($i = 0; $i < $maxAlloc; $i++) {
                    $h = $heads[$i] ?? null;
                    $a = $accs[$i] ?? null;
                    $m = isset($amts[$i]) ? floatval($amts[$i]) : 0;

                    // only save complete allocations: head + account + amount>0
                    if (!empty($h) && !empty($a) && $m > 0) {
                        \App\Models\PurchaseAccountAllocaations::create([
                            'purchase_id'     => $purchase->id,
                            'account_head_id' => $h,
                            'account_id'      => $a,
                            'amount'          => $m,
                        ]);

                        // update account opening balance
                        $account = \App\Models\Account::find($a);
                        if ($account) {
                            $account->opening_balance = ($account->opening_balance ?? 0) + $m;
                            $account->save();
                        }
                    }
                }

                // Vouchers if any (unchanged)
                if ($request->discount > 0) {
                    \App\Models\Voucher::create([
                        'voucher_type'  => 'Discount voucher',
                        'date'          => now(),
                        'sales_officer' => auth()->user()->name,
                        'type'          => 'Credit',
                        'person'        => $purchase->vendor_id,
                        'sub_head'      => 'Purchase Discount',
                        'narration'     => 'Discount applied on Purchase ID: ' . $purchase->id,
                        'amount'        => $request->discount,
                    ]);
                }

                if ($request->wht > 0) {
                    \App\Models\Voucher::create([
                        'voucher_type'  => 'Wht voucher',
                        'date'          => now(),
                        'sales_officer' => auth()->user()->name,
                        'type'          => 'Credit',
                        'person'        => $purchase->vendor_id,
                        'sub_head'      => 'WHT',
                        'narration'     => 'WHT applied on Purchase ID: ' . $purchase->id,
                        'amount'        => $request->wht,
                    ]);
                }
            }); // end transaction

            return redirect()->back()->with('success', 'Purchase saved successfully!');
        } catch (\Throwable $e) {
            // log error for debugging
            \Log::error('Purchase store error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            // show friendly message to user (for debugging you can append $e->getMessage())
            return redirect()->back()
                ->with('error', 'Failed to save purchase. Server error logged. ' . (config('app.debug') ? $e->getMessage() : ''))
                ->withInput();
        }
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
