<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StockTransfer;
use App\Models\WarehouseStock;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\StockTransferProduct;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    public function index(Request $request)
    {
        $query = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'items.product', 'creator', 'confirmer'])->latest();

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $transfers = $query->get();
        return view('admin_panel.warehouses.stock_transfers.index', compact('transfers'));
    }

    public function create()
    {
        $warehouses = Warehouse::orderBy('warehouse_name')->get();
        $products = Product::orderBy('name')->get();

        return view('admin_panel.warehouses.stock_transfers.create', compact('warehouses', 'products'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'from_warehouse_id' => 'required',
            'to_warehouse_id'   => 'required|exists:warehouses,id',
            'product_id'        => 'required|array|min:1',
            'product_id.*'      => 'required|exists:products,id',
            'quantity'          => 'required|array',
            'quantity.*'        => 'required|numeric|min:1',
            'remarks'           => 'nullable|string',
        ]);

        try {
            $transferId = DB::transaction(function () use ($request) {
                $isFromShop = $request->from_warehouse_id === 'shop';

                $transfer = StockTransfer::create([
                    'from_warehouse_id' => $isFromShop ? null : $request->from_warehouse_id,
                    'from_shop'         => $isFromShop ? 1 : 0,
                    'to_warehouse_id'   => $request->to_warehouse_id,
                    'to_shop'           => $request->has('to_shop') ? 1 : 0,
                    'entry_date'        => $request->entry_date ?? date('Y-m-d'),
                    'entry_time'        => $request->entry_time ?? date('H:i'),
                    'remarks'           => $request->remarks,
                    'status'            => 'Unposted',
                    'created_by'        => auth()->id(),
                ]);

                foreach ($request->product_id as $index => $productId) {
                    $qty = (float) $request->quantity[$index];
                    if ($qty <= 0) continue;

                    StockTransferProduct::create([
                        'stock_transfer_id' => $transfer->id,
                        'product_id'        => $productId,
                        'quantity'          => $qty,
                    ]);
                }

                return $transfer->id;
            });

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'id'      => $transferId,
                    'status'  => 'Unposted',
                    'message' => 'Stock Transfer Saved as Unposted',
                ]);
            }

            return redirect()->route('stock_transfers.index')->with('success', 'Stock Transfer saved.');

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function edit($id)
    {
        $transfer = StockTransfer::with('items')->findOrFail($id);
        
        if ($transfer->status !== 'Unposted') {
            return back()->with('error', 'Only unposted stock transfers can be edited.');
        }

        $warehouses = Warehouse::orderBy('warehouse_name')->get();
        $products = Product::orderBy('name')->get();

        return view('admin_panel.warehouses.stock_transfers.edit', compact('transfer', 'warehouses', 'products'));
    }

    public function update(Request $request, $id)
    {
        $transfer = StockTransfer::findOrFail($id);
        
        if ($transfer->status !== 'Unposted') {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Only unposted transfers can be edited'], 400);
            }
            return back()->with('error', 'Only unposted transfers can be edited');
        }

        $request->validate([
            'from_warehouse_id' => 'required',
            'to_warehouse_id'   => 'required|exists:warehouses,id',
            'product_id'        => 'required|array|min:1',
            'product_id.*'      => 'required|exists:products,id',
            'quantity'          => 'required|array',
            'quantity.*'        => 'required|numeric|min:1',
            'remarks'           => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($request, $transfer) {
                $isFromShop = $request->from_warehouse_id === 'shop';

                $transfer->update([
                    'from_warehouse_id' => $isFromShop ? null : $request->from_warehouse_id,
                    'from_shop'         => $isFromShop ? 1 : 0,
                    'to_warehouse_id'   => $request->to_warehouse_id,
                    'to_shop'           => $request->has('to_shop') ? 1 : 0,
                    'entry_date'        => $request->entry_date ?? date('Y-m-d'),
                    'entry_time'        => $request->entry_time ?? date('H:i'),
                    'remarks'           => $request->remarks,
                ]);

                // Delete old items
                $transfer->items()->delete();

                // Add new items
                foreach ($request->product_id as $index => $productId) {
                    $qty = (float) $request->quantity[$index];
                    if ($qty <= 0) continue;

                    StockTransferProduct::create([
                        'stock_transfer_id' => $transfer->id,
                        'product_id'        => $productId,
                        'quantity'          => $qty,
                    ]);
                }
            });

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'id'      => $transfer->id,
                    'status'  => 'Unposted',
                    'message' => 'Stock Transfer Updated',
                ]);
            }

            return redirect()->route('stock_transfers.index')->with('success', 'Stock Transfer updated.');

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function post($id)
    {
        $transfer = StockTransfer::with('items')->findOrFail($id);

        if ($transfer->status === 'Posted' || $transfer->status === 'Pending Approval') {
            return response()->json(['success' => false, 'message' => 'Already posted or pending approval.'], 422);
        }

        $isAdmin = auth()->user()->roles->pluck('name')->contains('Admin');

        try {
            DB::transaction(function () use ($transfer, $isAdmin) {
                if ($isAdmin) {
                    // Admin posts directly
                    foreach ($transfer->items as $item) {
                        if ($transfer->from_shop) {
                            $sourceProduct = Product::lockForUpdate()->find($item->product_id);

                            if ($sourceProduct) {
                                $sourceProduct->stock -= $item->quantity;
                                $sourceProduct->save();
                            }
                        } else {
                            $sourceStock = WarehouseStock::where('warehouse_id', $transfer->from_warehouse_id)
                                ->where('product_id', $item->product_id)
                                ->lockForUpdate()->first();

                            if ($sourceStock) {
                                $sourceStock->quantity -= $item->quantity;
                                $sourceStock->save();
                            } else {
                                WarehouseStock::create([
                                    'warehouse_id' => $transfer->from_warehouse_id,
                                    'product_id'   => $item->product_id,
                                    'quantity'     => -($item->quantity),
                                    'status'       => 'Posted',
                                ]);
                            }
                        }

                        if ($transfer->to_shop) {
                            $destProduct = Product::lockForUpdate()->find($item->product_id);
                            if ($destProduct) {
                                $destProduct->stock += $item->quantity;
                                $destProduct->save();
                            }
                        } else {
                            $destStock = WarehouseStock::where('warehouse_id', $transfer->to_warehouse_id)
                                ->where('product_id', $item->product_id)
                                ->lockForUpdate()->first();

                            if ($destStock) {
                                $destStock->quantity += $item->quantity;
                                $destStock->save();
                            } else {
                                WarehouseStock::create([
                                    'warehouse_id' => $transfer->to_warehouse_id,
                                    'product_id'   => $item->product_id,
                                    'quantity'     => $item->quantity,
                                    'status'       => 'Posted',
                                ]);
                            }
                        }
                    }

                    $transfer->status = 'Posted';
                    $transfer->confirmed_by = auth()->id();
                } else {
                    // Non-admin requires approval
                    $transfer->status = 'Pending Approval';
                }
                $transfer->save();
            });

            $msg = $isAdmin ? 'Stock Transfer Posted Successfully' : 'Stock Transfer Sent for Admin Approval!';

            if (request()->ajax()) {
                return response()->json(['success' => true, 'message' => $msg]);
            }
            return back()->with('success', $msg);

        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    public function printView($id)
    {
        $transfer = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'items.product', 'creator'])->findOrFail($id);
        return view('admin_panel.warehouses.stock_transfers.print', compact('transfer'));
    }

    // Show single transfer
    public function show($id)
    {
        $transfers  = StockTransfer::with('items.product', 'fromWarehouse', 'toWarehouse', 'creator', 'confirmer')->findOrFail($id);
        return view('admin_panel.warehouses.stock_transfers.show', compact('transfers'));
    }

    // List pending transfers for approval
    public function pending(Request $request)
    {
        $query = StockTransfer::with(['items.product', 'fromWarehouse', 'toWarehouse', 'creator'])->where('status', 'Pending Approval');

        $transfers = $query->orderBy('created_at', 'desc')->get();
        return view('admin_panel.warehouses.stock_transfers.pending', compact('transfers'));
    }

    public function warehouseStockQuantity(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required',
            'product_id'   => 'required|exists:products,id',
        ]);

        if ($request->warehouse_id === 'shop') {
            $product = Product::find($request->product_id);
            $quantity = $product ? (int) $product->stock : 0;
        } else {
            $ws = WarehouseStock::where('warehouse_id', $request->warehouse_id)
                ->where('product_id', $request->product_id)
                ->first();
            $quantity = $ws ? (int) $ws->quantity : 0;
        }

        return response()->json(['quantity' => $quantity]);
    }

    // Accept transfer: move reserved stock to destination
    public function accept(Request $request, $id)
    {
        DB::transaction(function () use ($id) {
            $transfer = StockTransfer::with('items')->lockForUpdate()->findOrFail($id);

            if ($transfer->status !== 'Pending Approval') {
                throw new \Exception("Transfer already processed.");
            }

            foreach ($transfer->items as $item) {
                if ($transfer->from_shop) {
                    $sourceProduct = Product::lockForUpdate()->find($item->product_id);

                    if ($sourceProduct) {
                        $sourceProduct->stock -= $item->quantity;
                        $sourceProduct->save();
                    }
                } else {
                    $sourceStock = WarehouseStock::where('warehouse_id', $transfer->from_warehouse_id)
                        ->where('product_id', $item->product_id)
                        ->lockForUpdate()->first();

                    if ($sourceStock) {
                        $sourceStock->quantity -= $item->quantity;
                        $sourceStock->save();
                    } else {
                        WarehouseStock::create([
                            'warehouse_id' => $transfer->from_warehouse_id,
                            'product_id'   => $item->product_id,
                            'quantity'     => -($item->quantity),
                            'status'       => 'Posted',
                        ]);
                    }
                }

                if ($transfer->to_shop) {
                    $destProduct = Product::lockForUpdate()->find($item->product_id);
                    if ($destProduct) {
                        $destProduct->stock += $item->quantity;
                        $destProduct->save();
                    }
                } else {
                    $destStock = WarehouseStock::where('warehouse_id', $transfer->to_warehouse_id)
                        ->where('product_id', $item->product_id)
                        ->lockForUpdate()
                        ->first();

                    if ($destStock) {
                        $destStock->quantity += $item->quantity;
                        $destStock->save();
                    } else {
                        WarehouseStock::create([
                            'warehouse_id' => $transfer->to_warehouse_id,
                            'product_id'   => $item->product_id,
                            'quantity'     => $item->quantity,
                            'status'       => 'Posted',
                        ]);
                    }
                }
            }

            // Update transfer status
            $transfer->status = 'Posted';
            $transfer->confirmed_by = auth()->id();
            $transfer->save();
        });

        return back()->with('success', 'Transfer accepted. Stock has been impacted.');
    }


    // Reject transfer: simply mark as rejected
    public function reject(Request $request, $id)
    {
        DB::transaction(function () use ($id) {
            $transfer = StockTransfer::with('items')->lockForUpdate()->findOrFail($id);

            if ($transfer->status !== 'Pending Approval') {
                throw new \Exception("Transfer already processed.");
            }

            $transfer->status = 'Rejected';
            $transfer->confirmed_by = auth()->id();
            $transfer->save();
        });

        return back()->with('error', 'Transfer rejected.');
    }

    public function destroy(StockTransfer $stockTransfer)
    {
        // Optional: reverse the transfer if needed
        return back()->with('error', 'Deleting transfers not allowed.');
    }
    public function getStockQuantity(Request $request)
    {
        $stock = WarehouseStock::where('warehouse_id', $request->warehouse_id)
            ->where('product_id', $request->product_id)
            ->first();

        return response()->json([
            'quantity' => $stock ? $stock->quantity : 0
        ]);
    }
}



// delvivery challan 
// convet out per  stock ledger maintain