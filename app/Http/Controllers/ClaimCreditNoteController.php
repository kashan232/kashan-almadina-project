<?php

namespace App\Http\Controllers;

use App\Services\PartyLedgerService;
use App\Models\ClaimCreditNote;
use App\Models\ClaimCreditNoteItem;
use App\Models\ClaimAcceptanceItem;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Vendor;
use App\Models\Customer;
use App\Models\AccountHead;
use App\Models\WarehouseStock;
use App\Models\VendorLedger;
use App\Models\CustomerLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ClaimCreditNoteController extends Controller
{
    public function index(Request $request)
    {
        $query = ClaimCreditNote::with(['vendor', 'customer', 'creator']);
        
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
        return view('admin_panel.claim_credit_note.index', compact('vouchers'));
    }

    public function create()
    {
        $voucherNo = ClaimCreditNote::generateVoucherNo();
        $warehouses = Warehouse::orderBy('warehouse_name')->get();
        $AccountHeads = AccountHead::where('status', 1)->get();
        return view('admin_panel.claim_credit_note.create', compact('voucherNo', 'warehouses', 'AccountHeads'));
    }

    public function edit($id)
    {
        $voucher = ClaimCreditNote::with(['items.product', 'whtAccount'])->findOrFail($id);
        if ($voucher->status === 'Posted') {
            return redirect()->route('claim-credit-note.index')->with('error', 'Posted vouchers cannot be edited.');
        }
        $warehouses = Warehouse::orderBy('warehouse_name')->get();
        $AccountHeads = AccountHead::where('status', 1)->get();
        return view('admin_panel.claim_credit_note.create', compact('voucher', 'warehouses', 'AccountHeads'));
    }

    public function show($id)
    {
        $voucher = ClaimCreditNote::with(['items.product.brandRelation', 'vendor', 'customer', 'whtAccount'])->findOrFail($id);
        $warehouses = Warehouse::orderBy('warehouse_name')->get();
        $AccountHeads = AccountHead::where('status', 1)->get();
        $viewMode = true;

        return view('admin_panel.claim_credit_note.create', compact('voucher', 'warehouses', 'AccountHeads', 'viewMode'));
    }

    public function fetchByBTR(Request $request)
    {
        $btr = $request->btr;
        $items = ClaimAcceptanceItem::with(['product:id,name,brand_id', 'product.brandRelation', 'product.latestPrice'])
            ->where('btr_no', $btr)
            ->where('status', 'Posted')
            ->get();

        if ($items->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No items found for this BTR# or Acceptance not Posted.']);
        }

        $data = $items->map(function($item) {
            $product = $item->product;
            $priceInfo = $product->latestPrice;
            return [
                'product_id' => $item->product_id,
                'product_name' => $product->name ?? 'N/A',
                'brand_name' => $product->brandRelation->brand_name ?? '-',
                'price' => $priceInfo->sale_net_amount ?? 0,
                'retail_price' => $priceInfo->sale_retail_price ?? 0,
                'quantity' => $item->quantity,
                'btr_no' => $item->btr_no
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function ajaxSave(Request $request)
    {
        $id = $request->id;
        $status = $request->action === 'post' ? 'Posted' : 'Draft';
        
        $rules = [
            'date'              => 'required|date',
            'from_warehouse_id' => 'required',
            'to_warehouse_id'   => 'nullable',
            'party_type'        => 'required',
            'party_id'          => 'required',
            'product_id'        => 'required|array',
            'qty'               => 'required|array',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $voucher = $id ? ClaimCreditNote::findOrFail($id) : new ClaimCreditNote();
            
            if ($voucher->status === 'Posted') {
                return response()->json(['success' => false, 'message' => 'Already posted'], 422);
            }

            if (!$id) {
                $voucher->voucher_no = ClaimCreditNote::generateVoucherNo();
            }
            
            $voucher->date              = $request->date;
            $voucher->entry_date        = $request->entry_date ?? date('Y-m-d');
            $voucher->entry_time        = $request->entry_time ?? date('H:i');
            $voucher->party_type        = $request->party_type;
            $voucher->party_id          = $request->party_id;
            $voucher->from_warehouse_id = $request->from_warehouse_id;
            $voucher->to_warehouse_id   = $request->to_warehouse_id;
            $voucher->subtotal          = $request->subtotal;
            $voucher->total_discount    = $request->total_discount;
            $voucher->wht_percent       = $request->wht_percent;
            $voucher->wht_amount        = $request->wht_amount;
            $voucher->wht_account_id    = $request->wht_account_id;
            $voucher->wht_type          = $request->wht_type ?? 'percent';
            $voucher->net_total         = $request->net_total;
            $voucher->remarks           = $request->remarks;
            $voucher->status            = $status;
            $voucher->created_by        = auth()->id();
            $voucher->save();

            if ($id) {
                ClaimCreditNoteItem::where('claim_credit_note_id', $id)->delete();
            }

            foreach ($request->product_id as $index => $pid) {
                $qty = (float)($request->qty[$index] ?? 0);
                if ($qty <= 0) continue;

                $price = (float)($request->price[$index] ?? 0);
                $retail = (float)($request->retail_price[$index] ?? 0);
                $disc_pct = (float)($request->discount_percent[$index] ?? 0);
                $disc_amt = (float)($request->discount_amount[$index] ?? 0);
                $line_total = (float)($request->line_total[$index] ?? 0);

                ClaimCreditNoteItem::create([
                    'claim_credit_note_id' => $voucher->id,
                    'product_id'           => $pid,
                    'btr_no'               => $request->btr_no[$index] ?? null,
                    'price'                => $price,
                    'retail_price'         => $retail,
                    'discount_percent'     => $disc_pct,
                    'discount_amount'      => $disc_amt,
                    'quantity'             => $qty,
                    'amount'               => $qty * $price,
                    'line_total'           => $line_total,
                    'status'               => $status
                ]);

                if ($status === 'Posted') {
                    // 1. Stock Adjustments
                    if ($voucher->from_warehouse_id !== null) {
                        $this->adjustStock($voucher->from_warehouse_id, $pid, -$qty);
                    }
                    if ($voucher->to_warehouse_id !== null) {
                        $this->adjustStock($voucher->to_warehouse_id, $pid, $qty);
                    }
                }
            }

            if ($status === 'Posted') {
                // 2. Ledger Adjustment
                $this->updateLedger($voucher);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Claim Credit Note ' . ($status == 'Posted' ? 'Posted' : 'Saved') . ' successfully.',
                'id'      => $voucher->id,
                'status'  => $status
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function post($id)
    {
        try {
            DB::beginTransaction();
            $voucher = ClaimCreditNote::with('items')->findOrFail($id);
            if ($voucher->status === 'Posted') return back()->with('error', 'Already posted.');

            $voucher->update(['status' => 'Posted']);
            foreach ($voucher->items as $item) {
                $item->update(['status' => 'Posted']);
                if ($voucher->from_warehouse_id !== null) {
                    $this->adjustStock($voucher->from_warehouse_id, $item->product_id, -$item->quantity);
                }
                if ($voucher->to_warehouse_id !== null) {
                    $this->adjustStock($voucher->to_warehouse_id, $item->product_id, $item->quantity);
                }
            }

            $this->updateLedger($voucher);

            DB::commit();
            return back()->with('success', 'Claim Credit Note Posted and Ledger updated.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function print($id)
    {
        $voucher = ClaimCreditNote::with(['items.product', 'vendor', 'customer', 'fromWarehouse', 'toWarehouse'])->findOrFail($id);
        return view('admin_panel.claim_credit_note.print', compact('voucher'));
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
            $stock = WarehouseStock::firstOrNew(['warehouse_id' => $warehouseId, 'product_id' => $productId]);
            $stock->quantity = ($stock->quantity ?? 0) + $qty;
            if (!$stock->exists) $stock->status = 'Posted';
            $stock->save();
        }
    }

    private function updateLedger($voucher)
    {
        $amount = (float) $voucher->net_total;
        if ($amount <= 0) {
            return;
        }

        app(PartyLedgerService::class)->postClaimDebit(
            $voucher->party_type ?? 'customer',
            (int) $voucher->party_id,
            $amount,
            $voucher->date ?? now()->toDateString(),
            'CIR: ' . $voucher->voucher_no
        );
    }
}
