<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Customer;
use App\Models\SaleItem;
use App\Models\Warehouse;
use App\Models\SaleReturn;
use Illuminate\Http\Request;

class SalereturnController extends Controller
{
   public function index_salereturn(){
    return view('admin_panel.sale.return.index');
   }

  
public function index_salereturn_Add($id)
{
    $sale = Sale::with(['items', 'items.product'])->findOrFail($id); // includes sale_items
    
    $warehouses = Warehouse::all();
    $customers = Customer::all();
    // Generate a unique invoice number for return
    do {
        $nextInvoiceNumber = 'SR-' . time() . rand(1, 99); // SR = Sale Return
        $exists = Sale::where('invoice_no', $nextInvoiceNumber)->exists();
    } while ($exists);

    return view('admin_panel.sale.return.create', compact(
        'sale',
        'warehouses',
        'customers',
        'nextInvoiceNumber'
    ));
}
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
        $sale = SaleReturn::create([
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

            SaleReturnitem::create([
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
}
