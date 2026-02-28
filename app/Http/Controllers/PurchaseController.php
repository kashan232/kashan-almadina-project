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
    public function index(Request $request)
    {
        $query = Purchase::with(['vendor', 'warehouse', 'purchasable', 'items.product']);

        if ($request->filled('start_date')) {
            $query->whereDate('current_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('current_date', '<=', $request->end_date);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $Purchase = $query->latest()->get();
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
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Validation error', 
                    'errors' => $validator->errors()
                ], 422);
            }
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

        $savedPurchase = null;
        try {
            DB::transaction(function () use ($request, &$savedPurchase) {
                $typeMap = [
                    'Vendor'       => \App\Models\Vendor::class,
                    'Customer'     => \App\Models\Customer::class,
                    'Walkin'       => \App\Models\Customer::class,
                    'SubCustomer'  => \App\Models\SubCustomer::class,
                ];

                $invoiceNo = \App\Models\Purchase::generateInvoiceNo();
                $typeKey = ucfirst(strtolower($request['vendor_type']));

                $purchase = \App\Models\Purchase::create([
                    'status'           => 'Unposted',
                    'invoice_no'       => $invoiceNo,
                    'warehouse_id'     => $request['warehouse_id'],
                    'vendor_id'        => $request['vendor_id'],
                    'purchasable_type' => $typeMap[$typeKey] ?? null,
                    'purchasable_id'   => $request['vendor_id'],
                    'current_date'     => $request['current_date'] ?? now(),
                    'dc_date'          => $request['dc_date'] ?? null,
                    'dc'               => $request['dc'] ?? null,
                    'bilty_no'         => $request['bilty_no'] ?? null,
                    'note'             => $request['remarks'] ?? null,
                    'subtotal'         => $request->subtotal,
                    'discount'         => $request->discount,
                    'wht'              => $request->wht,
                    'wht_percent'      => $request->wht_percent,
                    'wht_type'         => $request->wht_type,
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
                }

                $purchase->update([
                    'subtotal'   => $subtotal,
                    'due_amount' => $request->net_amount,
                ]);

                // Save account allocations IF provided and complete (optional)
                $heads = $request->input('account_head_id', []);
                $accs  = $request->input('account_id', []);
                $amts  = $request->input('account_amount', []);

                $maxAlloc = max(count($heads), count($accs), count($amts));
                for ($i = 0; $i < $maxAlloc; $i++) {
                    $h = $heads[$i] ?? null;
                    $a = $accs[$i] ?? null;
                    $m = isset($amts[$i]) ? floatval($amts[$i]) : 0;

                    if (!empty($h) && !empty($a) && $m > 0) {
                        \App\Models\PurchaseAccountAllocaations::create([
                            'purchase_id'     => $purchase->id,
                            'account_head_id' => $h,
                            'account_id'      => $a,
                            'amount'          => $m,
                        ]);
                    }
                }

                $savedPurchase = $purchase;
            });

            $msg = 'Purchase saved as Draft (Unposted)!';

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success'    => true,
                    'message'    => $msg,
                    'id'         => $savedPurchase->id,
                    'invoice_no' => $savedPurchase->invoice_no,
                ]);
            }

            return redirect()->route('Purchase.home')->with('success', $msg);
        } catch (\Throwable $e) {
            \Log::error('Purchase store error: ' . $e->getMessage());
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return redirect()->back()->with('error', 'Failed to save purchase. ' . $e->getMessage())->withInput();
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
                'status'          => 'Unposted',
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
                'wht_percent'     => $request->wht_percent,
                'wht_type'        => $request->wht_type,
                'net_amount'      => $request->net_amount,
                'branch_id'       => auth()->user()->branch_id ?? 1,
                'inward_id'       => $request->inward_id, // link inward
            ]);

            $subtotal = 0;
            // 2️⃣ Save Purchase Items
            foreach ($request['product_id'] as $index => $productId) {
                if (!$productId) continue;

                $qty       = $request['qty'][$index];
                $price     = $request['purchase_retail_price'][$index]; // ✅ retail
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
            }

            // 3️⃣ Update Purchase Totals
            $purchase->update([
                'subtotal'   => $subtotal,
                'due_amount' => $request->net_amount,
            ]);

            // 4️⃣ Account Allocations
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
                    }
                }
            }
        });

        return redirect()->route('Purchase.home')->with('success', 'Inward Purchase saved as Draft (Unposted)!');
    }


    public function edit($id)
    {
        $purchase = Purchase::with(['purchasable', 'items.product.brandRelation', 'items.product.latestPrice', 'accountAllocations.account'])
            ->findOrFail($id);
        
        $Vendor = Vendor::all();
        $customers = Customer::all();
        $Warehouse = Warehouse::all();
        $AccountHeads = AccountHead::all();
        
        // Use existing invoice number
        $nextInvoice = $purchase->invoice_no;
        
        return view('admin_panel.purchase.add_purchase', compact(
            'purchase', 
            'Vendor', 
            'customers',
            'Warehouse', 
            'AccountHeads',
            'nextInvoice'
        ));
    }

    public function update(Request $request, $id)
    {
        // Use same validation as store
        $rules = [
            'vendor_type'         => 'required|string',
            'vendor_id'           => 'required|integer',
            'warehouse_id'        => 'required|integer|exists:warehouses,id',
            'current_date'        => 'nullable|date',
            'product_id'          => 'required|array|min:1',
            'product_id.*'        => 'required|integer|exists:products,id',
            'qty'                 => 'required|array',
            'qty.*'               => 'required|numeric|min:1',
            'price'               => 'nullable|array',
            'price.*'             => 'nullable|numeric|min:0',
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

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Clean product rows
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

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($request, $id, $cleanProductIds, $cleanQtys, $cleanPrices, $cleanItemDiscs, $cleanDiscAmounts, $cleanPurchaseRetail, $cleanPurchaseNet, $cleanAmounts) {
                $purchase = Purchase::findOrFail($id);
                
                $typeMap = [
                    'Vendor'       => \App\Models\Vendor::class,
                    'Customer'     => \App\Models\Customer::class,
                    'SubCustomer'  => \App\Models\SubCustomer::class,
                ];
                
                $typeKey = ucfirst(strtolower($request['vendor_type']));

                // Update purchase header
                $purchase->update([
                    'warehouse_id'     => $request['warehouse_id'],
                    'vendor_id'        => $request['vendor_id'],
                    'purchasable_type' => $typeMap[$typeKey] ?? null,
                    'purchasable_id'   => $request['vendor_id'],
                    'current_date'     => $request['current_date'] ?? now(),
                    'dc_date'          => $request['dc_date'] ?? null,
                    'dc'               => $request['dc'] ?? null,
                    'bilty_no'         => $request['bilty_no'] ?? null,
                    'note'             => $request['remarks'] ?? null,
                    'subtotal'         => $request->subtotal,
                    'discount'         => $request->discount,
                    'wht'              => $request->wht,
                    'wht_percent'      => $request->wht_percent,
                    'wht_type'         => $request->wht_type,
                    'net_amount'       => $request->net_amount,
                ]);

                // Delete old items
                foreach ($purchase->items as $oldItem) {
                    $oldItem->delete();
                }

                // Add new items
                $subtotal = 0;
                foreach ($cleanProductIds as $index => $productId) {
                    if (empty($productId)) continue;

                    $qty  = $cleanQtys[$index] ?? 0;
                    $price = $cleanPrices[$index] ?? 0;
                    $disc = $cleanItemDiscs[$index] ?? 0;
                    $lineTotal = ($price * $qty) - ($cleanDiscAmounts[$index] ?? 0);

                    \App\Models\PurchaseItem::create([
                        'purchase_id'   => $purchase->id,
                        'product_id'    => $productId,
                        'price'         => $price,
                        'item_discount' => $disc,
                        'qty'           => $qty,
                        'line_total'    => $lineTotal,
                    ]);

                    $subtotal += $lineTotal;
                }

                $purchase->update([
                    'subtotal'   => $subtotal,
                    'net_amount' => $request->net_amount,
                    'due_amount' => $request->net_amount,
                ]);

                // Delete old account allocations
                \App\Models\PurchaseAccountAllocaations::where('purchase_id', $purchase->id)->delete();

                // Save new account allocations
                $heads = $request->input('account_head_id', []);
                $accs  = $request->input('account_id', []);
                $amts  = $request->input('account_amount', []);

                $maxAlloc = max(count($heads), count($accs), count($amts));
                for ($i = 0; $i < $maxAlloc; $i++) {
                    $h = $heads[$i] ?? null;
                    $a = $accs[$i] ?? null;
                    $m = isset($amts[$i]) ? floatval($amts[$i]) : 0;

                    if (!empty($h) && !empty($a) && $m > 0) {
                        \App\Models\PurchaseAccountAllocaations::create([
                            'purchase_id'     => $purchase->id,
                            'account_head_id' => $h,
                            'account_id'      => $a,
                            'amount'          => $m,
                        ]);
                    }
                }
            });

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Purchase updated successfully!',
                    'id'      => $id
                ]);
            }

            return redirect()->route('Purchase.home')->with('success', 'Purchase updated successfully!');
        } catch (\Throwable $e) {
            \Log::error('Purchase update error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return redirect()->back()
                ->with('error', 'Failed to update purchase. ' . (config('app.debug') ? $e->getMessage() : ''))
                ->withInput();
        }
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
            $data = Vendor::orderBy('name')->get();
            return response()->json($data->map(function($v) {
                return ['id' => $v->id, 'text' => $v->name];
            }));
        }

        $query = Customer::query();
        if ($type === 'walkin') {
            $query->where('customer_type', 'Walking Customer');
        } elseif ($type === 'customer') {
            // If you want to exclude walkin from 'customer' type, add:
            // $query->where('customer_type', '!=', 'Walking Customer');
        }

        $data = $query->orderBy('customer_name')->get();
        return response()->json($data->map(function($c) {
            return ['id' => $c->id, 'text' => $c->customer_name];
        }));
    }
    public function post(Request $request, $id)
    {
        try {
            DB::transaction(function () use ($id) {
                $purchase = Purchase::findOrFail($id);
                if ($purchase->status === 'Posted') {
                    throw new \Exception('This purchase is already posted.');
                }
                $this->performPosting($purchase);
                $purchase->update(['status' => 'Posted']);
            });

            $msg = 'Purchase posted successfully!';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg]);
            }
            return redirect()->back()->with('success', $msg);
        } catch (\Throwable $e) {
            \Log::error('Purchase posting error: ' . $e->getMessage());
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return redirect()->back()->with('error', 'Posting failed: ' . $e->getMessage());
        }
    }

    private function performPosting(Purchase $purchase)
    {
        // 1. Stock Update
        foreach ($purchase->items as $item) {
            $product = Product::find($item->product_id);
            if ($product) {
                $product->stock = ($product->stock ?? 0) + $item->qty;
                $product->save();
            }
        }

        // 2. Ledger Update
        $amount = $purchase->net_amount;
        $type = strtolower(class_basename($purchase->purchasable_type));
        $party_id = $purchase->purchasable_id;

        if ($type === 'vendor') {
            $ledger = VendorLedger::where('vendor_id', $party_id)->latest('id')->first();
            if ($ledger) {
                $ledger->previous_balance = $ledger->closing_balance;
                $ledger->closing_balance  = $ledger->closing_balance + $amount;
                $ledger->save();
            } else {
                VendorLedger::create([
                    'vendor_id'        => $party_id,
                    'admin_or_user_id' => auth()->id(),
                    'date'             => $purchase->current_date,
                    'description'      => 'Purchase ID: ' . $purchase->id,
                    'previous_balance' => 0,
                    'closing_balance'  => $amount,
                    'opening_balance'  => $amount,
                ]);
            }
        } elseif ($type === 'customer' || $type === 'walkin') {
            $ledger = CustomerLedger::where('customer_id', $party_id)->latest('id')->first();
            if ($ledger) {
                $ledger->previous_balance = $ledger->closing_balance;
                $ledger->closing_balance  = $ledger->closing_balance + $amount;
                $ledger->save();
            } else {
                CustomerLedger::create([
                    'customer_id'      => $party_id,
                    'admin_or_user_id' => auth()->id(),
                    'previous_balance' => 0,
                    'closing_balance'  => $amount,
                    'opening_balance'  => $amount,
                ]);
            }
        }

        // 3. Account Allocations impact
        foreach ($purchase->accountAllocations as $allocation) {
            $account = Account::find($allocation->account_id);
            if ($account) {
                $account->opening_balance = ($account->opening_balance ?? 0) + $allocation->amount;
                $account->save();
            }
        }

        // 4. Vouchers
        if ($purchase->discount > 0) {
            Voucher::create([
                'voucher_type'  => 'Discount voucher',
                'date'          => now(),
                'sales_officer' => auth()->user()->name,
                'type'          => 'Credit',
                'person'        => $purchase->purchasable_id,
                'sub_head'      => 'Purchase Discount',
                'narration'     => 'Discount applied on Purchase ID: ' . $purchase->id,
                'amount'        => $purchase->discount
            ]);
        }

        if ($purchase->wht > 0) {
            Voucher::create([
                'voucher_type'  => 'Wht voucher',
                'date'          => now(),
                'sales_officer' => auth()->user()->name,
                'type'          => 'Credit',
                'person'        => $purchase->purchasable_id,
                'sub_head'      => 'WHT',
                'narration'     => 'WHT applied on Purchase ID: ' . $purchase->id,
                'amount'        => $purchase->wht
            ]);
        }

        // 5. Inward Update if exists
        if ($purchase->inward_id) {
            InwardGatepass::where('id', $purchase->inward_id)->update(['status' => 'linked']);
        }
    }
}
