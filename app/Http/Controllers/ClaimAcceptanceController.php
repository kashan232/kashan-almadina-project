<?php

namespace App\Http\Controllers;

use App\Models\ClaimAcceptance;
use App\Models\ClaimAcceptanceItem;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\Customer;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ClaimAcceptanceController extends Controller
{
    public function index(Request $request)
    {
        $query = ClaimAcceptance::with(['vendor', 'customer', 'creator']);
        
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
        return view('admin_panel.claim_acceptance.index', compact('vouchers'));
    }

    public function create()
    {
        $voucherNo = ClaimAcceptance::generateVoucherNo();
        $customerWarehouses = Warehouse::withoutGlobalScopes()->where('claim_type', 'customer')->orderBy('warehouse_name')->get();
        $companyWarehouses = Warehouse::withoutGlobalScopes()->where('claim_type', 'company')->orderBy('warehouse_name')->get();
        return view('admin_panel.claim_acceptance.create', compact('voucherNo', 'customerWarehouses', 'companyWarehouses'));
    }

    public function edit($id)
    {
        $voucher = ClaimAcceptance::with('items.product')->findOrFail($id);
        if ($voucher->status === 'Posted') {
            return redirect()->route('claim-acceptance.index')->with('error', 'Posted vouchers cannot be edited.');
        }
        $customerWarehouses = Warehouse::withoutGlobalScopes()->where('claim_type', 'customer')->orderBy('warehouse_name')->get();
        $companyWarehouses = Warehouse::withoutGlobalScopes()->where('claim_type', 'company')->orderBy('warehouse_name')->get();
        return view('admin_panel.claim_acceptance.create', compact('voucher', 'customerWarehouses', 'companyWarehouses'));
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

            $voucher = $id ? ClaimAcceptance::findOrFail($id) : new ClaimAcceptance();
            
            if ($voucher->status === 'Posted') {
                return response()->json(['success' => false, 'message' => 'Already posted'], 422);
            }

            if (!$id) {
                $voucher->voucher_no = ClaimAcceptance::generateVoucherNo();
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

            // Clear old items if editing
            if ($id) {
                ClaimAcceptanceItem::where('claim_acceptance_id', $id)->delete();
            }

            foreach ($request->product_id as $index => $pid) {
                $qty = (float)($request->quantity[$index] ?? 0);
                if ($qty <= 0) continue;

                ClaimAcceptanceItem::create([
                    'claim_acceptance_id' => $voucher->id,
                    'product_id'          => $pid,
                    'btr_no'              => $request->btr_no[$index] ?? null,
                    'quantity'            => $qty,
                    'status'              => $status
                ]);

                if ($status === 'Posted') {
                    // 1. Deduct from Source Warehouse
                    $this->adjustStock($voucher->from_warehouse_id, $pid, -$qty);
                    
                    // 2. Add to Destination Warehouse
                    $this->adjustStock($voucher->to_warehouse_id, $pid, $qty);
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Claim Acceptance ' . ($status == 'Posted' ? 'Posted' : 'Saved') . ' successfully.',
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
            $voucher = ClaimAcceptance::with('items')->findOrFail($id);
            if ($voucher->status === 'Posted') return back()->with('error', 'Already posted.');

            $voucher->update(['status' => 'Posted']);
            foreach ($voucher->items as $item) {
                $item->update(['status' => 'Posted']);
                
                // 1. Deduct from Source Warehouse
                $this->adjustStock($voucher->from_warehouse_id, $item->product_id, -$item->quantity);
                
                // 2. Add to Destination Warehouse
                $this->adjustStock($voucher->to_warehouse_id, $item->product_id, $item->quantity);
            }

            DB::commit();
            return back()->with('success', 'Claim Acceptance Posted. Stock transferred successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
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

    public function destroy($id)
    {
        $voucher = ClaimAcceptance::findOrFail($id);
        if ($voucher->status === 'Posted') {
            return back()->with('error', 'Posted vouchers cannot be deleted.');
        }
        $voucher->items()->delete();
        $voucher->delete();
        return redirect()->route('claim-acceptance.index')->with('success', 'Claim Acceptance deleted successfully.');
    }

    public function print($id)
    {
        $voucher = ClaimAcceptance::with(['items.product', 'vendor', 'customer', 'creator'])->findOrFail($id);
        return view('admin_panel.claim_acceptance.print', compact('voucher'));
    }

    public function partyList(Request $request)
    {
        $type = $request->type;
        $q = $request->q;
        
        if ($type === 'vendor') {
            $data = Vendor::when($q, function($query) use ($q) {
                $query->where('name', 'like', "%$q%")->orWhere('id', 'like', "%$q%");
            })->limit(15)->get()->map(fn($v) => ['id' => $v->id, 'text' => $v->id . ' - ' . $v->name]);
        } elseif ($type === 'walkin') {
            $data = Customer::where('customer_type', 'Walking Customer')
                ->when($q, function($query) use ($q) {
                    $query->where('customer_name', 'like', "%$q%")->orWhere('id', 'like', "%$q%");
                })->limit(15)->get()->map(fn($c) => ['id' => $c->id, 'text' => $c->id . ' - ' . $c->customer_name]);
        } else {
            $data = Customer::where('customer_type', 'Main Customer')
                ->when($q, function($query) use ($q) {
                    $query->where('customer_name', 'like', "%$q%")->orWhere('id', 'like', "%$q%");
                })->limit(15)->get()->map(fn($c) => ['id' => $c->id, 'text' => $c->id . ' - ' . $c->customer_name]);
        }
        return response()->json($data);
    }
}
