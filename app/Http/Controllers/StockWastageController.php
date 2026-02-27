<?php

namespace App\Http\Controllers;

use App\Models\StockWastage;
use App\Models\StockWastageDetail;
use App\Models\Warehouse;
use App\Models\AccountHead;
use App\Models\Account;
use App\Models\Product;
use App\Models\ExpenseVoucher; // Assuming we use this for accounting
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StockWastageController extends Controller
{
    public function index(Request $request)
    {
        $query = StockWastage::with(['warehouse', 'account', 'accountHead', 'items.product'])->latest();

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $wastages = $query->get();
        return view('admin_panel.stock_wastage.index', compact('wastages'));
    }

    public function create()
    {
        $warehouses = Warehouse::all();
        // Fetch all heads to allow selection. User specificially asked for Expense A/c, 
        // but often 'Cost of Goods Sold' or other heads are used. 
        // We can filter if needed, but showing all gives flexibility.
        // Assuming user knows which head is for "Expense".
        // Or we can pre-filter for 'Expense' type if such column exists.
        // Let's pass all.
        $accountHeads = AccountHead::with('accounts')->get(); 
        
        $products = Product::all(); // Might fail if too many, but consistent with Purchase which uses search-products AJAX?
        // Purchase uses AJAX for search. I should probably use that too.
        // But for initial implementation I'll pass all if list is small, or use search.
        // User screenshot shows select box or input for Item.
        
        $gwnId = StockWastage::generateGWN();
        
        return view('admin_panel.stock_wastage.create', compact('warehouses', 'accountHeads', 'products', 'gwnId'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'warehouse_id' => 'required|exists:warehouses,id',
            'account_head_id' => 'required|exists:account_heads,id',
            'account_id' => 'required|exists:accounts,id',
            'product_id' => 'required|array|min:1',
            'product_id.*' => 'required|exists:products,id',
            'qty' => 'required|array',
            'qty.*' => 'required|numeric|min:0.01',
        ]);

        $status = $request->action === 'post' ? 'Posted' : 'Unposted';

        $savedWastage = null;

        try {
            DB::transaction(function () use ($request, $status, &$savedWastage) {
                // 1. Create Stock Wastage Header
                $wastage = StockWastage::create([
                    'gwn_id'          => $request->gwn_id,
                    'date'            => $request->date,
                    'warehouse_id'    => $request->warehouse_id,
                    'account_head_id' => $request->account_head_id,
                    'account_id'      => $request->account_id,
                    'ref_no'          => $request->ref_no,
                    'remarks'         => $request->remarks,
                    'total_amount'    => $request->grand_total ?? 0,
                    'status'          => $status,
                ]);

                $grandTotal = 0;

                // 2. Process Items
                foreach ($request->product_id as $index => $productId) {
                    if (empty($productId)) continue;

                    $qty    = $request->qty[$index]   ?? 0;
                    $price  = $request->price[$index]  ?? 0;
                    $amount = $qty * $price;

                    StockWastageDetail::create([
                        'stock_wastage_id' => $wastage->id,
                        'product_id'       => $productId,
                        'qty'              => $qty,
                        'price'            => $price,
                        'amount'           => $amount,
                    ]);

                    $grandTotal += $amount;

                    // 3. Stock Impact (Only if Posted)
                    if ($status === 'Posted') {
                        $product = Product::find($productId);
                        if ($product) {
                            $product->stock = ($product->stock ?? 0) - $qty;
                            $product->save();
                        }

                        $stock = \App\Models\Stock::where('warehouse_id', $request->warehouse_id)
                            ->where('product_id', $productId)
                            ->first();

                        if ($stock) {
                            $stock->qty = ($stock->qty ?? 0) - $qty;
                            $stock->save();
                        }
                    }
                }

                $wastage->update(['total_amount' => $grandTotal]);

                // 4. Accounting Impact (Only if Posted)
                if ($status === 'Posted' && $grandTotal > 0) {
                    $account = \App\Models\Account::find($request->account_id);
                    if ($account) {
                        $account->opening_balance = ($account->opening_balance ?? 0) + $grandTotal;
                        $account->save();
                    }
                }

                $savedWastage = $wastage;
            });

            $msg = 'Stock Wastage ' . ($status === 'Posted' ? 'Posted' : 'Saved') . ' successfully.';

            // ── AJAX save (no page reload) ──
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'id'      => $savedWastage->id,
                    'gwn_id'  => $savedWastage->gwn_id,
                    'status'  => $savedWastage->status,
                ]);
            }

            return redirect()->route('stock-wastage.index')->with('success', $msg);

        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
         // Optional: Show details
    }

    public function edit(StockWastage $stock_wastage)
    {
        if ($stock_wastage->status === 'Posted') {
            return redirect()->route('stock-wastage.index')->with('error', 'Posted entries cannot be edited.');
        }

        $warehouses = Warehouse::all();
        $accountHeads = AccountHead::with('accounts')->get();
        // Load the wastage items
        $stock_wastage->load('items.product');
        $gwnId = $stock_wastage->gwn_id;

        return view('admin_panel.stock_wastage.edit', compact('stock_wastage', 'warehouses', 'accountHeads', 'gwnId'));
    }

    public function update(Request $request, StockWastage $stock_wastage)
    {
        if ($stock_wastage->status === 'Posted') {
            return response()->json(['success' => false, 'message' => 'Cannot update posted record.'], 422);
        }

        $request->validate([
            'date' => 'required|date',
            'warehouse_id' => 'required|exists:warehouses,id',
            'account_head_id' => 'required|exists:account_heads,id',
            'account_id' => 'required|exists:accounts,id',
            'product_id' => 'required|array|min:1',
            'product_id.*' => 'required|exists:products,id',
            'qty' => 'required|array',
            'qty.*' => 'required|numeric|min:0.01',
        ]);

        $status = $request->action === 'post' ? 'Posted' : 'Unposted';

        try {
            DB::transaction(function () use ($request, $stock_wastage, $status) {
                // 1. Update Header
                $stock_wastage->update([
                    'date'            => $request->date,
                    'warehouse_id'    => $request->warehouse_id,
                    'account_head_id' => $request->account_head_id,
                    'account_id'      => $request->account_id,
                    'remarks'         => $request->remarks,
                    'status'          => $status,
                ]);

                // 2. Clear existing items (since it's unposted, no stock impact to reverse)
                $stock_wastage->items()->delete();

                $grandTotal = 0;

                // 3. Add Updated Items
                foreach ($request->product_id as $index => $productId) {
                    if (empty($productId)) continue;

                    $qty    = $request->qty[$index]   ?? 0;
                    $price  = $request->price[$index]  ?? 0;
                    $amount = $qty * $price;

                    StockWastageDetail::create([
                        'stock_wastage_id' => $stock_wastage->id,
                        'product_id'       => $productId,
                        'qty'              => $qty,
                        'price'            => $price,
                        'amount'           => $amount,
                    ]);

                    $grandTotal += $amount;

                    // 4. Stock Impact (Only if transitioning to Posted)
                    if ($status === 'Posted') {
                        $product = Product::find($productId);
                        if ($product) {
                            $product->stock = ($product->stock ?? 0) - $qty;
                            $product->save();
                        }

                        $stock = \App\Models\Stock::where('warehouse_id', $request->warehouse_id)
                            ->where('product_id', $productId)
                            ->first();

                        if ($stock) {
                            $stock->qty = ($stock->qty ?? 0) - $qty;
                            $stock->save();
                        }
                    }
                }

                $stock_wastage->update(['total_amount' => $grandTotal]);

                // 5. Accounting Impact (Only if transitioning to Posted)
                if ($status === 'Posted' && $grandTotal > 0) {
                    $account = \App\Models\Account::find($request->account_id);
                    if ($account) {
                        $account->opening_balance = ($account->opening_balance ?? 0) + $grandTotal;
                        $account->save();
                    }
                }
            });

            $msg = 'Stock Wastage ' . ($status === 'Posted' ? 'Posted' : 'Updated') . ' successfully.';

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'id'      => $stock_wastage->id,
                    'status'  => $stock_wastage->status,
                ]);
            }

            return redirect()->route('stock-wastage.index')->with('success', $msg);

        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    public function post(Request $request, $id)
    {
        $wastage = StockWastage::with('items')->findOrFail($id);

        if ($wastage->status === 'Posted') {
            return redirect()->back()->with('error', 'Already posted.');
        }

        try {
            DB::transaction(function () use ($wastage) {
                // Stock Impact
                foreach ($wastage->items as $item) {
                     // 1. Decrement Global Product Stock
                     $product = Product::find($item->product_id);
                     if ($product) {
                         $product->stock = ($product->stock ?? 0) - $item->qty;
                         $product->save();
                     }

                     // 2. Decrement Warehouse Specific Stock (stocks table)
                     $stock = \App\Models\Stock::where('warehouse_id', $wastage->warehouse_id)
                         ->where('product_id', $item->product_id)
                         ->first();
                     
                     if ($stock) {
                         $stock->qty = ($stock->qty ?? 0) - $item->qty;
                         $stock->save();
                     }
                }
                
                // Accounting Impact: Update Expense Account (opening_balance for consistency)
                if ($wastage->total_amount > 0) {
                    $account = \App\Models\Account::find($wastage->account_id);
                    if ($account) {
                        // Increment balance (opening_balance is used as current balance in VoucherController)
                        $account->opening_balance = ($account->opening_balance ?? 0) + $wastage->total_amount;
                        $account->save();
                    }
                }

                $wastage->status = 'Posted';
                $wastage->save();
            });
            $msg = 'Stock Wastage Posted successfully.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg]);
            }
            return redirect()->back()->with('success', $msg);
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return redirect()->back()->with('error', 'Error posting: ' . $e->getMessage());
        }
    }

    public function print($id)
    {
        $wastage = StockWastage::with(['warehouse', 'account', 'items.product'])->findOrFail($id);
        return view('admin_panel.stock_wastage.print', compact('wastage'));
    }

    public function destroy(StockWastage $stock_wastage)
    {
        if ($stock_wastage->status === 'Posted') {
             // If user wants to allow deleting posted, we should reverse stock.
             // But based on "uske bad nhi ho sakta", maybe we should prevent it?
             // Usually preventing delete of posted records is safer unless "Unpost" feature exists.
             // I'll return error for safety.
             return redirect()->back()->with('error', 'Cannot delete Posted record.');
        }

        DB::transaction(function () use ($stock_wastage) {
            // Only restore stock if it was deducted? 
            // My previous logic deducted only on 'Posted'.
            // So if Unposted, no stock deduction happened. So no restore needed.
            // Since we block deleting Posted above, we just delete the record here.
            
            $stock_wastage->delete(); // Soft delete
        });
        return redirect()->route('stock-wastage.index')->with('success', 'Stock Wastage deleted.');
    }
}
