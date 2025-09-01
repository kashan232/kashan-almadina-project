<?php

namespace App\Http\Controllers;

use DB;
use App\Models\Stock;
use App\Models\Vendor;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Warehouse;
use App\Models\PurchaseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseController extends Controller
{  public function index()
    {
        // $userId = Auth::id();
      $Purchase = Purchase::get();
      return  view("admin_panel.purchase.index",compact('Purchase'));
    }
      public function add_purchase()
    {
        // $userId = Auth::id();
      $Purchase = Purchase::get();
      $Vendor = Vendor::get();
      $Warehouse = Warehouse::get();
         return view('admin_panel.purchase.add_purchase',compact('Vendor',"Warehouse",'Purchase'));
    }

    public function store(Request $request)
{
    // dd($request->toArray());
    // $validated = $request->validate([
    //     'invoice_no'     => 'nullable|string',
    //     'vendor_id'      => 'nullable|exists:vendors,id',
    //     // 'branch_id'      => 'required|exists:branches,id',
    //     'purchase_date'  => 'nullable|date',
    //     'warehouse_id'   => 'required|exists:warehouses,id',
    //     'note'           => 'nullable|string',

    //     // Purchase Items
    //     'product_id'     => 'required|array',
    //     'product_id.*'   => 'required|exists:products,id',
    //     'qty'            => 'required|array',
    //     'qty.*'          => 'required|numeric|min:1',
    //     'price'          => 'required|array',
    //     'price.*'        => 'required|numeric|min:0',
    //     'unit'           => 'nullable|array',
    //     'unit.*'         => 'nullable|string',
    //     'item_discount'  => 'nullable|array',
    //     'item_discount.*'=> 'nullable|numeric|min:0',
    // ]);
// dd(Auth()->user()->id);
    DB::transaction(function () use ($request) {

        $typeMap = [
            'vendor'       => \App\Models\Vendor::class,
            'customer'     => \App\Models\Customer::class,
            'sub_customer' => \App\Models\SubCustomer::class,
        ];

        
        // 1️⃣ Save main Purchase
        $purchase = Purchase::create([
            'warehouse_id'  => $request['warehouse_id'],
            'vendor_id'     => 2,
            'purchasable_type' => $typeMap['vendor'],
            'purchasable_id'   => $request['vendor_id'],
            'current_date' => $request['current_date'] ?? now(),
            'dc_date'    => $request['dc_date'] ?? null,
            'note'          => $request['remarks'] ?? null,
            'subtotal'      => $request->subtotal,
            'discount'      => $request->discount, 
            'wht'      => $request->wht,
            'net_amount'      => $request->net_amount,
            'branch_id'     => 2,
        ]);

        $subtotal = 0;

        // 2️⃣ Loop purchase items
        foreach ($request['product_id'] as $index => $productId) {
            if ($productId === null) {
                continue; // skip karega null ko
            }
            $qty     = $request['qty'][$index];
            $price   = $request['price'][$index];
            $disc    = $request['item_discount'][$index] ?? 0;
            $lineTotal = ($price * $qty) - $disc;

            // Save purchase item
            PurchaseItem::create([
                'purchase_id'   => $purchase->id,
                'product_id'    => $productId,
                'unit'          => $request['unit'][$index] ?? null,
                'price'         => $price,
                'item_discount' => $disc,
                'qty'           => $qty,
                'line_total'    => $lineTotal,
            ]);

            $subtotal += $lineTotal;

            // 3️⃣ Update stock
            $stock = Stock::where('branch_id',  Auth()->user()->id,)
                ->where('warehouse_id', $request['warehouse_id'])
                ->where('product_id', $productId)
                ->first();
                
     $product = Product::where('id', $productId)->first();
                $product->stock += $qty;
                  $product->save();
            if ($stock) {
              
                $stock->qty += $qty;
                $stock->save();
              
            } else {
                Stock::create([
                    'branch_id'     => Auth()->user()->id,
                    'warehouse_id'  => $request['warehouse_id'],
                    'product_id'    => $productId,
                    'qty'           => $qty,
                ]);
            }
        }

        // 4️⃣ Update totals in purchase
        $purchase->update([
            'subtotal'    => $subtotal,
            'net_amount'  => $subtotal,
            'due_amount'  => $subtotal,
        ]);
        if($request['discount'] > 0){
    // Discount voucher
    \App\Models\Voucher::create([
        'voucher_type'    => 'Discount voucher',
        'date'            => now(),
        'sales_officer'   => auth()->user()->name,
        'type'            => 'Credit',
        'person'          => $purchase->vendor_id,
        'sub_head'        => 'Purchase Discount',
        'narration'       => 'Discount applied on Purchase ID: '.$purchase->id,
        'amount'          => $request['discount']
    ]);
}

if($request['wht'] > 0){
    // WHT voucher
    \App\Models\Voucher::create([
        'voucher_type'    => 'Wht voucher',
        'date'            => now(),
        'sales_officer'   => auth()->user()->name,
        'type'            => 'Credit',
        'person'          => $purchase->vendor_id,
        'sub_head'        => 'WHT',
        'narration'       => 'WHT applied on Purchase ID: '.$purchase->id,
        'amount'          => $request['wht']
    ]);
}
    });
// 5️⃣ Save voucher dynamically


    return redirect()->back()->with('success', 'Purchase saved successfully!');
}


public function edit($id) {
  $purchase   = Purchase::findOrFail($id);
  $Vendor     = Vendor::all();
  $Warehouse  = Warehouse::all();
//   $Transport  = Transport::all();
  return view('admin_panel.purchase.edit', compact('purchase','Vendor','Warehouse'));
}

public function update(Request $request, $id)
{
    $validated = $request->validate([
        'invoice_no' => 'nullable',
        'supplier' => 'nullable',
        'purchase_date' => 'nullable',
        'warehouse_id' => 'nullable',
        'item_category' => 'nullable',
        'item_name' => 'nullable|array',
        'quantity' => 'nullable|array',
        'price' => 'nullable|array',
        'unit' => 'nullable|array',
        'total' => 'nullable|array',
        'note' => 'nullable',
        'total_price' => 'nullable',
        'discount' => 'nullable',
        'Payable_amount' => 'nullable',
        'paid_amount' => 'nullable',
        'due_amount' => 'nullable',
        'status' => 'nullable',
        'is_return' => 'nullable',
    ]);

    $purchase = Purchase::findOrFail($id);

    $purchase->update([
        'invoice_no' => $validated['invoice_no'] ?? null,
        'supplier' => $validated['supplier'] ?? null,
        'purchase_date' => $validated['purchase_date'] ?? null,
        'warehouse_id' => $validated['warehouse_id'] ?? null,
        'item_category' => $validated['item_category'] ?? null,

        'item_name' => json_encode($validated['item_name'] ?? []),
        'quantity' => json_encode($validated['quantity'] ?? []),
        'price' => json_encode($validated['price'] ?? []),
        'unit' => json_encode($validated['unit'] ?? []),
        'total' => json_encode($validated['total'] ?? []),

        'note' => $validated['note'] ?? null,
        'total_price' => $validated['total_price'] ?? null,
        'discount' => $validated['discount'] ?? null,
        'Payable_amount' => $validated['Payable_amount'] ?? null,
        'paid_amount' => $validated['paid_amount'] ?? null,
        'due_amount' => $validated['due_amount'] ?? null,
        'status' => $validated['status'] ?? null,
        'is_return' => $validated['is_return'] ?? null,
    ]);

    return redirect()->route('Purchase.home')->with('success', 'Purchase updated successfully!');
}

public function destroy($id)
{
    $purchase = Purchase::findOrFail($id);
    $purchase->delete();

    return redirect()->back()->with('success', 'Purchase deleted successfully.');
}

public function addBill($gatepassId)
{
    // Fetch the gatepass along with its related items and products
    $gatepass = InwardGatepass::with('items.product')->findOrFail($gatepassId);

    // Pass the gatepass data to the view
    return view('admin_panel.inward.add_bill', compact('gatepass'));
}
}
