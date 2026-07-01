<?php

namespace App\Http\Controllers;

use App\Models\ClaimItemReceipt;
use App\Models\ClaimCreditNote;
use App\Models\ClaimItemReceiptItem;
use App\Models\ClaimAcceptanceItem;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Vendor;
use App\Models\Customer;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ClaimItemReceiptController extends Controller
{
    public function index(Request $request)
    {
        $receipts = ClaimItemReceipt::with(['vendor', 'customer', 'creator', 'fromWarehouse', 'toWarehouse']);
        $credits = ClaimCreditNote::with(['vendor', 'customer', 'creator', 'fromWarehouse', 'toWarehouse']);
        
        if ($request->start_date) {
            $receipts->whereDate('date', '>=', $request->start_date);
            $credits->whereDate('date', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $receipts->whereDate('date', '<=', $request->end_date);
            $credits->whereDate('date', '<=', $request->end_date);
        }
        if ($request->status) {
            $receipts->where('status', $request->status);
            $credits->where('status', $request->status);
        }
        
        $receiptList = $receipts->get()->map(function($item) {
            $item->doc_type = 'receipt';
            return $item;
        });
        
        $creditList = $credits->get()->map(function($item) {
            $item->doc_type = 'credit';
            return $item;
        });
        
        $vouchers = $receiptList->concat($creditList)->sortByDesc('created_at')->values();
        
        return view('admin_panel.claim_item_receipt.index', compact('vouchers'));
    }

    public function create()
    {
        $voucherNo = ClaimItemReceipt::generateVoucherNo();
        $creditNoteVoucherNo = \App\Models\ClaimCreditNote::generateVoucherNo();
        $warehouses = Warehouse::orderBy('warehouse_name')->get();
        $companyWarehouses = Warehouse::withoutGlobalScope('exclude_claims')->where('claim_type', 'company')->orderBy('warehouse_name')->get();
        $AccountHeads = \App\Models\AccountHead::where('status', 1)->get();
        return view('admin_panel.claim_item_receipt.create', compact('voucherNo', 'creditNoteVoucherNo', 'warehouses', 'companyWarehouses', 'AccountHeads'));
    }

    public function edit($id)
    {
        $voucher = ClaimItemReceipt::with('items.product')->findOrFail($id);
        if ($voucher->status === 'Posted') {
            return redirect()->route('claim-item-receipt.index')->with('error', 'Posted vouchers cannot be edited.');
        }
        $warehouses = Warehouse::orderBy('warehouse_name')->get();
        $companyWarehouses = Warehouse::withoutGlobalScope('exclude_claims')->where('claim_type', 'company')->orderBy('warehouse_name')->get();
        $AccountHeads = \App\Models\AccountHead::where('status', 1)->get();
        return view('admin_panel.claim_item_receipt.create', compact('voucher', 'warehouses', 'companyWarehouses', 'AccountHeads'));
    }

    public function fetchByBTR(Request $request)
    {
        $btr = $request->btr;
        $items = ClaimAcceptanceItem::with('product:id,name')
            ->where('btr_no', $btr)
            ->where('status', 'Posted') // Should be posted in acceptance
            ->get();

        if ($items->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No items found for this BTR# or Acceptance not Posted.']);
        }

        $data = $items->map(function($item) {
            return [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name ?? 'N/A',
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
            'to_warehouse_id'   => 'required',
            'party_type'        => 'required',
            'party_id'          => 'required',
            'product_id'        => 'required|array',
            'quantity'          => 'required|array',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $voucher = $id ? ClaimItemReceipt::findOrFail($id) : new ClaimItemReceipt();
            
            if ($voucher->status === 'Posted') {
                return response()->json(['success' => false, 'message' => 'Already posted'], 422);
            }

            if (!$id) {
                $voucher->voucher_no = ClaimItemReceipt::generateVoucherNo();
            }
            
            $voucher->date              = $request->date;
            $voucher->entry_date        = $request->entry_date ?? date('Y-m-d');
            $voucher->entry_time        = $request->entry_time ?? date('H:i');
            $voucher->from_warehouse_id = $request->from_warehouse_id;
            $voucher->to_warehouse_id   = $request->to_warehouse_id;
            $voucher->party_type        = $request->party_type;
            $voucher->party_id          = $request->party_id;
            $voucher->remarks           = $request->remarks;
            $voucher->status            = $status;
            $voucher->created_by        = auth()->id();
            $voucher->save();

            if ($id) {
                ClaimItemReceiptItem::where('claim_item_receipt_id', $id)->delete();
            }

            foreach ($request->product_id as $index => $pid) {
                $qty = (float)($request->quantity[$index] ?? 0);
                if ($qty <= 0) continue;

                ClaimItemReceiptItem::create([
                    'claim_item_receipt_id' => $voucher->id,
                    'product_id'            => $pid,
                    'btr_no'                => $request->btr_no[$index] ?? null,
                    'quantity'              => $qty,
                    'status'                => $status
                ]);

                if ($status === 'Posted') {
                    // 1. Deduct from Company Claim Stock (-) Cr
                    $this->adjustStock($voucher->from_warehouse_id, $pid, -$qty);
                    
                    // 2. Add to Selected Warehouse (+) Dr
                    $this->adjustStock($voucher->to_warehouse_id, $pid, $qty);
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Claim Receipt ' . ($status == 'Posted' ? 'Posted' : 'Saved') . ' successfully.',
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
            $voucher = ClaimItemReceipt::with('items')->findOrFail($id);
            if ($voucher->status === 'Posted') return back()->with('error', 'Already posted.');

            $voucher->update(['status' => 'Posted']);
            foreach ($voucher->items as $item) {
                $item->update(['status' => 'Posted']);
                $this->adjustStock($voucher->from_warehouse_id, $item->product_id, -$item->quantity);
                $this->adjustStock($voucher->to_warehouse_id, $item->product_id, $item->quantity);
            }

            DB::commit();
            return back()->with('success', 'Claim Receipt Posted and Stock updated.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function print($id)
    {
        $voucher = ClaimItemReceipt::with(['items.product', 'vendor', 'customer', 'fromWarehouse', 'toWarehouse'])->findOrFail($id);
        return view('admin_panel.claim_item_receipt.print', compact('voucher'));
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
            $stock = WarehouseStock::firstOrNew([
                'warehouse_id' => $warehouseId,
                'product_id'   => $productId
            ]);
            $stock->quantity = ($stock->quantity ?? 0) + $qty;
            if (!$stock->exists) $stock->status = 'Posted';
            $stock->save();
        }
    }
}
