<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Account;
use App\Models\AccountHead;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\VendorLedger;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\Productbooking;
use App\Models\ProductBookingItem;
use App\Models\WarehouseStock;
use App\Models\Voucher;
use App\Models\ReceiptsVoucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    /* -------- Lists & screens -------- */
    public function index(Request $request)
    {
        // Fetch all users for the filter
        $users = \App\Models\User::orderBy('name')->get();

        // Fetch Posted Sales with items and user
        $salesQuery = Sale::with(['customer', 'vendor', 'items.product', 'items.warehouse', 'user'])->latest();
        
        // Fetch Unposted Bookings with items and user
        $bookingsQuery = Productbooking::with(['customer', 'vendor', 'items.product', 'items.warehouse', 'user'])->latest();

        // Filters
        if ($request->filled('start_date')) {
            $salesQuery->whereDate('created_at', '>=', $request->start_date);
            $bookingsQuery->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $salesQuery->whereDate('created_at', '<=', $request->end_date);
            $bookingsQuery->whereDate('created_at', '<=', $request->end_date);
        }
        if ($request->filled('sale_type')) {
            $isOrder = $request->sale_type === 'order' ? 1 : 0;
            $salesQuery->where('is_sale_order', $isOrder);
            $bookingsQuery->where('is_sale_order', $isOrder);
        }
        if ($request->filled('created_by')) {
            $salesQuery->where('created_by', $request->created_by);
            $bookingsQuery->where('created_by', $request->created_by);
        }

        $salesRows = $salesQuery->get()->map(function($s) {
            $s->entry_status = 'Posted';
            $s->p_type = $s->partyType ?? 'customer';
            return $s;
        });

        $bookingsRows = $bookingsQuery->get()->map(function($b) {
            $b->entry_status = 'Unposted';
            $b->p_type = $b->party_type ?? 'customer'; 
            return $b;
        });

        $combined = $salesRows->concat($bookingsRows)->sortByDesc('created_at');

        if ($request->filled('status')) {
            $combined = $combined->where('entry_status', $request->status);
        }

        return view('admin_panel.sale.index', [
            'sales' => $combined,
            'users' => $users
        ]);
    }

    public function add_sale()
    {
        $warehouses = Warehouse::all();
        $customers = Customer::all();
        $accounts = Account::all();
        $accountHeads = AccountHead::all();

        // Get next invoice from Sale model generator (ensures INVSLE-003 -> INVSLE-004)
        $nextInvoiceNumber = Sale::generateInvoiceNo();

        return view('admin_panel.sale.add_sale', compact('warehouses', 'customers', 'nextInvoiceNumber', 'accounts', 'accountHeads'));
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
        $accountHeads = AccountHead::all();
        $accounts = Account::all();
        $nextInvoiceNumber = $booking->invoice_no;

        return view('admin_panel.sale.add_sale', compact('booking', 'warehouses', 'customers', 'nextInvoiceNumber', 'accountHeads', 'accounts'));
    }

    public function edit($id)
    {
        $sale = Sale::with('items.product', 'customer')->findOrFail($id);
        $warehouses = Warehouse::all();
        $customers = Customer::all();
        $accountHeads = AccountHead::all();
        $accounts = Account::all();
        $nextInvoiceNumber = $sale->invoice_no;
        return view('admin_panel.sale.add_sale', compact('sale', 'warehouses', 'customers', 'nextInvoiceNumber', 'accountHeads', 'accounts'));
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
            'entry_date' => $request->entry_date,
            'entry_time' => $request->entry_time,
        ]);

        SaleItem::where('sale_id', $sale->id)->delete();

        $productIds = $request->input('product_id', []);
        $warehouseIds = $request->input('warehouse_name', []);
        $stocks = $request->input('stock', []);
        $salesPrices = $request->input('sales-price', []);
        $salesQtys = $request->input('sales-qty', []);
        $retailPrices = $request->input('retail-price', []);
        $salesRates = $request->input('sales-rate', []);
        $discPercents = $request->input('discount-percent', []);
        $discAmounts = $request->input('discount-amount', []);
        $amounts = $request->input('sales-amount', []);

        foreach ($warehouseIds as $i => $warehouse_id) {
            $productId = $productIds[$i] ?? null;
            $qty = (float)($salesQtys[$i] ?? 0);

            if (($warehouse_id === null || $warehouse_id === '') || empty($productId) || $qty <= 0) {
                continue;
            }

            // Security Gate: Shop Stock
            if ($warehouse_id == 0 && !auth()->user()->canAccessShop()) {
                return back()->with('error', 'Unauthorized access to Shop Stock.');
            }

            // Security Gate: Warehouse Isolation
            if ($warehouse_id != 0 && !\App\Models\Warehouse::where('id', $warehouse_id)->exists()) {
                return back()->with('error', 'Unauthorized access to Warehouse.');
            }

            SaleItem::create([
                'sale_id' => $sale->id,
                'warehouse_id' => $warehouse_id,
                'product_id' => $productId,
                'stock' => (float)($stocks[$i] ?? 0),
                'price_level' => 0,
                'sales_price' => (float)($salesPrices[$i] ?? 0),
                'sales_qty' => $qty,
                'retail_price' => (float)($retailPrices[$i] ?? 0),
                'sales_rate' => (float)($salesRates[$i] ?? 0),
                'discount_percent' => (float)($discPercents[$i] ?? 0),
                'discount_amount' => (float)($discAmounts[$i] ?? 0),
                'amount' => (float)($amounts[$i] ?? 0),
            ]);
        }

        return back()->with('success', 'Sale updated successfully.');
    }

    /* -------- Legacy store (direct form submit) -------- */
    public function store(Request $request)
    {
        $isBooking = $request->has('booking');
        
        // Aggregate all receipts from common form input array
        $receiptAmounts = $request->input('receipt_amount', []);
        $totalReceipts = array_sum(array_map('floatval', $receiptAmounts));

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
                    'receipt1' => $totalReceipts,
                    'receipt2' => 0,
                    'final_balance1' => $request->finalBalance1 ?? 0,
                    'final_balance2' => $request->finalBalance2 ?? 0,
                    'weight' => $request->weight ?? null,
                    'entry_date' => $request->entry_date,
                    'entry_time' => $request->entry_time,
                ]);

            $totalQty = 0;
            foreach ($request->warehouse_name ?? [] as $i => $warehouse_id) {
                $productId = $request->input("product_id.$i");
                $qty = (float) $request->input("sales-qty.$i", 0);
                if (($warehouse_id === null || $warehouse_id === '') || empty($productId) || $qty <= 0) {
                    continue;
                }

                // Security Gate: Shop Stock
                if ($warehouse_id == 0 && !auth()->user()->canAccessShop()) {
                    return back()->with('error', 'Unauthorized access to Shop Stock.');
                }

                // Security Gate: Warehouse Isolation
                if ($warehouse_id != 0 && !\App\Models\Warehouse::where('id', $warehouse_id)->exists()) {
                    return back()->with('error', 'Unauthorized access to Warehouse.');
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
                    'sales_rate' => (float) $request->input("sales-rate.$i", 0),
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
                'entry_date' => $request->entry_date,
                'entry_time' => $request->entry_time,
            ]);

            foreach ($request->warehouse_name ?? [] as $i => $warehouse_id) {
                $productId = $request->input("product_id.$i");
                $saleQty = (float) $request->input("sales-qty.$i", 0);

                if (($warehouse_id === null || $warehouse_id === '') || empty($productId) || $saleQty <= 0) {
                    continue;
                }

                // Security Gate: Shop Stock
                if ($warehouse_id == 0 && !auth()->user()->canAccessShop()) {
                    return back()->with('error', 'Unauthorized access to Shop Stock.');
                }

                // Security Gate: Warehouse Isolation
                if ($warehouse_id != 0 && !\App\Models\Warehouse::where('id', $warehouse_id)->exists()) {
                    return back()->with('error', 'Unauthorized access to Warehouse.');
                }

                $saleQty = (float) $request->input("sales-qty.$i", 0);

                // Stock Logic: 0 = Shop, >0 = Warehouse
                if ($warehouse_id == 0) {
                    // Shop Stock
                    if ($p = Product::find($productId)) {
                        $p->stock = ($p->stock ?? 0) - $saleQty;
                        $p->save();
                    }
                } else {
                    // Warehouse Stock
                    if ($ws = WarehouseStock::where('warehouse_id', $warehouse_id)
                        ->where('product_id', $productId)
                        ->first()) {
                        $ws->quantity = ($ws->quantity ?? 0) - $saleQty;
                        $ws->save();
                    }
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
                    'sales_rate' => (float) $request->input("sales-rate.$i", 0),
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
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'partyType'    => 'required',
            'customer'     => 'required',
            'product_id'   => 'required|array|min:1',
            'sales-qty'    => 'required|array',
            'sales-qty.*'  => 'required|numeric|min:0.001',
        ], [
            'partyType.required'   => 'Please select party type.',
            'customer.required'    => 'Please select a customer.',
            'product_id.required'  => 'Add at least one item.',
            'sales-qty.*.required' => 'Qty required.',
            'sales-qty.*.min'      => 'Qty > 0.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok'     => false, 
                'errors' => $validator->errors(),
                'msg'    => 'Validation failed. Please check the fields.'
            ], 422);
        }

        return DB::transaction(function () use ($request) {
            $attempts = 0;
            $maxAttempts = 5;

            $bookingId = $request->input('booking_id');
            if ($bookingId) {
                $booking = Productbooking::findOrFail($bookingId);
                ProductBookingItem::where('booking_id', $booking->id)->delete();
            } else {
                // NEW booking path -> must ensure invoice_no unique
                do {
                    $attempts++;
                    $invoiceNo = Sale::generateInvoiceNo();

                    $booking = new Productbooking();
                    $booking->invoice_no = $invoiceNo;
                    try {
                        $booking->save(); // temporary save to get ID and lock invoice_no
                        break;
                    } catch (\Exception $ex) {
                        if ($attempts >= $maxAttempts) throw $ex;
                        continue;
                    }
                } while ($attempts < $maxAttempts);
            }

            // Common field updates
            $booking->manual_invoice = $request->Invoice_main;
            $booking->party_type     = $request->input('partyType');
            $booking->customer_id    = $request->customer;
            $booking->sub_customer   = $request->customerType;
            $booking->filer_type     = $request->filerType;
            $booking->address        = $request->address;
            $booking->tel            = $request->tel;
            $booking->remarks        = $request->remarks;
            $booking->sub_total1     = $request->subTotal1 ?? 0;
            $booking->sub_total2     = $request->subTotal2 ?? 0;
            $booking->discount_percent = $request->discountPercent ?? 0;
            $booking->discount_amount  = $request->discountAmount ?? 0;
            $booking->discount_head  = $request->discount_head;
            $booking->discount_account_id = $request->discount_account_id;
            $booking->previous_balance = $request->previousBalance ?? 0;
            $booking->total_balance    = $request->totalBalance ?? 0;
            
            // Sum up all receipt amounts and also save the details
            $receiptHeads = $request->input('receipt_head_id', []);
            $receiptAccounts = $request->input('receipt_account_id', []);
            $receiptNarrations = $request->input('receipt_narration', []);
            $receiptAmountsArr = $request->input('receipt_amount', []);
            
            $totalReceipts = array_sum(array_map('floatval', $receiptAmountsArr));
            
            $booking->receipt1 = $totalReceipts;
            $booking->receipt2 = 0;
            $booking->receipt_heads = json_encode($receiptHeads);
            $booking->receipt_accounts = json_encode($receiptAccounts);
            $booking->receipt_narrations = json_encode($receiptNarrations);
            $booking->receipt_amounts_json = json_encode($receiptAmountsArr);

            $booking->final_balance1 = $request->finalBalance1 ?? 0;
            $booking->final_balance2 = $request->finalBalance2 ?? 0;
            $booking->weight         = $request->weight;
            $booking->entry_date     = $request->entry_date;
            $booking->entry_time     = $request->entry_time;

            $productIds = $request->input('product_id', []);
            $warehouseIds = $request->input('warehouse_name', []);
            $stocks = $request->input('stock', []);
            $salesPrices = $request->input('sales-price', []);
            $salesQtys = $request->input('sales-qty', []);
            $retailPrices = $request->input('retail-price', []);
            $salesRates = $request->input('sales-rate', []);
            $discPercents = $request->input('discount-percent', []);
            $discAmounts = $request->input('discount-amount', []);
            $amounts = $request->input('sales-amount', []);

            $totalQty = 0;
            // Use warehouse_name as the primary loop key since it's now always present
            foreach ($warehouseIds as $i => $warehouse_id) {
                $productId = $productIds[$i] ?? null;
                $qty = (float) ($salesQtys[$i] ?? 0);

                if (($warehouse_id === null || $warehouse_id === '') || empty($productId) || $qty <= 0) {
                    continue;
                }

                // Security Gate: Shop Stock
                if ($warehouse_id == 0 && !auth()->user()->canAccessShop()) {
                    return response()->json(['ok' => false, 'msg' => 'Unauthorized access to Shop Stock.'], 403);
                }

                // Security Gate: Warehouse Isolation
                if ($warehouse_id != 0 && !\App\Models\Warehouse::where('id', $warehouse_id)->exists()) {
                    return response()->json(['ok' => false, 'msg' => 'Unauthorized access to Warehouse.'], 403);
                }

                $totalQty += $qty;

                $inputSalesPrice = (float) ($salesPrices[$i] ?? 0);
                $productDefaultPrice = \App\Models\Product::find($productId)->latestPrice->sale_net_amount ?? 0;
                $salesPriceToSave = $inputSalesPrice > 0 ? $inputSalesPrice : $productDefaultPrice;

                ProductBookingItem::create([
                    'booking_id' => $booking->id,
                    'warehouse_id' => $warehouse_id,
                    'product_id' => $productId,
                    'stock' => (float) ($stocks[$i] ?? 0),
                    'sales_price' => $salesPriceToSave,
                    'sales_qty' => $qty,
                    'retail_price' => (float) ($retailPrices[$i] ?? 0),
                    'sales_rate' => (float) ($salesRates[$i] ?? 0),
                    'discount_percent' => (float) ($discPercents[$i] ?? 0),
                    'discount_amount' => (float) ($discAmounts[$i] ?? 0),
                    'amount' => (float) ($amounts[$i] ?? 0),
                ]);
            }

            $booking->quantity = $totalQty;
            $booking->is_sale_order = $request->has('is_sale_order') ? 1 : 0;
            $booking->save();

            return response()->json(['ok' => true, 'booking_id' => $booking->id, 'invoice_no' => $booking->invoice_no]);
        });
    }

    /* -------- AJAX: Post booking -> Sale (stock minus) -------- */
    public function ajaxPost(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $bookingId = $request->input('booking_id');

            if ($bookingId) {
                $booking = Productbooking::with('items')->findOrFail($bookingId);

                $invNo = $booking->invoice_no;
                if (Sale::where('invoice_no', $invNo)->exists()) {
                    $invNo = Sale::generateInvoiceNo();
                }

                $sale = Sale::create([
                    'invoice_no' => $invNo,
                    'is_sale_order' => $booking->is_sale_order,
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
                    'receipt_heads' => $booking->receipt_heads,
                    'receipt_accounts' => $booking->receipt_accounts,
                    'receipt_narrations' => $booking->receipt_narrations,
                    'receipt_amounts_json' => $booking->receipt_amounts_json,
                    'final_balance1' => $booking->final_balance1,
                    'final_balance2' => $booking->final_balance2,
                    'weight' => $booking->weight,
                    'entry_date' => $booking->entry_date,
                    'entry_time' => $booking->entry_time,
                    'created_by' => $booking->created_by,
                    'user_group_ids' => $booking->user_group_ids,
                ]);

                // If Sale Order, create a Stock Hold Voucher (Prevent Double)
                $holdVoucher = null;
                if ($sale->is_sale_order) {
                    $holdVoucher = \App\Models\StockHoldVoucher::where('sale_id', $sale->id)->first();
                    if (!$holdVoucher) {
                        $holdVoucher = \App\Models\StockHoldVoucher::create([
                            'sale_id' => $sale->id,
                            'voucher_no' => \App\Models\StockHoldVoucher::generateVoucherNo(),
                            'party_type' => $sale->partyType,
                            'party_id' => $sale->customer_id,
                            'date' => now(),
                            'status' => 'Posted', // Automate posting for auto-holds
                            'remarks' => 'Auto-Hold from Sale Order #' . $sale->invoice_no,
                        ]);
                    } else {
                        // Hold already exists, skip creating items to avoid double
                        $holdVoucher = null; 
                    }
                }

                foreach ($booking->items as $it) {
                    if (!$it) continue;
                    
                    $salesQty = (float) data_get($it, 'sales_qty', 0);
                    $salesPrice = (float) data_get($it, 'sales_price', 0);
                    $retail = (float) data_get($it, 'retail_price', 0);
                    $discPct = (float) data_get($it, 'discount_percent', 0);
                    $discAmt = (float) data_get($it, 'discount_amount', 0);
                    $amount = (float) data_get($it, 'amount', 0);

                    // Stock Logic: Skip deduction if is_sale_order
                    if (!$sale->is_sale_order) {
                        if ($it->warehouse_id == 0) {
                            // Shop Stock
                            if ($p = Product::find($it->product_id)) {
                                $p->stock = ($p->stock ?? 0) - $salesQty;
                                $p->save();
                            }
                        } else {
                            // Warehouse Stock
                            if ($ws = WarehouseStock::where('warehouse_id', $it->warehouse_id)
                                ->where('product_id', $it->product_id)
                                ->first()) {
                                $ws->quantity = ($ws->quantity ?? 0) - $salesQty;
                                $ws->save();
                            }
                        }
                    } elseif ($holdVoucher) {
                        // Create Stock Hold Record (Only if we created/found a voucher just now)
                        \App\Models\StockHold::create([
                            'stock_hold_voucher_id' => $holdVoucher->id,
                            'sale_id' => $sale->id,
                            'party_type' => $sale->partyType,
                            'party_id' => $sale->customer_id,
                            'product_id' => $it->product_id,
                            'warehouse_id' => $it->warehouse_id,
                            'hold_qty' => $salesQty,
                            'sale_qty' => $salesQty,
                            'entry_date' => now(),
                            'status' => 0,
                        ]);
                    }

                    $productDefaultPrice = \App\Models\Product::find($it->product_id)->latestPrice->sale_net_amount ?? 0;
                    $salesPriceToSave = $salesPrice > 0 ? $salesPrice : $productDefaultPrice;

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'warehouse_id' => $it->warehouse_id,
                        'product_id' => $it->product_id,
                        'stock' => (float) data_get($it, 'stock', 0),
                        'price_level' => (float) data_get($it, 'price_level', 0),
                        'sales_price' => $salesPriceToSave,
                        'sales_qty' => $salesQty,
                        'retail_price' => $retail,
                        'sales_rate' => (float) data_get($it, 'sales_rate', 0),
                        'discount_percent' => $discPct,
                        'discount_amount' => $discAmt,
                        'amount' => $amount,
                    ]);
                }

                // --- Ledger & Accounts Update ---
                $this->performPosting($sale);

                // FIX: Delete the booking (draft) after it has been converted to a Sale
                $booking->items()->delete();
                $booking->delete();

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

    /* -------- Post booking -> Sale (from list) -------- */
    public function post($id)
    {
        return DB::transaction(function () use ($id) {
            $booking = Productbooking::with('items')->findOrFail($id);
            
            $invNo = $booking->invoice_no;
            if (Sale::where('invoice_no', $invNo)->exists()) {
                $invNo = Sale::generateInvoiceNo();
            }

            $sale = Sale::create([
                'invoice_no' => $invNo,
                'is_sale_order' => $booking->is_sale_order,
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
                'discount_head' => $booking->discount_head,
                'discount_account_id' => $booking->discount_account_id,
                'previous_balance' => $booking->previous_balance,
                'total_balance' => $booking->total_balance,
                'receipt1' => $booking->receipt1,
                'receipt2' => $booking->receipt2,
                'receipt_heads' => $booking->receipt_heads,
                'receipt_accounts' => $booking->receipt_accounts,
                'receipt_narrations' => $booking->receipt_narrations,
                'receipt_amounts_json' => $booking->receipt_amounts_json,
                'final_balance1' => $booking->final_balance1,
                'final_balance2' => $booking->final_balance2,
                'weight' => $booking->weight,
                'entry_date' => $booking->entry_date,
                'entry_time' => $booking->entry_time,
                'created_by' => $booking->created_by,
                'user_group_ids' => $booking->user_group_ids,
            ]);

            // If Sale Order, create a Stock Hold Voucher (Prevent Double)
            $holdVoucher = null;
            if ($sale->is_sale_order) {
                $holdVoucher = \App\Models\StockHoldVoucher::where('sale_id', $sale->id)->first();
                if (!$holdVoucher) {
                    $holdVoucher = \App\Models\StockHoldVoucher::create([
                        'sale_id' => $sale->id,
                        'voucher_no' => \App\Models\StockHoldVoucher::generateVoucherNo(),
                        'party_type' => $sale->partyType,
                        'party_id' => $sale->customer_id,
                        'date' => now(),
                        'status' => 'Posted', // Automate posting for auto-holds
                        'remarks' => 'Auto-Hold from Sale Order #' . $sale->invoice_no,
                    ]);
                } else {
                    $holdVoucher = null; // Already exists
                }
            }

            foreach ($booking->items as $it) {
                $salesQty = (float)($it->sales_qty ?? 0);
                
                // Stock Logic: Skip deduction if is_sale_order
                if (!$sale->is_sale_order) {
                    if ($it->warehouse_id == 0) {
                        if ($p = Product::find($it->product_id)) {
                            $p->stock = ($p->stock ?? 0) - $salesQty;
                            $p->save();
                        }
                    } else {
                        if ($ws = WarehouseStock::where('warehouse_id', $it->warehouse_id)
                            ->where('product_id', $it->product_id)
                            ->first()) {
                            $ws->quantity = ($ws->quantity ?? 0) - $salesQty;
                            $ws->save();
                        }
                    }
                } elseif ($holdVoucher) {
                    // Create Stock Hold Record (Only if we created/found a voucher just now)
                    \App\Models\StockHold::create([
                        'stock_hold_voucher_id' => $holdVoucher->id,
                        'sale_id' => $sale->id,
                        'party_type' => $sale->partyType,
                        'party_id' => $sale->customer_id,
                        'product_id' => $it->product_id,
                        'warehouse_id' => $it->warehouse_id,
                        'hold_qty' => $salesQty,
                        'sale_qty' => $salesQty,
                        'entry_date' => now(),
                        'status' => 0, // 0 = Active Hold
                    ]);
                }

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'warehouse_id' => $it->warehouse_id,
                    'product_id' => $it->product_id,
                    'stock' => (float)($it->stock ?? 0),
                    'price_level' => (float)($it->price_level ?? 0),
                    'sales_price' => (float)($it->sales_price ?? 0),
                    'sales_qty' => $salesQty,
                    'retail_price' => (float)($it->retail_price ?? 0),
                    'sales_rate' => (float)($it->sales_rate ?? 0),
                    'discount_percent' => (float)($it->discount_percent ?? 0),
                    'discount_amount' => (float)($it->discount_amount ?? 0),
                    'amount' => (float)($it->amount ?? 0),
                ]);
            }

            // --- Ledger & Accounts Update ---
            $this->performPosting($sale);

            $booking->items()->delete();
            $booking->delete();

            return redirect()->route('sale.index')->with('success', 'Sale posted successfully.');
        });
    }

    /* -------- Delete Unposted Booking -------- */
    public function destroy($id)
    {
        $booking = Productbooking::findOrFail($id);
        $booking->items()->delete();
        $booking->delete();
        return redirect()->route('sale.index')->with('success', 'Booking deleted successfully.');
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

    public function bookingPrint($id)
    {
        $booking = Productbooking::with('items.product')->findOrFail($id);
        return view('admin_panel.sale.booking.prints.print', compact('booking'));
    }
    public function bookingPrint2($id)
    {
        $booking = Productbooking::with('items.product')->findOrFail($id);
        return view('admin_panel.sale.booking.prints.print2', compact('booking'));
    }
    public function bookingDc($id)
    {
        $booking = Productbooking::with('items.product')->findOrFail($id);
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

    // Get ALL products for sale (not filtered by warehouse)
    public function getAllSaleProducts()
    {
        $products = Product::select('id', 'name')
            ->when(\Schema::hasColumn('products', 'status'), fn($q) => $q->where('status', 1))
            ->orderBy('name')
            ->get();
        
        return response()->json($products->values());
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

    public function getStock(Request $request, $productId)
    {
        $product = Product::with('prices')->find($productId);
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $warehouseId = $request->get('warehouse_id', 0);
        $stock = 0;

        if ($warehouseId == 0) {
            $stock = $product->stock;
        } else {
            $ws = WarehouseStock::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->first();
            $stock = $ws ? $ws->quantity : 0;
        }

        // Fetch the latest price record
        $price = $product->prices()->latest()->first();

        return response()->json([
            'stock' => (float) ($stock ?? 0),
            'sales_price' => (float) ($price->sale_net_amount ?? 0),
            'retail_price' => (float) ($price->sale_retail_price ?? 0),
        ]);
    }
    // SaleController.php
    public function filterCustomers(Request $request)
    {
        // Default type is 'customer', if not provided
        $type = $request->query('type', 'customer');
        $isAdmin = \Illuminate\Support\Facades\Auth::user()->roles->pluck('name')->contains('Admin') || \Illuminate\Support\Facades\Auth::id() == 1;
        $userId = \Illuminate\Support\Facades\Auth::id();
        $userGroupIds = \Illuminate\Support\Facades\Auth::user()->userGroups()->pluck('user_groups.id')->toArray();

        // Check if the type is 'vendor'
        if ($type === 'vendor') {
            $query = Vendor::query();
            if (!$isAdmin) {
                $query->where(function($q) use ($userId, $userGroupIds) {
                    if (empty($userGroupIds)) {
                        $q->where('created_by', $userId);
                    } else {
                        $q->where(function($sub) use ($userGroupIds) {
                            foreach ($userGroupIds as $groupId) {
                                $sub->orWhereJsonContains('user_group_ids', (string)$groupId);
                                $sub->orWhereJsonContains('user_group_ids', (int)$groupId);
                            }
                        });
                    }
                });
            }
            $rows = $query->orderBy('name')->get(['id', 'name', 'phone']); 

            return response()->json(
                $rows->map(
                    fn($v) => [
                        'id' => $v->id,
                        'text' => $v->name . ($v->phone ? ' (' . $v->phone . ')' : ''), 
                        'customer_id' => $v->id,
                    ],
                ),
            );
        }

        // Check if the type is 'walking'
        if ($type === 'walking') {
            $query = Customer::where('customer_type', 'Walking Customer');
            if (!$isAdmin) {
                $query->where(function($q) use ($userId, $userGroupIds) {
                    if (empty($userGroupIds)) {
                        $q->where('created_by', $userId);
                    } else {
                        $q->where(function($sub) use ($userGroupIds) {
                            foreach ($userGroupIds as $groupId) {
                                $sub->orWhereJsonContains('user_group_ids', (string)$groupId);
                                $sub->orWhereJsonContains('user_group_ids', (int)$groupId);
                            }
                        });
                    }
                });
            }
            $rows = $query->orderBy('customer_name')->get(['id', 'customer_id', 'customer_name']);
            return response()->json(
                $rows->map(
                    fn($c) => [
                        'id' => $c->id,
                        'text' => $c->customer_name,
                        'customer_id' => $c->customer_id,
                    ],
                ),
            );
        }

        // Default: Fetch customers for 'customer' type
        $query = Customer::where('customer_type', 'Main Customer');
        if (!$isAdmin) {
            $query->where(function($q) use ($userId, $userGroupIds) {
                if (empty($userGroupIds)) {
                    $q->where('created_by', $userId);
                } else {
                    $q->where(function($sub) use ($userGroupIds) {
                        foreach ($userGroupIds as $groupId) {
                            $sub->orWhereJsonContains('user_group_ids', (string)$groupId);
                            $sub->orWhereJsonContains('user_group_ids', (int)$groupId);
                        }
                    });
                }
            });
        }
        $rows = $query->orderBy('customer_name')->get(['id', 'customer_id', 'customer_name']);

        return response()->json(
            $rows->map(
                fn($c) => [
                    'id' => $c->id,
                    'text' => $c->customer_name,
                    'customer_id' => $c->customer_id,
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

            // Fetch dynamic balance matching the General Ledger
            $gl = new \App\Http\Controllers\GeneralLedgerController();
            $previous_balance = $gl->calculateOpeningBalance('vendor', $id, date('Y-m-d', strtotime('+1 day')));

            // Revert sign for Vendors (GL displays Credits as positive for Vendor)
            // Wait, GL Preview displays: $runningBalance >= 0 ? 'DR.' : 'CR.'
            // So if $previous_balance is -420485, it's CR. 420485.
            // The Sale Add screen just displays the numeric value, so we leave it as is.

            return response()->json([
                'address' => $v->address,
                'mobile' => $v->phone, // assuming 'phone' field for vendors
                'remarks' => '', // No remarks for vendors
                'previous_balance' => $previous_balance, 
            ]);
        }

        // Default: Fetch Customer data (including walking)
        $c = Customer::find($id);
        if (!$c) {
            return response()->json(['error' => 'Customer not found'], 404);
        }

        // Fetch dynamic balance matching the General Ledger
        $gl = new \App\Http\Controllers\GeneralLedgerController();
        $previous_balance = $gl->calculateOpeningBalance('customer', $id, date('Y-m-d', strtotime('+1 day')));

        return response()->json([
            'filer_type' => $c->filer_type,
            'customer_type' => $c->customer_type,
            'address' => $c->address,
            'mobile' => $c->mobile,
            'remarks' => $c->remarks ?? '',
            'previous_balance' => $previous_balance,
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
            $rows = \App\Models\Vendor::orderBy('name')->get();
            return response()->json(
                $rows->map(fn($v) => [
                    'id' => $v->id,
                    'text' => $v->id . ' - ' . $v->name
                ])->values()
            );
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

    private function performPosting(Sale $sale)
    {
        $partyId = $sale->customer_id;
        $pType = strtolower($sale->partyType ?? 'customer');

        // sub_total2 is Total Sale Amount after row discounts but before order discount
        $saleAmount = (float)($sale->sub_total2 ?? 0);
        $orderDiscount = (float)($sale->discount_amount ?? 0);
        $receiptAmount = (float)($sale->receipt1 + $sale->receipt2);

        $date = $sale->entry_date ?? now()->format('Y-m-d');
        $invoiceNo = $sale->invoice_no;

        // 1. Identify Ledger Model
        $ledgerModel = null;
        $partyCol = '';
        if ($pType === 'vendor') {
            $ledgerModel = \App\Models\VendorLedger::class;
            $partyCol = 'vendor_id';
        } else {
            $ledgerModel = \App\Models\CustomerLedger::class;
            $partyCol = 'customer_id';
        }

        // 2. CONSOLIDATED UPDATE (MODIFIES LATEST ROW)
        // The user requires updating the existing ledger row instead of creating a new one.
        $ledger = $ledgerModel::where($partyCol, $partyId)->latest('id')->first();
        
        $totalDebit = $saleAmount;
        $totalCredit = $orderDiscount + $receiptAmount;
        $impact = $totalDebit - $totalCredit;

        if ($ledger) {
            // Update the existing latest row
            $ledger->previous_balance = $ledger->closing_balance;
            $ledger->date = $date;
            $ledger->description = 'Sale: ' . $invoiceNo . ($orderDiscount > 0 ? ' (Incl. Discount)' : '') . ($receiptAmount > 0 ? ' (Incl. Receipt)' : '');
            $ledger->debit = $totalDebit;
            $ledger->credit = $totalCredit;
            $ledger->closing_balance += $impact;
            $ledger->save();
        } else {
            // Create a new one only if no ledger exists at all for this customer
            $ledgerModel::create([
                $partyCol => $partyId,
                'admin_or_user_id' => auth()->id(),
                'date' => $date,
                'description' => 'Sale: ' . $invoiceNo,
                'opening_balance' => 0,
                'previous_balance' => 0,
                'debit' => $totalDebit,
                'credit' => $totalCredit,
                'closing_balance' => $totalDebit - $totalCredit,
            ]);
        }

        // 3. Create Vouchers for documentation
        if ($orderDiscount > 0 && $sale->discount_account_id) {
            $discountAccount = \App\Models\Account::find($sale->discount_account_id);
            if ($discountAccount) {
                // Sale Discount is an expense. Debit increases expense.
                $discountAccount->opening_balance = ($discountAccount->opening_balance ?? 0) + $orderDiscount;
                $discountAccount->save();

                \App\Models\JournalVoucher::create([
                    'jvid' => 'SJ-DISC-' . $invoiceNo,
                    'entry_date' => $date,
                    'status' => 'posted',
                    'total_debit' => $orderDiscount,
                    'total_credit' => $orderDiscount,
                    'party_type' => json_encode([$pType, (string)$discountAccount->head_id]),
                    'party_id' => json_encode([$partyId, $discountAccount->id]),
                    'debit' => json_encode([0, $orderDiscount]), // Debit Discount Account
                    'credit' => json_encode([$orderDiscount, 0]), // Credit Customer
                    'remarks' => 'Discount on Sale: ' . $invoiceNo . ' ; ' . ($discountAccount->head->name ?? 'Head') . ' ; ' . ($discountAccount->title ?? 'Subhead'),
                ]);
            }
        } elseif ($orderDiscount > 0) {
            Voucher::create([
                'voucher_type'  => 'Discount voucher',
                'date'          => $date,
                'sales_officer' => auth()->user()->name,
                'type'          => 'Credit',
                'person'        => $partyId,
                'sub_head'      => 'Sale Discount',
                'narration'     => 'Discount on Sale: ' . $invoiceNo,
                'amount'        => $orderDiscount,
                'status'        => 'posted'
            ]);
        }

        if ($receiptAmount > 0) {
            $receiptHeads = json_decode($sale->receipt_heads, true);
            $receiptAccounts = json_decode($sale->receipt_accounts, true);
            $receiptNarrations = json_decode($sale->receipt_narrations, true);
            $receiptAmounts = json_decode($sale->receipt_amounts_json, true);

            ReceiptsVoucher::create([
                'rvid' => ReceiptsVoucher::generateInvoiceNo(),
                'receipt_date' => $date,
                'entry_date' => $date,
                'entry_time' => $sale->entry_time ?? now()->format('H:i'),
                'type' => $pType,
                'party_id' => $partyId,
                'tel' => $sale->tel,
                'remarks' => 'Auto-generated from Sale: ' . $invoiceNo,
                'narration_id' => json_encode($receiptNarrations),
                'row_account_head' => json_encode($receiptHeads),
                'row_account_id' => json_encode($receiptAccounts),
                'amount' => json_encode($receiptAmounts),
                'total_amount' => $receiptAmount,
                'status' => 'posted',
            ]);

            // Update Account Balances
            if (is_array($receiptAccounts)) {
                foreach ($receiptAccounts as $idx => $accId) {
                    $accAmt = (float)($receiptAmounts[$idx] ?? 0);
                    if ($accAmt > 0) {
                        $acc = Account::find($accId);
                        if ($acc) {
                            $acc->opening_balance = ($acc->opening_balance ?? 0) + $accAmt;
                            $acc->save();
                        }
                    }
                }
            }
        }
    }
}
