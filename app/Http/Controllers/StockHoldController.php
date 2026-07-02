<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Models\StockHold;
use App\Models\StockHoldVoucher;
use App\Models\StockRelease;
use App\Models\Warehouse;
use App\Models\Vendor;
use App\Models\Customer;
use App\Models\AccountHead;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StockHoldController extends Controller
{
    // store holds from the form submission
    public function stockholdlist(Request $request)
    {
        $query = StockHoldVoucher::with([
            'warehouse:id,warehouse_name',
            'partyCustomer:id,customer_name',
            'partyVendor:id,name',
            'items.product'
        ]);

        if ($request->start_date) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->whereDate('date', '<=', $request->end_date);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $vouchers = $query->latest()->get();

        return view("admin_panel.stock_hold.stock_hold_list", compact('vouchers'));
    }




    public function create()
    {
        $warehouses = Warehouse::orderBy('warehouse_name')->get();
        $products = Product::select('id', 'name')->orderBy('name')->get();
        return view('admin_panel.stock_hold.create_stock_hold', compact('warehouses', 'products'));
    }

    public function partyList(Request $request)
    {
        $type = $request->type; // vendor, customer, walkin
        if ($type === 'vendor') {
            return Vendor::orderBy('name')->get()->map(fn($v) => ['id' => $v->id, 'text' => $v->name]);
        }
        
        $customerType = ($type === 'walkin' || $type === 'walking') ? 'Walking Customer' : 'Main Customer';
        
        return Customer::where('customer_type', $customerType)
            ->orderBy('customer_name')
            ->get()
            ->map(fn($c) => ['id' => $c->id, 'text' => $c->customer_name]);
    }

    public function partyInvoices(Request $request, $partyId)
    {
        $type = $request->type;
        if ($type === 'walkin') {
            $type = 'walking';
        }
        
        // In this project, 'sales' table uses 'customer_id' and 'partyType' (camelCase)
        $invoices = Sale::where('customer_id', $partyId)
            ->where('partyType', $type)
            ->latest()
            ->get();

        return $invoices->map(fn($s) => [
            'id' => $s->id, 
            'text' => $s->invoice_no . ' (' . ($s->created_at ? $s->created_at->format('Y-m-d') : '-') . ')'
        ]);
    }

    public function invoiceItems($id)
    {
        $warehouse_id = null;
        // First try SaleItems (Posted Sale)
        $items = \App\Models\SaleItem::where('sale_id', $id)->with('product:id,name', 'sale')->get();
        if ($items->isNotEmpty()) {
            // Favor item-level warehouse_id first
            $warehouse_id = $items->first()->warehouse_id;
            if (is_null($warehouse_id)) {
                $warehouse_id = $items->first()->sale->warehouse_id ?? null;
            }
        } else {
            // Try ProductBookingItem (Draft Booking)
            $items = \App\Models\ProductBookingItem::where('booking_id', $id)->with('product:id,name', 'booking')->get();
            if ($items->isNotEmpty()) {
                $warehouse_id = $items->first()->warehouse_id;
                if (is_null($warehouse_id)) {
                    $warehouse_id = $items->first()->booking->warehouse_id ?? null;
                }
            }
        }

        $res = $items->map(function ($it) use ($warehouse_id) {
            return [
                'product_id'   => $it->product_id,
                'item_name'    => optional($it->product)->name ?: 'Unknown',
                'qty'          => (float) ($it->sales_qty ?? $it->quantity ?? 0),
                'warehouse_id' => $warehouse_id
            ];
        });
        
        return response()->json($res);
    }

    public function store(Request $request)
    {
        $request->validate([
            'entry_date'   => 'required|date',
            'vendor_type'  => 'required',
            'vendor_id'    => 'required',
            'warehouse_id' => 'required',
            'product_id'   => 'required|array',
            'hold_qty'     => 'required|array',
        ]);

        // Security check for Shop Stock
        if ($request->warehouse_id == 0 && !auth()->user()->canAccessShop()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to Shop Stock.'], 403);
        }

        $status = $request->action === 'post' ? 'Posted' : 'Unposted';

        try {
            DB::beginTransaction();
            
            $voucherId = $request->id;
            $voucher = $voucherId ? StockHoldVoucher::findOrFail($voucherId) : new StockHoldVoucher();

            if ($voucherId && $voucher->status === 'Posted') {
                return response()->json(['success' => false, 'message' => 'Posted records cannot be modified.'], 422);
            }

            // Prevent Double Hold for same Sale (Excluding current voucher if updating)
            if ($request->filled('sale_id')) {
                $query = StockHold::where('sale_id', $request->sale_id);
                if ($voucherId) {
                    $query->where('stock_hold_voucher_id', '!=', $voucherId);
                }
                if ($query->exists()) {
                    return response()->json(['success' => false, 'message' => 'This Sale/Invoice already has hold records. Duplicate holds are not allowed.'], 422);
                }
            }

            if (!$voucherId) {
                $voucher->voucher_no = StockHoldVoucher::generateVoucherNo();
            } elseif ($request->filled('voucher_no')) {
                $voucher->voucher_no = $request->voucher_no;
            }
            
            $voucher->date         = $request->entry_date;
            $voucher->entry_time   = $request->entry_time ?? date('H:i');
            $voucher->party_type   = $request->vendor_type;
            $voucher->party_id     = $request->vendor_id;
            $voucher->warehouse_id = $request->warehouse_id;
            $voucher->sale_id      = $request->sale_id;
            $voucher->hold_type    = $request->hold_type ?? 'hold';
            $voucher->remarks      = $request->remarks;
            $voucher->status       = $status;
            $voucher->save();

            if ($voucherId) {
                $voucher->items()->delete();
            }

            foreach ($request->product_id as $index => $productId) {
                $qty = (float) $request->hold_qty[$index];
                if ($qty <= 0) continue;

                StockHold::create([
                    'stock_hold_voucher_id' => $voucher->id,
                    'entry_date'   => $request->entry_date,
                    'entry_time'   => $request->entry_time ?? date('H:i'),
                    'sale_id'      => $request->sale_id,
                    'party_type'   => $request->vendor_type,
                    'party_id'     => $request->vendor_id,
                    'warehouse_id' => $request->warehouse_id,
                    'product_id'   => $productId,
                    'sale_qty'     => $request->sale_qty[$index] ?? 0,
                    'hold_qty'     => $qty,
                    'remarks'      => $request->remarks,
                    'status'       => $status === 'Posted' ? 0 : 0, // In this system, 0 means Active Hold
                ]);
                if ($status === 'Posted') {
                    $this->adjustStock($request->warehouse_id, $productId, $qty);
                }
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Stock Hold ' . ($status == 'Posted' ? 'Posted' : ($voucherId ? 'Updated' : 'Saved')) . ' successfully.',
                    'status'  => $status,
                    'id'      => $voucher->id
                ]);
            }

            return redirect()->route('stock-hold-list')->with('success', 'Stock Hold saved successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    public function edit($id)
    {
        $voucher = StockHoldVoucher::with('items.product')->findOrFail($id);
        if ($voucher->status === 'Posted') {
            return redirect()->route('stock-hold-list')->with('error', 'Posted holds cannot be edited.');
        }
        $warehouses = Warehouse::orderBy('warehouse_name')->get();
        $products = Product::select('id', 'name')->orderBy('name')->get();
        return view('admin_panel.stock_hold.edit_stock_hold', compact('voucher', 'warehouses', 'products'));
    }

    public function update(Request $request, $id)
    {
        $request->merge(['id' => $id]);

        return $this->store($request);
    }

    public function post($id)
    {
        $voucher = StockHoldVoucher::with('items')->findOrFail($id);
        if ($voucher->status === 'Posted') {
            return back()->with('error', 'Already posted.');
        }
        $voucher->update(['status' => 'Posted']);
        
        foreach ($voucher->items as $item) {
            $this->adjustStock($item->warehouse_id, $item->product_id, $item->hold_qty);
        }
        
        return back()->with('success', 'Stock Hold Posted successfully.');
    }

    // Mark hold(s) as claimed for an invoice or item (called when sale completes)
    public function claimByInvoice(Request $request, $invoiceId)
    {
        // Validate if needed
        $updated = StockHold::where('invoice_id', $invoiceId)
            ->where('status', 0)
            ->update(['status' => 1]);

        return response()->json(['updated' => $updated]);
    }

    // Alternatively claim a single item hold:
    public function claimItem(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|integer',
            'item_id' => 'required'
        ]);

        $updated = StockHold::where('invoice_id', $request->invoice_id)
            ->where('item_id', $request->item_id)
            ->where('status', 0)
            ->update(['status' => 1]);

        return response()->json(['updated' => $updated]);
    }

    // stock realse work 


    protected function nextReleaseNumber()
    {
        $last = StockRelease::withoutGlobalScopes()->where('id', '>', 0)
            ->orderBy('id', 'desc')
            ->first();

        $next = 1;
        if ($last && $last->id) {
            $next = (int) preg_replace('/[^0-9]/', '', $last->voucher_no) + 1;
        }
        return str_pad($next, 3, '0', STR_PAD_LEFT);
    }

    // show a prefilled form to release stock for a given hold
    public function createFromHold($id)
    {
        $hold = StockHold::with(['product', 'warehouse', 'sale', 'partyCustomer', 'partyVendor'])
            ->findOrFail($id);

        $warehouses = Warehouse::select('id', 'warehouse_name')->get();

        $releaseNumber = $this->nextReleaseNumber();

        // suggested default release = hold_qty
        $suggestedQty = (float) ($hold->hold_qty ?? 0);

        return view('admin_panel.stock_hold.release_form', compact('hold', 'warehouses', 'releaseNumber', 'suggestedQty'));
    }

    public function storeFromHold(Request $request, $id)
    {
        $hold = StockHold::findOrFail($id);

        $data = $request->validate([
            'release_no'  => 'nullable|string',
            'release_qty' => 'required|numeric|min:0.0001',
            'warehouse_id' => 'nullable|integer',
            'remarks'     => 'nullable|string',
        ]);

        // Security check for Shop Stock
        if (($request->warehouse_id == 0 || (is_null($request->warehouse_id) && $hold->warehouse_id == 0)) && !auth()->user()->canAccessShop()) {
            return back()->withErrors(['error' => 'Unauthorized access to Shop Stock.']);
        }

        DB::beginTransaction();
        try {
            $releaseQty = (float) $data['release_qty'];

            // prepare meta: record it came from this hold and what the hold meta was
            $meta = [
                'from_hold' => $hold->id,
                'held_meta' => $hold->meta ?? null,
            ];

            $release = \App\Models\StockRelease::create([
                'release_no'  => $data['release_no'] ?? null,
                'hold_id'     => $hold->id,
                'sale_id'     => $hold->sale_id,
                'invoice_id'  => $hold->invoice_id,
                'party_type'  => $hold->party_type,
                'party_id'    => $hold->party_id,
                'warehouse_id' => $data['warehouse_id'] ?? $hold->warehouse_id,
                'product_id'  => $hold->product_id,
                'item_id'     => $hold->item_id,
                'sale_qty'    => $hold->sale_qty,
                'release_qty' => $releaseQty,
                'remarks'     => $data['remarks'] ?? null,
                'meta'        => $meta,
            ]);

            // update hold: subtract release and set status to released if nothing left
            $remaining = ((float) $hold->hold_qty) - $releaseQty;
            if ($remaining <= 0) {
                $hold->status = 1;         // released
                $hold->hold_qty = 0;
            } else {
                $hold->hold_qty = $remaining;
            }
            $hold->save();

            DB::commit();

            return redirect()->route('stock-hold-list')->with('success', 'Stock released successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Stock release error: ' . $e->getMessage(), ['hold_id' => $id, 'payload' => $data]);
            return back()->withErrors(['error' => 'Failed to release stock: ' . $e->getMessage()]);
        }
    }

    public function print($id)
    {
        $voucher = StockHoldVoucher::with(['items.product', 'warehouse', 'partyCustomer', 'partyVendor'])->findOrFail($id);
        return view('admin_panel.stock_hold.print', compact('voucher'));
    }

    public function printRelease($id)
    {
        $voucher = \App\Models\StockReleaseVoucher::with(['items.product', 'warehouse', 'partyCustomer', 'partyVendor', 'holdVoucher'])->findOrFail($id);
        return view('admin_panel.stock_hold.print_release', compact('voucher'));
    }

    public function stockrelaselist()
    {
        // load releases with product, warehouse and hold + party info
        $vouchers = \App\Models\StockReleaseVoucher::with([
            'items.product',
            'warehouse',
            'holdVoucher',
            'partyCustomer',
            'partyVendor'
        ])->orderBy('id', 'desc')->get();

        return view('admin_panel.stock_hold.stock_relase_list', compact('vouchers'));
    }
    public function createRelease()
    {
        $warehouses = Warehouse::orderBy('warehouse_name')->get();
        $releaseNo = \App\Models\StockReleaseVoucher::generateVoucherNo();
        return view('admin_panel.stock_hold.create_release', compact('warehouses', 'releaseNo'));
    }

    public function holdVoucherList(Request $request)
    {
        $q = $request->q;
        $partyType = $request->party_type;
        $partyId = $request->party_id;

        $query = StockHoldVoucher::where('status', 'Posted')
            ->when($partyType, function($query) use ($partyType) {
                $query->where('party_type', $partyType);
            })
            ->when($partyId, function($query) use ($partyId) {
                $query->where('party_id', $partyId);
            })
            ->when($q, function($query) use ($q) {
                $query->where('voucher_no', 'like', "%$q%");
            })
            ->latest()
            ->get()
            ->map(fn($v) => ['id' => 'hold:' . $v->id, 'text' => 'Hold: ' . $v->voucher_no . ' (Date: ' . $v->date . ')']);

        if($request->include_claims) {
            $claims = \App\Models\CustomerClaim::where('status', 'Posted')
                ->where('claim_type', 'claim_hold')
                ->when($partyType, function($query) use ($partyType) {
                    $query->where('party_type', $partyType);
                })
                ->when($partyId, function($query) use ($partyId) {
                    $query->where('party_id', $partyId);
                })
                ->when($q, function($query) use ($q) {
                    $query->where('claim_no', 'like', "%$q%");
                })
                ->get()
                ->map(fn($v) => ['id' => 'claim:' . $v->id, 'text' => 'Claim: ' . $v->claim_no . ' (Date: ' . $v->claim_date . ')']);
            
            return $query->merge($claims);
        }

        return $query;
    }

    public function voucherDetails($id)
    {
        $voucher = StockHoldVoucher::with(['items.product', 'warehouse', 'partyCustomer', 'partyVendor'])->findOrFail($id);
        
        $partyName = $voucher->party_type == 'vendor' ? ($voucher->partyVendor->name ?? '-') : ($voucher->partyCustomer->customer_name ?? '-');
        
        return response()->json([
            'id' => $voucher->id,
            'party_type' => $voucher->party_type,
            'party_id' => $voucher->party_id,
            'party_name' => $partyName,
            'warehouse_id' => $voucher->warehouse_id,
            'warehouse_name' => ($voucher->warehouse_id == 0) ? 'Shop' : ($voucher->warehouse->warehouse_name ?? '-'),
            'items' => $voucher->items->map(function($it) {
                return [
                    'product_id' => $it->product_id,
                    'item_name' => $it->product->name ?? 'Product',
                    'sale_qty' => (float)$it->sale_qty,
                    'hold_qty' => (float)$it->hold_qty,
                ];
            })
        ]);
    }

    public function storeBulkRelease(Request $request)
    {
        $request->validate([
            'entry_date' => 'required|date',
            'product_id' => 'required|array',
            'release_qty' => 'required|array',
            'warehouse_id' => 'required|integer',
        ]);

        // Security check for Shop Stock
        if ($request->warehouse_id == 0 && !auth()->user()->canAccessShop()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to Shop Stock.'], 403);
        }

        $status = $request->action === 'post' ? 'Posted' : 'Unposted';
        $releaseType = $request->claim_id ? 'claim' : 'stock';

        try {
            DB::beginTransaction();

            $holdVoucherId = $request->hold_voucher_id;
            $claimId = $request->claim_id;
            
            // Create Release Voucher
            $voucher = \App\Models\StockReleaseVoucher::create([
                'voucher_no'      => \App\Models\StockReleaseVoucher::generateVoucherNo(),
                'date'            => $request->entry_date,
                'entry_time'      => $request->entry_time ?? date('H:i'),
                'release_type'    => $releaseType,
                'hold_voucher_id' => $holdVoucherId,
                'claim_id'        => $claimId,
                'party_type'      => $request->vendor_type,
                'party_id'        => $request->vendor_id,
                'warehouse_id'    => $request->warehouse_id,
                'remarks'         => $request->remarks,
                'status'          => $status
            ]);

            foreach ($request->product_id as $index => $pid) {
                $releaseQty = (float)($request->release_qty[$index] ?? 0);
                if ($releaseQty <= 0) continue;

                // Find original hold item
                if ($releaseType === 'claim') {
                    $holdItem = \App\Models\StockHold::where('meta->claim_id', (string)$claimId)
                        ->where('product_id', $pid)
                        ->where('status', 0)
                        ->first();
                } else {
                    $holdItem = \App\Models\StockHold::where('stock_hold_voucher_id', $holdVoucherId)
                        ->where('product_id', $pid)
                        ->where('status', 0)
                        ->first();
                }

                if (!$holdItem) {
                    // Manual item (No prior hold)
                    \App\Models\StockRelease::create([
                        'stock_release_voucher_id' => $voucher->id,
                        'release_no'  => $voucher->voucher_no,
                        'hold_id'     => null,
                        'sale_id'     => null,
                        'party_type'  => $request->vendor_type,
                        'party_id'    => $request->vendor_id,
                        'warehouse_id' => $request->warehouse_id,
                        'product_id'  => $pid,
                        'sale_qty'    => 0,
                        'release_qty' => $releaseQty,
                        'remarks'     => $request->remarks,
                        'status'      => $status
                    ]);

                    if ($status === 'Posted') {
                        $this->adjustStock($request->warehouse_id, $pid, -$releaseQty);
                    }
                    continue;
                }

                // Create Release Record
                \App\Models\StockRelease::create([
                    'stock_release_voucher_id' => $voucher->id,
                    'release_no'  => $voucher->voucher_no,
                    'hold_id'     => $holdItem->id,
                    'sale_id'     => $holdItem->sale_id,
                    'party_type'  => $holdItem->party_type,
                    'party_id'    => $holdItem->party_id,
                    'warehouse_id' => $request->warehouse_id, // Local selection
                    'product_id'  => $pid,
                    'sale_qty'    => $holdItem->sale_qty,
                    'release_qty' => $releaseQty,
                    'remarks'     => $request->remarks,
                    'status'      => $status
                ]);

                if ($status === 'Posted') {
                    // 1. Subtract from Logic Reserve (StockHold)
                    $remaining = (float)$holdItem->hold_qty - $releaseQty;
                    $holdItem->hold_qty = max(0, $remaining);
                    if ($holdItem->hold_qty <= 0) {
                        $holdItem->status = 1; // Released
                    }
                    $holdItem->save();

                    // 2. Physical Stock Deduction
                    $this->adjustStock($request->warehouse_id, $pid, -$releaseQty);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock Released ' . ($status == 'Posted' ? 'Posted' : 'Saved') . ' successfully.',
                'status'  => $status,
                'id'      => $voucher->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function adjustStock($warehouseId, $productId, $qty)
    {
        if ($warehouseId == 0) {
            $product = Product::find($productId);
            if ($product) {
                $product->stock = ($product->stock ?? 0) + $qty;
                $product->save();
            }
        } else {
            $stock = \App\Models\WarehouseStock::firstOrNew([
                'warehouse_id' => $warehouseId,
                'product_id'   => $productId
            ]);
            $stock->quantity = ($stock->quantity ?? 0) + $qty;
            if (!$stock->exists) $stock->status = 'Posted';
            $stock->save();
        }
    }

    public function editRelease($id)
    {
        $voucher = \App\Models\StockReleaseVoucher::with('items.product', 'warehouse')->findOrFail($id);
        $warehouses = Warehouse::orderBy('warehouse_name')->get();
        return view('admin_panel.stock_hold.edit_release', compact('voucher', 'warehouses'));
    }

    public function updateRelease(Request $request, $id)
    {
        $request->validate([
            'entry_date' => 'required|date',
            'product_id' => 'required|array',
            'release_qty' => 'required|array',
        ]);

        $status = $request->action === 'post' ? 'Posted' : 'Unposted';

        try {
            DB::beginTransaction();
            $voucher = \App\Models\StockReleaseVoucher::findOrFail($id);
            $voucher->update([
                'date'    => $request->entry_date,
                'entry_time' => $request->entry_time ?? date('H:i'),
                'remarks' => $request->remarks,
                'status'  => $status
            ]);

            // Sync items (delete old then create new for simplicity as it's bulk updated)
            $voucher->items()->delete();

            foreach ($request->product_id as $index => $pid) {
                $releaseQty = (float)($request->release_qty[$index] ?? 0);
                if ($releaseQty <= 0) continue;

                // Attempt to link to the original hold item
                $holdItem = \App\Models\StockHold::where('stock_hold_voucher_id', $voucher->hold_voucher_id)
                    ->where('product_id', $pid)
                    ->first();

                \App\Models\StockRelease::create([
                    'stock_release_voucher_id' => $voucher->id,
                    'release_no'  => $voucher->voucher_no,
                    'hold_id'     => $holdItem->id ?? null,
                    'sale_id'     => $holdItem->sale_id ?? null,
                    'party_type'  => $voucher->party_type,
                    'party_id'    => $voucher->party_id,
                    'warehouse_id' => $voucher->warehouse_id,
                    'product_id'  => $pid,
                    'sale_qty'    => $holdItem->sale_qty ?? 0,
                    'release_qty' => $releaseQty,
                    'remarks'     => $request->remarks,
                    'status'      => $status
                ]);

                if ($status === 'Posted') {
                    if ($holdItem) {
                        $remaining = (float)$holdItem->hold_qty - $releaseQty;
                        $holdItem->hold_qty = max(0, $remaining);
                        if ($holdItem->hold_qty <= 0) $holdItem->status = 1;
                        $holdItem->save();
                    }
                    // Physical Stock Deduction
                    $this->adjustStock($voucher->warehouse_id, $pid, -$releaseQty);
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Release ' . $status, 'status' => $status, 'id' => $voucher->id]);
        } catch (\Exception $e) { DB::rollBack(); return response()->json(['success' => false, 'message' => $e->getMessage()], 500); }
    }

    public function postRelease($id)
    {
        try {
            DB::beginTransaction();
            $voucher = \App\Models\StockReleaseVoucher::with('items.hold')->findOrFail($id);
            if ($voucher->status === 'Posted') return response()->json(['success' => false, 'message' => 'Already posted']);

            $voucher->update(['status' => 'Posted']);
            foreach ($voucher->items as $item) {
                $item->update(['status' => 'Posted']);
                if ($item->hold) {
                    $remaining = (float)$item->hold->hold_qty - (float)$item->release_qty;
                    $item->hold->hold_qty = max(0, $remaining);
                    if ($item->hold->hold_qty <= 0) $item->hold->status = 1;
                    $item->hold->save();
                }
                // Physical Stock Deduction
                $this->adjustStock($voucher->warehouse_id, $item->product_id, -(float)$item->release_qty);
            }
            DB::commit();
            if (request()->ajax()) {
                return response()->json(['success' => true, 'message' => 'Posted successfully']);
            }
            return redirect()->back()->with('success', 'Stock Release Posted successfully.');
        } catch (\Exception $e) { 
            DB::rollBack(); 
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}

