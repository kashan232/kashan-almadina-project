<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WarehouseStock;
use App\Models\Warehouse;
use App\Models\Product;

class WarehouseStockController extends Controller
{
    public function index()
    {
        $stocks = WarehouseStock::with('warehouse', 'product')->get();
        return view('admin_panel.warehouses.warehouse_stocks.index', compact('stocks'));
    }

    public function create()
    {
        $warehouses = Warehouse::all();
        $products = Product::all();
        return view('admin_panel.warehouses.warehouse_stocks.create', compact('warehouses', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id'   => 'required|exists:products,id',
            'quantity'     => 'required|integer|min:1',
        ]);

        $stock = WarehouseStock::updateOrCreate(
            ['warehouse_id' => $request->warehouse_id, 'product_id' => $request->product_id],
            ['price' => $request->price ?? null, 'remarks' => $request->remarks ?? null]
        );

        // increment quantity
        $stock->increment('quantity', $request->quantity);

        return back()->with('success', 'Stock added/updated.');
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
