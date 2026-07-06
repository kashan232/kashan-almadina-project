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
use App\Models\AccountHead;
use App\Models\JournalVoucher;
use App\Services\PartyLedgerService;
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
        $nextInvoice = SaleReturn::generateReturnNo();
        $sales = Sale::orderBy('id', 'desc')->get(['id', 'invoice_no', 'partyType', 'customer_id']);
        $vendors = \App\Models\Vendor::all();
        $customers = \App\Models\Customer::all();
        $warehouses = \App\Models\Warehouse::all();
        $accountHeads = AccountHead::all();
        return view('admin_panel.sale_return.add_return', compact('nextInvoice', 'sales', 'vendors', 'customers', 'warehouses', 'accountHeads'));
    }

    public function nextNumber()
    {
        return response()->json([
            'next' => SaleReturn::generateReturnNo(),
        ]);
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
                $fallbackPrice = $pPrice ? $pPrice->sale_net_amount : ($product->prices()->latest('start_date')->first()->sale_net_amount ?? 0);
                
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $product->name ?? 'N/A',
                    'sales_price' => (float)$item->sales_price > 0 ? $item->sales_price : $fallbackPrice,
                    'price' => (float)$item->sales_price > 0 ? $item->sales_price : $fallbackPrice,
                    'purchase_price' => $product->latestPrice->purchase_net_amount ?? 0,
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
                'discount_head' => $sale->discount_head,
                'discount_account_id' => $sale->discount_account_id,
                'discount_amount' => $sale->discount_amount,
                'discount_percent' => $sale->discount_percent,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'vendor_type' => 'required',
            'party_id' => 'required',
            'sale_id' => 'required_if:return_mode,invoice',
            'current_date' => 'required|date',
            'product_id' => 'required|array|min:1',
            'qty' => 'required|array',
            'warehouse_id' => 'required',
        ], [
            'vendor_type.required' => 'Please select Party Type.',
            'party_id.required' => 'Please select a Party.',
            'sale_id.required_if' => 'Please select a Sale Invoice.',
            'product_id.required' => 'Please add at least one item.',
            'warehouse_id.required' => 'Please select a Warehouse.',
        ]);

        $maxAttempts = 3;
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $result = DB::transaction(function () use ($request) {
                    $saleId = $request->sale_id;
                    $invoiceNo = SaleReturn::generateReturnNo(true);

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
                        'entry_date'       => $request->entry_date ?? date('Y-m-d'),
                        'entry_time'       => $request->entry_time ?? date('H:i'),
                        'current_date'     => $request->entry_date ?? date('Y-m-d'),
                        'remarks'          => $request->remarks,
                        'sub_total2'       => $request->subtotal,
                        'discount_amount'  => $request->discount,
                        'discount_head'    => $request->discount_head,
                        'discount_account_id' => $request->discount_account_id,
                        'total_balance'    => $request->net_amount,
                        'status'           => 'Unposted',
                    ]);

                    foreach ($request->product_id as $index => $productId) {
                        $qty = $request->qty[$index];
                        if ($qty <= 0) continue;

                        $price = $request->sales_price[$index];
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
            } catch (\Illuminate\Database\QueryException $e) {
                $lastException = $e;
                if ($attempt < $maxAttempts && isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1062) {
                    continue;
                }
                break;
            } catch (\Exception $e) {
                $lastException = $e;
                break;
            }
        }

        $e = $lastException ?? new \RuntimeException('Failed to save sale return.');
        \Illuminate\Support\Facades\Log::error('Sale Return Error: ' . $e->getMessage());

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
        return redirect()->back()->with('error', 'Error: ' . $e->getMessage())->withInput();
    }

    public function edit($id)
    {
        $returnData = SaleReturn::with(['items.product.latestPrice', 'sale'])->findOrFail($id);
        $nextInvoice = $returnData->invoice_no;
        $sales = Sale::orderBy('id', 'desc')->get(['id', 'invoice_no', 'partyType', 'customer_id']);
        $vendors = \App\Models\Vendor::all();
        $customers = \App\Models\Customer::all();
        $warehouses = \App\Models\Warehouse::all();
        $accountHeads = AccountHead::all();
        
        return view('admin_panel.sale_return.add_return', compact('returnData', 'nextInvoice', 'sales', 'vendors', 'customers', 'warehouses', 'accountHeads'));
    }

    public function show($id)
    {
        $returnData = SaleReturn::with(['items.product.latestPrice', 'sale'])->findOrFail($id);
        $nextInvoice = $returnData->invoice_no;
        $sales = Sale::orderBy('id', 'desc')->get(['id', 'invoice_no', 'partyType', 'customer_id']);
        $vendors = \App\Models\Vendor::all();
        $customers = \App\Models\Customer::all();
        $warehouses = \App\Models\Warehouse::all();
        $accountHeads = AccountHead::all();
        $viewMode = true;

        return view('admin_panel.sale_return.add_return', compact(
            'returnData',
            'nextInvoice',
            'sales',
            'vendors',
            'customers',
            'warehouses',
            'accountHeads',
            'viewMode'
        ));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'vendor_type' => 'required',
            'party_id' => 'required',
            'sale_id' => 'required_if:return_mode,invoice',
            'current_date' => 'required|date',
            'product_id' => 'required|array|min:1',
            'qty' => 'required|array',
            'warehouse_id' => 'required',
        ], [
            'vendor_type.required' => 'Please select Party Type.',
            'party_id.required' => 'Please select a Party.',
            'sale_id.required_if' => 'Please select a Sale Invoice.',
            'product_id.required' => 'Please add at least one item.',
            'warehouse_id.required' => 'Please select a Warehouse.',
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
                    'entry_date'   => $request->entry_date ?? date('Y-m-d'),
                    'entry_time'   => $request->entry_time ?? date('H:i'),
                    'current_date' => $request->entry_date ?? date('Y-m-d'),
                    'remarks'          => $request->remarks,
                    'sub_total2'       => $request->subtotal,
                    'discount_amount'  => $request->discount,
                    'discount_head'    => $request->discount_head,
                    'discount_account_id' => $request->discount_account_id,
                    'total_balance'    => $request->net_amount,
                ]);

                // Clear old items
                $saleReturn->items()->delete();

                foreach ($request->product_id as $index => $productId) {
                    $qty = $request->qty[$index];
                    if ($qty <= 0) continue;

                    $price = $request->sales_price[$index];
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

                // 2. Ledger Impact — SRJ matches GL (credit sub_total2, debit discount).
                $pType = $ret->party_type ?? 'customer';
                $pId = (int) $ret->customer_id;
                $subTotal = (float) ($ret->sub_total2 ?? 0);
                $discount = (float) ($ret->discount_amount ?? 0);

                app(PartyLedgerService::class)->postSaleReturn(
                    $pType,
                    $pId,
                    $subTotal,
                    $discount,
                    $ret->entry_date ?: $ret->current_date,
                    $ret->invoice_no
                );

                // 3. Order discount — same account heads as sale (JournalVoucher for GL)
                if ($ret->discount_amount > 0) {
                    if ($ret->discount_account_id) {
                        $discountAccount = Account::with('head')->find($ret->discount_account_id);
                        if ($discountAccount) {
                            $discountAccount->opening_balance = ($discountAccount->opening_balance ?? 0) - $ret->discount_amount;
                            $discountAccount->save();

                            JournalVoucher::create([
                                'jvid' => 'SR-DISC-' . $ret->invoice_no,
                                'entry_date' => $ret->entry_date ?: $ret->current_date,
                                'status' => 'posted',
                                'total_debit' => $ret->discount_amount,
                                'total_credit' => $ret->discount_amount,
                                'party_type' => json_encode([$pType, (string) $discountAccount->head_id]),
                                'party_id' => json_encode([$pId, $discountAccount->id]),
                                'debit' => json_encode([$ret->discount_amount, 0]),
                                'credit' => json_encode([0, $ret->discount_amount]),
                                'remarks' => 'Discount on Sale Return: ' . $ret->invoice_no . ' ; ' . ($discountAccount->head->name ?? 'Head') . ' ; ' . ($discountAccount->title ?? 'Subhead'),
                            ]);
                        }
                    } else {
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
                }

                $ret->status = 'Posted';
                $ret->save();
            });

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sale Return Posted successfully and impacts applied!',
                    'return_id' => (int) $id,
                    'invoice_url' => route('sale.return.invoice', $id),
                    'dc_url' => route('sale.return.dc', $id),
                ]);
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
        $ret = SaleReturn::with(['items.product', 'sale'])->findOrFail($id);
        return view('admin_panel.sale_return.print_return', compact('ret'));
    }

    public function invoice($id)
    {
        $ret = SaleReturn::with(['items.product', 'sale'])->findOrFail($id);
        return view('admin_panel.sale_return.prints.invoice', compact('ret'));
    }

    public function dc($id)
    {
        $ret = SaleReturn::with(['items.product', 'sale'])->findOrFail($id);
        return view('admin_panel.sale_return.prints.dc', compact('ret'));
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
