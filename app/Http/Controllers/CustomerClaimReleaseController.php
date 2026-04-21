<?php

namespace App\Http\Controllers;

use App\Models\CustomerClaim;
use App\Models\CustomerClaimRelease;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Models\StockHold;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CustomerClaimReleaseController extends Controller
{
    public function index(Request $request)
    {
        $query = CustomerClaimRelease::with(['claim', 'product', 'warehouse', 'party', 'creator']);

        if ($request->filled('start_date')) {
            $query->whereDate('release_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('release_date', '<=', $request->end_date);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $releases = $query->latest()->get();
        return view('admin_panel.customer_claims.release.index', compact('releases'));
    }

    public function post($id)
    {
        try {
            DB::beginTransaction();
            $release = CustomerClaimRelease::findOrFail($id);
            if ($release->status === 'Posted') {
                return back()->with('error', 'Already posted.');
            }

            $release->status = 'Posted';
            $release->save();

            $this->processReleaseInventory($release);

            DB::commit();
            return back()->with('success', 'Claim Release Posted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error posting release: ' . $e->getMessage());
        }
    }

    public function create()
    {
        $warehouses = Warehouse::orderBy('warehouse_name')->get();
        $releaseNo = 'CLM-REL-' . (CustomerClaimRelease::count() + 1);
        return view('admin_panel.customer_claims.release.create', compact('warehouses', 'releaseNo'));
    }

    public function getHoldClaims(Request $request)
    {
        $q = $request->q;
        $claims = CustomerClaim::where('claim_type', 'claim_hold')
            ->where('status', 'Posted')
            ->whereDoesntHave('release', function($query) {
                $query->where('status', 'Posted');
            })
            ->when($q, function($query) use ($q) {
                $query->where('claim_no', 'like', "%$q%");
            })
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'text' => $item->claim_no . ' (Date: ' . $item->claim_date . ')'
                ];
            });

        return response()->json($claims);
    }

    public function getClaimDetails($id)
    {
        $claim = CustomerClaim::with(['product', 'party', 'warehouse'])->findOrFail($id);
        
        $partyName = '';
        if ($claim->party_type === 'vendor') {
            $partyName = $claim->party->name ?? 'N/A';
        } else {
            $partyName = $claim->party->customer_name ?? 'N/A';
        }

        return response()->json([
            'claim_id' => $claim->id,
            'claim_no' => $claim->claim_no,
            'party_type' => $claim->party_type,
            'party_id' => $claim->party_id,
            'party_name' => $partyName,
            'product_id' => $claim->product_id,
            'product_name' => $claim->product->name ?? 'N/A',
            'warehouse_id' => $claim->claim_warehouse_id,
            'warehouse_name' => $claim->warehouse->warehouse_name ?? 'Shop',
            'hold_qty' => 1,
        ]);
    }

    public function ajaxSave(Request $request)
    {
        $id = $request->id;
        $release = $id ? CustomerClaimRelease::find($id) : new CustomerClaimRelease();

        if ($release && $release->status === 'Posted') {
            return response()->json(['ok' => false, 'msg' => 'Posted record cannot be edited.']);
        }

        $rules = [
            'release_date' => 'required|date',
            'claim_id' => 'required',
            'warehouse_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'msg' => 'Validation error', 'errors' => $validator->errors()]);
        }

        try {
            DB::beginTransaction();

            if (!$id) {
                $release->release_no = 'CLM-REL-' . (CustomerClaimRelease::count() + 1);
            }

            $release->release_date = $request->release_date;
            $release->claim_id = $request->claim_id;
            $release->party_type = $request->party_type;
            $release->party_id = $request->party_id;
            $release->product_id = $request->product_id;
            $release->warehouse_id = $request->warehouse_id;
            $release->release_qty = $request->release_qty ?? 1;
            $release->remarks = $request->remarks;
            
            $newStatus = $request->action === 'post' ? 'Posted' : 'Draft';
            $release->status = $newStatus;
            $release->created_by = auth()->id();
            $release->save();

            if ($newStatus === 'Posted') {
                $this->processReleaseInventory($release);
            }

            DB::commit();

            return response()->json([
                'ok' => true,
                'msg' => 'Release ' . $newStatus . ' Successfully',
                'id' => $release->id,
                'release_no' => $release->release_no,
                'status' => $release->status
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['ok' => false, 'msg' => $e->getMessage()]);
        }
    }

    private function processReleaseInventory(CustomerClaimRelease $release)
    {
        // 1. Physical Stock Deduction (Deliver From)
        $this->adjustStock($release->warehouse_id, $release->product_id, -($release->release_qty));

        // 2. Logic Reserve Deduction (StockHold)
        // We find the hold entry linked to this claim
        $hold = StockHold::where('meta->claim_id', (string)$release->claim_id)
            ->where('status', 0)
            ->first();

        if ($hold) {
            $remaining = (float)$hold->hold_qty - (float)$release->release_qty;
            $hold->hold_qty = max(0, $remaining);
            if ($hold->hold_qty <= 0) {
                $hold->status = 1; // Mark as Released
            }
            $hold->save();
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
