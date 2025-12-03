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
    public function index()
    {
        $transfers = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'items.product', 'creator', 'confirmer'])
            ->orderBy('created_at', 'desc')
            ->paginate(25);

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
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id'   => 'required|exists:warehouses,id|different:from_warehouse_id',
            'product_id'        => 'required|array|min:1',
            'product_id.*'      => 'required|exists:products,id',
            'quantity'          => 'required|array',
            'quantity.*'        => 'required|integer|min:1',
            'to_shop'           => 'nullable|boolean',
            'remarks'           => 'nullable|string',
        ]);

        $from = $request->from_warehouse_id;
        $to = $request->to_warehouse_id;
        $toShop = $request->has('to_shop') ? 1 : 0;

        DB::transaction(function () use ($request, $from, $to, $toShop) {

            $transfer = StockTransfer::create([
                'from_warehouse_id' => $from,
                'to_warehouse_id'   => $to,
                'to_shop'           => $toShop,
                'remarks'           => $request->remarks,
                'status'            => 'pending',
                'created_by'        => auth()->id(),
            ]);

            foreach ($request->product_id as $index => $productId) {
                $qty = (int) $request->quantity[$index];
                if ($qty <= 0) {
                    throw new \Exception("Invalid quantity for product ID: $productId");
                }

                // Lock row to avoid race conditions
                $sourceStock = WarehouseStock::where('warehouse_id', $from)
                    ->where('product_id', $productId)
                    ->lockForUpdate()
                    ->first();

                if (!$sourceStock || $sourceStock->quantity < $qty) {
                    throw new \Exception("Not enough stock for product ID: {$productId} in source warehouse.");
                }

                // Reserve / decrease source warehouse stock now
                $sourceStock->quantity = $sourceStock->quantity - $qty;
                $sourceStock->save();

                // Create transfer item
                StockTransferProduct::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id'        => $productId,
                    'quantity'          => $qty,
                ]);
            }
        });

        return redirect()->back()->with('success', 'Stock transfer created and pending confirmation.');
    }

    // Show single transfer
    public function show($id)
    {
        $transfers  = StockTransfer::with('items.product', 'fromWarehouse', 'toWarehouse', 'creator', 'confirmer')->findOrFail($id);
        return view('admin_panel.warehouses.stock_transfers.show', compact('transfers'));
    }

    // List pending transfers for a warehouse (optional helper route)
    public function pending(Request $request)
    {
        // If you have user->warehouse_id logic, filter by that. Here we accept a query param or show all pending.
        $warehouseId = $request->get('warehouse_id');
        $query = StockTransfer::with('items.product', 'fromWarehouse', 'toWarehouse')->where('status', 'pending');

        if ($warehouseId) {
            $query->where('to_warehouse_id', $warehouseId);
        }

        $transfers = $query->orderBy('created_at', 'desc')->get();
        return view('stock_transfers.pending', compact('transfers'));
    }

    // AJAX: get available quantity for a product in a warehouse
    public function warehouseStockQuantity(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id'   => 'required|exists:products,id',
        ]);

        $ws = WarehouseStock::where('warehouse_id', $request->warehouse_id)
            ->where('product_id', $request->product_id)
            ->first();

        $quantity = $ws ? (int) $ws->quantity : 0;

        return response()->json(['quantity' => $quantity]);
    }

    // Accept transfer: move reserved stock to destination
    public function accept(Request $request, $id)
    {
        DB::transaction(function () use ($id) {
            $transfer = StockTransfer::with('items')->lockForUpdate()->findOrFail($id);

            if ($transfer->status !== 'pending') {
                throw new \Exception("Transfer already processed.");
            }

            foreach ($transfer->items as $item) {
                // --- DO NOT subtract from source here (we assume it was reserved at creation) ---

                // Add to destination warehouse only
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
                    ]);
                }
            }

            // Update transfer status
            $transfer->status = 'accepted';
            $transfer->confirmed_by = auth()->id();
            $transfer->save();
        });

        return back()->with('success', 'Transfer accepted. Destination stock updated.');
    }


    // Reject transfer: return reserved stock back to source
    public function reject(Request $request, $id)
    {
        DB::transaction(function () use ($id) {
            $transfer = StockTransfer::with('items')->lockForUpdate()->findOrFail($id);

            if ($transfer->status !== 'pending') {
                throw new \Exception("Transfer already processed.");
            }

            foreach ($transfer->items as $item) {
                $sourceStock = WarehouseStock::where('warehouse_id', $transfer->from_warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                if ($sourceStock) {
                    $sourceStock->quantity = $sourceStock->quantity + $item->quantity;
                    $sourceStock->save();
                } else {
                    WarehouseStock::create([
                        'warehouse_id' => $transfer->from_warehouse_id,
                        'product_id'   => $item->product_id,
                        'quantity'     => $item->quantity,
                    ]);
                }
            }

            $transfer->status = 'rejected';
            $transfer->confirmed_by = auth()->id();
            $transfer->save();
        });

        return back()->with('error', 'Transfer rejected and stock returned to source.');
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