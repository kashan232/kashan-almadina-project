<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Account;
use App\Models\AccountHead;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\Productbooking;
use App\Models\ProductBookingItem;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    /* -------- Lists & screens -------- */
    public function index()
    {
        $sales = Sale::with(['customer', 'vendor', 'items.product'])->latest()->get();
        return view('admin_panel.sale.index', compact('sales'));
    }

    public function add_sale()
    {
        $warehouses = Warehouse::all();
        $customers = Customer::all();
        $accounts = Account::all();

        // Get next invoice from Sale model generator (ensures INVSLE-003 -> INVSLE-004)
        $nextInvoiceNumber = Sale::generateInvoiceNo();

        return view('admin_panel.sale.add_sale', compact('warehouses', 'customers', 'nextInvoiceNumber', 'accounts'));
    }


    public function Booking()
    {
        $sales = Productbooking::with('items')->latest()->get();
        return view('admin_panel.sale.booking.index', compact('sales'));
    }

    public function editBooking($id)
    {
        $booking = Productbooking::with('items.product', 'customer')->findOrFail($id);
        $warehouses = Warehouse::all();
        $customers = Customer::all();
        $lastInvoice = Productbooking::latest('id')->first();
        $nextInvoiceNumber = $lastInvoice ? intval($lastInvoice->invoice_no) + 1 : 1;

        return view('admin_panel.sale.booking.edit', compact('booking', 'warehouses', 'customers', 'nextInvoiceNumber'));
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

        SaleItem::where('sale_id', $sale->id)->delete();

        foreach ($request->warehouse_name ?? [] as $i => $warehouse_id) {
            $productId = $request->input("product_name.$i");
            if (empty($warehouse_id) || empty($productId)) {
                continue;
            }

            SaleItem::create([
                'sale_id' => $sale->id,
                'warehouse_id' => $warehouse_id,
                'product_id' => $productId,
                'stock' => (float) $request->input("stock.$i", 0),
                'price_level' => (float) $request->input("price.$i", 0),
                'sales_price' => (float) $request->input("sales-price.$i", 0),
                'sales_qty' => (float) $request->input("sales-qty.$i", 0),
                'retail_price' => (float) $request->input("retail-price.$i", 0),
                'discount_percent' => (float) $request->input("discount-percent.$i", 0),
                'discount_amount' => (float) $request->input("discount-amount.$i", 0),
                'amount' => (float) $request->input("sales-amount.$i", 0),
            ]);
        }

        return back()->with('success', 'Sale updated successfully.');
    }

    /* -------- Legacy store (direct form submit) -------- */
    public function store(Request $request)
    {
        $isBooking = $request->has('booking');
        if ($isBooking) {
            $booking = Productbooking::create([
                'invoice_no' => $request->Invoice_no,
                'manual_invoice' => $request->Invoice_main,
                'customer_id' => $request->customer,
                'party_type' => $request->input('partyType') ?? null,
                'sub_customer' => $request->customerType,
                'filer_type' => $request->filerType,
                'address' => $request->address,
                'tel' => $request->tel,
                'remarks' => $request->remarks,
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
                'weight' => $request->weight ?? null,
            ]);

            $totalQty = 0;
            foreach ($request->warehouse_name ?? [] as $i => $warehouse_id) {
                $productId = $request->input("product_name.$i");
                if (empty($warehouse_id) || empty($productId)) {
                    continue;
                }

                $qty = (float) $request->input("sales-qty.$i", 0);
                $totalQty += $qty;

                ProductBookingItem::create([
                    'booking_id' => $booking->id,
                    'warehouse_id' => $warehouse_id,
                    'product_id' => $productId,
                    'stock' => (float) $request->input("stock.$i", 0),
                    'price_level' => (float) $request->input("price.$i", 0),
                    'sales_price' => (float) $request->input("sales-price.$i", 0),
                    'sales_qty' => $qty,
                    'retail_price' => (float) $request->input("retail-price.$i", 0),
                    'discount_percent' => (float) $request->input("discount-percent.$i", 0),
                    'discount_amount' => (float) $request->input("discount-amount.$i", 0),
                    'amount' => (float) $request->input("sales-amount.$i", 0),
                ]);
            }
            $booking->quantity = $totalQty;
            $booking->save();

            return back()->with('success', 'Booking saved successfully!');
        }

        // Direct Sale (stock minus)
        return DB::transaction(function () use ($request) {
            $invoiceNo = Sale::generateInvoiceNo();
            $sale = Sale::create([
                'invoice_no' => $invoiceNo,
                'manual_invoice' => $request->Invoice_main ?? null,
                'partyType' => $request->input('partyType') ?? null,
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
                'weight' => $request->weight ?? null,
            ]);

            foreach ($request->warehouse_name ?? [] as $i => $warehouse_id) {
                $productId = $request->input("product_name.$i");
                if (empty($warehouse_id) || empty($productId)) {
                    continue;
                }

                $saleQty = (float) $request->input("sales-qty.$i", 0);

                // Per-warehouse stock
                if ($ws = WarehouseStock::where('warehouse_id', $warehouse_id)->where('product_id', $productId)->first()) {
                    $ws->quantity = max(0, $ws->quantity - $saleQty);
                    $ws->save();
                }

                // Global stock
                if ($p = Product::find($productId)) {
                    $p->stock = max(0, ($p->stock ?? 0) - $saleQty);
                    $p->save();
                }

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'warehouse_id' => $warehouse_id,
                    'product_id' => $productId,
                    'stock' => (float) $request->input("stock.$i", 0),
                    'price_level' => (float) $request->input("price.$i", 0),
                    'sales_price' => (float) $request->input("sales-price.$i", 0),
                    'sales_qty' => $saleQty,
                    'retail_price' => (float) $request->input("retail-price.$i", 0),
                    'discount_percent' => (float) $request->input("discount-percent.$i", 0),
                    'discount_amount' => (float) $request->input("discount-amount.$i", 0),
                    'amount' => (float) $request->input("sales-amount.$i", 0),
                ]);
            }

            return back()->with('success', 'Sale saved successfully!');
        });
    }

    /* -------- AJAX: Save as booking (no stock minus) -------- */
    public function ajaxSave(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $attempts = 0;
            $maxAttempts = 5;

            // if updating existing booking, keep previous behavior
            $bookingId = $request->input('booking_id');
            if ($bookingId) {
                $booking = Productbooking::findOrFail($bookingId);
                ProductBookingItem::where('booking_id', $booking->id)->delete();
                // update fields below & save as before
                $booking->manual_invoice = $request->Invoice_main;
                // ... other fields ...
                $booking->save();

                // recreate items (same as your existing code)...
                // (you can keep your existing foreach here)
                // ...
                return response()->json(['ok' => true, 'booking_id' => $booking->id]);
            }

            // NEW booking path -> must ensure invoice_no unique
            do {
                $attempts++;

                // generate invoice - adapt to your existing generator if you have one
                // e.g. Productbooking::generateInvoiceNo() or Sale::generateInvoiceNo()
                // I'll attempt to call a method; if you don't have it, create below.
                $invoiceNo = method_exists(Productbooking::class, 'generateInvoiceNo')
                    ? Productbooking::generateInvoiceNo()
                    : ('INVSLE-' . str_pad((Productbooking::max('id') ?? 0) + 1, 3, '0', STR_PAD_LEFT));

                $booking = new Productbooking();
                $booking->invoice_no = $invoiceNo;
                $booking->manual_invoice = $request->Invoice_main;
                $booking->party_type = $request->input('partyType');
                $booking->customer_id = $request->customer;
                $booking->sub_customer = $request->customerType;
                $booking->filer_type = $request->filerType;
                $booking->address = $request->address;
                $booking->tel = $request->tel;
                $booking->remarks = $request->remarks;
                $booking->sub_total1 = $request->subTotal1 ?? 0;
                $booking->sub_total2 = $request->subTotal2 ?? 0;
                $booking->discount_percent = $request->discountPercent ?? 0;
                $booking->discount_amount = $request->discountAmount ?? 0;
                $booking->previous_balance = $request->previousBalance ?? 0;
                $booking->total_balance = $request->totalBalance ?? 0;
                $booking->receipt1 = $request->receipt1 ?? 0;
                $booking->receipt2 = $request->receipt2 ?? 0;
                $booking->final_balance1 = $request->finalBalance1 ?? 0;
                $booking->final_balance2 = $request->finalBalance2 ?? 0;
                $booking->weight = $request->weight;

                try {
                    $booking->save(); // may throw unique constraint exception
                } catch (QueryException $ex) {
                    // 23000 / 1062 is duplicate key — try again with new invoice
                    if ($ex->getCode() == '23000' || str_contains($ex->getMessage(), 'Duplicate entry')) {
                        // if max reached, rethrow
                        if ($attempts >= $maxAttempts) {
                            throw $ex;
                        }
                        // otherwise try again (loop)
                        continue;
                    }
                    throw $ex;
                }

                // if saved ok -> create items
                $totalQty = 0;
                foreach ($request->warehouse_name ?? [] as $i => $warehouse_id) {
                    $productId = $request->input("product_name.$i");
                    $qty = (float) $request->input("sales-qty.$i", 0);

                    // Skip invalid/empty or zero-qty lines
                    if (empty($warehouse_id) || empty($productId) || $qty <= 0) {
                        continue;
                    }

                    $totalQty += $qty;

                    ProductBookingItem::create([
                        'booking_id' => $booking->id,
                        'warehouse_id' => $warehouse_id,
                        'product_id' => $productId,
                        'stock' => (float) $request->input("stock.$i", 0),
                        'price_level' => (float) $request->input("price.$i", 0),
                        'sales_price' => (float) $request->input("sales-price.$i", 0),
                        'sales_qty' => $qty,
                        'retail_price' => (float) $request->input("retail-price.$i", 0),
                        'discount_percent' => (float) $request->input("discount-percent.$i", 0),
                        'discount_amount' => (float) $request->input("discount-amount.$i", 0),
                        'amount' => (float) $request->input("sales-amount.$i", 0),
                    ]);
                }


                $booking->quantity = $totalQty;
                $booking->save();

                return response()->json(['ok' => true, 'booking_id' => $booking->id]);
            } while ($attempts < $maxAttempts);

            // if we somehow exit loop
            return response()->json(['ok' => false, 'msg' => 'Failed to allocate invoice no'], 500);
        });
    }

    /* -------- AJAX: Post booking -> Sale (stock minus) -------- */
    public function ajaxPost(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $bookingId = $request->input('booking_id');

            if ($bookingId) {
                $booking = Productbooking::with('items')->findOrFail($bookingId);

                $sale = Sale::create([
                    'invoice_no' => $booking->invoice_no,
                    'manual_invoice' => $booking->manual_invoice,
                    'partyType' => $booking->party_type,
                    'customer_id' => $booking->customer_id,
                    'sub_customer' => $booking->sub_customer,
                    'filer_type' => $booking->filer_type,
                    'address' => $booking->address,
                    'tel' => $booking->tel,
                    'remarks' => $booking->remarks,
                    'sub_total1' => $booking->sub_total1,
                    'sub_total2' => $booking->sub_total2,
                    'discount_percent' => $booking->discount_percent,
                    'discount_amount' => $booking->discount_amount,
                    'previous_balance' => $booking->previous_balance,
                    'total_balance' => $booking->total_balance,
                    'receipt1' => $booking->receipt1,
                    'receipt2' => $booking->receipt2,
                    'final_balance1' => $booking->final_balance1,
                    'final_balance2' => $booking->final_balance2,
                    'weight' => $booking->weight,
                ]);

                foreach ($booking->items as $it) {
                    if (!$it) {
                        continue;
                    } // guard

                    $salesQty = (float) data_get($it, 'sales_qty', 0);
                    $salesPrice = (float) data_get($it, 'sales_price', 0);
                    $retail = (float) data_get($it, 'retail_price', 0);
                    $discPct = (float) data_get($it, 'discount_percent', 0);
                    $discAmt = (float) data_get($it, 'discount_amount', 0);
                    $amount = (float) data_get($it, 'amount', 0);

                    // Per-warehouse stock
                    if ($ws = WarehouseStock::where('warehouse_id', $it->warehouse_id)->where('product_id', $it->product_id)->first()) {
                        $ws->quantity = max(0, $ws->quantity - $salesQty);
                        $ws->save();
                    }
                    // Global stock
                    if ($p = Product::find($it->product_id)) {
                        $p->stock = max(0, ($p->stock ?? 0) - $salesQty);
                        $p->save();
                    }

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'warehouse_id' => $it->warehouse_id,
                        'product_id' => $it->product_id,
                        'stock' => (float) data_get($it, 'stock', 0),
                        'price_level' => (float) data_get($it, 'price_level', 0),
                        'sales_price' => $salesPrice,
                        'sales_qty' => $salesQty,
                        'retail_price' => $retail,
                        'discount_percent' => $discPct,
                        'discount_amount' => $discAmt,
                        'amount' => $amount,
                    ]);
                }

                return response()->json([
                    'ok' => true,
                    'sale_id' => $sale->id,
                    'invoice_url' => route('sale.invoice', $sale->id),
                ]);
            }

            // Direct form -> sale (rare path)
            $request->merge(['booking' => false]);
            $this->store($request);
            $sale = Sale::latest('id')->first();

            return response()->json([
                'ok' => true,
                'sale_id' => $sale->id,
                'invoice_url' => route('sale.invoice', $sale->id),
            ]);
        });
    }

    /* -------- Prints -------- */
    public function invoice(Sale $sale)
    {
        return view('admin_panel.sale.invoice', compact('sale'));
    }
    public function print2(Sale $sale)
    {
        return view('admin_panel.sale.prints.print2', compact('sale'));
    }
    public function dc(Sale $sale)
    {
        return view('admin_panel.sale.prints.dc', compact('sale'));
    }

    public function bookingPrint(Productbooking $booking)
    {
        return view('admin_panel.sale.booking.prints.print', compact('booking'));
    }
    public function bookingPrint2(Productbooking $booking)
    {
        return view('admin_panel.sale.booking.prints.print2', compact('booking'));
    }
    public function bookingDc(Productbooking $booking)
    {
        return view('admin_panel.sale.booking.prints.dc', compact('booking'));
    }

    /* -------- Support APIs -------- */
    public function getProductsByWarehouse($warehouseId)
    {
        // JSON: [{id, name}]
        $rows = WarehouseStock::with('product:id,name')->where('warehouse_id', $warehouseId)->get()->map(fn($ws) => ['id' => $ws->product_id, 'name' => optional($ws->product)->name ?? '']);

        if ($rows->isEmpty()) {
            $rows = Product::select('id', 'name')->when(\Schema::hasColumn('products', 'status'), fn($q) => $q->where('status', 1))->orderBy('name')->get();
        }
        return response()->json($rows->values());
    }

    // public function getStock($productId)
    // {
    //     $product = Product::with('prices')->find($productId);
    //     if (!$product) return response()->json(['error'=>'Product not found'],404);

    //     $price = optional($product->prices->first())->sale_retail_price ?? 0;
    //     return response()->json([
    //         'stock'=> (float)($product->stock ?? 0),
    //         'price'=> (float)$price,
    //     ]);
    // }

    public function getStock($productId)
    {
        $product = Product::with('prices')->find($productId);
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        // Fetch the latest price record (or based on your logic, you can fetch based on date range)
        $price = $product->prices()->latest()->first();

        return response()->json([
            'stock' => (float) ($product->stock ?? 0),
            'sales_price' => (float) $price->sale_net_amount, // Using sale_net_amount as sales price
            'retail_price' => (float) $price->sale_retail_price, // Using sale_retail_price for retail price
        ]);
    }
    // SaleController.php
    public function filterCustomers(Request $request)
    {
        // Default type is 'customer', if not provided
        $type = $request->query('type', 'customer');

        // Check if the type is 'vendor'
        if ($type === 'vendor') {
            // If type is 'vendor', fetch vendors
            $rows = Vendor::orderBy('name')->get(['id', 'name', 'phone']); // Vendor details you need

            // Return the vendor data
            return response()->json(
                $rows->map(
                    fn($v) => [
                        'id' => $v->id,
                        'text' => $v->name . ' - ' . ($v->phone ?? 'No phone'), // Display vendor name and phone
                    ],
                ),
            );
        }

        // Check if the type is 'walking'
        if ($type === 'walking') {
            // If type is 'walking', fetch walking customers
            $rows = Customer::where('customer_type', 'Walking Customer')
                ->orderBy('customer_name')
                ->get(['id', 'customer_id', 'customer_name']);
            // Return walking customer data
            return response()->json(
                $rows->map(
                    fn($c) => [
                        'id' => $c->id,
                        'text' => $c->customer_id . ' - ' . $c->customer_name, // Display customer ID and name
                    ],
                ),
            );
        }

        // Default: Fetch customers for 'customer' type
        $rows = Customer::where('customer_type', 'Main Customer')
            ->orderBy('customer_name')
            ->get(['id', 'customer_id', 'customer_name']);

        // Return customer data
        return response()->json(
            $rows->map(
                fn($c) => [
                    'id' => $c->id,
                    'text' => $c->customer_id . ' - ' . $c->customer_name, // Display customer ID and name
                ],
            ),
        );
    }

    public function getCustomerData($id, Request $request)
    {
        $type = strtolower($request->query('type', 'customer'));

        if ($type === 'vendor') {
            // Fetch Vendor data
            $v = Vendor::find($id);
            if (!$v) {
                return response()->json(['error' => 'Vendor not found'], 404);
            }

            return response()->json([
                'address' => $v->address,
                'mobile' => $v->phone, // assuming 'phone' field for vendors
                'remarks' => '', // No remarks for vendors
                'previous_balance' => 0, // Vendors may not have balance logic
            ]);
        }

        // Default: Fetch Customer data (including walking)
        $c = Customer::find($id);
        if (!$c) {
            return response()->json(['error' => 'Customer not found'], 404);
        }

        // Retrieve the latest ledger entry for the customer
        $latestLedger = CustomerLedger::where('customer_id', $id)->latest()->first();

        // If a ledger entry exists, use its closing_balance; otherwise, set it to 0
        $previous_balance = $latestLedger ? $latestLedger->closing_balance : 0;

        return response()->json([
            'filer_type' => $c->filer_type,
            'customer_type' => $c->customer_type,
            'address' => $c->address,
            'mobile' => $c->mobile,
            'remarks' => $c->remarks ?? '',
            'previous_balance' => $previous_balance, // Use the latest closing_balance
        ]);
    }

    public function getVendorData($id)
    {
        $vendor = Vendor::find($id);
        if (!$vendor) {
            return response()->json(['error' => 'Vendor not found'], 404);
        }

        // Return vendor data (adjust as per your requirements)
        return response()->json([
            'address' => $vendor->address,
            'mobile' => $vendor->phone,
            'remarks' => '', // No remarks for vendors or set as required
            'previous_balance' => $vendor->debit, // Example using vendor's debit balance
        ]);
    }

    // OPTIONAL: delete selected customer via AJAX
    public function deleteCustomer(Customer $customer)
    {
        // yahan authorization lagana better hai (Policy/Gate)
        $customer->delete();
        return response()->json(['ok' => true]);
    }

    public function getAccountList(Request $request)
    {
        // Get all accounts with 'cashbank' scope (assuming you filter by this scope)
        $accounts = Account::get(['id', 'title']);

        return response()->json($accounts);
    }


    public function create_stock_hold()
    {
        $Vendor = Vendor::get();
        $Warehouses = Warehouse::get();
        $AccountHeads = AccountHead::get();
        $customers = Customer::all();

        return view('admin_panel.stock_hold.create_stock_hold', compact('Warehouses', 'customers'));
    }


    public function search(Request $request)
    {
        $q = $request->get('q', '');
        $rows = \App\Models\Product::query()
            ->select('id', 'name', 'stock')
            ->where('name', 'like', "%{$q}%")
            ->limit(20)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'stock' => $p->stock ?? 0,
                ];
            });

        return response()->json($rows);
    }



    // Return list of parties depending on type (vendor/customer/walkin)
    public function partyList(Request $request)
    {
        $type = strtolower($request->query('type', 'customer'));

        if ($type === 'vendor') {
            $rows = \App\Models\Vendor::orderBy('name')->get(['id', 'name as text']);
            return response()->json($rows->values());
        }

        if ($type === 'walkin' || $type === 'walking') {
            $rows = \App\Models\Customer::where('customer_type', 'Walking Customer')
                ->orderBy('customer_name')
                ->get()
                ->map(fn($c) => ['id' => $c->id, 'text' => $c->customer_id . ' - ' . $c->customer_name]);
            return response()->json($rows);
        }

        // default customers
        $rows = \App\Models\Customer::where('customer_type', 'Main Customer')
            ->orderBy('customer_name')
            ->get()
            ->map(fn($c) => ['id' => $c->id, 'text' => $c->customer_id . ' - ' . $c->customer_name]);
        return response()->json($rows);
    }

    // Given a party id + type, return list of invoices (productbookings) for that party
    public function partyInvoices($id, Request $request)
    {
        $type = strtolower($request->query('type', 'customer')); // vendor/customer/walkin

        // party stored in productbookings as customer_id and party_type
        $invoices = Productbooking::where('party_type', $type)
            ->where('customer_id', $id)
            ->orderBy('id', 'desc')
            ->get(['id', 'invoice_no']);

        // map to {id, text}
        $list = $invoices->map(fn($r) => ['id' => $r->id, 'text' => $r->invoice_no])->values();

        return response()->json($list);
    }

    // Return items for a productbooking (invoice)
    public function invoiceItems($id)
    {
        $items = ProductBookingItem::where('booking_id', $id)
            ->with('product:id,name')
            ->get()
            ->map(function ($it) use ($id) {
                return [
                    'item_id' => $it->id,                  // unique booking item id (not product_id)
                    'product_id' => $it->product_id,
                    'warehouse_id' => $it->warehouse_id ?? null,
                    'item_name' => optional($it->product)->name ?: ($it->item_name ?? 'Unknown'),
                    'sales_qty' => (float) ($it->sales_qty ?? $it->quantity ?? 0),
                    'hold_qty' => (float) ($it->hold_qty ?? 0), // if you store previous holds
                    'sale_id' => $id,                       // include sale/invoice id
                ];
            });

        return response()->json($items);
    }
}
