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


class ProductController extends Controller
{
      public function index()
    {
        $products = Product::with('latestPrice')->get();
        return view('admin_panel.product.index', compact('products'));
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
            'end_date' => null ,
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
        
        return view('admin_panel.product.edit', compact('product'));
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


    public function getSubcategories($category_id)
    {
        $subcategories = SubCategory::where('category_id', $category_id)->get();
        return response()->json($subcategories);
    }
   
    public function searchProducts(Request $request)
    {
        $query = $request->get('q');


        \Log::info("Search query: " . $query); // Debug log

        $products = Product::where('name', 'like', '%' . $query . '%')->get();

        if ($products->isEmpty()) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $products = $products->map(function ($product) {
            return [
                'name' => $product->name,
            ];
        });

        return response()->json($products);
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

    public function bulkSetPriceUpdate(Request $request){
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
    $ids = $request->input('ids', []);

    if (empty($ids)) {
        return response()->json(['status' => 'error', 'message' => 'No products selected.'], 422);
    }

    if ($action === 'delete') {
        Product::whereIn('id', $ids)->delete();
        return response()->json(['status' => 'success', 'message' => 'Selected products deleted.']);
    }

    if ($action === 'deactivate') {
        Product::whereIn('id', $ids)->update(['status' => 0]);
        return response()->json(['status' => 'success', 'message' => 'Selected products deactivated.']);
    }

    return response()->json(['status' => 'error', 'message' => 'Invalid action.'], 400);
}

}