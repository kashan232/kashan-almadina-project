<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Warehouse;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem; // NOTE: agar model ka naam SaleReturnitem hai to yahi import badal dein
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalereturnController extends Controller
{
    public function index_salereturn()
    {
        return view('admin_panel.sale.return.index');
    }

    // public function index_salereturn_Add($id)
    // {
    //     $sale = Sale::with(['items','items.product'])->findOrFail($id);

    //     $warehouses = Warehouse::all();
    //     $customers  = Customer::all();

    //     do {
    //         $nextInvoiceNumber = 'SR-' . time() . rand(1, 99);
    //         $exists = SaleReturn::where('invoice_no', $nextInvoiceNumber)->exists();
    //     } while ($exists);

    //     return view('admin_panel.sale.return.create', compact(
    //         'sale','warehouses','customers','nextInvoiceNumber'
    //     ));
    // }

    public function index_salereturn_Add($id)
{
    $sale = Sale::with(['items','items.product'])->findOrFail($id);

    $warehouses = Warehouse::all();
    $customers  = Customer::all();

    do {
        $nextInvoiceNumber = 'SR-' . time() . rand(1, 99);
        $exists = \App\Models\SaleReturn::where('invoice_no', $nextInvoiceNumber)->exists();
    } while ($exists);

    return view('admin_panel.sale.return.create', compact(
        'sale','warehouses','customers','nextInvoiceNumber'
    ));
}


    public function store(Request $request)
{
    $request->validate([
        'Invoice_no'        => 'required',
        'warehouse_id.*'    => 'required|integer',
        'product_id.*'      => 'required|integer',
        'sold_qty.*'        => 'nullable',            // if you post it
        'return_qty.*'      => 'required|numeric|min:0',
        'sales-price.*'     => 'required|numeric|min:0',
    ]);

    return DB::transaction(function () use ($request) {

        $sr = SaleReturn::create([
            'invoice_no'       => $request->Invoice_no,
            'manual_invoice'   => $request->Invoice_main,
            'customer_id'      => $request->customer,
            'address'          => $request->address,
            'tel'              => $request->tel,
            'remarks'          => $request->remarks,
            'sub_total1'       => (float) $request->subTotal1,
            'sub_total2'       => (float) $request->subTotal2,
            'discount_percent' => (float) $request->discountPercent,
            'discount_amount'  => (float) $request->discountAmount,
            'total_balance'    => (float) $request->totalBalance,
        ]);

        foreach (($request->warehouse_id ?? []) as $i => $wid) {
            $pid    = (int) $request->product_id[$i];
            $sold   = (float) ($request->sold_qty[$i] ?? 0);
            $retQty = max(0, (float) $request->return_qty[$i]);

            // cap: return cannot exceed sold
            if ($sold > 0 && $retQty > $sold) { $retQty = $sold; }
            if ($retQty <= 0) continue;

            $price   = (float) $request->input("sales-price.$i", 0);
            $discPct = (float) $request->input("discount-percent.$i", 0);
            $discAmt = (float) $request->input("discount-amount.$i", 0);
            $gross   = $price * $retQty;
            if ($discPct > 0) { $discAmt = ($gross * $discPct)/100.0; }
            $amount  = $gross - $discAmt;

            // + stock in the SELECTED warehouse (return location)
            $ws = WarehouseStock::firstOrNew(['warehouse_id'=>$wid, 'product_id'=>$pid]);
            $ws->quantity = (float)($ws->quantity ?? 0) + $retQty;
            $ws->save();

            // + global stock
            if ($p = Product::find($pid)) {
                $p->stock = (float)($p->stock ?? 0) + $retQty;
                $p->save();
            }

            // save line
            SaleReturnItem::create([
                'sale_return_id'   => $sr->id,
                'warehouse_id'     => $wid,   // selected warehouse
                'product_id'       => $pid,
                'stock'            => (float) $request->input("stock.$i", 0),
                'price_level'      => (float) $request->input("price.$i", 0),
                'sales_price'      => $price,
                'sales_qty'        => $retQty,
                'discount_percent' => $discPct,
                'discount_amount'  => $discAmt,
                'amount'           => $amount,
            ]);
        }

        return back()->with('success','Sale Return saved successfully!');
    });
}
}

