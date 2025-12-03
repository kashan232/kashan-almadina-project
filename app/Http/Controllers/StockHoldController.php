<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Models\StockHold;
use App\Models\StockRelease;
use App\Models\Warehouse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StockHoldController extends Controller
{
    // store holds from the form submission
    public function stockholdlist()
    {
        $holds = StockHold::with([
            'product:id,name',
            'warehouse:id,warehouse_name',
            'partyCustomer:id,customer_name,mobile',
            'partyVendor:id,name,phone',
            'sale:id,invoice_no'
        ])
            ->orderBy('id', 'desc')
            ->get();

        return view("admin_panel.stock_hold.stock_hold_list", compact('holds'));
    }




    public function store(Request $request)
    {
        // validation
        $validator = Validator::make($request->all(), [
            'entry_date' => 'nullable|date',
            'vendor_type' => 'nullable|in:vendor,customer,walkin',
            'vendor_id' => 'nullable|integer',
            'warehouse_id' => 'nullable|integer',          // <-- form-level warehouse
            'sale_id' => 'nullable|integer',
            'invoice_id' => 'nullable|integer',
            'hold_type' => 'nullable|string',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'nullable',              // manual rows may have empty
            'items.*.sale_qty' => 'nullable|numeric',
            'items.*.hold_qty' => 'required|numeric|min:0.0001',
            'items.*.product_id' => 'nullable|integer',
            'items.*.warehouse_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Prefer sale_id (invoice) if provided
        $saleId = $data['sale_id'] ?? $data['invoice_id'] ?? null;

        DB::beginTransaction();
        try {
            $entryDate = $data['entry_date'] ?? now()->toDateString();
            $partyType = $data['vendor_type'] ?? null;
            $partyId = $data['vendor_id'] ?? null;
            $formWarehouseId = $data['warehouse_id'] ?? null; // <-- top-level fallback
            $remarks = $data['remarks'] ?? null;

            $processed = 0;
            $created = [];
            $updated = [];

            foreach ($data['items'] as $itemKey => $it) {
                // normalize values
                $rawItemId = $it['item_id'] ?? null; // may be empty string for manual
                $productId = $it['product_id'] ?? null;

                // item-level warehouse first; fallback to form-level warehouse
                $warehouseId = $it['warehouse_id'] ?? $formWarehouseId ?? null;

                $saleQty = isset($it['sale_qty']) ? (float) $it['sale_qty'] : null;
                $holdQty = isset($it['hold_qty']) ? (float) $it['hold_qty'] : 0.0;

                // skip zero or negative hold qty (defensive)
                if ($holdQty <= 0) {
                    continue;
                }

                // Detect manual vs invoice:
                // - treat as manual if item_id is empty OR itemKey starts with 'manual_'
                $isManual = empty($rawItemId) || Str::startsWith((string) $itemKey, 'manual_');

                // Prepare meta to store source info
                $meta = [
                    'source' => $isManual ? 'manual' : 'invoice',
                ];

                // For manual rows ensure item_id null and sale_qty zero; for invoice keep item_id
                $dbItemId = $isManual ? null : $rawItemId;

                $payload = [
                    'entry_date'   => $entryDate,
                    'sale_id'      => $isManual ? null : $saleId,
                    'invoice_id'   => $data['invoice_id'] ?? null,
                    'party_type'   => $partyType,
                    'party_id'     => $partyId,
                    'warehouse_id' => $warehouseId,
                    'product_id'   => $productId,
                    'item_id'      => $dbItemId,
                    'sale_qty'     => $isManual ? 0 : $saleQty,
                    'hold_qty'     => $holdQty,
                    'remarks'      => $remarks,
                    'status'       => 0,
                    'meta'         => $meta,
                ];

                // Upsert logic
                if ($isManual) {
                    // match by product + party + warehouse (manual holds are not tied to sale_id)
                    $existing = StockHold::whereNull('sale_id')
                        ->where('product_id', $productId)
                        ->where('party_id', $partyId)
                        ->where(function ($q) use ($warehouseId) {
                            if ($warehouseId) {
                                $q->where('warehouse_id', $warehouseId);
                            } else {
                                $q->whereNull('warehouse_id');
                            }
                        })
                        ->where('status', 0)
                        ->first();

                    if ($existing) {
                        $existing->update($payload);
                        $updated[] = $existing->id;
                    } else {
                        $rec = StockHold::create($payload);
                        $created[] = $rec->id;
                    }
                } else {
                    // invoice-based: match by sale_id + item_id (only when both available)
                    $existing = null;
                    if ($saleId && $dbItemId) {
                        $existing = StockHold::where('sale_id', $saleId)
                            ->where('item_id', $dbItemId)
                            ->where('status', 0)
                            ->first();
                    }

                    if ($existing) {
                        $existing->update($payload);
                        $updated[] = $existing->id;
                    } else {
                        $rec = StockHold::create($payload);
                        $created[] = $rec->id;
                    }
                }

                $processed++;
            }

            if ($processed === 0) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'No items with hold_qty > 0 were submitted.',
                ], 422);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Stock hold saved successfully.',
                'processed' => $processed,
                'created' => $created,
                'updated' => $updated,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('StockHold store error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save stock hold.',
                'detail' => $e->getMessage(),
            ], 500);
        }
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
