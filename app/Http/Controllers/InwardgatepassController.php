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
        $pdf = Pdf::loadView('admin_panel.inward.pdf', compact('gatepass'));
        return $pdf->download('gatepass_' . $gatepass->id . '.pdf');
    }

    // 1. List all inward gatepasses
    public function index()
    {
        $gatepasses = InwardGatepass::with('items.product', 'branch', 'warehouse', 'vendor')
            ->latest()->get();
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
            'qty.*'          => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($request) {
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
                'status'        => 'pending', // default status
                'created_by'    => auth()->id(),
            ]);

            // 2. Save Products
            $productIds = $request->input('product_id', []);
            $qtys       = $request->input('qty', []);
            $brands     = $request->input('brand', []); // optional brand

            foreach ($productIds as $i => $pid) {
                $q = isset($qtys[$i]) ? (int)$qtys[$i] : 0;
                if (!$pid || $q <= 0) continue;

                InwardGatepassItem::create([
                    'inward_gatepass_id' => $gatepass->id,
                    'product_id'         => $pid,
                    'brand'              => $brands[$i] ?? null,
                    'qty'                => $q,
                ]);

                // 3. Update Stock
                $stock = Stock::firstOrNew([
                    'branch_id'    => $request->branch_id,
                    'warehouse_id' => $request->warehouse_id,
                    'product_id'   => $pid,
                ]);
                $stock->qty = ($stock->qty ?? 0) + $q;
                $stock->save();
            }
        });

        return redirect()->route('InwardGatepass.home')
            ->with('success', 'Inward Gatepass Created Successfully');
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

    // 6. Update gatepass + adjust stock
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
            'qty.*'          => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($request, $id) {
            $gatepass = InwardGatepass::with('items')->findOrFail($id);

            // rollback stock of old items
            foreach ($gatepass->items as $item) {
                $stock = Stock::where([
                    'branch_id'    => $gatepass->branch_id,
                    'warehouse_id' => $gatepass->warehouse_id,
                    'product_id'   => $item->product_id,
                ])->first();
                if ($stock) {
                    $stock->qty -= $item->qty;
                    if ($stock->qty < 0) $stock->qty = 0; // safety
                    $stock->save();
                }
            }

            // delete old items
            InwardGatepassItem::where('inward_gatepass_id', $gatepass->id)->delete();

            // update header
            $gatepass->update([
                'branch_id'    => $request->branch_id,
                'warehouse_id' => $request->warehouse_id,
                'vendor_id'    => $request->vendor_id,
                'gatepass_date' => $request->gatepass_date,
                'note'         => $request->note ?? null,
                'transport_name' => $request->transport_name ?? null,
            ]);

            // insert new items + stock update
            $productIds = $request->input('product_id', []);
            $qtys       = $request->input('qty', []);
            for ($i = 0; $i < count($productIds); $i++) {
                $pid = $productIds[$i];
                $q   = isset($qtys[$i]) ? (int)$qtys[$i] : 0;
                if (!$pid || $q <= 0) continue;

                InwardGatepassItem::create([
                    'inward_gatepass_id' => $gatepass->id,
                    'product_id'         => $pid,
                    'qty'                => $q,
                ]);

                $stock = Stock::firstOrNew([
                    'branch_id'    => $request->branch_id,
                    'warehouse_id' => $request->warehouse_id,
                    'product_id'   => $pid,
                ]);
                $stock->qty = ($stock->qty ?? 0) + $q;
                $stock->save();
            }
        });

        return redirect()->route('InwardGatepass.home')
            ->with('success', 'Inward Gatepass Updated Successfully');
    }

    // 7. Delete gatepass + adjust stock
    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            $gatepass = InwardGatepass::with('items')->findOrFail($id);

            // rollback stock
            foreach ($gatepass->items as $item) {
                $stock = Stock::where([
                    'branch_id'    => $gatepass->branch_id,
                    'warehouse_id' => $gatepass->warehouse_id,
                    'product_id'   => $item->product_id,
                ])->first();
                if ($stock) {
                    $stock->qty -= $item->qty;
                    if ($stock->qty < 0) $stock->qty = 0;
                    $stock->save();
                }
            }

            // delete gatepass + items
            InwardGatepassItem::where('inward_gatepass_id', $gatepass->id)->delete();
            $gatepass->delete();
        });

        return redirect()->route('InwardGatepass.home')
            ->with('success', 'Inward Gatepass Deleted Successfully');
    }

    // 8. Search products
    public function searchProducts(Request $request)
    {
        $q = $request->get('q');
        $products = Product::with('brand')
            ->where('name', 'like', "%{$q}%")
            ->limit(10)
            ->get();

        return response()->json($products);
    }
}
