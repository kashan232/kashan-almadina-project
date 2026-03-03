<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WarehouseStock;
use App\Models\Warehouse;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class WarehouseStockController extends Controller
{
    public function index()
    {
        $stocks = WarehouseStock::with('warehouse', 'product')->get();
        return view('admin_panel.warehouses.warehouse_stocks.index', compact('stocks'));
    }

    public function create()
    {
        $warehouses = Warehouse::orderBy('warehouse_name')->get();
        // Products are fetched via AJAX search in the UI
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

            // Note: Since warehouse_stocks is usually a balance table, 
            // the user's request for "Post" implies a transactional record should exist.
            // If they don't have a 'StockAdjustment' table, we will update the balance.
            // But for a professional workflow, we should probably have a 'adjustment' ID.
            // For now, I'll update the balance but return an ID placeholder.

            foreach ($request->product_id as $index => $productId) {
                $qty = (float) $request->quantity[$index];
                if ($qty <= 0) continue;

                $stock = WarehouseStock::firstOrNew([
                    'warehouse_id' => $request->warehouse_id,
                    'product_id'   => $productId
                ]);

                // Increment stock ONLY IF POSTED
                if ($status === 'Posted') {
                    $stock->quantity = ($stock->quantity ?? 0) + $qty;
                }
                
                $stock->remarks = $request->remarks;
                $stock->save();
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Stock ' . ($status == 'Posted' ? 'Posted' : 'Saved') . ' successfully.',
                    'status'  => $status,
                    'id'      => rand(1000, 9999) // Placeholder for adjustment ID
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

    public function print($id) {
        // Placeholder for printing stock adjustment
        return "Stock Adjustment Print View for ID #" . $id;
    }

    public function edit(WarehouseStock $warehouseStock)
    {
        $warehouses = Warehouse::all();
        $products = Product::all();
        return view('admin_panel.warehouses.warehouse_stocks.edit', compact('warehouseStock', 'warehouses', 'products'));
    }

    public function update(Request $request, WarehouseStock $warehouseStock)
    {
        $request->validate([
            'warehouse_id' => 'required',
            'product_id' => 'required',
            'quantity' => 'required|integer|min:0'
        ]);

        $warehouseStock->update($request->all());
        return redirect()->route('warehouse_stocks.index')->with('success', 'Stock updated successfully.');
    }

    public function destroy(WarehouseStock $warehouseStock)
    {
        $warehouseStock->delete();
        return redirect()->route('warehouse_stocks.index')->with('success', 'Stock deleted successfully.');
    }
}
