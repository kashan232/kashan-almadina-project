<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WarehouseStock;
use App\Models\Warehouse;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class WarehouseStockController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\StockAdjustment::with(['warehouse', 'items.product'])->latest();

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $stocks = $query->get(); // Using 'stocks' for compatibility with view variable name if preferred, or rename to 'adjustments'
        $warehouses = Warehouse::orderBy('warehouse_name')->get();

        return view('admin_panel.warehouses.warehouse_stocks.index', compact('stocks', 'warehouses'));
    }

    public function create()
    {
        $warehouses = Warehouse::orderBy('warehouse_name')->get();
        return view('admin_panel.warehouses.warehouse_stocks.create', compact('warehouses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id'   => 'required|array|min:1',
            'product_id.*' => 'required|exists:products,id',
            'quantity'     => 'required|array',
            'quantity.*'   => 'required|numeric|min:0',
        ]);

        $status = $request->action === 'post' ? 'Posted' : 'Unposted';

        try {
            DB::beginTransaction();

            $adjustment = \App\Models\StockAdjustment::create([
                'adj_id'       => \App\Models\StockAdjustment::generateAdjID(),
                'date'         => now(),
                'warehouse_id' => $request->warehouse_id,
                'remarks'      => $request->remarks,
                'status'       => $status,
            ]);

            foreach ($request->product_id as $index => $productId) {
                $qty = (float) $request->quantity[$index];
                if ($qty <= 0) continue;

                $adjustment->items()->create([
                    'product_id' => $productId,
                    'qty'        => $qty,
                ]);

                if ($status === 'Posted') {
                    $stock = WarehouseStock::firstOrNew([
                        'warehouse_id' => $request->warehouse_id,
                        'product_id'   => $productId
                    ]);
                    $stock->quantity = ($stock->quantity ?? 0) + $qty;
                    $stock->remarks = 'Manual Adjustment #' . $adjustment->adj_id;
                    $stock->save();
                }
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Stock ' . ($status == 'Posted' ? 'Posted' : 'Saved') . ' successfully.',
                    'status'  => $status,
                    'id'      => $adjustment->id
                ]);
            }

            return redirect()->route('warehouse_stocks.index')->with('success', 'Stock updated successfully.');

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
        try {
            DB::beginTransaction();
            $adjustment = \App\Models\StockAdjustment::with('items')->findOrFail($id);

            if ($adjustment->status === 'Posted') {
                throw new \Exception('This adjustment is already posted.');
            }

            foreach ($adjustment->items as $item) {
                $stock = WarehouseStock::firstOrNew([
                    'warehouse_id' => $adjustment->warehouse_id,
                    'product_id'   => $item->product_id
                ]);
                $stock->quantity = ($stock->quantity ?? 0) + $item->qty;
                $stock->remarks = 'Manual Adjustment #' . $adjustment->adj_id;
                $stock->save();
            }

            $adjustment->status = 'Posted';
            $adjustment->save();

            DB::commit();
            return back()->with('success', 'Stock Adjustment Posted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function print($id)
    {
        $adjustment = \App\Models\StockAdjustment::with(['warehouse', 'items.product'])->findOrFail($id);
        return view('admin_panel.warehouses.warehouse_stocks.print', compact('adjustment'));
    }

    public function edit($id)
    {
        $adjustment = \App\Models\StockAdjustment::with(['items.product', 'warehouse'])->findOrFail($id);
        
        if ($adjustment->status === 'Posted') {
            return redirect()->route('warehouse_stocks.index')->with('error', 'Posted adjustments cannot be edited.');
        }

        $warehouses = Warehouse::orderBy('warehouse_name')->get();
        return view('admin_panel.warehouses.warehouse_stocks.edit', compact('adjustment', 'warehouses'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id'   => 'required|array|min:1',
            'product_id.*' => 'required|exists:products,id',
            'quantity'     => 'required|array',
            'quantity.*'   => 'required|numeric|min:0',
        ]);

        $adjustment = \App\Models\StockAdjustment::findOrFail($id);

        if ($adjustment->status === 'Posted') {
            return response()->json(['success' => false, 'message' => 'Posted records cannot be modified.'], 422);
        }

        $status = $request->action === 'post' ? 'Posted' : 'Unposted';

        try {
            DB::beginTransaction();

            // Update main adjustment
            $adjustment->update([
                'warehouse_id' => $request->warehouse_id,
                'remarks'      => $request->remarks,
                'status'       => $status,
                'date'         => now(), // Update date to current edit time or keep original? Keeping original date might be better but user might want current.
            ]);

            // Sync items: simplest way is to delete and recreate or match IDs.
            // Given the small number of items, deleting and recreating is safer.
            $adjustment->items()->delete();

            foreach ($request->product_id as $index => $productId) {
                $qty = (float) $request->quantity[$index];
                if ($qty <= 0) continue;

                $adjustment->items()->create([
                    'product_id' => $productId,
                    'qty'        => $qty,
                ]);

                if ($status === 'Posted') {
                    $stock = WarehouseStock::firstOrNew([
                        'warehouse_id' => $request->warehouse_id,
                        'product_id'   => $productId
                    ]);
                    $stock->quantity = ($stock->quantity ?? 0) + $qty;
                    $stock->remarks = 'Manual Adjustment Updated #' . $adjustment->adj_id;
                    $stock->save();
                }
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Stock ' . ($status == 'Posted' ? 'Posted' : 'Updated') . ' successfully.',
                    'status'  => $status,
                    'id'      => $adjustment->id
                ]);
            }

            return redirect()->route('warehouse_stocks.index')->with('success', 'Stock updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $adjustment = \App\Models\StockAdjustment::findOrFail($id);
        
        if ($adjustment->status === 'Posted') {
            return redirect()->route('warehouse_stocks.index')->with('error', 'Posted adjustments cannot be deleted.');
        }

        $adjustment->delete();
        return redirect()->route('warehouse_stocks.index')->with('success', 'Adjustment deleted successfully.');
    }
}
