<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\StockReportBuilder;
use Illuminate\Support\Facades\DB;

class WarehouseStockController extends Controller
{
    private function accessibleWarehouses()
    {
        return Warehouse::accessibleQuery()->orderBy('warehouse_name')->get();
    }

    private function accessibleWarehouseIds(): array
    {
        return $this->accessibleWarehouses()->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function assertWarehouseAccessible(int $warehouseId): void
    {
        if (!in_array($warehouseId, $this->accessibleWarehouseIds(), true)) {
            abort(403, 'You do not have access to this warehouse.');
        }
    }

    private function assertAdjustmentAccessible(\App\Models\StockAdjustment $adjustment): void
    {
        $this->assertWarehouseAccessible((int) $adjustment->warehouse_id);
    }

    public function index(Request $request)
    {
        // Self-heal any warehouse_stocks accidentally marked Unposted
        WarehouseStock::where('status', '!=', 'Posted')->update(['status' => 'Posted']);

        $user = auth()->user();
        $isAdmin = $user->isAdmin();
        $canAccessShop = $user->canAccessShop();

        // View mode: 'balances' or 'history'
        $view = $request->get('view', 'balances');
        $filter_warehouse_ids = (array) $request->get('filter_warehouse_id', []);
        $filter_claim_type = $request->get('claim_type', 'none'); // Default to 'none' (Normal)
        $filter_product_ids = (array) $request->get('filter_product_id', []);
        $filter_product_ids = array_filter(array_map('intval', $filter_product_ids));
        $filter_product_id = !empty($filter_product_ids) ? $filter_product_ids[0] : null;
        $filterProducts = !empty($filter_product_ids) ? Product::whereIn('id', $filter_product_ids)->get() : collect();
        $filterProduct = $filterProducts->first();

        $allWarehouses = $this->accessibleWarehouses();
        $accessibleWarehouseIds = $allWarehouses->pluck('id')->map(fn ($id) => (int) $id)->all();

        if (!empty($filter_warehouse_ids)) {
            $filter_warehouse_ids = array_values(array_intersect(
                array_map('intval', $filter_warehouse_ids),
                $accessibleWarehouseIds
            ));
            $warehouses = $allWarehouses->whereIn('id', $filter_warehouse_ids)->values();
        } else {
            $warehouses = $allWarehouses;
        }

        if ($filter_claim_type !== 'all') {
            $warehouses = $warehouses->where('claim_type', $filter_claim_type)->values();
        }

        $filter_brand_id = $request->filled('filter_brand_id') ? (int) $request->filter_brand_id : null;
        $brands = \App\Models\Brand::orderBy('name')->get();

        if ($view === 'history') {
            $query = \App\Models\StockAdjustment::with(['warehouse', 'items.product'])
                ->whereIn('warehouse_id', $accessibleWarehouseIds)
                ->latest();

            if ($request->filled('start_date')) {
                $query->whereDate('date', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->whereDate('date', '<=', $request->end_date);
            }
            if ($request->filled('warehouse_id')) {
                $warehouseId = (int) $request->warehouse_id;
                if (in_array($warehouseId, $accessibleWarehouseIds, true)) {
                    $query->where('warehouse_id', $warehouseId);
                }
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if (!empty($filter_product_ids)) {
                $query->whereHas('items', function ($q) use ($filter_product_ids) {
                    $q->whereIn('product_id', $filter_product_ids);
                });
            }
            if ($filter_brand_id) {
                $query->whereHas('items.product', function ($q) use ($filter_brand_id) {
                    $q->where('brand_id', $filter_brand_id);
                });
            }

            $stocks = $query->get();

            return view('admin_panel.warehouses.warehouse_stocks.index', compact(
                'stocks', 'warehouses', 'allWarehouses', 'view', 'isAdmin', 'canAccessShop',
                'filter_product_ids', 'filterProducts', 'filter_product_id', 'filterProduct', 'brands', 'filter_brand_id'
            ));
        }

        // Live Balances Mode
        // Fetch all products with their shop stock and warehouse stock relations
        $productsQuery = Product::with([
            'warehouseStocks' => function ($q) use ($accessibleWarehouseIds) {
                $q->where('status', 'Posted')
                    ->whereIn('warehouse_id', $accessibleWarehouseIds);
            },
            'stockHolds' => function ($q) {
                $q->withSum(\App\Models\StockHold::postedReleasesWithSum(), 'release_qty')
                  ->where('hold_qty', '!=', 0)
                  ->where(function ($query) {
                      $query->whereNull('stock_hold_voucher_id')
                            ->orWhereHas('voucher', function ($v) {
                                $v->where('status', 'Posted');
                            });
                  });
            },
            'brandRelation'
        ])->orderBy('name');

        if (!empty($filter_product_ids)) {
            $productsQuery->whereIn('id', $filter_product_ids);
        }

        if ($filter_brand_id) {
            $productsQuery->where('brand_id', $filter_brand_id);
        }

        $products = $productsQuery->get();

        return view('admin_panel.warehouses.warehouse_stocks.index', compact(
            'products', 'warehouses', 'allWarehouses', 'view', 'filter_warehouse_ids',
            'filter_claim_type', 'filter_product_ids', 'filterProducts', 'filter_product_id', 'filterProduct', 'brands', 'filter_brand_id', 'isAdmin', 'canAccessShop'
        ));
    }

    public function create()
    {
        $warehouses = $this->accessibleWarehouses();
        return view('admin_panel.warehouses.warehouse_stocks.create', compact('warehouses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id'   => 'required|array|min:1',
            'product_id.*' => 'required|exists:products,id',
            'quantity'     => 'required|array',
            'quantity.*'   => 'required|numeric',
        ]);

        $this->assertWarehouseAccessible((int) $request->warehouse_id);

        $status = $request->action === 'post' ? 'Posted' : 'Unposted';

        try {
            DB::beginTransaction();

            $adjustment = \App\Models\StockAdjustment::create([
                'adj_id'       => \App\Models\StockAdjustment::generateAdjID(),
                'date'         => $request->date ?? now()->toDateString(),
                'entry_date'   => $request->entry_date ?? now()->toDateString(),
                'entry_time'   => $request->entry_time ?? now()->toTimeString(),
                'warehouse_id' => $request->warehouse_id,
                'remarks'      => $request->remarks,
                'status'       => $status,
            ]);

            foreach ($request->product_id as $index => $productId) {
                $qty = (float) $request->quantity[$index];
                if ($qty == 0) continue;

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
                    $stock->status = 'Posted';
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
            $this->assertAdjustmentAccessible($adjustment);

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
                $stock->status = 'Posted';
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
        $this->assertAdjustmentAccessible($adjustment);

        return view('admin_panel.warehouses.warehouse_stocks.print', compact('adjustment'));
    }

    public function edit($id)
    {
        $adjustment = \App\Models\StockAdjustment::with(['items.product', 'warehouse'])->findOrFail($id);
        $this->assertAdjustmentAccessible($adjustment);
        
        if ($adjustment->status === 'Posted') {
            return redirect()->route('warehouse_stocks.index')->with('error', 'Posted adjustments cannot be edited.');
        }

        $warehouses = $this->accessibleWarehouses();
        return view('admin_panel.warehouses.warehouse_stocks.edit', compact('adjustment', 'warehouses'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id'   => 'required|array|min:1',
            'product_id.*' => 'required|exists:products,id',
            'quantity'     => 'required|array',
            'quantity.*'   => 'required|numeric',
        ]);

        $adjustment = \App\Models\StockAdjustment::findOrFail($id);
        $this->assertAdjustmentAccessible($adjustment);

        if ($adjustment->status === 'Posted') {
            return response()->json(['success' => false, 'message' => 'Posted records cannot be modified.'], 422);
        }

        $this->assertWarehouseAccessible((int) $request->warehouse_id);

        $status = $request->action === 'post' ? 'Posted' : 'Unposted';

        try {
            DB::beginTransaction();

            // Update main adjustment
            $adjustment->update([
                'warehouse_id' => $request->warehouse_id,
                'remarks'      => $request->remarks,
                'status'       => $status,
                'entry_date'   => $request->entry_date ?? $adjustment->entry_date,
                'entry_time'   => $request->entry_time ?? $adjustment->entry_time,
                'date'         => $request->date ?? $adjustment->date,
            ]);

            // Sync items: simplest way is to delete and recreate or match IDs.
            // Given the small number of items, deleting and recreating is safer.
            $adjustment->items()->delete();

            foreach ($request->product_id as $index => $productId) {
                $qty = (float) $request->quantity[$index];
                if ($qty == 0) continue;

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
                    $stock->status = 'Posted';
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
        $this->assertAdjustmentAccessible($adjustment);
        
        if ($adjustment->status === 'Posted') {
            return redirect()->route('warehouse_stocks.index')->with('error', 'Posted adjustments cannot be deleted.');
        }

        $adjustment->delete();
        return redirect()->route('warehouse_stocks.index')->with('success', 'Adjustment deleted successfully.');
    }

    public function export(\App\Services\WarehouseStockImportService $service)
    {
        return $service->downloadExportResponse();
    }

    public function showImport()
    {
        return view('admin_panel.warehouses.warehouse_stocks.import');
    }

    public function processImport(Request $request, \App\Services\WarehouseStockImportService $service)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $file = $request->file('excel_file');
        $result = $service->importFromFile($file->getRealPath());

        if ($result['imported'] === 0 && !empty($result['errors'])) {
            return redirect()->back()->with('error', 'Import failed: ' . implode(' | ', array_slice($result['errors'], 0, 5)));
        }

        $message = "Successfully imported stock balances for {$result['imported']} products.";
        if ($result['skipped'] > 0) {
            $message .= " {$result['skipped']} rows skipped. Errors: " . implode(' | ', array_slice($result['errors'], 0, 3));
        }

        return redirect()->route('warehouse_stocks.index')->with('success', $message);
    }

    public function auditIndex(Request $request, StockReportBuilder $builder)
    {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');

        $startDate = $request->start_date ?? '2020-01-01';
        $endDate = $request->end_date ?? date('Y-m-d');
        $warehouseFilter = $request->input('warehouse_id', '');
        $brandFilter = $request->input('brand_id', '');
        $productSearch = trim((string) $request->input('product_name', ''));
        $onlyDiff = $request->boolean('only_diff');

        $builderParams = [
            'from_date' => $startDate,
            'to_date' => $endDate,
        ];
        if ($warehouseFilter !== '' && $warehouseFilter !== null) {
            $builderParams['warehouse'] = [(string) $warehouseFilter];
        }
        if ($brandFilter !== '' && $brandFilter !== null) {
            $builderParams['brand'] = [(string) $brandFilter];
        }

        $report = $builder->build(new Request($builderParams));

        $warehouses = Warehouse::withoutGlobalScopes()->orderBy('warehouse_name')->get();
        $brands = Brand::orderBy('name')->get();

        // Pre-load system saved stocks
        $shopStocks = Product::withoutGlobalScopes()->pluck('stock', 'id')->map(fn ($v) => (float) ($v ?? 0))->all();
        $whStocks = [];
        foreach (WarehouseStock::all(['warehouse_id', 'product_id', 'quantity']) as $ws) {
            $whStocks[(int) $ws->warehouse_id][(int) $ws->product_id] = (float) ($ws->quantity ?? 0);
        }

        $seenKeys = [];
        $auditRows = [];

        foreach ($report['grouped'] as $whId => $rows) {
            $whIdInt = (int) $whId;
            foreach ($rows as $r) {
                $pid = (int) $r['product_id'];
                $seenKeys[$whIdInt . '|' . $pid] = true;

                if ($productSearch !== '' && stripos((string) $r['product_name'], $productSearch) === false) {
                    continue;
                }

                $savedStock = $whIdInt === 0
                    ? ($shopStocks[$pid] ?? 0.0)
                    : ($whStocks[$whIdInt][$pid] ?? 0.0);
                $calcStock = (float) $r['closing'];
                $diff = $calcStock - $savedStock;

                if ($onlyDiff && abs($diff) < 0.01) {
                    continue;
                }

                $clmIn = (float) ($r['clm_in'] ?? 0) + (float) ($r['cla_in'] ?? 0) + (float) ($r['cli_in'] ?? 0);
                $clmOut = (float) ($r['clm_rep'] ?? 0) + (float) ($r['clm_out'] ?? 0) + (float) ($r['cla_out'] ?? 0) + (float) ($r['cli_out'] ?? 0);

                $auditRows[] = [
                    'product_id' => $pid,
                    'warehouse_id' => $whIdInt,
                    'warehouse_label' => $r['warehouse_label'],
                    'brand_name' => $r['brand_name'] ?? '-',
                    'product_name' => $r['product_name'],
                    'opening' => (float) $r['opening'],
                    'pur' => (float) ($r['pur'] ?? 0),
                    'sales_ret' => (float) ($r['sales_ret'] ?? 0),
                    'trf_in' => (float) ($r['trf_in'] ?? 0),
                    'clm_in' => $clmIn,
                    'hold' => (float) ($r['hold'] ?? 0),
                    'sales' => (float) ($r['sales'] ?? 0),
                    'pur_ret' => (float) ($r['pur_ret'] ?? 0),
                    'trf_out' => (float) ($r['trf_out'] ?? 0),
                    'clm_out' => $clmOut,
                    'waste' => (float) ($r['waste'] ?? 0),
                    'release' => (float) ($r['release'] ?? 0),
                    'calc_stock' => $calcStock,
                    'saved_stock' => $savedStock,
                    'diff' => $diff,
                ];
            }
        }

        // Catch any non-zero saved stocks in DB that had zero ledger movements
        $productsMap = Product::withoutGlobalScopes()->with('brandRelation')->get()->keyBy('id');
        $warehousesMap = $warehouses->keyBy('id');

        if ($warehouseFilter === '' || $warehouseFilter === '0') {
            foreach ($shopStocks as $pid => $qty) {
                if (abs($qty) < 0.0001 || isset($seenKeys['0|' . $pid])) {
                    continue;
                }
                $prod = $productsMap->get($pid);
                if (!$prod) {
                    continue;
                }
                if ($brandFilter !== '' && (int) $prod->brand_id !== (int) $brandFilter) {
                    continue;
                }
                if ($productSearch !== '' && stripos((string) $prod->name, $productSearch) === false) {
                    continue;
                }
                $auditRows[] = [
                    'product_id' => (int) $pid,
                    'warehouse_id' => 0,
                    'warehouse_label' => 'Shop Stock',
                    'brand_name' => $prod->brandRelation?->name ?? 'Other',
                    'product_name' => $prod->name,
                    'opening' => 0.0, 'pur' => 0.0, 'sales_ret' => 0.0, 'trf_in' => 0.0,
                    'clm_in' => 0.0, 'hold' => 0.0, 'sales' => 0.0, 'pur_ret' => 0.0,
                    'trf_out' => 0.0, 'clm_out' => 0.0, 'waste' => 0.0, 'release' => 0.0,
                    'calc_stock' => 0.0,
                    'saved_stock' => $qty,
                    'diff' => -$qty,
                ];
            }
        }

        foreach ($whStocks as $whIdInt => $prodList) {
            if ($warehouseFilter !== '' && (int) $warehouseFilter !== $whIdInt) {
                continue;
            }
            $whName = $warehousesMap->get($whIdInt)?->warehouse_name ?? ('WH #' . $whIdInt);
            foreach ($prodList as $pid => $qty) {
                if (abs($qty) < 0.0001 || isset($seenKeys[$whIdInt . '|' . $pid])) {
                    continue;
                }
                $prod = $productsMap->get($pid);
                if (!$prod) {
                    continue;
                }
                if ($brandFilter !== '' && (int) $prod->brand_id !== (int) $brandFilter) {
                    continue;
                }
                if ($productSearch !== '' && stripos((string) $prod->name, $productSearch) === false) {
                    continue;
                }
                $auditRows[] = [
                    'product_id' => (int) $pid,
                    'warehouse_id' => $whIdInt,
                    'warehouse_label' => $whName,
                    'brand_name' => $prod->brandRelation?->name ?? 'Other',
                    'product_name' => $prod->name,
                    'opening' => 0.0, 'pur' => 0.0, 'sales_ret' => 0.0, 'trf_in' => 0.0,
                    'clm_in' => 0.0, 'hold' => 0.0, 'sales' => 0.0, 'pur_ret' => 0.0,
                    'trf_out' => 0.0, 'clm_out' => 0.0, 'waste' => 0.0, 'release' => 0.0,
                    'calc_stock' => 0.0,
                    'saved_stock' => $qty,
                    'diff' => -$qty,
                ];
            }
        }

        $groupedAudit = collect($auditRows)
            ->groupBy('warehouse_id')
            ->sortKeys()
            ->map(function ($items, $whId) {
                $sorted = $items->sortBy([
                    ['brand_name', 'asc'],
                    ['product_name', 'asc'],
                ])->values();

                $mismatchCount = $sorted->filter(fn ($r) => abs($r['diff']) >= 0.01)->count();

                return [
                    'warehouse_id' => (int) $whId,
                    'warehouse_label' => $sorted->first()['warehouse_label'] ?? ('WH #' . $whId),
                    'rows' => $sorted->all(),
                    'mismatch_count' => $mismatchCount,
                    'total_count' => $sorted->count(),
                ];
            })
            ->values()
            ->all();

        return view('admin_panel.warehouses.warehouse_stocks.audit', compact(
            'groupedAudit',
            'auditRows',
            'startDate',
            'endDate',
            'warehouses',
            'brands',
            'warehouseFilter',
            'brandFilter',
            'onlyDiff'
        ));
    }

    public function syncStocks(Request $request, StockReportBuilder $builder)
    {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');

        $report = $builder->build(new Request([
            'from_date' => '2020-01-01',
            'to_date' => date('Y-m-d'),
        ]));

        $syncedCount = 0;
        $ledgerMap = [];

        foreach ($report['grouped'] as $whId => $rows) {
            $whIdInt = (int) $whId;
            foreach ($rows as $r) {
                $pid = (int) $r['product_id'];
                $calcStock = round((float) $r['closing'], 2);
                $ledgerMap[$whIdInt][$pid] = $calcStock;

                if ($whIdInt === 0) {
                    $prod = Product::withoutGlobalScopes()->find($pid);
                    if ($prod && abs((float) ($prod->stock ?? 0) - $calcStock) > 0.01) {
                        $prod->stock = $calcStock;
                        $prod->save();
                        $syncedCount++;
                    }
                } else {
                    $ws = WarehouseStock::where('warehouse_id', $whIdInt)->where('product_id', $pid)->first();
                    $currentQty = $ws ? (float) ($ws->quantity ?? 0) : 0.0;
                    if (abs($currentQty - $calcStock) > 0.01) {
                        if ($ws) {
                            $ws->quantity = $calcStock;
                            $ws->save();
                        } elseif ($calcStock != 0.0) {
                            WarehouseStock::create([
                                'warehouse_id' => $whIdInt,
                                'product_id' => $pid,
                                'quantity' => $calcStock,
                            ]);
                        }
                        $syncedCount++;
                    }
                }
            }
        }

        // Zero out any orphaned non-zero warehouse_stocks not present in ledger
        foreach (WarehouseStock::where('quantity', '!=', 0)->get() as $ws) {
            if (!isset($ledgerMap[(int) $ws->warehouse_id][(int) $ws->product_id])) {
                $ws->quantity = 0;
                $ws->save();
                $syncedCount++;
            }
        }

        return back()->with('success', "All warehouse & shop stocks synced with Stock Ledger! ({$syncedCount} records updated)");
    }
}
