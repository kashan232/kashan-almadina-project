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

                if ($status === 'Posted') {
                    $qty = $this->applyHoldReservedIncrease(
                        (int) $productId,
                        $qty,
                        $request->vendor_type,
                        (int) $request->vendor_id,
                        (int) $voucher->id
                    );
                }

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
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Stock Hold ' . ($status == 'Posted' ? 'Posted' : ($voucherId ? 'Updated' : 'Saved')) . ' successfully.',
                    'status'  => $status,
                    'id'      => $voucher->id,
                    'voucher_no' => $voucher->voucher_no,
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

    public function showHold($id)
    {
        $voucher = StockHoldVoucher::with('items.product')->findOrFail($id);
        $warehouses = Warehouse::orderBy('warehouse_name')->get();
        $products = Product::select('id', 'name')->orderBy('name')->get();
        $viewMode = true;

        return view('admin_panel.stock_hold.edit_stock_hold', compact('voucher', 'warehouses', 'products', 'viewMode'));
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

        try {
            DB::beginTransaction();

            foreach ($voucher->items as $item) {
                $grossQty = (float) $item->hold_qty;
                if ($grossQty <= 0) {
                    $item->delete();
                    continue;
                }

                $effectiveQty = $this->applyHoldReservedIncrease(
                    (int) $item->product_id,
                    $grossQty,
                    $voucher->party_type,
                    (int) $voucher->party_id,
                    (int) $voucher->id
                );

                if ($effectiveQty <= 0) {
                    $item->delete();
                    continue;
                }

                $item->hold_qty = $effectiveQty;
                $item->status = 0;
                $item->save();
            }

            $voucher->update(['status' => 'Posted']);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to post hold: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Failed to post hold: ' . $e->getMessage());
        }

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Stock Hold Posted successfully.',
                'print_url' => route('stock-holds.print', $id),
            ]);
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
                'status'      => 'Posted',
            ]);

            $warehouseId = (int) ($data['warehouse_id'] ?? $hold->warehouse_id);
            $this->applyReleaseEffects(
                $warehouseId,
                (int) $hold->product_id,
                $releaseQty,
                (int) $hold->id,
                $hold->stock_hold_voucher_id ? (int) $hold->stock_hold_voucher_id : null,
                null,
                $hold->party_type,
                $hold->party_id ? (int) $hold->party_id : null
            );

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
        return view('admin_panel.stock_hold.create_release', compact('warehouses'));
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
                    'hold_id'    => $it->id,
                    'product_id' => $it->product_id,
                    'item_name'  => $it->product->name ?? 'Product',
                    'sale_qty'   => (float)$it->sale_qty,
                    'hold_qty'   => (float)$it->hold_qty,
                ];
            })
        ]);
    }

    public function storeBulkRelease(Request $request)
    {
        $request->validate([
            'entry_date'   => 'required|date',
            'vendor_type'  => 'required',
            'vendor_id'    => 'required',
            'product_id'   => 'required|array',
            'release_qty'  => 'required|array',
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

            $holdVoucherId = $request->filled('hold_voucher_id') ? $request->hold_voucher_id : null;
            $claimId = $request->filled('claim_id') ? $request->claim_id : null;
            
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

                $holdId = !empty($request->hold_id[$index]) ? (int) $request->hold_id[$index] : null;
                $linkedHold = $this->resolveHoldItemForRelease(
                    $request,
                    (int) $pid,
                    $releaseType,
                    $holdVoucherId,
                    $claimId,
                    $holdId
                );

                if ($status === 'Posted') {
                    $linkedHold = $this->applyReleaseEffects(
                        (int) $request->warehouse_id,
                        (int) $pid,
                        $releaseQty,
                        $holdId,
                        $holdVoucherId ? (int) $holdVoucherId : null,
                        $claimId ? (int) $claimId : null,
                        $request->vendor_type,
                        (int) $request->vendor_id
                    ) ?? $linkedHold;
                }

                \App\Models\StockRelease::create([
                    'stock_release_voucher_id' => $voucher->id,
                    'release_no'  => $voucher->voucher_no,
                    'hold_id'     => $linkedHold?->id,
                    'sale_id'     => $linkedHold?->sale_id,
                    'party_type'  => $linkedHold?->party_type ?? $request->vendor_type,
                    'party_id'    => $linkedHold?->party_id ?? $request->vendor_id,
                    'warehouse_id' => $request->warehouse_id,
                    'product_id'  => $pid,
                    'sale_qty'    => $linkedHold?->sale_qty ?? ($request->sale_qty[$index] ?? 0),
                    'release_qty' => $releaseQty,
                    'remarks'     => $request->remarks,
                    'status'      => $status
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock Released ' . ($status == 'Posted' ? 'Posted' : 'Saved') . ' successfully.',
                'status'  => $status,
                'id'      => $voucher->id,
                'voucher_no' => $voucher->voucher_no,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function findHoldItemForRelease(
        Request $request,
        int $productId,
        string $releaseType,
        $holdVoucherId,
        $claimId
    ): ?StockHold {
        $query = StockHold::withoutGlobalScopes()
            ->where('product_id', $productId)
            ->where(function ($q) {
                $q->where('status', 0)->orWhereNull('status');
            })
            ->where('hold_qty', '>', 0);

        if ($releaseType === 'claim' && $claimId) {
            $hold = (clone $query)->where('meta->claim_id', (string) $claimId)->first();
            if ($hold) {
                return $hold;
            }
        }

        if ($holdVoucherId) {
            $hold = (clone $query)->where('stock_hold_voucher_id', $holdVoucherId)->first();
            if ($hold) {
                return $hold;
            }
        }

        if ($request->filled('vendor_type') && $request->filled('vendor_id')) {
            return (clone $query)
                ->where('party_type', $request->vendor_type)
                ->where('party_id', $request->vendor_id)
                ->orderByDesc('id')
                ->first();
        }

        return null;
    }

    private function resolveHoldItemForRelease(
        Request $request,
        int $productId,
        string $releaseType,
        $holdVoucherId,
        $claimId,
        $explicitHoldId = null
    ): ?StockHold {
        if ($explicitHoldId) {
            $hold = StockHold::withoutGlobalScopes()->find($explicitHoldId);
            if ($hold && (int) $hold->product_id === $productId) {
                return $hold;
            }
        }

        return $this->findHoldItemForRelease(
            $request,
            $productId,
            $releaseType,
            $holdVoucherId,
            $claimId
        );
    }

    /** Deduct physical stock and reduce reserved (stock_holds.hold_qty). */
    private function applyReleaseEffects(
        int $warehouseId,
        int $productId,
        float $releaseQty,
        ?int $explicitHoldId = null,
        ?int $holdVoucherId = null,
        ?int $claimId = null,
        ?string $partyType = null,
        ?int $partyId = null
    ): ?StockHold {
        if ($releaseQty <= 0) {
            return null;
        }

        $primaryHold = $this->reduceReservedForRelease(
            $productId,
            $releaseQty,
            $explicitHoldId,
            $holdVoucherId,
            $claimId,
            $partyType,
            $partyId
        );

        $this->adjustStock($warehouseId, $productId, -$releaseQty);

        return $primaryHold;
    }

    private function reduceReservedForRelease(
        int $productId,
        float $releaseQty,
        ?int $explicitHoldId = null,
        ?int $holdVoucherId = null,
        ?int $claimId = null,
        ?string $partyType = null,
        ?int $partyId = null
    ): ?StockHold {
        $remaining = $releaseQty;
        $primaryHold = null;
        $explicitHold = null;

        if ($explicitHoldId) {
            $explicitHold = StockHold::withoutGlobalScopes()->find($explicitHoldId);
            if ($explicitHold && (int) $explicitHold->product_id === $productId) {
                $primaryHold = $explicitHold;
            }
        }

        if (!$holdVoucherId && $explicitHold?->stock_hold_voucher_id) {
            $holdVoucherId = (int) $explicitHold->stock_hold_voucher_id;
        }

        $query = StockHold::withoutGlobalScopes()
            ->where('product_id', $productId)
            ->where(function ($q) {
                $q->where('status', 0)->orWhereNull('status');
            })
            ->where('hold_qty', '>', 0);

        if ($claimId) {
            $query->where('meta->claim_id', (string) $claimId);
        } elseif ($holdVoucherId) {
            $query->where('stock_hold_voucher_id', $holdVoucherId);
        } elseif ($partyType && $partyId) {
            $query->where('party_type', $partyType)->where('party_id', $partyId);
        } elseif ($explicitHoldId) {
            if ($explicitHold?->stock_hold_voucher_id) {
                $query->where('stock_hold_voucher_id', $explicitHold->stock_hold_voucher_id);
            } else {
                $query->where('id', $explicitHoldId);
            }
        } else {
            return $this->applyReleaseOverflow($productId, $releaseQty, $partyType, $partyId, null);
        }

        if ($explicitHoldId) {
            $query->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$explicitHoldId]);
        }
        $holds = $query->orderBy('id')->get();

        foreach ($holds as $hold) {
            if ($remaining <= 0) {
                break;
            }
            if (!$primaryHold) {
                $primaryHold = $hold;
            }

            $deduct = min((float) $hold->hold_qty, $remaining);
            $hold->hold_qty = (float) $hold->hold_qty - $deduct;
            $hold->status = (float) $hold->hold_qty <= 0 ? 1 : 0;
            $hold->save();
            $remaining -= $deduct;
        }

        if ($remaining > 0) {
            $overflowHold = $primaryHold ?? $explicitHold ?? $this->findReleaseOverflowHold($productId, $partyType, $partyId);
            if (!$overflowHold) {
                $overflowHold = $this->createReleaseOverflowHold($productId, $partyType, $partyId, $explicitHold?->warehouse_id);
            }

            $overflowHold->hold_qty = (float) $overflowHold->hold_qty - $remaining;
            $overflowHold->status = (float) $overflowHold->hold_qty < 0 ? 0 : (((float) $overflowHold->hold_qty == 0) ? 1 : 0);
            $overflowHold->save();
            $primaryHold = $primaryHold ?? $overflowHold;
        }

        return $primaryHold;
    }

    /** When no hold link exists, still track reserved impact (can go negative / over-release). */
    private function applyReleaseOverflow(
        int $productId,
        float $releaseQty,
        ?string $partyType = null,
        ?int $partyId = null,
        ?StockHold $preferredHold = null
    ): ?StockHold {
        $hold = $preferredHold ?? $this->findReleaseOverflowHold($productId, $partyType, $partyId);
        if (!$hold) {
            $hold = $this->createReleaseOverflowHold($productId, $partyType, $partyId, null);
        }

        $hold->hold_qty = (float) $hold->hold_qty - $releaseQty;
        $hold->status = (float) $hold->hold_qty < 0 ? 0 : (((float) $hold->hold_qty == 0) ? 1 : 0);
        $hold->save();

        return $hold;
    }

    private function findReleaseOverflowHold(int $productId, ?string $partyType, ?int $partyId): ?StockHold
    {
        $query = StockHold::withoutGlobalScopes()
            ->where('product_id', $productId)
            ->where(function ($q) {
                $q->where('status', 0)->orWhereNull('status');
            });

        if ($partyType && $partyId) {
            $query->where('party_type', $partyType)->where('party_id', $partyId);
        }

        return $query->orderByRaw('CASE WHEN hold_qty <= 0 THEN 0 ELSE 1 END')
            ->orderByDesc('id')
            ->first();
    }

    /** Increase reserved on hold — pay off negative (over-release) balance first, same signed math as release. */
    private function applyHoldReservedIncrease(
        int $productId,
        float $holdQty,
        ?string $partyType = null,
        ?int $partyId = null,
        ?int $excludeVoucherId = null
    ): float {
        $remaining = $holdQty;

        $query = StockHold::withoutGlobalScopes()
            ->where('product_id', $productId)
            ->where(function ($q) {
                $q->where('status', 0)->orWhereNull('status');
            })
            ->where('hold_qty', '<', 0)
            ->where(function ($q) {
                $q->whereNull('stock_hold_voucher_id')
                    ->orWhereHas('voucher', function ($v) {
                        $v->where('status', 'Posted');
                    });
            });

        if ($partyType && $partyId) {
            $query->where('party_type', $partyType)->where('party_id', $partyId);
        }

        if ($excludeVoucherId) {
            $query->where(function ($q) use ($excludeVoucherId) {
                $q->whereNull('stock_hold_voucher_id')
                    ->orWhere('stock_hold_voucher_id', '!=', $excludeVoucherId);
            });
        }

        $negativeHolds = $query->orderBy('id')->get();

        foreach ($negativeHolds as $hold) {
            if ($remaining <= 0) {
                break;
            }

            $debt = abs((float) $hold->hold_qty);
            $pay = min($remaining, $debt);
            $hold->hold_qty = (float) $hold->hold_qty + $pay;
            $hold->status = (float) $hold->hold_qty == 0 ? 1 : 0;
            $hold->save();
            $remaining -= $pay;
        }

        return $remaining;
    }

    private function createReleaseOverflowHold(
        int $productId,
        ?string $partyType,
        ?int $partyId,
        $warehouseId
    ): StockHold {
        return StockHold::create([
            'product_id'   => $productId,
            'party_type'   => $partyType,
            'party_id'     => $partyId,
            'warehouse_id' => $warehouseId ?? 0,
            'hold_qty'     => 0,
            'status'       => 0,
            'entry_date'   => now()->toDateString(),
            'entry_time'   => now()->format('H:i'),
        ]);
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
        $voucher = \App\Models\StockReleaseVoucher::with([
            'items.product',
            'items.hold',
            'warehouse',
            'holdVoucher',
            'partyCustomer',
            'partyVendor',
        ])->findOrFail($id);

        if ($voucher->status === 'Posted') {
            return redirect()->route('stock-relase-list')->with('error', 'Posted releases cannot be edited.');
        }

        $warehouses = Warehouse::orderBy('warehouse_name')->get();

        return view('admin_panel.stock_hold.edit_release', compact('voucher', 'warehouses'));
    }

    public function showRelease($id)
    {
        $voucher = \App\Models\StockReleaseVoucher::with([
            'items.product',
            'items.hold',
            'warehouse',
            'holdVoucher',
            'partyCustomer',
            'partyVendor',
        ])->findOrFail($id);

        $warehouses = Warehouse::orderBy('warehouse_name')->get();
        $viewMode = true;

        return view('admin_panel.stock_hold.edit_release', compact('voucher', 'warehouses', 'viewMode'));
    }

    public function updateRelease(Request $request, $id)
    {
        $request->validate([
            'entry_date'   => 'required|date',
            'warehouse_id' => 'required|integer',
            'vendor_type'  => 'required',
            'vendor_id'    => 'required',
            'product_id'   => 'required|array',
            'release_qty'  => 'required|array',
        ]);

        if ($request->warehouse_id == 0 && !auth()->user()->canAccessShop()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to Shop Stock.'], 403);
        }

        $status = $request->action === 'post' ? 'Posted' : 'Unposted';

        try {
            DB::beginTransaction();
            $voucher = \App\Models\StockReleaseVoucher::findOrFail($id);

            if ($voucher->status === 'Posted') {
                return response()->json(['success' => false, 'message' => 'Posted records cannot be modified.'], 422);
            }

            $holdVoucherId = $request->filled('hold_voucher_id') ? $request->hold_voucher_id : null;
            $claimId = $request->filled('claim_id') ? $request->claim_id : null;
            $releaseType = $claimId ? 'claim' : 'stock';

            $voucher->update([
                'date'            => $request->entry_date,
                'entry_time'      => $request->entry_time ?? date('H:i'),
                'warehouse_id'    => $request->warehouse_id,
                'party_type'      => $request->vendor_type,
                'party_id'        => $request->vendor_id,
                'hold_voucher_id' => $holdVoucherId,
                'claim_id'        => $claimId,
                'remarks'         => $request->remarks,
                'status'          => $status,
            ]);

            $voucher->load('items');
            $existingHoldIds = $voucher->items->pluck('hold_id', 'product_id');

            $voucher->items()->delete();

            foreach ($request->product_id as $index => $pid) {
                $releaseQty = (float)($request->release_qty[$index] ?? 0);
                if ($releaseQty <= 0) continue;

                $holdId = !empty($request->hold_id[$index]) ? (int) $request->hold_id[$index] : ($existingHoldIds->get((int) $pid) ? (int) $existingHoldIds->get((int) $pid) : null);
                $linkedHold = $this->resolveHoldItemForRelease(
                    $request,
                    (int) $pid,
                    $releaseType,
                    $holdVoucherId,
                    $claimId,
                    $holdId
                );

                if ($status === 'Posted') {
                    $linkedHold = $this->applyReleaseEffects(
                        (int) $request->warehouse_id,
                        (int) $pid,
                        $releaseQty,
                        $holdId,
                        $holdVoucherId ? (int) $holdVoucherId : null,
                        $claimId ? (int) $claimId : null,
                        $request->vendor_type,
                        (int) $request->vendor_id
                    ) ?? $linkedHold;
                }

                \App\Models\StockRelease::create([
                    'stock_release_voucher_id' => $voucher->id,
                    'release_no'  => $voucher->voucher_no,
                    'hold_id'     => $linkedHold?->id,
                    'sale_id'     => $linkedHold?->sale_id,
                    'party_type'  => $request->vendor_type,
                    'party_id'    => $request->vendor_id,
                    'warehouse_id' => $request->warehouse_id,
                    'product_id'  => $pid,
                    'sale_qty'    => $linkedHold?->sale_qty ?? ($request->sale_qty[$index] ?? 0),
                    'release_qty' => $releaseQty,
                    'remarks'     => $request->remarks,
                    'status'      => $status
                ]);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Release ' . ($status === 'Posted' ? 'Posted' : 'Updated') . ' successfully.',
                'status'  => $status,
                'id'      => $voucher->id,
                'voucher_no' => $voucher->voucher_no,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
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
                $this->applyReleaseEffects(
                    (int) $voucher->warehouse_id,
                    (int) $item->product_id,
                    (float) $item->release_qty,
                    $item->hold_id ? (int) $item->hold_id : null,
                    $voucher->hold_voucher_id ? (int) $voucher->hold_voucher_id : null,
                    $voucher->claim_id ? (int) $voucher->claim_id : null,
                    $voucher->party_type,
                    $voucher->party_id ? (int) $voucher->party_id : null
                );
            }
            DB::commit();
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Posted successfully',
                    'print_url' => route('stock-holds.release.print', $id),
                ]);
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

