<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Subcategory;
// use App\Models\Size;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ProductPrice;
use App\Models\Brand;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('latestPrice')->get();
        return view('admin_panel.product.index', compact('products'));
    }
    public function prices($id)
    {
        // load product with all price records (adjust relation name if different)
        $product = Product::with(['prices' => function ($q) {
            $q->orderByDesc('start_date')->orderByDesc('id');
        }])->findOrFail($id);

        return view('admin_panel.product.prices', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => [
                'required',
                Rule::unique('products')->ignore($product->id),
            ],
            'category' => 'required',
            'sub_category' => 'required',
            'brand' => 'required',
        ]);

        $totalOpeningStock = (float) ($request->stock ?? 0);
        $warehouseTotal = 0;
        if ($request->has('warehouse_stocks')) {
            foreach ($request->warehouse_stocks as $qty) {
                $warehouseTotal += (float) $qty;
            }
        }
        $shopStock = $totalOpeningStock - $warehouseTotal;

        $product->update([
            'name' => $request->name,
            'category_id' => $request->category,
            'sub_category_id' => $request->sub_category,
            'brand_id' => $request->brand,
            'stock' => $shopStock,
            'alert_qty' => $request->alert_qty,
            'status' => $request->status ?? $product->status,
            'weight' => $request->weight,
        ]);

        $latestPrice = $product->latestPrice;

        $newPriceData = [
            'purchase_retail_price' => $request->purchase_retail_price,
            'purchase_tax_percent' => $request->purchase_tax_percent,
            'purchase_tax_amount' => $request->purchase_tax_amount,
            'purchase_discount_percent' => $request->purchase_discount_percent,
            'purchase_discount_amount' => $request->purchase_discount_amount,
            'purchase_net_amount' => $request->purchase_net_amount,
            'sale_retail_price' => $request->sale_retail_price,
            'sale_tax_percent' => $request->sale_tax_percent,
            'sale_tax_amount' => $request->sale_tax_amount,
            'sale_wht_percent' => $request->sale_wht_percent,
            'sale_discount_percent' => $request->sale_discount_percent,
            'sale_discount_amount' => $request->sale_discount_amount,
            'sale_net_amount' => $request->sale_net_amount,
        ];

        $priceChanged = false;
        if ($latestPrice) {
            foreach ($newPriceData as $key => $value) {
                if ((string) $latestPrice->$key !== (string) $value) {
                    $priceChanged = true;
                    break;
                }
            }
        } else {
            $priceChanged = true;
        }

        if ($priceChanged) {
            if ($latestPrice) {
                $latestPrice->update([
                    'end_date' => now()->setTimezone('Asia/Karachi')->toDateString(),
                ]);
            }

            $product->prices()->create(array_merge($newPriceData, [
                'start_date' => now()->setTimezone('Asia/Karachi')->toDateString(),
                'end_date' => null,
            ]));
        }

        $this->syncWarehouseStocksFromForm($product, $request);
        $this->saveOpeningStockFromForm($product, $request);

        return redirect()->route('products.index')->with('success', 'Product Updated');
    }

    public function create()
    {
        $categories = Category::get();
        $brands = Brand::get();
        $warehouses = Warehouse::all();
        return view('admin_panel.product.create', compact('categories', 'brands', 'warehouses'));
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'name' => 'required|unique:products,name',
            'category' => 'required',
            'sub_category' => 'required',
            'brand' => 'required',
        ]);

        // Calculate distribution
        $totalOpeningStock = (float)($request->stock ?? 0);
        $warehouseTotal = 0;
        if ($request->has('warehouse_stocks')) {
            foreach ($request->warehouse_stocks as $qty) {
                $warehouseTotal += (float)$qty;
            }
        }

        // Shop Stock (product table) is the remainder
        $shopStock = $totalOpeningStock - $warehouseTotal;

        $product = Product::create([
            'name' => $request->name,
            'category_id' => $request->category,
            'sub_category_id' => $request->sub_category,
            'brand_id' => $request->brand,
            'stock' => $shopStock, // Remainder goes to shop
            'alert_qty' => $request->alert_qty,
            'status' => $request->status,
            'weight' => $request->weight,
        ]);

        $product->prices()->create([
            'purchase_retail_price' => $request->purchase_retail_price,
            'purchase_tax_percent' => $request->purchase_tax_percent,
            'purchase_tax_amount' => $request->purchase_tax_amount,
            'purchase_discount_percent' => $request->purchase_discount_percent,
            'purchase_discount_amount' => $request->purchase_discount_amount,
            'purchase_net_amount' => $request->purchase_net_amount,
            'sale_retail_price' => $request->sale_retail_price,
            'sale_tax_percent' => $request->sale_tax_percent,
            'sale_tax_amount' => $request->sale_tax_amount,
            'sale_wht_percent' => $request->sale_wht_percent,
            'sale_discount_percent' => $request->sale_discount_percent,
            'sale_discount_amount' => $request->sale_discount_amount,
            'sale_net_amount' => $request->sale_net_amount,
            'start_date' => now()->setTimezone('Asia/Karachi')->toDateString(),
            'end_date' => null,
        ]);

        // Save Warehouse Stocks & Create Stock Adjustments
        $this->syncWarehouseStocksFromForm($product, $request);
        $this->saveOpeningStockFromForm($product, $request);

        return redirect()->route('products.index')->with('success', 'Product Created and Stock Distributed');
    }

    private function syncWarehouseStocksFromForm(Product $product, Request $request): void
    {
        if (!$request->has('warehouse_ids')) {
            return;
        }

        foreach ($request->warehouse_ids as $index => $warehouseId) {
            $qty = (float) ($request->warehouse_stocks[$index] ?? 0);

            $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                ->where('product_id', $product->id)
                ->orderByRaw("CASE WHEN status = 'Posted' THEN 0 WHEN status IS NULL THEN 1 ELSE 2 END")
                ->orderByDesc('id')
                ->first();

            if ($qty == 0) {
                if ($stock) {
                    $stock->update(['quantity' => 0, 'status' => 'Posted']);
                }
                continue;
            }

            if (!$stock) {
                WarehouseStock::create([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'remarks' => 'Opening Stock Distribution',
                    'status' => 'Posted',
                ]);

                continue;
            }

            if ((float) $stock->quantity !== $qty) {
                $stock->update([
                    'quantity' => $qty,
                    'status' => 'Posted',
                ]);
            }
        }
    }

    public function edit(Product $product)
    {
        $product->load(['latestPrice']);

        $categories = Category::get();
        $brands = Brand::get();
        $warehouses = Warehouse::all();

        $subCategories = collect();
        if (!empty($product->category_id)) {
            $subCategories = Subcategory::where('category_id', $product->category_id)->get();
        }

        $warehouseStockMap = $this->resolveOpeningWarehouseStockMap($product);
        $openingTotalStock = $this->resolveOpeningTotalStock($product, $warehouseStockMap);

        return view('admin_panel.product.edit', compact(
            'product',
            'categories',
            'brands',
            'warehouses',
            'subCategories',
            'warehouseStockMap',
            'openingTotalStock'
        ));
    }

    public function updatePrice(Request $request, Product $product)
    {
        $request->validate([
            'price' => 'required|numeric',
            'tax_percent' => 'required|numeric',
            'discount_percent' => 'required|numeric',
        ]);

        $product->prices()->create([
            'price' => $request->price,
            'tax_percent' => $request->tax_percent,
            'discount_percent' => $request->discount_percent,
            'effective_date' => now()->toDateString(),
        ]);

        return redirect()->route('products.index')->with('success', 'Price Updated');
    }

    public function showPrices(Product $product)
    {
        $prices = $product->prices()->orderByDesc('start_date')->get();

        return response()->json([
            'product_name' => $product->name,
            'brand_name' => $product->brandRelation->name ?? 'N/A',
            'prices' => $prices,
        ]);
    }


    public function getSubcategories($categoryId)
    {
        $subcategories = Subcategory::where('category_id', $categoryId)->get();

        // ✅ Proper JSON response bhejo
        return response()->json($subcategories);
    }


    public function searchProducts(Request $request)
    {
        $query = $request->get('q');
        $warehouseId = $request->get('warehouse_id', 0);

        // Optimize: Only load the warehouse stocks for the SPECIFIC warehouse selected
        $withArray = [
            'brandRelation', 
            'latestPrice', 
            'warehouseStocks' => function($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            }
        ];

        // If query is blank, return top 20 active products
        if (blank($query)) {
             $products = Product::with($withArray)
                ->where('status', 1)
                ->orderBy('id', 'desc')
                ->limit(20)
                ->get();
        } else {
            $products = Product::with($withArray)
                ->where('status', 1);

            if (is_numeric($query)) {
                $products->where(function($q) use ($query) {
                    $q->where('id', $query)
                      ->orWhere('name', 'like', $query . '%')
                      ->orWhere('name', 'like', '%' . $query . '%');
                })
                ->orderByRaw("CASE 
                    WHEN id = ? THEN 0 
                    WHEN name = ? THEN 1
                    WHEN name LIKE ? THEN 2
                    ELSE 3 
                END", [$query, $query, $query . '%']);
            } else {
                $products->where('name', 'like', '%' . $query . '%')
                        ->orderByRaw("CASE 
                            WHEN name = ? THEN 0 
                            WHEN name LIKE ? THEN 1 
                            ELSE 2 
                        END", [$query, $query . '%']);
            }

            $products = $products->limit(20)->get();
        }

        if ($products->isEmpty()) {
            return response()->json([], 200);
        }

        $results = $products->map(function ($product) use ($warehouseId) {
            $price = $product->latestPrice;

            // Stock Logic: 0 = Shop, >0 = Warehouse
            $stock = 0;
            if ($warehouseId == 0) {
                $stock = $product->stock;
            } else {
                // Since we eager loaded with a filter, we can just grab the first()
                $ws = $product->warehouseStocks->first();
                $stock = $ws ? $ws->quantity : 0;
            }

            return [
                'id' => $product->id,
                'name' => $product->name,
                'brand' => $product->brandRelation ? $product->brandRelation->name : null,
                'stock' => $stock,
                'sale_price' => $price->sale_net_amount ?? 0,
                'sale_retail_price' => $price->sale_retail_price ?? 0,
                'retail_price' => $price->sale_retail_price ?? 0,
                'net_price' => $price->sale_net_amount ?? 0,
                'purchase_net_amount' => $price->purchase_net_amount ?? 0,
                'purchase_retail_price' => $price->purchase_retail_price ?? 0,
            ];
        });

        return response()->json($results);
    }



    public function bulkSetPrice(Request $request)
    {
        $ids = explode(',', $request->ids);
        $type = $request->get('type', 'both'); // purchase, sale, or both

        // Products fetch
        $products = Product::with('latestPrice')->whereIn('id', $ids)->get();

        $product_ids = $request->ids;
        return view('admin_panel.product.bulk_set_price', compact('products', 'product_ids', 'type'));
    }

    public function bulkSetPriceUpdate(Request $request)
    {
        $productIds = $request->product_id;
        $type = $request->get('type', 'both');
        $startDate = $request->get('start_date', now()->setTimezone('Asia/Karachi')->toDateString());

        foreach ($productIds as $index => $id) {
            $product = Product::find($id);
            if ($product) {
                $latestP = $product->prices()->whereNull('end_date')->latest('start_date')->first();
                
                $data = [
                    'product_id' => $id,
                    'start_date' => $startDate,
                    'end_date' => null
                ];

                if ($latestP) {
                    // Carry over old values first
                    $data = array_merge($latestP->toArray(), $data);
                    unset($data['id']); // remove old ID
                    
                    $latestP->update([
                        'end_date' => $startDate
                    ]);
                }

                // Update only relevant type fields
                if ($type === 'purchase' || $type === 'both') {
                    $data['purchase_retail_price'] = $request->purchase_retail_price[$index];
                    $data['purchase_tax_percent'] = $request->purchase_tax_percent[$index];
                    $data['purchase_tax_amount'] = $request->purchase_tax_amount[$index];
                    $data['purchase_discount_percent'] = $request->purchase_discount_percent[$index];
                    $data['purchase_discount_amount'] = $request->purchase_discount_amount[$index];
                    $data['purchase_net_amount'] = $request->purchase_net_amount[$index];
                }

                if ($type === 'sale' || $type === 'both') {
                    $data['sale_retail_price'] = $request->sale_retail_price[$index];
                    $data['sale_tax_percent'] = $request->sale_tax_percent[$index];
                    $data['sale_tax_amount'] = $request->sale_tax_amount[$index];
                    $data['sale_wht_percent'] = $request->sale_wht_percent[$index];
                    $data['sale_wht_amount'] = $request->sale_wht_amount[$index];
                    $data['sale_discount_percent'] = $request->sale_discount_percent[$index];
                    $data['sale_discount_amount'] = $request->sale_discount_amount[$index];
                    $data['sale_net_amount'] = $request->sale_net_amount[$index];
                }

                ProductPrice::create($data);
            }
        }

        return redirect()->route('products.index')->with('success', 'Prices updated successfully!');
    }

    //         public function searchProducts(Request $request)
    // {
    //     $q = $request->get('q');

    //     $products = Product::with('brand', 'latestPrice')->where(function ($query) use ($q) {
    //             $query->where('name', 'like', "%{$q}%");
    //         })->get();

    //     return response()->json($products);


    // }   

    public function bulkAction(Request $request)
    {
        $action = $request->input('action');
        $ids = (array) $request->input('ids', []);

        if (empty($ids)) {
            return response()->json(['status' => 'error', 'message' => 'No products selected.'], 422);
        }

        if ($action === 'delete') {
            // 1) Find which of the selected products appear in purchase_items
            $usedProductIds = DB::table('purchase_items')
                ->whereIn('product_id', $ids)
                ->pluck('product_id')
                ->unique()
                ->values()
                ->toArray();

            if (!empty($usedProductIds)) {
                // Fetch product names for better error message
                $products = Product::whereIn('id', $usedProductIds)
                    ->get(['id', 'name'])
                    ->map(function ($p) {
                        return ['id' => $p->id, 'name' => $p->name];
                    });

                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete product(s) because they have purchase records.',
                    'blocked' => $products,
                ], 409); // 409 Conflict
            }

            // 2) Safe to delete: run inside transaction
            DB::transaction(function () use ($ids) {
                Product::whereIn('id', $ids)->delete();
            });

            return response()->json(['status' => 'success', 'message' => 'Selected products deleted.']);
        }

        if ($action === 'deactivate') {
            Product::whereIn('id', $ids)->update(['status' => 0]);
            return response()->json(['status' => 'success', 'message' => 'Selected products deactivated.']);
        }

    }

    public function getProductById($id)
    {
        $product = Product::with(['latestPrice'])->find($id);
        if (!$product) return response()->json(['success' => false], 404);
        
        return response()->json([
            'success' => true,
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->latestPrice->sale_net_amount ?? 0
        ]);
    }

    private function saveOpeningStockFromForm(Product $product, Request $request): void
    {
        $totalOpeningStock = (float) ($request->stock ?? 0);
        $warehouseMap = [];
        $warehouseTotal = 0;

        if ($request->has('warehouse_ids')) {
            foreach ($request->warehouse_ids as $index => $warehouseId) {
                $qty = (float) ($request->warehouse_stocks[$index] ?? 0);
                $warehouseMap[(string) $warehouseId] = $qty;
                $warehouseTotal += $qty;
            }
        }

        $product->update([
            'opening_total_stock' => $totalOpeningStock,
            'opening_shop_stock' => $totalOpeningStock - $warehouseTotal,
            'opening_warehouse_stocks' => $warehouseMap,
        ]);
    }

    private function resolveOpeningWarehouseStockMap(Product $product): array
    {
        $stored = $product->opening_warehouse_stocks;
        if (is_array($stored) && !empty($stored)) {
            $map = [];
            foreach ($stored as $warehouseId => $qty) {
                $map[(int) $warehouseId] = (float) $qty;
            }

            return $map;
        }

        $fromAdjustments = StockAdjustmentItem::query()
            ->join('stock_adjustments as sa', 'sa.id', '=', 'stock_adjustment_details.stock_adjustment_id')
            ->where('stock_adjustment_details.product_id', $product->id)
            ->where('sa.remarks', 'like', 'Opening Stock Distribution for Product:%')
            ->pluck('stock_adjustment_details.qty', 'sa.warehouse_id')
            ->map(fn ($qty) => (float) $qty)
            ->all();

        if (!empty($fromAdjustments)) {
            return collect($fromAdjustments)
                ->mapWithKeys(fn ($qty, $warehouseId) => [(int) $warehouseId => (float) $qty])
                ->all();
        }

        return WarehouseStock::where('product_id', $product->id)
            ->where('status', 'Posted')
            ->selectRaw('warehouse_id, SUM(quantity) as qty')
            ->groupBy('warehouse_id')
            ->pluck('qty', 'warehouse_id')
            ->map(fn ($qty) => (float) $qty)
            ->all();
    }

    private function resolveOpeningTotalStock(Product $product, array $warehouseStockMap): float
    {
        if ($product->opening_total_stock !== null && $product->opening_total_stock !== '') {
            return (float) $product->opening_total_stock;
        }

        if ($product->opening_shop_stock !== null && $product->opening_shop_stock !== '') {
            return (float) $product->opening_shop_stock + (float) collect($warehouseStockMap)->sum();
        }

        if (!empty($warehouseStockMap)) {
            return (float) collect($warehouseStockMap)->sum() + (float) ($product->opening_shop_stock ?? $product->stock ?? 0);
        }

        return (float) ($product->stock ?? 0);
    }
}
