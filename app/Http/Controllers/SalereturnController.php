<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Product;
use App\Models\VendorLedger;
use App\Models\CustomerLedger;
use App\Models\Account;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaleReturnController extends Controller
{
    public function index(Request $request)
    {
        $query = SaleReturn::with(['items.product']);

        if ($request->filled('start_date')) {
            $query->whereDate('current_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('current_date', '<=', $request->end_date);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $SaleReturns = $query->orderBy('id', 'desc')->get();
        return view('admin_panel.sale_return.index', compact('SaleReturns'));
    }

    public function create()
    {
        $nextInvoice = $this->generateReturnNo();
        $sales = Sale::get(['id', 'invoice_no', 'partyType', 'customer_id']);
        $vendors = \App\Models\Vendor::all();
        $customers = \App\Models\Customer::all();
        $warehouses = \App\Models\Warehouse::all();
        return view('admin_panel.sale_return.add_return', compact('nextInvoice', 'sales', 'vendors', 'customers', 'warehouses'));
    }

    private function generateReturnNo()
    {
        $lastReturn = SaleReturn::orderBy('id', 'desc')->first();
        if (!$lastReturn) return 'SR-1';
        $parts = explode('-', $lastReturn->invoice_no);
        $num = intval(end($parts)) + 1;
        return 'SR-' . $num;
    }

    public function getSaleDetails($invoice)
    {
        try {
            $sale = Sale::with(['items.product.latestPrice', 'items.warehouse'])
                ->where('invoice_no', $invoice)
                ->first();

            if (!$sale) {
                return response()->json(['error' => 'Sale not found'], 404);
            }

            // Map items
            $items = $sale->items->map(function($item) {
                $product = $item->product;
                $pPrice = $product->latestPrice; 
                
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $product->name ?? 'N/A',
                    'price' => $item->sales_price,
                    'qty' => $item->sales_qty,
                    'item_discount' => $item->discount_amount,
                    'discount_percent' => $item->discount_percent,
                    'retail_price' => $item->retail_price,
                    'warehouse_id' => $item->warehouse_id,
                    'warehouse_name' => $item->warehouse->warehouse_name ?? 'N/A',
                ];
            });

            // Get party info
            $party_name = 'N/A';
            if ($sale->partyType == 'vendor') {
                $party_name = \App\Models\Vendor::find($sale->customer_id)->name ?? 'N/A';
            } elseif ($sale->partyType == 'customer') {
                $party_name = \App\Models\Customer::find($sale->customer_id)->customer_name ?? 'N/A';
            } else {
                $party_name = \App\Models\Customer::find($sale->customer_id)->customer_name ?? 'Walk-in Customer';
            }

            $warehouse_id = $sale->items->first()->warehouse_id ?? null;

            return response()->json([
                'sale' => $sale,
                'items' => $items,
                'party_name' => $party_name,
                'party_type' => $sale->partyType,
                'customer_id' => $sale->customer_id,
                'warehouse_id' => $warehouse_id,
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
                $saleId = $request->sale_id;
                $invoiceNo = $this->generateReturnNo();
                
                $party_type = $request->vendor_type;
                $customer_id = $request->party_id;

                if ($saleId) {
                    $sale = Sale::findOrFail($saleId);
                    $party_type = $sale->partyType;
                    $customer_id = $sale->customer_id;
                }

                $saleReturn = SaleReturn::create([
                    'invoice_no'       => $invoiceNo,
                    'sale_id'          => $saleId,
                    'party_type'       => $party_type,
                    'customer_id'      => $customer_id,
                    'current_date'     => $request->current_date,
                    'remarks'          => $request->remarks,
                    'sub_total2'       => $request->subtotal, // Use sub_total2 as net matching
                    'discount_amount'  => $request->discount,
                    'total_balance'    => $request->net_amount,
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
                    $whId = $request->warehouse_id;

                    SaleReturnItem::create([
                        'sale_return_id'    => $saleReturn->id,
                        'warehouse_id'      => $whId,
                        'product_id'        => $productId,
                        'sales_price'       => $price,
                        'retail_price'      => $retail,
                        'discount_percent'  => $disc_percent,
                        'discount_amount'   => $disc_amount,
                        'sales_qty'         => $qty,
                        'amount'            => $lineTotal,
                    ]);
                }

                return $saleReturn;
            });

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sale Return saved as Unposted!',
                    'id' => $result->id,
                    'invoice_no' => $result->invoice_no
                ]);
            }

            return redirect()->route('sale.return.home')->with('success', 'Sale Return saved as Unposted!');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Sale Return Error: ' . $e->getMessage());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    public function edit($id)
    {
        $returnData = SaleReturn::with(['items.product.latestPrice'])->findOrFail($id);
        $nextInvoice = $returnData->invoice_no;
        $sales = Sale::get(['id', 'invoice_no', 'partyType', 'customer_id']);
        $vendors = \App\Models\Vendor::all();
        $customers = \App\Models\Customer::all();
        $warehouses = \App\Models\Warehouse::all();
        
        return view('admin_panel.sale_return.add_return', compact('returnData', 'nextInvoice', 'sales', 'vendors', 'customers', 'warehouses'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'current_date' => 'required|date',
            'product_id' => 'required|array',
            'qty' => 'required|array',
            'warehouse_id' => 'required',
        ]);

        try {
            $result = DB::transaction(function () use ($request, $id) {
                $saleReturn = SaleReturn::findOrFail($id);
                if ($saleReturn->status === 'Posted') {
                    throw new \Exception("Cannot edit a posted return.");
                }

                $saleId = $request->sale_id;
                $party_type = $request->vendor_type;
                $customer_id = $request->party_id;

                if ($saleId) {
                    $sale = Sale::findOrFail($saleId);
                    $party_type = $sale->partyType;
                    $customer_id = $sale->customer_id;
                }

                $saleReturn->update([
                    'sale_id'          => $saleId,
                    'party_type'       => $party_type,
                    'customer_id'      => $customer_id,
                    'current_date'     => $request->current_date,
                    'remarks'          => $request->remarks,
                    'sub_total2'       => $request->subtotal,
                    'discount_amount'  => $request->discount,
                    'total_balance'    => $request->net_amount,
                ]);

                // Clear old items
                $saleReturn->items()->delete();

                foreach ($request->product_id as $index => $productId) {
                    $qty = $request->qty[$index];
                    if ($qty <= 0) continue;

                    $price = $request->price[$index];
                    $retail = $request->retail_price[$index] ?? 0;
                    $disc_percent = $request->discount_percent[$index] ?? 0;
                    $disc_amount = ($request->item_disc_amount[$index] ?? 0) * $qty;
                    $lineTotal = ($price * $qty) - $disc_amount;
                    $whId = $request->warehouse_id;

                    SaleReturnItem::create([
                        'sale_return_id'    => $saleReturn->id,
                        'warehouse_id'      => $whId,
                        'product_id'        => $productId,
                        'sales_price'       => $price,
                        'retail_price'      => $retail,
                        'discount_percent'  => $disc_percent,
                        'discount_amount'   => $disc_amount,
                        'sales_qty'         => $qty,
                        'amount'            => $lineTotal,
                    ]);
                }

                return $saleReturn;
            });

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sale Return updated successfully!',
                    'id' => $result->id
                ]);
            }

            return redirect()->route('sale.return.home')->with('success', 'Sale Return updated successfully!');
        } catch (\Exception $e) {
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
                $ret = SaleReturn::with('items')->findOrFail($id);
                if ($ret->status === 'Posted') {
                    throw new \Exception("Already Posted");
                }

                foreach ($ret->items as $item) {
                    // Stock Logic: 0 = Shop, >0 = Warehouse
                    $qty = $item->sales_qty;
                    if ($item->warehouse_id == 0) {
                        // Shop Stock
                        $product = Product::find($item->product_id);
                        if ($product) {
                            $product->stock = ($product->stock ?? 0) + $qty;
                            $product->save();
                        }
                    } else {
                        // Warehouse Stock
                        $stock = \App\Models\WarehouseStock::where('product_id', $item->product_id)
                            ->where('warehouse_id', $item->warehouse_id)
                            ->first();
                        if ($stock) {
                            $stock->increment('quantity', $qty);
                        } else {
                            \App\Models\WarehouseStock::create([
                                'warehouse_id' => $item->warehouse_id,
                                'product_id'   => $item->product_id,
                                'quantity'     => $qty,
                            ]);
                        }
                    }
                }

                // 2. Ledger Impact (Customer receives credit / balance decreases)
                $amount = $ret->total_balance;
                $pType = $ret->party_type;
                $pId = $ret->customer_id;

                if ($pType === 'vendor') {
                    $ledger = VendorLedger::where('vendor_id', $pId)->latest('id')->first();
                    if ($ledger) {
                        // For vendor, sale return means they owe us less
                        $ledger->previous_balance = $ledger->closing_balance;
                        $ledger->closing_balance  = $ledger->closing_balance - $amount;
                        $ledger->save();
                    } else {
                        VendorLedger::create([
                            'vendor_id'        => $pId,
                            'admin_or_user_id' => auth()->id(),
                            'date'             => $ret->current_date,
                            'description'      => 'Sale Return Posted: ' . $ret->invoice_no,
                            'previous_balance' => 0,
                            'closing_balance'  => -$amount,
                            'opening_balance'  => -$amount,
                        ]);
                    }
                } elseif ($pType === 'customer' || $pType === 'walking') {
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

                // 3. Vouchers for Discount if any
                if ($ret->discount_amount > 0) {
                    Voucher::create([
                        'voucher_type'  => 'Sale Return Discount',
                        'date'          => now(),
                        'sales_officer' => auth()->user()->name ?? 'Admin',
                        'type'          => 'Credit',
                        'person'        => $ret->customer_id,
                        'sub_head'      => 'Sale Return Discount',
                        'narration'     => 'Discount on Sale Return Posted: ' . $ret->invoice_no,
                        'amount'        => $ret->discount_amount,
                    ]);
                }

                $ret->status = 'Posted';
                $ret->save();
            });

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Sale Return Posted successfully and impacts applied!']);
            }
            return redirect()->back()->with('success', 'Sale Return Posted successfully and impacts applied!');
        } catch (\Exception $e) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function print($id)
    {
        $ret = SaleReturn::with(['items.product'])->findOrFail($id);
        return view('admin_panel.sale_return.print_return', compact('ret'));
    }

    public function destroy($id)
    {
        try {
            $ret = SaleReturn::findOrFail($id);
            if ($ret->status === 'Posted') {
                return redirect()->back()->with('error', 'Cannot delete a posted return.');
            }
            $ret->items()->delete();
            $ret->delete();
            return redirect()->back()->with('success', 'Sale Return deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
