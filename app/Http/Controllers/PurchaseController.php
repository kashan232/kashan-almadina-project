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
use App\Models\JournalVoucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\User;


class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = Purchase::with(['vendor', 'warehouse', 'purchasable', 'items.product', 'user']);

        if ($request->filled('start_date')) {
            $query->whereDate('current_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('current_date', '<=', $request->end_date);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('user_id')) {
            $query->where('created_by', $request->user_id);
        }

        $Purchase = $query->orderBy('current_date', 'desc')->latest()->get();
        $users = User::orderBy('name')->get();
        return view("admin_panel.purchase.index", compact('Purchase', 'users'));
    }
    public function add_purchase()
    {
        $Vendor = Vendor::get();
        $Warehouse = Warehouse::get();
        $AccountHeads = AccountHead::get();
        $customers = Customer::all();

        // ---------- GET LAST INVOICE ----------
        $nextInvoice = Purchase::generateInvoiceNo();

        $expenseAccounts = Account::where('head_id', 1)->get();

        // Pass this to the view
        return view(
            'admin_panel.purchase.add_purchase',
            compact('Vendor', "Warehouse", 'AccountHeads', 'customers', 'nextInvoice', 'expenseAccounts')
        );
    }



    public function store(Request $request)
    {
        // 1) Server-side validation (accounts removed from required rules)
        $rules = [
            'vendor_type'         => 'required|string',
            'vendor_id'           => 'required|integer',
            'warehouse_id'        => 'required|integer',
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
            'vendor_type.required'  => 'Please select Type.',
            'vendor_id.required'    => 'Please select Party.',
            'warehouse_id.required' => 'Please select Warehouse or Shop.',
                        'product_id.required'   => 'Please add at least one Item.',
            'product_id.*.exists'   => 'One or more selected products are invalid.',
            'qty.*.required'        => 'Please provide quantity for each item.',
            'qty.*.min'             => 'Quantity must be at least 1.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        
        $validator->after(function ($validator) use ($request) {
            $amounts = $request->input('account_amount', []);
            $heads = $request->input('account_head_id', []);
            $subs = $request->input('account_id', []);

            foreach ($amounts as $key => $amount) {
                if (floatval($amount) > 0) {
                    if (empty($heads[$key])) {
                        $validator->errors()->add("account_head_id.$key", "Account Head is required if amount is given.");
                    }
                    if (empty($subs[$key])) {
                        $validator->errors()->add("account_id.$key", "Account is required if amount is given.");
                    }
                }
            }
        });

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

        // Security check for Shop Stock
        if ($request->warehouse_id == 0 && !auth()->user()->canAccessShop()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized access to Shop Stock.'], 403);
            }
            return redirect()->back()->with('error', 'Unauthorized access to Shop Stock.');
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
                    'entry_date'       => $request['entry_date'] ?? date('Y-m-d'),
                    'entry_time'       => $request['entry_time'] ?? date('H:i'),
                    'current_date'     => $request['entry_date'] ?? date('Y-m-d'),
                    'dc_date'          => $request['dc_date'] ?? null,
                    'dc'               => $request['dc'] ?? null,
                    'bilty_no'         => $request['bilty_no'] ?? null,
                    'note'             => $request['remarks'] ?? null,
                    'subtotal'         => $request->subtotal,
                    'discount'         => $request->discount,
                    'wht'              => $request->wht,
                    'wht_percent'      => $request->wht_percent,
                    'wht_type'         => $request->wht_type,
                    'wht_account_id'   => $request->wht_account_id,
                    'net_amount'       => $request->net_amount,
                    'branch_id'        => auth()->user()->branch_id ?? 1,
                    'created_by'       => auth()->id(),
                ]);

                $subtotal = 0;

                // Purchase items
                foreach ($request->product_id as $index => $productId) {
                    if (empty($productId)) continue;

                    $qty  = $request->qty[$index] ?? 0;
                    $price = $request->price[$index] ?? 0;
                    $disc = $request->item_disc[$index] ?? 0;
                    $lineTotal = ($price * $qty) - ($request->item_disc_amount[$index] ?? 0);
                    $rate = ($qty > 0) ? ($lineTotal / $qty) : $price;

                    \App\Models\PurchaseItem::create([
                        'purchase_id'   => $purchase->id,
                        'product_id'    => $productId,
                        'price'         => $price,
                        'purchase_rate' => $rate,
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
                'entry_date'      => $request['entry_date'] ?? date('Y-m-d'),
                'entry_time'      => $request['entry_time'] ?? date('H:i'),
                'current_date'    => $request['entry_date'] ?? date('Y-m-d'),
                'dc_date'         => $request['dc_date'] ?? null,
                'note'            => $request['remarks'] ?? null,
                'subtotal'        => $request->subtotal,
                'discount'        => $request->discount,
                'wht'             => $request->wht,
                'wht_percent'     => $request->wht_percent,
                'wht_type'        => $request->wht_type,
                'wht_account_id'  => $request->wht_account_id,
                'net_amount'      => $request->net_amount,
                'branch_id'       => auth()->user()->branch_id ?? 1,
                'inward_id'       => $request->inward_id, // link inward
                'created_by'      => auth()->id(),
            ]);

            $subtotal = 0;
            // 2️⃣ Save Purchase Items
            foreach ($request['product_id'] as $index => $productId) {
                if (!$productId) continue;

                $qty       = $request['qty'][$index];
                $price     = $request['purchase_retail_price'][$index]; // ✅ retail
                $discAmt   = $request['item_disc_amount'][$index] ?? 0;
                $lineTotal = ($price * $qty) - $discAmt;
                $rate      = ($qty > 0) ? ($lineTotal / $qty) : $price;

                PurchaseItem::create([
                    'purchase_id'   => $purchase->id,
                    'product_id'    => $productId,
                    'price'         => $price,
                    'purchase_rate' => $rate,
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
        
        $expenseAccounts = Account::where('head_id', 1)->get();
        
        // Use existing invoice number
        $nextInvoice = $purchase->invoice_no;
        
        return view('admin_panel.purchase.add_purchase', compact(
            'purchase', 
            'Vendor', 
            'customers',
            'Warehouse', 
            'AccountHeads',
            'nextInvoice',
            'expenseAccounts'
        ));
    }

    public function update(Request $request, $id)
    {
        // Use same validation as store
        $rules = [
            'vendor_type'         => 'required|string',
            'vendor_id'           => 'required|integer',
            'warehouse_id'        => 'required|integer',
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
            'vendor_type.required'  => 'Please select Type.',
            'vendor_id.required'    => 'Please select Party.',
            'warehouse_id.required' => 'Please select Warehouse or Shop.',
                        'product_id.required'   => 'Please add at least one Item.',
            'product_id.*.exists'   => 'One or more selected products are invalid.',
            'qty.*.required'        => 'Please provide quantity for each item.',
            'qty.*.min'             => 'Quantity must be at least 1.',
        ];

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules, $messages);
        
        $validator->after(function ($validator) use ($request) {
            $amounts = $request->input('account_amount', []);
            $heads = $request->input('account_head_id', []);
            $subs = $request->input('account_id', []);

            foreach ($amounts as $key => $amount) {
                if (floatval($amount) > 0) {
                    if (empty($heads[$key])) {
                        $validator->errors()->add("account_head_id.$key", "Account Head is required if amount is given.");
                    }
                    if (empty($subs[$key])) {
                        $validator->errors()->add("account_id.$key", "Account is required if amount is given.");
                    }
                }
            }
        });

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

        // Security check for Shop Stock
        if ($request->warehouse_id == 0 && !auth()->user()->canAccessShop()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized access to Shop Stock.'], 403);
            }
            return redirect()->back()->with('error', 'Unauthorized access to Shop Stock.');
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

                if ($purchase->status === 'Posted') {
                    // Reverse the previous posting before updating
                    $this->reversePosting($purchase);
                }

                // Update purchase header
                $purchase->update([
                    'status'           => 'Unposted',
                    'warehouse_id'     => $request['warehouse_id'],
                    'vendor_id'        => $request['vendor_id'],
                    'purchasable_type' => $typeMap[$typeKey] ?? null,
                    'purchasable_id'   => $request['vendor_id'],
                    'entry_date'       => $request['entry_date'] ?? date('Y-m-d'),
                    'entry_time'       => $request['entry_time'] ?? date('H:i'),
                    'current_date'     => $request['entry_date'] ?? date('Y-m-d'),
                    'dc_date'          => $request['dc_date'] ?? null,
                    'dc'               => $request['dc'] ?? null,
                    'bilty_no'         => $request['bilty_no'] ?? null,
                    'note'             => $request['remarks'] ?? null,
                    'subtotal'         => $request->subtotal,
                    'discount'         => $request->discount,
                    'wht'              => $request->wht,
                    'wht_percent'      => $request->wht_percent,
                    'wht_type'         => $request->wht_type,
                    'wht_account_id'   => $request->wht_account_id,
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
                    $rate = ($qty > 0) ? ($lineTotal / $qty) : $price;

                    \App\Models\PurchaseItem::create([
                        'purchase_id'   => $purchase->id,
                        'product_id'    => $productId,
                        'price'         => $price,
                        'purchase_rate' => $rate,
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
        $purchase   = Purchase::with(['vendor', 'warehouse', 'items.product', 'accountAllocations.account', 'whtAccount'])->findOrFail($id);
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
            $query->where('customer_type', '!=', 'Walking Customer');
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
            $productId = $item->product_id;
            $qty = (float)($item->qty ?? 0);
            
            if ($purchase->warehouse_id == 0) {
                // UPDATE SHOP STOCK
                $product = Product::find($productId);
                if ($product) {
                    $product->stock = ($product->stock ?? 0) + $qty;
                    $product->save();
                }
            } else {
                // UPDATE WAREHOUSE STOCK
                $stock = \App\Models\WarehouseStock::firstOrNew([
                    'warehouse_id' => $purchase->warehouse_id,
                    'product_id'   => $productId,
                ]);
                $stock->quantity = ($stock->quantity ?? 0) + $qty;
                $stock->save();
            }
        }

        // 2. Ledger Impact (CONSOLIDATED UPDATE)
        // We post the GROSS amount (Subtotal) first, then deductions follow.
        $grossAmount = $purchase->subtotal;
        $type        = strtolower(class_basename($purchase->purchasable_type));
        $party_id    = $purchase->purchasable_id;

        $ledgerModel = null;
        $partyCol = '';
        if ($type === 'vendor') {
            $ledgerModel = \App\Models\VendorLedger::class;
            $partyCol = 'vendor_id';
        } elseif ($type === 'customer') {
            $ledgerModel = \App\Models\CustomerLedger::class;
            $partyCol = 'customer_id';
        } elseif ($type === 'subcustomer') {
            $ledgerModel = \App\Models\SubCustomerLedger::class;
            $partyCol = 'sub_customer_id';
        }

        if ($ledgerModel) {
            // Find existing ledger record (ONLY ONE PER PARTY)
            $ledger = $ledgerModel::where($partyCol, $party_id)->first();
            
            // The user requires the NET AMOUNT to be the impact on the ledger.
            // Net Amount = Subtotal - Discount/Allocations + WHT
            $impact = (float)($purchase->net_amount ?? 0);

            if ($ledger) {
                $ledger->update([
                    'previous_balance' => $ledger->closing_balance,
                    'closing_balance'  => $ledger->closing_balance + $impact,
                    'date'             => $purchase->current_date,
                    'description'      => 'Purchase: ' . $purchase->invoice_no . ' (Consolidated Update)',
                ]);
            } else {
                $ledgerModel::create([
                    $partyCol => $party_id,
                    'admin_or_user_id' => auth()->id(),
                    'date' => $purchase->current_date,
                    'description' => 'Purchase: ' . $purchase->invoice_no,
                    'opening_balance' => 0,
                    'previous_balance' => 0,
                    'debit' => 0,
                    'credit' => $impact,
                    'closing_balance' => $impact,
                ]);
            }

            // --- Secondary Impacts (JV & Account Balances) ---
            
            // Determine Party Type for JV (vendor, customer, or walkin)
            $partyType = strtolower(class_basename($purchase->purchasable_type));
            
            // B. Post Discount Impact (Order-level Discount) removed as requested
            
            // C. Post WHT Account Impact (Tax)
            // The user requires WHT to be on the CREDIT side for both Vendor and WHT Account.
            if ($purchase->wht > 0 && $purchase->wht_account_id) {
                $whtAccount = Account::find($purchase->wht_account_id);
                $purchaseExpAccId = 6; // Pur-expnse
                
                if ($whtAccount) {
                    // Update WHT account balance (Debit increases Asset)
                    $whtAccount->opening_balance = ($whtAccount->opening_balance ?? 0) + $purchase->wht;
                    $whtAccount->save();

                    JournalVoucher::create([
                        'jvid' => 'PJ-WHT-' . $purchase->invoice_no,
                        'entry_date' => $purchase->current_date,
                        'status' => 'posted',
                        'total_debit' => $purchase->wht,
                        'total_credit' => $purchase->wht,
                        'party_type' => json_encode([$partyType, (string)$whtAccount->head_id]),
                        'party_id' => json_encode([$purchase->purchasable_id, $whtAccount->id]),
                        'debit' => json_encode([0, $purchase->wht]), // Debit WHT Account
                        'credit' => json_encode([$purchase->wht, 0]), // Credit Vendor
                        'remarks' => $whtAccount->title ?? 'WHT (Tax)',
                    ]);
                }
            }

            // D. Post Account Allocations Impact (Total Discount)
            foreach ($purchase->accountAllocations as $allocation) {
                $account = $allocation->account;
                $purchaseExpAccId = 6; // Pur-expnse
                
                if ($account) {
                    // Update account balance (Credit decreases asset/increases liability)
                    $account->opening_balance = ($account->opening_balance ?? 0) - $allocation->amount; 
                    $account->save();
                    
                    JournalVoucher::create([
                        'jvid' => 'PJ-ALLOC-' . $purchase->invoice_no,
                        'entry_date' => $purchase->current_date,
                        'status' => 'posted',
                        'total_debit' => $allocation->amount,
                        'total_credit' => $allocation->amount,
                        'party_type' => json_encode([$partyType, (string)$account->head_id]), 
                        'party_id' => json_encode([$purchase->purchasable_id, $account->id]),
                        'debit' => json_encode([$allocation->amount, 0]), // Debit Vendor
                        'credit' => json_encode([0, $allocation->amount]), // Credit Account
                        'remarks' => $account->title ?? 'Allocation',
                    ]);
                }
            }
        }

        // 6. Inward Update if exists
        if ($purchase->inward_id) {
            InwardGatepass::where('id', $purchase->inward_id)->update(['status' => 'linked']);
        }
    }

    private function reversePosting(Purchase $purchase)
    {
        // 1. Reverse Stock
        foreach ($purchase->items as $item) {
            $productId = $item->product_id;
            $qty = (float)($item->qty ?? 0);
            
            if ($purchase->warehouse_id == 0) {
                $product = Product::find($productId);
                if ($product) {
                    $product->stock = ($product->stock ?? 0) - $qty;
                    $product->save();
                }
            } else {
                $stock = \App\Models\WarehouseStock::where('warehouse_id', $purchase->warehouse_id)
                    ->where('product_id', $productId)->first();
                if ($stock) {
                    $stock->quantity = ($stock->quantity ?? 0) - $qty;
                    $stock->save();
                }
            }
        }

        // 2. Reverse Ledger Impact
        $impact = (float)($purchase->net_amount ?? 0);
        $type = strtolower(class_basename($purchase->purchasable_type));
        $party_id = $purchase->purchasable_id;

        $ledgerModel = null;
        $partyCol = '';
        if ($type === 'vendor') {
            $ledgerModel = \App\Models\VendorLedger::class;
            $partyCol = 'vendor_id';
        } elseif ($type === 'customer') {
            $ledgerModel = \App\Models\CustomerLedger::class;
            $partyCol = 'customer_id';
        } elseif ($type === 'subcustomer') {
            $ledgerModel = \App\Models\SubCustomerLedger::class;
            $partyCol = 'sub_customer_id';
        }

        if ($ledgerModel) {
            $ledger = $ledgerModel::where($partyCol, $party_id)->first();
            if ($ledger) {
                $ledger->update([
                    'closing_balance'  => $ledger->closing_balance - $impact,
                ]);
            }
        }

        // 3. Reverse WHT Account Impact
        if ($purchase->wht > 0 && $purchase->wht_account_id) {
            $whtAccount = Account::find($purchase->wht_account_id);
            if ($whtAccount) {
                $whtAccount->opening_balance = ($whtAccount->opening_balance ?? 0) - $purchase->wht;
                $whtAccount->save();
            }
        }

        // 4. Reverse Account Allocations Impact
        foreach ($purchase->accountAllocations as $allocation) {
            $account = $allocation->account;
            if ($account) {
                $account->opening_balance = ($account->opening_balance ?? 0) + $allocation->amount; 
                $account->save();
            }
        }

        // 5. Delete Journal Vouchers
        JournalVoucher::where('jvid', 'PJ-WHT-' . $purchase->invoice_no)->delete();
        JournalVoucher::where('jvid', 'PJ-ALLOC-' . $purchase->invoice_no)->delete();

        // 6. Reverse Inward Status if exists
        if ($purchase->inward_id) {
            InwardGatepass::where('id', $purchase->inward_id)->update(['status' => 'pending']);
        }
    }
}
