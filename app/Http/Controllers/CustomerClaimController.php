<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Category;
use App\Models\ProductPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\CustomerClaim;
use App\Models\WarehouseStock;
use App\Models\StockHold;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class CustomerClaimController extends Controller
{
    public function index()
    {
        $claims = CustomerClaim::with(['party', 'product', 'warehouse', 'originalWarehouse', 'replacementProduct', 'replacementFromWarehouse', 'creator'])->latest()->get();
        return view('admin_panel.customer_claims.index', compact('claims'));
    }

    public function create()
    {
        $products = Product::where('status', 1)->orderBy('name')->get();
        $warehouses = Warehouse::orderBy('warehouse_name')->get();
        $claimNo = 'CLM-' . (CustomerClaim::count() + 1);
        
        return view('admin_panel.customer_claims.create', compact('products', 'warehouses', 'claimNo'));
    }

    public function edit($id)
    {
        $claim = CustomerClaim::findOrFail($id);
        if ($claim->status === 'Posted') {
            // Usually we show read-only, which we handled in blade
        }
        $products = Product::where('status', 1)->orderBy('name')->get();
        $warehouses = Warehouse::orderBy('warehouse_name')->get();
        
        return view('admin_panel.customer_claims.edit', compact('claim', 'products', 'warehouses'));
    }

    public function ajaxSave(Request $request)
    {
        $id = $request->id;
        $claim = $id ? CustomerClaim::find($id) : new CustomerClaim();

        if ($claim && $claim->status === 'Posted') {
            return response()->json(['ok' => false, 'msg' => 'Posted record cannot be edited.']);
        }

        $rules = [
            'claim_date' => 'required|date',
            'claim_type' => 'required',
            'party_id' => 'required',
            'product_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'msg' => 'Validation error', 'errors' => $validator->errors()]);
        }

        try {
            DB::beginTransaction();

            if (!$id) {
                $claim->claim_no = 'CLM-' . (CustomerClaim::count() + 1);
            }
            
            $claim->entry_date = $request->entry_date ?? date('Y-m-d');
            $claim->entry_time = $request->entry_time ?? date('H:i');
            $claim->claim_date = $request->claim_date ?? $claim->entry_date;
            $claim->claim_type = $request->claim_type;
            $claim->party_type = $request->party_type;
            $claim->party_id = $request->party_id;
            $claim->product_id = $request->product_id;
            $claim->mfg_date = $request->mfg_date;
            $claim->sales_price = $request->sales_price ?? 0;
            $claim->card_no = $request->card_no;
            $claim->bill_date = $request->bill_date;
            $claim->original_warehouse_id = $request->original_warehouse_id;
            $claim->claim_warehouse_id = $request->claim_warehouse_id;
            $claim->claim_income = $request->claim_income ?? 0;
            $claim->fault_found = $request->fault_found;
            $claim->remarks = $request->remarks;
            
            if ($request->claim_type === 'credit_note') {
                $claim->replacement_product_id = $request->replacement_product_id;
                $claim->replacement_sales_price = $request->replacement_sales_price ?? 0;
                $claim->replacement_from_warehouse_id = $request->replacement_from_warehouse_id;
                $claim->replacement_to_warehouse_id = $request->replacement_to_warehouse_id;
            }

            $currentStatus = $claim->status;
            $newStatus = $request->action === 'post' ? 'Posted' : 'Draft';
            
            $claim->status = $newStatus;
            $claim->created_by = auth()->id();
            $claim->save();

            if ($newStatus === 'Posted' && $currentStatus !== 'Posted') {
                $this->syncInventory($claim);
            }

            DB::commit();

            return response()->json([
                'ok' => true, 
                'msg' => 'Claim Saved Successfully', 
                'id' => $claim->id,
                'claim_no' => $claim->claim_no,
                'status' => $claim->status
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['ok' => false, 'msg' => $e->getMessage()]);
        }
    }

    public function post($id)
    {
        $claim = CustomerClaim::findOrFail($id);
        if ($claim->status === 'Posted') {
            return back()->with('error', 'Already posted.');
        }

        try {
            DB::beginTransaction();
            $claim->status = 'Posted';
            $claim->save();

            $this->syncInventory($claim);
            DB::commit();

            return back()->with('success', 'Claim posted and inventory updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Post failed: ' . $e->getMessage());
        }
    }

    private function syncInventory(CustomerClaim $claim)
    {
        // 1. Faulty Item Movement
        // Subtract from original warehouse
        if (isset($claim->original_warehouse_id)) {
            $this->adjustStock($claim->original_warehouse_id, $claim->product_id, -1);
        }
        
        // Add to claim warehouse
        if (isset($claim->claim_warehouse_id)) {
            $this->adjustStock($claim->claim_warehouse_id, $claim->product_id, 1);
        }

        // 2. Replacement Item (Credit Note)
        if ($claim->claim_type === 'credit_note' && $claim->replacement_product_id) {
            if (isset($claim->replacement_from_warehouse_id)) {
                $this->adjustStock($claim->replacement_from_warehouse_id, $claim->replacement_product_id, -1);
            }
        }

        // 3. Handle Claim Hold (Reservation)
        if ($claim->claim_type === 'claim_hold') {
            StockHold::create([
                'entry_date'   => $claim->entry_date,
                'entry_time'   => $claim->entry_time,
                'party_type'   => $claim->party_type,
                'party_id'     => $claim->party_id,
                'warehouse_id' => $claim->claim_warehouse_id,
                'product_id'   => $claim->product_id,
                'hold_qty'     => 1,
                'remarks'      => 'Reserved via Customer Claim Hold: ' . $claim->claim_no,
                'status'       => 0,
                'meta'         => ['claim_id' => $claim->id, 'claim_no' => $claim->claim_no]
            ]);
        }
    }

    private function adjustStock($warehouseId, $productId, $qty)
    {
        if ($warehouseId == 0) {
            // Update Shop Stock in Product table
            $product = Product::find($productId);
            if ($product) {
                $product->stock = ($product->stock ?? 0) + $qty;
                $product->save();
            }
        } else {
            // Update Warehouse Stock table
            $stock = WarehouseStock::firstOrNew([
                'warehouse_id' => $warehouseId,
                'product_id'   => $productId
            ]);
            
            $stock->quantity = ($stock->quantity ?? 0) + $qty;
            if (!$stock->exists) {
                $stock->status = 'Posted';
            }
            $stock->save();
        }
    }
}
