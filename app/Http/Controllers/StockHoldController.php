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
    public function stockholdlist()
    {
        $vouchers = StockHoldVoucher::with([
            'warehouse:id,warehouse_name',
            'partyCustomer:id,customer_name',
            'partyVendor:id,name',
            'items.product'
        ])->latest()->get();

        return view("admin_panel.stock_hold.stock_hold_list", compact('vouchers'));
    }




    public function create()
    {
        $warehouses = Warehouse::orderBy('warehouse_name')->get();
        return view('admin_panel.stock_hold.create_stock_hold', compact('warehouses'));
    }

    public function partyList(Request $request)
    {
        $type = $request->type; // vendor, customer, walkin
        if ($type === 'vendor') {
            return Vendor::orderBy('name')->get()->map(fn($v) => ['id' => $v->id, 'text' => $v->id . ' - ' . $v->name]);
        }
        
        $customerType = ($type === 'walkin' || $type === 'walking') ? 'Walking Customer' : 'Main Customer';
        
        return Customer::where('customer_type', $customerType)
            ->orderBy('customer_name')
            ->get()
            ->map(fn($c) => ['id' => $c->id, 'text' => ($c->customer_id ?? $c->id) . ' - ' . $c->customer_name]);
    }

    public function partyInvoices(Request $request, $partyId)
    {
        $type = $request->type;
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
        // First try SaleItems (Posted Sale)
        $items = \App\Models\SaleItem::where('sale_id', $id)
            ->with('product:id,name')
            ->get();
            
        if ($items->isEmpty()) {
            // Try ProductBookingItem (Draft Booking)
            $items = \App\Models\ProductBookingItem::where('booking_id', $id)
                ->with('product:id,name')
                ->get();
        }

        $res = $items->map(function ($it) {
            return [
                'product_id' => $it->product_id,
                'item_name'  => optional($it->product)->name ?: 'Unknown',
                'qty'        => (float) ($it->sales_qty ?? $it->quantity ?? 0),
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

        $status = $request->action === 'post' ? 'Posted' : 'Unposted';

        try {
            DB::beginTransaction();

            $voucher = StockHoldVoucher::create([
                'voucher_no'   => StockHoldVoucher::generateVoucherNo(),
                'date'         => $request->entry_date,
                'party_type'   => $request->vendor_type,
                'party_id'     => $request->vendor_id,
                'warehouse_id' => $request->warehouse_id,
                'sale_id'      => $request->sale_id,
                'hold_type'    => $request->hold_type ?? 'hold',
                'remarks'      => $request->remarks,
                'status'       => $status,
            ]);

            foreach ($request->product_id as $index => $productId) {
                $qty = (float) $request->hold_qty[$index];
                if ($qty <= 0) continue;

                StockHold::create([
                    'stock_hold_voucher_id' => $voucher->id,
                    'entry_date'   => $request->entry_date,
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
                    'message' => 'Stock Hold ' . ($status == 'Posted' ? 'Posted' : 'Saved') . ' successfully.',
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
        return view('admin_panel.stock_hold.edit_stock_hold', compact('voucher', 'warehouses'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'entry_date'   => 'required|date',
            'product_id'   => 'required|array',
            'hold_qty'     => 'required|array',
        ]);

        $voucher = StockHoldVoucher::findOrFail($id);
        if ($voucher->status === 'Posted') {
            return response()->json(['success' => false, 'message' => 'Posted records cannot be modified.'], 422);
        }

        $status = $request->action === 'post' ? 'Posted' : 'Unposted';

        try {
            DB::beginTransaction();

            $voucher->update([
                'date'         => $request->entry_date,
                'remarks'      => $request->remarks,
                'status'       => $status,
            ]);

            $voucher->items()->delete();

            foreach ($request->product_id as $index => $productId) {
                $qty = (float) $request->hold_qty[$index];
                if ($qty <= 0) continue;

                StockHold::create([
                    'stock_hold_voucher_id' => $voucher->id,
                    'entry_date'   => $request->entry_date,
                    'sale_id'      => $voucher->sale_id,
                    'party_type'   => $voucher->party_type,
                    'party_id'     => $voucher->party_id,
                    'warehouse_id' => $voucher->warehouse_id,
                    'product_id'   => $productId,
                    'sale_qty'     => $request->sale_qty[$index] ?? 0,
                    'hold_qty'     => $qty,
                    'remarks'      => $request->remarks,
                    'status'       => 0,
                ]);
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Stock Hold ' . ($status == 'Posted' ? 'Posted' : 'Updated') . ' successfully.',
                    'status'  => $status,
                    'id'      => $voucher->id
                ]);
            }

            return redirect()->route('stock-hold-list')->with('success', 'Stock Hold updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    public function post($id)
    {
        $voucher = StockHoldVoucher::findOrFail($id);
        if ($voucher->status === 'Posted') {
            return back()->with('error', 'Already posted.');
        }
        $voucher->update(['status' => 'Posted']);
        // Here we could also update main stock if "Hold" meant moving to a hold warehouse, 
        // but typically hold just marks availability.
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
        $prefix = 'REL-';
        $last = StockRelease::where('id', '>', 0)
            ->orderBy('id', 'desc')
            ->first();

        $next = 1;
        if ($last && $last->id) {
            $next = $last->id + 1;
        }
        return $prefix . str_pad($next, 3, '0', STR_PAD_LEFT);
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

    public function stockrelaselist()
    {
        // load releases with product, warehouse and hold + party info
        $releases = StockRelease::with([
            'product:id,name',
            'warehouse:id,warehouse_name',
            'hold', // to fetch original hold row
            'hold.product:id,name',
        ])->orderBy('id', 'desc')->get();

        return view('admin_panel.stock_hold.stock_relase_list', compact('releases'));
    }
}
