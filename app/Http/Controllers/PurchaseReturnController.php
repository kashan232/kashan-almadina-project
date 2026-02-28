<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Product;
use App\Models\VendorLedger;
use App\Models\CustomerLedger;
use App\Models\Account;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseReturnController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseReturn::with(['purchasable', 'items.product']);

        if ($request->filled('start_date')) {
            $query->whereDate('current_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('current_date', '<=', $request->end_date);
        }

        $PurchaseReturns = $query->latest()->get();
        return view('admin_panel.purchase_return.index', compact('PurchaseReturns'));
    }

    public function create()
    {
        $nextInvoice = PurchaseReturn::generateReturnNo();
        $purchases = Purchase::where('status', 'Posted')->get(['id', 'invoice_no', 'purchasable_type', 'purchasable_id']);
        $vendors = \App\Models\Vendor::all();
        $customers = \App\Models\Customer::all();
        $warehouses = \App\Models\Warehouse::all();
        return view('admin_panel.purchase_return.add_return', compact('nextInvoice', 'purchases', 'vendors', 'customers', 'warehouses'));
    }

    public function getPurchaseDetails($invoice)
    {
        try {
            $purchase = Purchase::with(['items.product.latestPrice', 'purchasable', 'warehouse'])
                ->where('invoice_no', $invoice)
                ->first();

            if (!$purchase) {
                return response()->json(['error' => 'Purchase not found'], 404);
            }

            // Map items to include retail price and discount percent if possible
            $items = $purchase->items->map(function($item) {
                $product = $item->product;
                $pPrice = $product->latestPrice; 
                
                // Calculate discount percent if not stored (total_disc / (price * qty)) * 100
                $totalVal = $item->price * $item->qty;
                // If item_discount contains the percentage directly (from DB column item_discount)
                $discPercent = $item->item_discount ?? 0;
                
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $product->name ?? 'N/A',
                    'price' => $item->price,
                    'qty' => $item->qty,
                    'item_discount' => $item->item_discount,
                    'discount_percent' => $discPercent,
                    'retail_price' => $pPrice->purchase_retail_price ?? 0,
                ];
            });

            return response()->json([
                'purchase' => $purchase,
                'items' => $items,
                'party_name' => $purchase->purchasable->name ?? ($purchase->purchasable->customer_name ?? 'N/A'),
                'party_type' => class_basename($purchase->purchasable_type),
                'warehouse_id' => $purchase->warehouse_id,
                'warehouse_name' => $purchase->warehouse->warehouse_name ?? 'N/A',
                'wht' => $purchase->wht ?? 0,
                'wht_percent' => $purchase->wht_percent ?? 0,
                'wht_type' => $purchase->wht_type ?? 'percent'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'current_date' => 'required|date',
            'product_id' => 'required|array',
            'qty' => 'required|array',
            'warehouse_id' => 'required',
        ]);

        try {
            $result = DB::transaction(function () use ($request) {
                $purchaseId = $request->purchase_id;
                $invoiceNo = PurchaseReturn::generateReturnNo();
                
                $branch_id = \App\Models\Branch::first()->id ?? null; // Get first branch if exists
                $purchasable_type = null;
                $purchasable_id = null;
                $vendor_id = null;

                if ($purchaseId) {
                    $purchase = Purchase::findOrFail($purchaseId);
                    $branch_id = $purchase->branch_id;
                    $purchasable_type = $purchase->purchasable_type;
                    $purchasable_id = $purchase->purchasable_id;
                    $vendor_id = $purchase->vendor_id;
                } else {
                    // Manual Return Logic
                    if ($request->vendor_type == 'vendor') {
                        $purchasable_type = \App\Models\Vendor::class;
                        $purchasable_id = $request->party_id;
                        $vendor_id = $request->party_id;
                    } else {
                        $purchasable_type = \App\Models\Customer::class;
                        $purchasable_id = $request->party_id;
                    }
                }

                $purchaseReturn = PurchaseReturn::create([
                    'invoice_no'       => $invoiceNo,
                    'purchase_id'      => $purchaseId,
                    'branch_id'        => $branch_id,
                    'warehouse_id'     => $request->warehouse_id,
                    'purchasable_type' => $purchasable_type,
                    'purchasable_id'   => $purchasable_id,
                    'vendor_id'        => $vendor_id,
                    'current_date'     => $request->current_date,
                    'note'             => $request->remarks,
                    'subtotal'         => $request->subtotal,
                    'discount'         => $request->discount,
                    'wht'              => $request->wht,
                    'net_amount'       => $request->net_amount,
                    'status'           => 'Unposted',
                ]);

                foreach ($request->product_id as $index => $productId) {
                    $qty = $request->qty[$index];
                    if ($qty <= 0) continue;

                    $price = $request->price[$index];
                    $retail = $request->retail_price[$index] ?? 0;
                    $disc_percent = $request->discount_percent[$index] ?? 0;
                    $disc_amount = ($request->item_disc_amount[$index] ?? 0) * $qty;
                    $lineTotal = ($price * $qty) - $disc_amount;

                    PurchaseReturnItem::create([
                        'purchase_return_id' => $purchaseReturn->id,
                        'product_id'        => $productId,
                        'price'             => $price,
                        'retail_price'      => $retail,
                        'discount_percent'  => $disc_percent,
                        'item_discount'     => $disc_amount,
                        'qty'               => $qty,
                        'line_total'        => $lineTotal,
                    ]);
                }

                return $purchaseReturn; // return the instance
            });

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Purchase Return saved as Unposted!',
                    'id' => $result->id
                ]);
            }

            return redirect()->route('purchase.return.home')->with('success', 'Purchase Return saved as Unposted!');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Purchase Return Error: ' . $e->getMessage());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    public function post($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $ret = PurchaseReturn::with('items')->findOrFail($id);
                if ($ret->status === 'Posted') {
                    throw new \Exception("Already Posted");
                }

                foreach ($ret->items as $item) {
                    // 1. Stock Impact
                    $product = Product::find($item->product_id);
                    if ($product) {
                        $product->stock = ($product->stock ?? 0) - $item->qty;
                        $product->save();
                    }

                    $stock = \App\Models\WarehouseStock::where('product_id', $item->product_id)
                        ->where('warehouse_id', $ret->warehouse_id)
                        ->first();
                    if ($stock) {
                        $stock->decrement('quantity', $item->qty);
                    } else {
                        \App\Models\WarehouseStock::create([
                            'warehouse_id' => $ret->warehouse_id,
                            'product_id'   => $item->product_id,
                            'quantity'     => -$item->qty,
                        ]);
                    }
                }

                // 2. Ledger Impact
                $amount = $ret->net_amount;
                $pType = class_basename($ret->purchasable_type);
                $pId = $ret->purchasable_id;

                if ($pType === 'Vendor') {
                    $ledger = VendorLedger::where('vendor_id', $pId)->latest('id')->first();
                    if ($ledger) {
                        $ledger->previous_balance = $ledger->closing_balance;
                        $ledger->closing_balance  = $ledger->closing_balance - $amount;
                        $ledger->save();
                    } else {
                        VendorLedger::create([
                            'vendor_id'        => $pId,
                            'admin_or_user_id' => auth()->id(),
                            'date'             => $ret->current_date,
                            'description'      => 'Purchase Return Posted: ' . $ret->invoice_no,
                            'previous_balance' => 0,
                            'closing_balance'  => -$amount,
                            'opening_balance'  => -$amount,
                        ]);
                    }
                } elseif ($pType === 'Customer') {
                    $ledger = CustomerLedger::where('customer_id', $pId)->latest('id')->first();
                    if ($ledger) {
                        $ledger->previous_balance = $ledger->closing_balance;
                        $ledger->closing_balance  = $ledger->closing_balance - $amount;
                        $ledger->save();
                    } else {
                        CustomerLedger::create([
                            'customer_id'      => $pId,
                            'admin_or_user_id' => auth()->id(),
                            'previous_balance' => 0,
                            'closing_balance'  => -$amount,
                            'opening_balance'  => -$amount,
                        ]);
                    }
                }

                // 3. Vouchers
                if ($ret->discount > 0) {
                    Voucher::create([
                        'voucher_type'  => 'Purchase Return Discount',
                        'date'          => now(),
                        'sales_officer' => auth()->user()->name,
                        'type'          => 'Debit',
                        'person'        => $ret->purchasable_id,
                        'sub_head'      => 'Purchase Return Discount',
                        'narration'     => 'Discount on Purchase Return Posted: ' . $ret->invoice_no,
                        'amount'        => $ret->discount,
                    ]);
                }

                $ret->status = 'Posted';
                $ret->save();
            });

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Purchase Return Posted successfully and impacts applied!']);
            }
            return redirect()->back()->with('success', 'Purchase Return Posted successfully and impacts applied!');
        } catch (\Exception $e) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function print($id)
    {
        $ret = PurchaseReturn::with(['items.product', 'purchasable', 'warehouse'])->findOrFail($id);
        return view('admin_panel.purchase_return.print_return', compact('ret'));
    }
}
