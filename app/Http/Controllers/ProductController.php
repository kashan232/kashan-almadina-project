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
                // Check karein ki name unique ho, lekin current product ID ko ignore karein
                Rule::unique('products')->ignore($product->id),
            ],
            'category' => 'required', // This should likely be 'category_id' in request
            'sub_category' => 'required',
            'brand' => 'required',
            'stock' => 'required|integer',
            'status' => 'required|boolean',
            'weight' => 'required|numeric',
            'alert_qty' => 'required|integer',
            'purchase_retail_price' => 'required|numeric',
            'purchase_tax_percent' => 'required|numeric',
            'purchase_tax_amount' => 'required|numeric',
            'purchase_discount_percent' => 'required|numeric',
            'purchase_discount_amount' => 'required|numeric',
            'purchase_net_amount' => 'required|numeric',
            'sale_retail_price' => 'required|numeric',
            'sale_tax_percent' => 'required|numeric',
            'sale_tax_amount' => 'required|numeric',
            'sale_wht_percent' => 'required|numeric',
            'sale_discount_percent' => 'required|numeric',
            'sale_discount_amount' => 'required|numeric',
            'sale_net_amount' => 'required|numeric',
        ]);

        // 1. Product Table Update
        $product->update([
            'name' => $request->name,
            'category_id' => $request->category,
            'sub_category_id' => $request->sub_category,
            'brand_id' => $request->brand,
            'stock' => $request->stock,
            'alert_qty' => $request->alert_qty,
            'status' => $request->status,
            'weight' => $request->weight,
        ]);

        // 2. Prices Table Update Logic
        // Latest price record fetch karo
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

        // Check karte hain agar koi price detail change hui hai ya nahi.
        $priceChanged = false;
        if ($latestPrice) {
            foreach ($newPriceData as $key => $value) {
                // Comparing values (may need more robust float comparison if precision is an issue)
                if ((string)$latestPrice->$key !== (string)$value) {
                    $priceChanged = true;
                    break;
                }
            }
        } else {
            // Agar latestPrice nahi mila, toh naya record banayenge.
            $priceChanged = true;
        }

        if ($priceChanged) {
            // Agar price change hua hai toh current (latest) price record ko expire kar do
            if ($latestPrice) {
                $latestPrice->update([
                    'end_date' => now()->setTimezone('Asia/Karachi')->toDateString(),
                ]);
            }

            // Aur naya price record create karo
            // Yahan $product->prices()->create() hi Product ID set karta hai.
            $product->prices()->create(array_merge($newPriceData, [
                'start_date' => now()->setTimezone('Asia/Karachi')->toDateString(),
                'end_date' => null,
            ]));
        }

        return redirect()->route('products.index')->with('success', 'Product Updated');
    }

    public function create()
    {
        $categories = Category::get();
        $brands = Brand::get();
        return view('admin_panel.product.create', compact('categories', 'brands'));
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'name' => 'required|unique:products,name',
            'category' => 'required',
            'sub_category' => 'required',
            'brand' => 'required',
            'stock' => 'required',
            'status' => 'required',
            'weight' => 'required',
            'alert_qty' => 'required',
            'purchase_retail_price' => 'required',
            'purchase_tax_percent' => 'required',
            'purchase_tax_amount' => 'required',
            'purchase_discount_percent' => 'required',
            'purchase_discount_amount' => 'required',
            'purchase_net_amount' => 'required',
            'sale_retail_price' => 'required',
            'sale_tax_percent' => 'required',
            'sale_tax_amount' => 'required',
            'sale_wht_percent' => 'required',
            'sale_discount_percent' => 'required',
            'sale_discount_amount' => 'required',
            'sale_net_amount' => 'required',
        ]);

        $product = Product::create([
            'name' => $request->name,
            'category_id' => $request->category,
            'sub_category_id' => $request->sub_category,
            'brand_id' => $request->brand,
            'stock' => $request->stock,
            'alert_qty' => $request->alert_qty,
            'status' => $request->status,
            'weight' => $request->weight,
        ]);

        $product->prices()->create([
            // 'price' => $request->price,
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
            // 'sub_category_id' => $request->sub_category,
            // 'brand' => $request->brand,
            // 'stock' => $request->stock,
            // 'alert_qty' => $request->alert_qty,
        ]);

        // $product->prices()->create([
        //     'price' => $request->price,
        //     'tax_percent' => $request->tax_percent,
        //     'discount_percent' => $request->discount_percent,
        //     'weight' => $request->weight,
        //     //   'wht_percent' => $request->discount_percent,
        //     'effective_date' => now()->toDateString(),
        // ]);

        return redirect()->route('products.index')->with('success', 'Product Created');
    }

    public function edit(Product $product)
    {

        $product->load(['latestPrice']);

        $categories = Category::all();
        $brands = Brand::all();
        // Agar subcategory chahiye toh use bhi eager load kar sakte hain
        // ya phir AJAX se fetch kar sakte hain jaisa create mein hai.
        // Edit page par, agar category selected hai, toh uski subcategories pass karni padengi.
        $subCategories = $product->category ? $product->category->subCategories : collect();

        return view('admin_panel.product.edit', compact('product', 'categories', 'brands', 'subCategories'));
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

        $products = Product::with('brand')
            ->where('name', 'like', '%' . $query . '%')
            ->get();

        if ($products->isEmpty()) {
            return response()->json([], 200);
        }

        $results = $products->map(function ($product) {
            // get latest product_price (example: latest by id)
            $price = DB::table('product_prices')
                ->where('product_id', $product->id)
                ->orderByDesc('id')
                ->first();

            return [
                'id' => $product->id,
                'name' => $product->name,
                'brand' => $product->brand ? $product->brand->name : null,
                'stock' => $product->stock,
                'purchase_net_amount' => $price->purchase_net_amount ?? 0,
                'purchase_retail_price' => $price->purchase_retail_price ?? 0,
            ];
        });

        return response()->json($results);
    }



    public function bulkSetPrice(Request $request)
    {
        // dd($request->toArray());
        $ids = explode(',', $request->ids);

        // Products fetch
        $products = Product::with('latestPrice')->whereIn('id', $ids)->get();

        $product_ids = $request->ids;
        return view('admin_panel.product.bulk_set_price', compact('products', 'product_ids'));
    }

    public function bulkSetPriceUpdate(Request $request)
    {
        // dd(now()->setTimezone('Asia/Karachi')->toDateString());
        // Product IDs le rahe hain
        $productIds = $request->product_id;

        foreach ($productIds as $index => $id) {
            $product = Product::find($id);
            if ($product) {

                // Purane active price ka end_date set karo
                $latestP = $product->prices()->whereNull('end_date')->latest('start_date')->first();
                if ($latestP) {
                    $latestP->update([
                        'end_date' => now()->setTimezone('Asia/Karachi')->toDateString()
                    ]);
                }

                $product->latestPrice->create([
                    'product_id' => $request->product_id[$index],
                    'purchase_retail_price' => $request->purchase_retail_price[$index],
                    'purchase_tax_percent' => $request->purchase_tax_percent[$index],
                    'purchase_tax_amount' => $request->purchase_tax_amount[$index],
                    'purchase_discount_percent' => $request->purchase_discount_percent[$index],
                    'purchase_discount_amount' => $request->purchase_discount_amount[$index],
                    'purchase_net_amount' => $request->purchase_net_amount[$index],

                    'sale_retail_price' => $request->sale_retail_price[$index],
                    'sale_tax_percent' => $request->sale_tax_percent[$index],
                    'sale_tax_amount' => $request->sale_tax_amount[$index],
                    'sale_wht_percent' => $request->sale_wht_percent[$index],
                    'sale_discount_percent' => $request->sale_discount_percent[$index],
                    'sale_discount_amount' => $request->sale_discount_amount[$index],
                    'sale_net_amount' => $request->sale_net_amount[$index],
                    // 'effective_date' => now()->setTimezone('Asia/Karachi')->toDateString(),
                    'start_date' => now()->setTimezone('Asia/Karachi')->toDateString(),
                    'end_date' => null // Active price
                ]);
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

        return response()->json(['status' => 'error', 'message' => 'Invalid action.'], 400);
    }
}
