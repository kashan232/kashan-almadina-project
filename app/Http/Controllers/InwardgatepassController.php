<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\InwardGatepass;
use App\Models\InwardGatepassItem;
use App\Models\Stock;
use App\Models\Product;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InwardgatepassController extends Controller
{
    public function pdf($id)
    {
        $gatepass = InwardGatepass::with(['branch', 'warehouse', 'vendor', 'items.product'])->findOrFail($id);
        return view('admin_panel.inward.print', compact('gatepass'));
    }

    // 1. List all inward gatepasses
    public function index(Request $request)
    {
        $query = InwardGatepass::with('items.product', 'branch', 'warehouse', 'vendor')->latest();

        if ($request->filled('start_date')) {
            $query->whereDate('gatepass_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('gatepass_date', '<=', $request->end_date);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('vendor')) {
            $query->whereHas('vendor', fn($q) => $q->where('name', 'like', '%'.$request->vendor.'%'));
        }

        $gatepasses = $query->get();
        return view('admin_panel.inward.index', compact('gatepasses'));
    }

    // 2. Show create form
    public function create()
    {
        $branches   = Branch::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('warehouse_name')->get();
        $vendors    = Vendor::orderBy('name')->get();
        return view('admin_panel.inward.create', compact('branches', 'warehouses', 'vendors'));
    }

    // 3. Store gatepass + items + update stock
    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'branch_id'      => 'required|exists:branches,id',
    //         'warehouse_id'   => 'required|exists:warehouses,id',
    //         'vendor_id'      => 'required|exists:vendors,id',
    //         'gatepass_date'  => 'required|date',
    //         'product_id'     => 'required|array|min:1',
    //         'product_id.*'   => 'required|exists:products,id',
    //         'qty'            => 'required|array',
    //         'qty.*'          => 'required|integer|min:1',
    //     ]);

    //     DB::transaction(function () use ($request) {
    //         $gatepass = InwardGatepass::create([
    //             'branch_id'    => $request->branch_id,
    //             'warehouse_id' => $request->warehouse_id,
    //             'vendor_id'    => $request->vendor_id,
    //             'gatepass_date'=> $request->gatepass_date,
    //             'note'         => $request->note ?? null,
    //             'transport_name'=> $request->transport_name ?? null,
    //             'created_by'   => auth()->id() ?? null,
    //         ]);

    //         $productIds = $request->input('product_id', []);
    //         $qtys       = $request->input('qty', []);

    //         for ($i = 0; $i < count($productIds); $i++) {
    //             $pid = $productIds[$i];
    //             $q   = isset($qtys[$i]) ? (int)$qtys[$i] : 0;
    //             if (!$pid || $q <= 0) continue;

    //             InwardGatepassItem::create([
    //                 'inward_gatepass_id' => $gatepass->id,
    //                 'product_id'         => $pid,
    //                 'qty'                => $q,
    //             ]);

    //             $stock = Stock::firstOrNew([
    //                 'branch_id'    => $request->branch_id,
    //                 'warehouse_id' => $request->warehouse_id,
    //                 'product_id'   => $pid,
    //             ]);
    //             $stock->qty = ($stock->qty ?? 0) + $q;
    //             $stock->save();
    //         }
    //     });

    //     return redirect()->route('InwardGatepass.home')
    //                      ->with('success','Inward Gatepass Created Successfully');
    // }
    public function store(Request $request)
    {
        $request->validate([
            'branch_id'      => 'required|exists:branches,id',
            'warehouse_id'   => 'required|exists:warehouses,id',
            'vendor_id'      => 'required|exists:vendors,id',
            'gatepass_date'  => 'required|date',
            'product_id'     => 'required|array|min:1',
            'product_id.*'   => 'required|exists:products,id',
            'qty'            => 'required|array',
            'qty.*'          => 'required|numeric|min:0.01',
        ]);

        $gatepassId = DB::transaction(function () use ($request) {
            $invoiceNo = InwardGatepass::generateInvoiceNo();
            // 1. Create Gatepass
            $gatepass = InwardGatepass::create([
                'invoice_no'     => $invoiceNo,
                'branch_id'     => $request->branch_id,
                'warehouse_id'  => $request->warehouse_id,
                'vendor_id'     => $request->vendor_id,
                'gatepass_date' => $request->gatepass_date,
                'transport_name' => $request->transport_name,
                'gatepass_no'      => $request->bilty_no,
                'remarks'          => $request->note,
                'status'        => 'Unposted', // initially Unposted
                'created_by'    => auth()->id(),
            ]);

            // 2. Save Products
            $productIds = $request->input('product_id', []);
            $qtys       = $request->input('qty', []);
            $brands     = $request->input('brand', []);

            foreach ($productIds as $i => $pid) {
                $q = isset($qtys[$i]) ? (float)$qtys[$i] : 0;
                if (!$pid || $q <= 0) continue;

                InwardGatepassItem::create([
                    'inward_gatepass_id' => $gatepass->id,
                    'product_id'         => $pid,
                    'brand'              => $brands[$i] ?? null,
                    'qty'                => $q,
                ]);
                // Stock is updated only on POST
            }
            return $gatepass->id;
        });

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'id'      => $gatepassId,
                'status'  => 'Unposted',
                'message' => 'Inward Gatepass Saved as Unposted'
            ]);
        }

        return redirect()->route('InwardGatepass.home')
            ->with('success', 'Inward Gatepass Saved as Unposted');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'branch_id'      => 'required|exists:branches,id',
            'warehouse_id'   => 'required|exists:warehouses,id',
            'vendor_id'      => 'required|exists:vendors,id',
            'gatepass_date'  => 'required|date',
            'product_id'     => 'required|array|min:1',
            'product_id.*'   => 'required|exists:products,id',
            'qty'            => 'required|array',
            'qty.*'          => 'required|numeric|min:0.01',
        ]);

        $gatepassId = DB::transaction(function () use ($request, $id) {
            $gatepass = InwardGatepass::findOrFail($id);
            if ($gatepass->status === 'Posted') {
                throw new \Exception("Cannot update a posted gatepass.");
            }

            // Update Header
            $gatepass->update([
                'branch_id'      => $request->branch_id,
                'warehouse_id'   => $request->warehouse_id,
                'vendor_id'      => $request->vendor_id,
                'gatepass_date'  => $request->gatepass_date,
                'transport_name' => $request->transport_name,
                'gatepass_no'    => $request->bilty_no,
                'remarks'        => $request->note,
            ]);

            // Delete old items
            $gatepass->items()->delete();

            // Insert new items
            $productIds = $request->input('product_id', []);
            $qtys       = $request->input('qty', []);
            $brands     = $request->input('brand', []);

            foreach ($productIds as $i => $pid) {
                $q = isset($qtys[$i]) ? (float)$qtys[$i] : 0;
                if (!$pid || $q <= 0) continue;

                InwardGatepassItem::create([
                    'inward_gatepass_id' => $gatepass->id,
                    'product_id'         => $pid,
                    'brand'              => $brands[$i] ?? null,
                    'qty'                => $q,
                ]);
            }
            return $gatepass->id;
        });

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'id'      => $gatepassId,
                'status'  => 'Unposted',
                'message' => 'Inward Gatepass Updated successfully'
            ]);
        }

        return redirect()->route('InwardGatepass.home')
            ->with('success', 'Inward Gatepass Updated successfully');
    }

    public function post($id)
    {
        $gatepass = InwardGatepass::with('items')->findOrFail($id);
        if ($gatepass->status === 'Posted') {
            return response()->json(['success' => false, 'message' => 'Already posted.'], 422);
        }

        DB::transaction(function () use ($gatepass) {
            // Update Stock
            foreach ($gatepass->items as $item) {
                // Global Stock
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->stock = ($product->stock ?? 0) + $item->qty;
                    $product->save();
                }

                // Branch/Warehouse Stock
                $stock = Stock::firstOrNew([
                    'branch_id'    => $gatepass->branch_id,
                    'warehouse_id' => $gatepass->warehouse_id,
                    'product_id'   => $item->product_id,
                ]);
                $stock->qty = ($stock->qty ?? 0) + $item->qty;
                $stock->save();
            }

            $gatepass->update(['status' => 'Posted']);
        });

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Inward Gatepass Posted Successfully'
            ]);
        }

        return redirect()->back()->with('success', 'Inward Gatepass Posted Successfully');
    }



    // 4. Show single gatepass
    public function show($id)
    {
        $gatepass = InwardGatepass::with('items.product', 'branch', 'warehouse', 'vendor')->findOrFail($id);
        return view('admin_panel.inward.show', compact('gatepass'));
    }

    // 5. Edit gatepass
    public function edit($id)
    {
        $gatepass = InwardGatepass::with('items')->findOrFail($id);
        $branches   = Branch::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('warehouse_name')->get();
        $vendors    = Vendor::orderBy('name')->get();

        return view('admin_panel.inward.edit', compact('gatepass', 'branches', 'warehouses', 'vendors'));
    }

    // 7. Delete gatepass
    public function destroy($id)
    {
        $gatepass = InwardGatepass::findOrFail($id);
        if ($gatepass->status === 'Posted') {
            return redirect()->back()->with('error', 'Cannot delete a posted gatepass.');
        }

        DB::transaction(function () use ($gatepass) {
            $gatepass->items()->delete();
            $gatepass->delete();
        });

        return redirect()->route('InwardGatepass.home')
            ->with('success', 'Inward Gatepass Deleted Successfully');
    }

    // 8. Search products
    public function searchProducts(Request $request)
    {
        $query = $request->get('q');

        if (blank($query)) {
            $products = Product::with(['brandRelation'])
                ->where('status', 1)
                ->latest()
                ->limit(20)
                ->get();
        } else {
            $products = Product::with(['brandRelation'])
                ->where('status', 1)
                ->where(function($q) use ($query) {
                    $q->where('name', 'like', '%' . $query . '%')
                      ->orWhere('id', $query);
                })
                ->limit(20)
                ->get();
        }

        $results = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'brand' => $product->brandRelation ? $product->brandRelation->name : null,
                'stock' => $product->stock,
            ];
        });

        return response()->json($results);
    }
}
