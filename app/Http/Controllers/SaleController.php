<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Product;
use App\Models\Customer;
use App\Models\SaleItem;
use App\Models\Warehouse;
use App\Models\ProductPrice;
use App\Models\PurchaseItem;
use Illuminate\Http\Request;
use App\Models\Productbooking;
use App\Models\ProductBookingItem;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{


public function editBooking($id)
{
    $booking = Productbooking::with('items.product', 'customer')->findOrFail($id);

    $warehouses = Warehouse::all();
    $customers = Customer::all(); // ✅ All customers for select dropdown

    // Optional: calculate next invoice number if creating a new booking
    $lastInvoice = Productbooking::latest('id')->first();
    $nextInvoiceNumber = $lastInvoice ? intval($lastInvoice->invoice_no) + 1 : 1;

    return view('admin_panel.sale.booking.edit', compact(
        'booking',
        'warehouses',
        'customers',
        'nextInvoiceNumber'
    ));
}

    /**
     * Display a listing of the resource.
     */
public function add_sale()
{
    $warehouses = Warehouse::all();
    $customers = Customer::all();

    // Generate a unique invoice that does not exist in the sales table
    do {
        $nextInvoiceNumber = 'INV-' . time() . rand(10, 99);
        $exists = Sale::where('invoice_no', $nextInvoiceNumber)->exists();
    } while ($exists); // repeat if invoice already exists

    return view('admin_panel.sale.add_sale', compact('warehouses', 'customers', 'nextInvoiceNumber'));
}

    public function invoice($id)
{
    $sale = Sale::with(['customer', 'items.product'])->findOrFail($id);

    return view('admin_panel.sale.invoice', compact('sale'));
}
public function index()
{
    // Fetch sales with selected columns and related items
    $sales = Sale::with('items')->get();

    return view('admin_panel.sale.index', compact('sales'));
}

public function Booking()
{
    // Fetch sales with selected columns and related items
    $sales = Productbooking::with('items')->get();

    return view('admin_panel.sale.booking.index', compact('sales'));
}



public function getProductsByWarehouse($warehouseId)
{
    // PurchaseItems join with Purchase to filter by warehouse
    $products = Product::whereIn('id', function($query) use ($warehouseId) {
        $query->select('product_id')
              ->from('purchase_items')
              ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
              ->where('purchases.warehouse_id', $warehouseId);
    })->get();

    return response()->json($products);
}

// public function getStock( $productName)
// {
//     // 1️⃣ Find product
//     $product = Product::with('Prices')->whereRaw('LOWER(id) = ?', [strtolower($productName)])->first();
//     dd( $product->stock.'sadasd'. $productName);
//     return response()->json(['stock' => $product->stock, 'price' => $productName]);

// }
public function getStock($productId)
{
    // Find the product with its price relationship
    $product = Product::with('prices')->find($productId);

    if (!$product) {
        return response()->json(['error' => 'Product not found'], 404);
    }

    // Get latest/first price record (adjust logic if multiple prices exist per product)
    $price = optional($product->prices->first())->sale_retail_price ?? 0;

    return response()->json([
        'stock' => $product->stock,
        'price' => $price,
    ]);
}

// public function getStock($productId)
// {
//     $price = product::where('name',$productId)->first();
//     $price1 = product::with('prices')->where('id',$productId)->first();
//     // return $price;
//    return response()->json([
//         'stock'    => $price1,

//     ]);
// }



// CustomerController.php
public function getCustomerData($id)
{
    $customer = Customer::find($id);

    if (!$customer) {
        return response()->json(['error' => 'Customer not found'], 404);
    }

    return response()->json([
        'filer_type'    => $customer->filer_type,
        'customer_type' => $customer->customer_type,
        'address'       => $customer->address,
        'mobile'        => $customer->mobile,
        'remarks'       => $customer->remarks ?? '',
    ]);
}

 


  // Save Sale Items
// public function store(Request $request)
// {
//     // Validate main sale fields if needed
   
//     // Generate invoice number if not provided

//     // Create Sale
//     $sale = Sale::create([
//         'invoice_no' =>  $request->Invoice_no,
//         'manual_invoice' => $request->Invoice_main ?? null,
//         'customer_id' => $request->customer ?? null,
//         'sub_customer' => $request->customerType ?? null,
//         'filer_type' => $request->filerType ?? null,
//         'address' => $request->address ?? null,
//         'tel' => $request->tel ?? null,
//         'remarks' => $request->remarks ?? null,
//         'sub_total1' => $request->subTotal1 ?? 0,
//         'sub_total2' => $request->subTotal2 ?? 0,
//         'discount_percent' => $request->discountPercent ?? 0,
//         'discount_amount' => $request->discountAmount ?? 0,
//         'previous_balance' => $request->previousBalance ?? 0,
//         'total_balance' => $request->totalBalance ?? 0,
//         'receipt1' => $request->receipt1 ?? 0,
//         'receipt2' => $request->receipt2 ?? 0,
//         'final_balance1' => $request->finalBalance1 ?? 0,
//         'final_balance2' => $request->finalBalance2 ?? 0,
//         'weight' => $request->weight
//     ]);

//     // Save Sale Items (skip empty rows)
//     foreach ($request->warehouse_name as $key => $warehouse_id) {
//     if (empty($warehouse_id) || empty($request->product_name[$key])) {
//         continue;
//     }

//     $productId = $request->product_name[$key];
//     $saleQty = floatval($request->{'sales-qty'}[$key]);

//     // 🔽 Reduce stock from product table
//     $product = Product::find($productId);
//     if ($product) {
//         $currentStock = floatval($product->stock);
//         $newStock = max(0, $currentStock - $saleQty); // prevent negative stock
//         $product->stock = $newStock;
//         $product->save();
//     }

//     // Save sale item
//     SaleItem::create([
//         'sale_id' => $sale->id,
//         'warehouse_id' => $warehouse_id,
//         'product_id' => $productId,
//         'stock' => $request->stock[$key] ?? 0,
//         'price_level' => $request->price[$key] ?? null,
//         'sales_price' => $request->{'sales-price'}[$key] ?? 0,
//         'sales_qty' => $saleQty,
//         'retail_price' => $request->{'retail-price'}[$key] ?? 0,
//         'discount_percent' => $request->{'discount-percent'}[$key] ?? 0,
//         'discount_amount' => $request->{'discount-amount'}[$key] ?? 0,
//         'amount' => $request->{'sales-amount'}[$key] ?? 0,
//     ]);
// }


// return redirect()->back()->with("success", "Successfully added");

// }

public function store(Request $request)
{
    $isBooking = $request->has('booking'); // booking button pe click kiya ya nahi

    if ($isBooking) {
        // ------------------- Booking -------------------
        $booking = Productbooking::create([
            'invoice_no' => $request->Invoice_no,
            'manual_invoice' => $request->Invoice_main ?? null,
            'customer_id' => $request->customer ?? null,
            'sub_customer' => $request->customerType ?? null,
            'filer_type' => $request->filerType ?? null,
            'address' => $request->address ?? null,
            'tel' => $request->tel ?? null,
            'remarks' => $request->remarks ?? null,
            'sub_total1' => $request->subTotal1 ?? 0,
            'sub_total2' => $request->subTotal2 ?? 0,
            'discount_percent' => $request->discountPercent ?? 0,
            'discount_amount' => $request->discountAmount ?? 0,
            'previous_balance' => $request->previousBalance ?? 0,
            'total_balance' => $request->totalBalance ?? 0,
            'receipt1' => $request->receipt1 ?? 0,
            'receipt2' => $request->receipt2 ?? 0,
            'final_balance1' => $request->finalBalance1 ?? 0,
            'final_balance2' => $request->finalBalance2 ?? 0,
            'weight' => $request->weight ?? null
        ]);

        foreach ($request->warehouse_name as $key => $warehouse_id) {
            if (empty($warehouse_id) || empty($request->product_name[$key])) continue;

            ProductBookingItem::create([
                'booking_id' => $booking->id,
                'warehouse_id' => $warehouse_id,
                'product_id' => $request->product_name[$key],
                'stock' => $request->stock[$key] ?? 0,
                'price_level' => $request->price[$key] ?? 0,
                'sales_price' => $request->{'sales-price'}[$key] ?? 0,
                'sales_qty' => $request->{'sales-qty'}[$key] ?? 0,
                'retail_price' => $request->{'retail-price'}[$key] ?? 0,
                'discount_percent' => $request->{'discount-percent'}[$key] ?? 0,
                'discount_amount' => $request->{'discount-amount'}[$key] ?? 0,
                'amount' => $request->{'sales-amount'}[$key] ?? 0,
            ]);
        }

        return redirect()->back()->with("success", "Booking saved successfully!");

    } else {
        // ------------------- Sale -------------------
        $sale = Sale::create([
            'invoice_no' => $request->Invoice_no,
            'manual_invoice' => $request->Invoice_main ?? null,
            'customer_id' => $request->customer ?? null,
            'sub_customer' => $request->customerType ?? null,
            'filer_type' => $request->filerType ?? null,
            'address' => $request->address ?? null,
            'tel' => $request->tel ?? null,
            'remarks' => $request->remarks ?? null,
            'sub_total1' => $request->subTotal1 ?? 0,
            'sub_total2' => $request->subTotal2 ?? 0,
            'discount_percent' => $request->discountPercent ?? 0,
            'discount_amount' => $request->discountAmount ?? 0,
            'previous_balance' => $request->previousBalance ?? 0,
            'total_balance' => $request->totalBalance ?? 0,
            'receipt1' => $request->receipt1 ?? 0,
            'receipt2' => $request->receipt2 ?? 0,
            'final_balance1' => $request->finalBalance1 ?? 0,
            'final_balance2' => $request->finalBalance2 ?? 0,
            'weight' => $request->weight
        ]);

        foreach ($request->warehouse_name as $key => $warehouse_id) {
            if (empty($warehouse_id) || empty($request->product_name[$key])) continue;

            $productId = $request->product_name[$key];
            $saleQty = floatval($request->{'sales-qty'}[$key]);

            // Reduce stock
            $product = Product::find($productId);
            if ($product) {
                $product->stock = max(0, $product->stock - $saleQty);
                $product->save();
            }

            SaleItem::create([
                'sale_id' => $sale->id,
                'warehouse_id' => $warehouse_id,
                'product_id' => $productId,
                'stock' => $request->stock[$key] ?? 0,
                'price_level' => $request->price[$key] ?? 0,
                'sales_price' => $request->{'sales-price'}[$key] ?? 0,
                'sales_qty' => $saleQty,
                'retail_price' => $request->{'retail-price'}[$key] ?? 0,
                'discount_percent' => $request->{'discount-percent'}[$key] ?? 0,
                'discount_amount' => $request->{'discount-amount'}[$key] ?? 0,
                'amount' => $request->{'sales-amount'}[$key] ?? 0,
            ]);
        }

        return redirect()->back()->with("success", "Sale saved successfully!");
    }
}

    // Optional: show sale items for a sale
    public function getBySale($saleId)
    {
        $items = SaleItem::where('sale_id', $saleId)->with(['product','warehouse'])->get();
        return response()->json($items);
    }

  
public function edit($id)
{
    $sale = Sale::with('items')->findOrFail($id);
    $warehouses = Warehouse::all();
    $customers = Customer::all();

    return view('admin_panel.sale.edit', compact('sale', 'warehouses', 'customers'));
}


public function update(Request $request, $id)
{
    $sale = Sale::findOrFail($id);
    // Update main sale data
    $sale->update([
        'manual_invoice' => $request->Invoice_main ?? null,
        'customer_id' => $request->customer ?? null,
        'sub_customer' => $request->customerType ?? null,
        'filer_type' => $request->filerType ?? null,
        'address' => $request->address ?? null,
        'tel' => $request->tel ?? null,
        'remarks' => $request->remarks ?? null,
        'sub_total1' => $request->subTotal1 ?? 0,
        'sub_total2' => $request->subTotal2 ?? 0,
        'discount_percent' => $request->discountPercent ?? 0,
        'discount_amount' => $request->discountAmount ?? 0,
        'previous_balance' => $request->previousBalance ?? 0,
        'total_balance' => $request->totalBalance ?? 0,
        'receipt1' => $request->receipt1 ?? 0,
        'receipt2' => $request->receipt2 ?? 0,
        'final_balance1' => $request->finalBalance1 ?? 0,
        'final_balance2' => $request->finalBalance2 ?? 0,
    ]);

    // Delete existing items before re-inserting
    SaleItem::where('sale_id', $sale->id)->delete();

    // Insert updated items
    if ($request->warehouse_name && is_array($request->warehouse_name)) {
        foreach ($request->warehouse_name as $key => $warehouse_id) {
            if (empty($warehouse_id) || empty($request->product_name[$key])) {
                continue;
            }

            SaleItem::create([
                'sale_id' => $sale->id,
                'warehouse_id' => $warehouse_id,
                'product_id' => $request->product_name[$key],
                'stock' => $request->stock[$key] ?? 0,
                'price_level' => $request->price[$key] ?? null,
                'sales_price' => $request->{'sales-price'}[$key] ?? 0,
                'sales_qty' => $request->{'sales-qty'}[$key] ?? 0,
                'retail_price' => $request->{'retail-price'}[$key] ?? 0,
                'discount_percent' => $request->{'discount-percent'}[$key] ?? 0,
                'discount_amount' => $request->{'discount-amount'}[$key] ?? 0,
                'amount' => $request->{'sales-amount'}[$key] ?? 0,
            ]);
        }
    }

    return redirect()->back()->with('success', 'Sale updated successfully.');
}

}
