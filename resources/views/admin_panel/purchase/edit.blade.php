@extends('admin_panel.layout.app')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    /* Select2 customizations to match theme */
    .select2-container .select2-selection--single {
        height: 31px !important;
        border: 1px solid #ced4da;
    }
    .select2-container .select2-selection--single .select2-selection__rendered {
        line-height: 31px !important;
        padding-left: 8px;
    }
    .select2-container .select2-selection--single .select2-selection__arrow {
        height: 31px !important;
    }
</style>
@section('content')
<div class="main-content bg-white">
    <div class="main-content-inner">
        <div class="row">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"
                rel="stylesheet">

            <style>
                .table-scroll tbody {
                    display: block;
                    max-height: calc(60px * 5);
                    overflow-y: auto;
                }

                .table-scroll thead,
                .table-scroll tbody tr {
                    display: table;
                    width: 100%;
                    table-layout: fixed;
                }

                .table-scroll thead {
                    width: calc(100% - 1em);
                }

                .table-scroll .icon-col {
                    width: 51px;
                    min-width: 51px;
                    max-width: 40px;
                }

                .table-scroll {
                    max-height: none !important;
                    overflow-y: visible !important;
                }

                .disabled-row input {
                    background-color: #f8f9fa;
                    pointer-events: none;
                }
            </style>

            <body>
                <div class="body-wrapper">
                    <div class="bodywrapper__inner">
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-nowrap overflow-auto">
                            <div class="flex-grow-1">
                                <h6 class="page-title ml-4">Edit Purchase</h6>
                                <span class="badge bg-primary ms-3" style="font-size:14px;">Invoice: {{ $purchase->invoice_no }}</span>
                            </div>


                            <!-- Right side buttons -->
                            <div class="d-flex mt-2 mb-2 align-items-center">
                                <!-- Add Purchase List button -->
                                <a href="{{ route('Purchase.home') }}" class="btn btn-sm btn-primary" title="View Purchase List">
                                    <i class="bi bi-list-check me-1"></i> Purchase List
                                </a>
                            </div>
                        </div>

                        <div class="row gy-3 ">
                            <div class="col-lg-12 col-md-12 mb-30 m-auto">
                                <div class="card">
                                    <div class="card-body  ml-2">

                                        @if (session('success'))
                                        <div class="alert alert-success alert-dismissible fade show"
                                            role="alert">
                                            <strong>Success!</strong> {{ session('success') }}
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"
                                                aria-label="Close"></button>
                                        </div>
                                        @endif

                                        <form id="purchaseForm" action="{{ route('purchase.update', $purchase->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <table class="table table-bordered table-sm text-center align-middle">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Current Date</th>
                                                        <th>DC Date</th>
                                                        <th>Type</th>
                                                        <th>Vendor</th>
                                                        <th cla>DC #</th>
                                                        <th>Warehouse</th>
                                                        <th>Bilty No</th>
                                                        <th>Remarks</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>
                                                            <input name="current_date" value="{{ old('current_date', $purchase->current_date ? \Carbon\Carbon::parse($purchase->current_date)->format('Y-m-d') : date('Y-m-d')) }}"
                                                                type="date" class="form-control form-control-sm" required>
                                                            @error('current_date')
                                                                <div class="alert alert-danger p-1 mt-1" style="font-size: 12px;">{{ $message }}</div>
                                                            @enderror
                                                        </td>
                                                        <td><input name="dc_date" value="{{ old('dc_date', $purchase->dc_date ? \Carbon\Carbon::parse($purchase->dc_date)->format('Y-m-d') : date('Y-m-d')) }}"
                                                                type="date" class="form-control form-control-sm">
                                                            @error('dc_date')
                                                                <div class="alert alert-danger p-1 mt-1" style="font-size: 12px;">{{ $message }}</div>
                                                            @enderror
                                                        </td>
                                                        <td>
                                                            <select name="vendor_type" class="form-control form-control-sm" id="vendor_type_select">
                                                                <option value="" {{ old('vendor_type') ? '' : 'selected' }} disabled>Select</option>

                                                                <option value="vendor" {{ old('vendor_type') == 'vendor' ? 'selected' : '' }}>Vendor</option>
                                                                <option value="customer" {{ old('vendor_type') == 'customer' ? 'selected' : '' }}>Customer</option>
                                                                <option value="walkin" {{ old('vendor_type') == 'walkin' ? 'selected' : '' }}>Walkin Customer</option>
                                                            </select>
                                                            @error('vendor_type')
                                                                <div class="alert alert-danger p-1 mt-1" style="font-size: 12px;">{{ $message }}</div>
                                                            @enderror
                                                        </td>

                                                        <td>
                                                            <select name="vendor_id" class="form-control form-control-sm" style="width:105px;">
                                                                <option value="" disabled {{ old('vendor_id') ? '' : 'selected' }}>Select</option>
                                                            </select>
                                                            @error('vendor_id')
                                                                <div class="alert alert-danger p-1 mt-1" style="font-size: 12px;">{{ $message }}</div>
                                                            @enderror
                                                        </td>
                                                        <td><input name="dc" type="text" value="{{ old('dc', $purchase->dc) }}"
                                                                class="form-control form-control-sm" style="width:90px;">
                                                            @error('dc')
                                                                <div class="alert alert-danger p-1 mt-1" style="font-size: 12px;">{{ $message }}</div>
                                                            @enderror
                                                        </td>
                                                        <td>
                                                            <select name="warehouse_id" class="form-control form-control-sm">
                                                                <option value="" disabled {{ old('warehouse_id') ? '' : 'selected' }}>Select</option>
                                                                @foreach ($Warehouse as $ware)
                                                                <option value="{{ $ware->id }}" {{ (string)old('warehouse_id') === (string)$ware->id ? 'selected' : '' }}>
                                                                    {{ $ware->warehouse_name }}
                                                                </option>
                                                                @endforeach
                                                            </select>
                                                            @error('warehouse_id')
                                                                <div class="alert alert-danger p-1 mt-1" style="font-size: 12px;">{{ $message }}</div>
                                                            @enderror
                                                        </td>
                                                        <td>
                                                            <input name="bilty_no" type="text" value="{{ old('bilty_no', $purchase->bilty_no) }}"
                                                                class="form-control form-control-sm" style="width:90px;">
                                                            @error('bilty_no')
                                                                <div class="alert alert-danger p-1 mt-1" style="font-size: 12px;">{{ $message }}</div>
                                                            @enderror
                                                        </td>
                                                        <td><input name="remarks" type="text" value="{{ old('remarks', $purchase->note) }}" class="form-control form-control-sm">
                                                            @error('remarks')
                                                                <div class="alert alert-danger p-1 mt-1" style="font-size: 12px;">{{ $message }}</div>
                                                            @enderror
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <table
                                                class="table table-bordered table-sm text-center align-middle mt-2">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Item ID</th>
                                                        <th>Product</th>
                                                        <th>Brand</th>
                                                        <th>Price</th>
                                                        <th>Retail Price</th> <!-- ✅ New column -->
                                                        <th>Disc</th>
                                                        <th>Qty</th>
                                                        <th>Amount</th>
                                                        <th>Total</th>
                                                        <th>X</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="purchaseItems">
                                                    <tr>
                                                        <td style="width: 100px;">
                                                            <input type="text" class="form-control form-control-sm item-id-input" placeholder="ID">
                                                        </td>
                                                        <td style="width: 250px;">
                                                            <select name="product_id[]" class="form-control form-control-sm product-select" style="width: 100%;">
                                                                <option value="" disabled selected>Select Product</option>
                                                            </select>
                                                            <input type="hidden" name="product_name[]" class="product_name_hidden">
                                                        </td>

                                                        <td class="uom border">
                                                            <input type="text" name="brand[]" class="form-control form-control-sm" readonly>
                                                        </td>
                                                        <td><input type="number" step="0.01" name="price[]" class="form-control form-control-sm price"></td>
                                                        <td>
                                                            <input type="text" name="retail_price_show[]" class="form-control form-control-sm retail_price_show" readonly>
                                                        </td>
                                                        <td>
                                                            <div class="input-group">
                                                                <input type="number" step="0.01" min="0" name="item_disc[]" class="form-control form-control-sm item_disc" placeholder="%">
                                                                <input type="text" name="item_disc_amount[]" class="form-control form-control-sm disc_amount" readonly placeholder="Disc Amt">
                                                            </div>
                                                            <input type="hidden" name="purchase_retail_price[]" class="purchase_retail_price">
                                                            <input type="hidden" name="purchase_net_amount[]" class="purchase_net_amount">
                                                        </td>
                                                        <td>
                                                            <input type="number" name="qty[]" class="form-control form-control-sm quantity" value="" min="1">
                                                        </td>
                                                        <td>
                                                            <input type="text" name="amount[]" class="form-control form-control-sm row-amount" readonly> <!-- ✅ New -->
                                                        </td>
                                                        <td>
                                                            <input type="text" name="total[]" class="form-control form-control-sm row-total" readonly>
                                                        </td>
                                                        <td><button type="button" class="btn btn-sm btn-danger remove-row">X</button></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <div class="row mt-2">
                                                <!-- Accounts Allocation -->
                                                <div class="col-md-6">
                                                    <div class="card h-100">

                                                        <div class="card-header p-2 bg-light fw-bold d-flex justify-content-between align-items-center">
                                                            <span>Accounts Allocation</span>
                                                            <button type="button" id="addAccountRow" class="btn btn-sm btn-primary">
                                                                + Add
                                                            </button>
                                                        </div>

                                                        <div class="card-body p-2">
                                                            <table class="table table-bordered table-sm text-center align-middle" id="accountsTable">
                                                                <thead class="table-light">
                                                                    <tr>
                                                                        <th>Account Head</th>
                                                                        <th>Account</th>
                                                                        <th>Amount</th>
                                                                        <th>X</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <tr>
                                                                        <td>
                                                                            <select name="account_head_id[]" class="form-control form-control-sm accountHead">
                                                                                <option value="" disabled selected>Select Head</option>
                                                                                @foreach ($AccountHeads as $head)
                                                                                <option value="{{ $head->id }}">{{ $head->name }}</option>
                                                                                @endforeach
                                                                            </select>
                                                                        </td>
                                                                        <td>
                                                                            <select name="account_id[]" class="form-control form-control-sm accountSub" disabled>
                                                                                <option value="" disabled selected>Select Account</option>
                                                                            </select>
                                                                        </td>
                                                                        <td>
                                                                            <input type="number" step="0.01" name="account_amount[]" class="form-control form-control-sm accountAmount" value="0" disabled>
                                                                        </td>
                                                                        <td>
                                                                            <button type="button" class="btn btn-sm btn-danger removeAccountRow">X</button>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>

                                                            <div class="mt-2 text-end">
                                                                <label class="fw-bold">Accounts Total:</label>
                                                                <input type="text" id="accountsTotal" class="form-control form-control-sm d-inline-block w-auto fw-bold" value="0" readonly>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Totals -->
                                                <div class="col-md-6">
                                                    <div class="card h-100">
                                                        <div class="card-header p-2 bg-light fw-bold">Totals</div>
                                                        <div class="card-body p-2">
                                                            <table class="table table-bordered table-sm text-center align-middle mb-0">
                                                                <tr>
                                                                    <th>Subtotal</th>
                                                                    <td><input type="text" id="subtotal" name="subtotal" class="form-control form-control-sm" value="{{ old('subtotal', $purchase->subtotal ?? 0) }}" readonly></td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Discount</th>
                                                                    <td><input type="number" step="0.01" id="overallDiscount" name="discount" class="form-control form-control-sm" value="{{ old('discount', $purchase->discount ?? 0) }}" readonly>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <th>WHT</th>
                                                                    <td>
                                                                        <div class="input-group">
                                                                            <input type="number" step="0.01" id="whtValue" name="wht" class="form-control form-control-sm" value="{{ old('wht', $purchase->wht ?? 0) }}">
                                                                            <select id="whtType" class="form-select form-select-sm" style="max-width:90px;">
                                                                                <option value="percent" selected>%</option>
                                                                                <option value="amount">PKR</option>
                                                                            </select>
                                                                        </div>
                                                                    </td>
                                                                </tr>

                                                                <tr>
                                                                    <th>WHT Amount</th>
                                                                    <td>
                                                                        <input type="text" id="whtAmount" name="wht_amount" class="form-control form-control-sm" value="0" readonly>
                                                                    </td>
                                                                </tr>


                                                                <tr>
                                                                    <th>Net</th>
                                                                    <td><input type="text" id="netAmount" name="net_amount" class="form-control form-control-sm fw-bold" value="{{ old('net_amount', $purchase->net_amount ?? 0) }}" readonly></td>
                                                                </tr>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-primary btn-sm w-100">Update
                                                Purchase</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div><!-- bodywrapper__inner end -->
                </div><!-- body-wrapper end -->
        </div>
    </div>
</div>

@endsection

@section('scripts')



@if(session('error'))
<script>
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: @json(session('error')),
        confirmButtonColor: '#d33',
    });
</script>
@endif

@if (session('success'))
<script>
    Swal.fire({
        icon: 'success',
        title: 'Success',
        text: @json(session('success')),
        confirmButtonColor: '#3085d6',
    });
</script>
@endif






<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Global helper for initializing Select2 on a row
        window.initProductSelect = function($row) {
            const $select = $row.find('.product-select');
            
            $select.select2({
                placeholder: "Select Product",
                allowClear: true,
                width: '100%',
                ajax: {
                    url: "{{ route('search-products') }}",
                    dataType: 'json',
                    delay: 100, 
                    data: function (params) {
                        return {
                            q: params.term // search term
                        };
                    },
                    processResults: function (data, params) {
                        const term = (params.term || '').toLowerCase();
                        const results = data.map(function(item) {
                            return {
                                id: item.id,
                                text: item.name,
                                // Pass custom data
                                brand: item.brand,
                                price_net: item.purchase_net_amount,
                                price_retail: item.purchase_retail_price
                            };
                        });

                        // Prioritize exact matches (ID or Name) at the top of the list
                        results.sort((a, b) => {
                            if (String(a.id) === term || a.text.toLowerCase() === term) return -1;
                            if (String(b.id) === term || b.text.toLowerCase() === term) return 1;
                            return 0;
                        });

                        return { results };
                    },
                    cache: true
                },
                minimumInputLength: 1
            });

            // Tab/Enter on Item ID -> Auto-Append Row if last
            $row.find('.item-id-input').on('keydown', function(e) {
                if (e.key === 'Enter' || e.key === 'Tab') {
                    const $currentRow = $(this).closest('tr');
                    // Always append a new row at the bottom if we are on the last row
                    if ($currentRow.is(':last-child')) {
                        // focus = false so the focus doesn't jump to the new row yet
                        window.appendBlankRow(true, false);
                    }

                    // If empty ID, open the product selector
                    if (!$(this).val()) {
                        e.preventDefault();
                        $select.select2('open');
                    }
                }
            });

            // Sync ID input -> Select2
            $row.find('.item-id-input').on('change', function() {
                const $input = $(this);
                const id = $input.val().trim();
                if (!id) {
                    $select.val(null).trigger('change');
                    return;
                }
                
                $input.addClass('loading-indicator');
                $.getJSON("{{ route('search-products') }}", { 
                    q: id,
                    warehouse_id: $('select[name="warehouse_id"]').val() 
                }, function(data) {
                    $input.removeClass('loading-indicator');
                    
                    // Precise matching prioritize: Exact ID -> Exact Name (Case Insensitive) -> First Result if only 1
                    let product = data.find(p => String(p.id) === String(id)) 
                               || data.find(p => p.name.toLowerCase() === id.toLowerCase());
                    
                    if (!product && data.length === 1) {
                         product = data[0];
                    }

                    if (product) {
                        const newOption = new Option(product.name, product.id, true, true);
                        $select.empty().append(newOption).trigger('change');
                        
                        // Populate and trigger row calcs
                        $select.trigger({
                            type: 'select2:select',
                            params: {
                                data: {
                                    id: product.id,
                                    text: product.name,
                                    brand: product.brand,
                                    price_net: product.purchase_net_amount,
                                    price_retail: product.purchase_retail_price
                                }
                            }
                        });
                    } else {
                        $select.val(null).trigger('change');
                        showToast('❌ Product ID not found!', 'error');
                        $input.val('');
                    }
                }).fail(function() {
                    $input.removeClass('loading-indicator');
                    showToast('❌ Server error!', 'error');
                });
            });

            // Handle selection
            $select.on('select2:select', function (e) {
                const data = e.params.data;
                const $currentRow = $(this).closest('tr');

                // Update ID input
                $currentRow.find('.item-id-input').val(data.id);

                // Populate fields
                $currentRow.find('.product_name_hidden').val(data.text);
                $currentRow.find('input[name="brand[]"]').val(data.brand || '');
                
                const net = parseFloat(data.price_net || 0).toFixed(2);
                let retail = parseFloat(data.price_retail || 0);

                // Safe check for NaN
                if(isNaN(retail)) { retail = 0; }
                const retailStr = retail.toFixed(2);
                
                // Set prices
                $currentRow.find('.price').val(net);
                $currentRow.find('.retail_price_show').val(retailStr);
                $currentRow.find('.purchase_net_amount').val(net);
                $currentRow.find('.purchase_retail_price').val(retailStr); // store hidden
                
                // Default Quantity to 1
                $currentRow.find('.quantity').val(1);
                $currentRow.find('.item_disc').val(0);
                $currentRow.find('.disc_amount').val('0.00');

                // Trigger calculation
                if(typeof window.recalcRow === 'function') {
                    window.recalcRow($currentRow);
                }
                if(typeof window.recalcSummary === 'function') {
                    window.recalcSummary();
                }

                // Focus next field (Price)
                setTimeout(() => {
                    $currentRow.find('.price').focus().select();
                }, 100);
            });
            
            $select.on('select2:clear', function (e) {
                const $currentRow = $(this).closest('tr');
                $currentRow.find('input').not(this).val('');
                $currentRow.find('.quantity').val(1);
                if(typeof window.recalcRow === 'function') window.recalcRow($currentRow);
                if(typeof window.recalcSummary === 'function') window.recalcSummary();
            });
        };
    });
</script>

{{-- Item Row Autocomplete + Add/Remove --}}
<!-- Make sure jQuery and Bootstrap Typeahead are included -->

<script>
    (function() {
        @php
            // Prepare default data from $purchase relationship for edit mode
            $items = $purchase->items;
            
            $pIds = $items->pluck('product_id')->toArray();
            $pNames = $items->map(fn($i) => $i->product->name ?? '')->toArray();
            $brands = $items->map(fn($i) => $i->product->brand->name ?? '')->toArray();
            $prices = $items->pluck('price')->toArray();
            $qtys   = $items->pluck('qty')->toArray();
            // item_discount col stores the % input
            $discs  = $items->pluck('item_discount')->toArray(); 
            
            // Calculate discount amount: (price * qty) - line_total
            // or if line_total is consistent, just use that difference.
            // Ideally re-calc from %: (price/retail * %/100) * qty. 
            // But let's rely on stored line_total for consistency if possible, 
            // or just let JS recalc it. 
            // For restoration, we'll pass 0 or let JS recalc. 
            // Actually, we can pass (price*qty - line_total).
            $discAmts = $items->map(fn($i) => round(($i->price * $i->qty) - $i->line_total, 2))->toArray();

            // Product latest prices for retail/cost reference
            $retails = $items->map(fn($i) => optional(optional($i->product)->latestPrice)->purchase_retail_price ?? 0)->toArray();
            $nets    = $items->map(fn($i) => optional(optional($i->product)->latestPrice)->purchase_net_amount ?? 0)->toArray();
            $totals  = $items->pluck('line_total')->toArray();

            // Accounts
            $allocs = $purchase->accountAllocations;
            $accHeads = $allocs->pluck('account_head_id')->toArray();
            $accIds   = $allocs->pluck('account_id')->toArray();
            $accAmts  = $allocs->pluck('amount')->toArray();
        @endphp

        // restore old arrays from server (Blade -> JS)
        // If old() is present (validation fail), use it. Otherwise use $purchase data.
        const oldProducts = @json(old('product_id', $pIds));
        const oldPrices = @json(old('price', $prices));
        const oldQtys = @json(old('qty', $qtys));
        const oldItemDiscs = @json(old('item_disc', $discs));
        const oldDiscAmounts = @json(old('item_disc_amount', $discAmts));
        
        // For retail/net, we favor the request input if validation fails, 
        // else we grab from current product master data (or you could store these in purchase_items if you migrated).
        const oldRetailPrices = @json(old('purchase_retail_price', $retails));
        const oldPurchaseNet = @json(old('purchase_net_amount', $nets));
        
        const oldRowAmounts = @json(old('total', $totals));
        const oldProductNames = @json(old('product_name', $pNames));
        const oldBrands = @json(old('brand', $brands));

        // account allocations
        const oldAccHeads = @json(old('account_head_id', $accHeads));
        const oldAccIds = @json(old('account_id', $accIds));
        const oldAccAmounts = @json(old('account_amount', $accAmts));

        const errors = @json($errors->toArray());
        const accountHeadsList = @json($AccountHeads);

        // helper: create a product row HTML (same structure as appendBlankRow)
        window.makeRowHtml = function(data, index = null) {
            // Error handling helper
            const getError = (field) => {
                if (index !== null && errors[field + '.' + index]) {
                     return `<div class="alert alert-danger p-1 mt-1" style="font-size: 12px; margin-bottom:0;">${errors[field + '.' + index][0]}</div>`;
                }
                return '';
            };

            // Pre-select option if data exists
            let optionHtml = '<option value="" disabled selected>Select Product</option>';
            if(data.product_id) {
                const pName = data.product_name || 'Product ' + data.product_id;
                optionHtml = `<option value="${data.product_id}" selected>${pName}</option>`;
            }

            // data: { product_id, product_name, brand, price, retail_show, item_disc, disc_amount, qty, row_amount, row_total, purchase_retail, purchase_net }
            return `
      <tr>
        <td style="width: 100px;">
          <input type="text" class="form-control form-control-sm item-id-input" placeholder="ID" value="${data.product_id || ''}">
        </td>
        <td style="width: 250px;">
          <select name="product_id[]" class="form-control form-control-sm product-select" style="width: 100%;">
            ${optionHtml}
          </select>
          <input type="hidden" name="product_name[]" class="product_name_hidden" value="${(data.product_name || '')}">
          ${getError('product_id')}
        </td>
        <td class="uom border">
          <input type="text" name="brand[]" class="form-control form-control-sm" readonly value="${data.brand || ''}">
        </td>
        <td>
          <input type="number" step="0.01" name="price[]" class="form-control form-control-sm price" value="${data.price || ''}">
          ${getError('price')}
        </td>
        <td>
          <input type="text" name="retail_price_show[]" class="form-control form-control-sm retail_price_show" readonly value="${data.retail_show || ''}">
        </td>
        <td>
          <div class="input-group">
            <input type="number" step="0.01" min="0" name="item_disc[]" class="form-control form-control-sm item_disc" placeholder="%" value="${data.item_disc || ''}">
            <input type="text" name="item_disc_amount[]" class="form-control form-control-sm disc_amount" readonly placeholder="Disc Amt" value="${data.disc_amount || ''}">
          </div>
          ${getError('item_disc')}
          <input type="hidden" name="purchase_retail_price[]" class="purchase_retail_price" value="${data.purchase_retail || ''}">
          <input type="hidden" name="purchase_net_amount[]" class="purchase_net_amount" value="${data.purchase_net || ''}">
        </td>
        <td>
          <input type="number" name="qty[]" class="form-control form-control-sm quantity" value="${data.qty}" min="1">
          ${getError('qty')}
        </td>
        <td>
          <input type="text" name="amount[]" class="form-control form-control-sm row-amount" readonly value="${data.row_amount || ''}">
        </td>
        <td>
          <input type="text" name="total[]" class="form-control form-control-sm row-total" readonly value="${data.row_total || ''}">
        </td>
        <td>
          <button type="button" class="btn btn-sm btn-danger remove-row">X</button>
        </td>
      </tr>
    `;
        }

        function restoreProducts() {
            // if there is no old product data, do nothing (keep initial blank row)
            if (!oldProducts || oldProducts.length === 0) return;

            // clear current rows
            $('#purchaseItems').empty();

            const max = Math.max(oldProducts.length, oldPrices.length, oldQtys.length);

            for (let i = 0; i < max; i++) {
                
                let rShow = oldRetailPrices[i];
                // Force to number and format
                let rShowAttributes = parseFloat(rShow);
                if (isNaN(rShowAttributes)) {
                    rShowAttributes = 0;
                }
                rShow = rShowAttributes.toFixed(2);

                const rowData = {
                    product_id: oldProducts[i] ?? '',
                    product_name: (oldProductNames[i] ?? ''),
                    brand: (oldBrands[i] ?? ''),
                    price: oldPrices[i] ?? '',
                    retail_show: rShow,
                    item_disc: oldItemDiscs[i] ?? '',
                    disc_amount: oldDiscAmounts[i] ?? '',
                    purchase_retail: rShow,
                    purchase_net: oldPurchaseNet[i] ?? '',
                    qty: oldQtys[i] ?? 1,
                    row_amount: '', // will be recalculated
                    row_total: oldRowAmounts[i] ?? ''
                };

                const $newRow = $(window.makeRowHtml(rowData, i));
                $('#purchaseItems').append($newRow);
                if(window.initProductSelect) window.initProductSelect($newRow);
            }

            // re-bind any plugin stuff? (your handlers are delegated so okay)
            // Recalculate rows and summary
            $('#purchaseItems tr').each(function() {
                try {
                    if (typeof recalcRow === 'function') recalcRow($(this));
                } catch (err) {
                    console && console.error && console.error('recalcRow on restore error', err);
                }
            });
            if (typeof recalcSummary === 'function') recalcSummary();
        }

        function restoreAccounts() {
            if (!oldAccHeads || oldAccHeads.length === 0) return;

            // clear table except header
            $('#accountsTable tbody').empty();

            const max = Math.max(oldAccHeads.length, oldAccIds.length, oldAccAmounts.length);
            for (let i = 0; i < max; i++) {
                const head = oldAccHeads[i] ?? '';
                const acc = oldAccIds[i] ?? '';
                const amt = oldAccAmounts[i] ?? '';

                const getError = (field) => {
                    if (errors[field + '.' + i]) {
                        return `<div class="alert alert-danger p-1 mt-1" style="font-size: 12px; margin-bottom:0;">${errors[field + '.' + i][0]}</div>`;
                    }
                    return '';
                };

                let optionsHtml = '<option value="" disabled>Select Head</option>';
                if(accountHeadsList && accountHeadsList.length) {
                    accountHeadsList.forEach(h => {
                        const selected = (String(head) === String(h.id)) ? 'selected' : '';
                        optionsHtml += `<option value="${h.id}" ${selected}>${h.name}</option>`;
                    });
                }

                const row = `
        <tr>
          <td>
            <select name="account_head_id[]" class="form-control form-control-sm accountHead">
              ${optionsHtml}
            </select>
            ${getError('account_head_id')}
          </td>
          <td>
            <select name="account_id[]" class="form-control form-control-sm accountSub">
               <!-- We might not have options loaded yet if we rely solely on trigger change. 
                    However, we can force-load them manually here or allow the trigger to handle it.
                    Since we set value="${acc}", if options arrive later, we must re-select.
               -->
              <option value="${acc}" selected>${acc}</option>
            </select>
            ${getError('account_id')}
          </td>
          <td>
            <input type="number" step="0.01" name="account_amount[]" class="form-control form-control-sm accountAmount" value="${amt || 0}">
             ${getError('account_amount')}
          </td>
          <td>
            <button type="button" class="btn btn-sm btn-danger removeAccountRow">X</button>
          </td>
        </tr>
      `;
                $('#accountsTable tbody').append(row);
                
                // Trigger change to load accounts if head is selected
                if(head) {
                     const $lastRow = $('#accountsTable tbody tr:last');
                     // Manually call the load logic effectively to ensure value is preserved
                     const headId = head;
                     const $accountSelect = $lastRow.find('.accountSub');
                     const $amountInput = $lastRow.find('.accountAmount');
                     
                     // Enable inputs
                     $accountSelect.prop('disabled', false).prop('required', true);
                     $amountInput.prop('disabled', false).prop('required', true).attr('min', '0.01');

                     $.ajax({
                        url: "/get-accounts-by-head/" + headId,
                        type: "GET",
                        dataType: 'json',
                        success: function(res) {
                            // keep logic similar to change handler
                            const currentAcc = acc; // The value we want to restore
                            let html = '<option value="" disabled>Select Account</option>';
                            if (Array.isArray(res) && res.length) {
                                res.forEach(a => {
                                    const selected = String(a.id) === String(currentAcc) ? ' selected' : '';
                                    html += `<option value="${a.id}"${selected}>${a.title}</option>`;
                                });
                            }
                            $accountSelect.html(html);
                            
                            // If currentAcc was set but not in list (shouldn't happen if valid), handle
                            if (!currentAcc) {
                                $accountSelect.prepend('<option value="" disabled selected>Select Account</option>');
                                $accountSelect.val('');
                            }
                            if (typeof recalcAccountsTotal === 'function') recalcAccountsTotal();
                        },
                        error: function() {
                             // retain what we have or show error, but better to keep enabled if possible
                             if (typeof recalcAccountsTotal === 'function') recalcAccountsTotal();
                        }
                    });
                }
            }

            // trigger recalc of allocations
            if (typeof recalcAccountsTotal === 'function') recalcAccountsTotal();
        }

        // Run restore on DOM ready (after your other handlers)
        $(function() {
            try {
                restoreProducts();
                restoreAccounts();
            } catch (e) {
                console && console.error && console.error('restore error', e);
            }
        });
    })();

    $(document).ready(function() {

        function num(n) {
            return isNaN(parseFloat(n)) ? 0 : parseFloat(n);
        }

        function recalcRow($row) {
            // Robust number parser
            const getVal = (selector) => {
                let val = $row.find(selector).val();
                return (val === '' || val === undefined || val === null) ? 0 : parseFloat(val);
            };

            // Quantity logic:
            // If user clears quantity (empty string), treat it as 1 for "Total" calculation
            // so they see the single unit price as the total.
            // Only if they explicitly type 0 does it become 0.
            let qRaw = $row.find('.quantity').val();
            let qty = (qRaw === '' || qRaw === null || isNaN(parseFloat(qRaw))) ? 1 : parseFloat(qRaw);

            const priceInput = getVal('.price'); 
            const purchaseNet = getVal('.purchase_net_amount'); 
            const purchaseRetail = getVal('.purchase_retail_price');
            const discPercent = getVal('.item_disc');

            // Determine Base Unit Price
            let baseUnit = priceInput;
            if (baseUnit === 0 && purchaseNet > 0) {
                 baseUnit = purchaseNet;
            }

            // Calculate Discount
            const discBase = (purchaseRetail > 0) ? purchaseRetail : baseUnit;
            const perUnitDisc = discBase * (discPercent / 100);

            // Calculate Final Unit Amount
            let perUnitAmount = baseUnit - perUnitDisc;
            if (perUnitAmount < 0) perUnitAmount = 0;

            // Calculate Total
            const rowTotal = perUnitAmount * qty;

            // Update UI Fields
            const format = (n) => isNaN(n) ? '0.00' : n.toFixed(2);

            $row.find('.disc_amount').val(format(perUnitDisc * qty));
            $row.find('.row-amount').val(format(perUnitAmount));
            $row.find('.row-total').val(format(rowTotal));
        }


        $('#overallDiscount, #whtValue, #whtType').on('input change', function() {
            recalcSummary();
        });

        $('#purchaseItems').on('input', '.quantity, .item_disc, .price', function() {
            const $row = $(this).closest('tr');
            recalcRow($row);
            recalcSummary();
        });

        // Auto-select value when focusing on numeric fields
        // This ensures typing replaces the value instead of appending
        $('#purchaseItems').on('focus', '.quantity, .item_disc, .price', function() {
            $(this).select();
        });


        function recalcSummary() {
            let sub = 0;
            $('#purchaseItems .row-total').each(function() {
                sub += num($(this).val());
            });
            $('#subtotal').val(sub.toFixed(2));

            const oDisc = num($('#overallDiscount').val());
            let whtVal = num($('#whtValue').val());
            const whtType = $('#whtType').val();

            // Calculate WHT amount (separate field)
            let whtAmount = 0;
            if (whtType === 'percent') {
                // percent of (subtotal - overallDiscount)
                const taxable = Math.max(0, sub - oDisc);
                whtAmount = taxable * (whtVal / 100);
            } else {
                // amount in PKR
                whtAmount = whtVal;
            }

            // show WHT amount
            $('#whtAmount').val(whtAmount.toFixed(2));

            // Net = Subtotal - Overall Discount - WHTAmount
            const net = sub - oDisc - whtAmount;
            $('#netAmount').val(net.toFixed(2));
        }

        function lastRowHasProduct() {
            const $last = $('#purchaseItems tr:last');
            if (!$last.length) return false;
            const pid = $last.find('.product-select').val() || '';
            return pid.toString().trim().length > 0;
        }

        function appendBlankRow() {
            // only append if last row has product selected (prevents extra empties)
            if ($('#purchaseItems tr').length > 0 && !lastRowHasProduct()) {
                // open the product dropdown of last row to encourage selection
                $('#purchaseItems tr:last .product-select').select2('open');
                return;
            }

            const rowData = {
            product_id: '', product_name: '', brand: '', price: '', retail_show: '',
                item_disc: '', disc_amount: '', purchase_retail: '', purchase_net: '',
                qty: '', row_amount: '', row_total: ''
            };

            const $newRow = $(window.makeRowHtml(rowData));
            $('#purchaseItems').append($newRow);
            if(window.initProductSelect) window.initProductSelect($newRow);
            
            // Open the new dropdown
            setTimeout(() => {
                $newRow.find('.product-select').select2('open');
            }, 50);
        }





        // Handle Enter key navigation for inputs
        $(document).on('keydown', '#purchaseItems input', function(e) {
            
            if (e.key === 'Enter') {
                e.preventDefault(); 
                const $row = $(this).closest('tr');

                // 1. Price -> Discount
                if ($(this).hasClass('price')) {
                    $row.find('.item_disc').focus().select();
                    return false;
                }

                // 2. Discount -> Quantity
                if ($(this).hasClass('item_disc')) {
                    $row.find('.quantity').focus().select();
                    return false;
                }

                // 3. Quantity -> ensure product selected before creating new row
                if ($(this).hasClass('quantity')) {
                    const productIdVal = $row.find('.product-select').val() || '';
                    if (!productIdVal) {
                        $row.find('.product-select').select2('open');
                        return false;
                    }
                    
                    const isLast = $row.is(':last-child');
                    if (isLast) {
                        appendBlankRow();
                    } else {
                        // Focus next row product select
                        $row.next().find('.product-select').select2('open');
                    }
                    return false;
                }
                
                // Fallback: move to next input
                const $inputs = $row.find('input:visible, select:visible, button:visible');
                const idx = $inputs.index(this);
                if (idx >= 0 && idx < $inputs.length - 1) {
                    $inputs.eq(idx + 1).focus();
                }
            }
        });

        function initRecalcAllRows() {
            $('#purchaseItems tr').each(function() {
                try {
                    if (typeof recalcRow === 'function') recalcRow($(this));
                } catch (err) {
                    // ignore individual row errors but log for debugging
                    console && console.error && console.error('recalcRow error', err);
                }
            });
            if (typeof recalcSummary === 'function') recalcSummary();
        }

        // call it now (inside ready)
        initRecalcAllRows();


        // keyboard Enter on suggestion list
        $(document).on('keydown', '.searchResults .search-result-item', function(e) {
            if (e.key === 'Enter') {
                $(this).trigger('click');
            }
        });

        // On change of qty or discount percent recalc
        $('#purchaseItems').on('input', '.quantity, .item_disc', function() {
            const $row = $(this).closest('tr');
            recalcRow($row);
            recalcSummary();
        });

        $('#purchaseItems').on('input', '.price', function() {
            const $row = $(this).closest('tr');
            recalcRow($row);
            recalcSummary();
        });

        // Remove row
        $(document).on('click', '.remove-row', function(e) {
            e.preventDefault();
            const $row = $(this).closest('tr');
            const rowCount = $('#purchaseItems tr').length;

            if (rowCount === 1) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'At least one row required',
                        text: 'You cannot remove the last row. The row will be cleared instead.',
                        timer: 1200,
                        showConfirmButton: false
                    });
                }
                // clear fields
                $row.find('.product_id').val('');
                $row.find('.productSearch').val('');
                $row.find('input[name="brand[]"]').val('');
                $row.find('.price').val('');
                $row.find('.retail_price_show').val('');
                $row.find('.purchase_retail_price').val('');
                $row.find('.purchase_net_amount').val('');
                $row.find('.quantity').val(1);
                $row.find('.item_disc').val(0);
                $row.find('.disc_amount').val('0.00');
                $row.find('.row-amount').val('0.00');
                $row.find('.row-total').val('0.00');
                $row.find('.searchResults').empty();

                if (typeof recalcRow === 'function') recalcRow($row);
                if (typeof recalcSummary === 'function') recalcSummary();

                setTimeout(() => $row.find('.productSearch').focus(), 50);
                return;
            }

            // normal remove
            $row.remove();
            if ($('#purchaseItems tr').length === 0) {
                if (typeof appendBlankRow === 'function') appendBlankRow();
            }
            if (typeof recalcSummary === 'function') recalcSummary();
        });


        // Summary inputs
        $('#overallDiscount, #extraCost').on('input', function() {
            recalcSummary();
        });

        // init first row
        recalcRow($('#purchaseItems tr:first'));
        recalcSummary();


        $('#addAccountRow').on('click', function() {
            const heads = @json($AccountHeads);
            let headOptions = '<option value="" disabled selected>Select Head</option>';
            heads.forEach(h => {
                headOptions += `<option value="${h.id}">${h.name}</option>`;
            });

            let newRow = `
<tr>
  <td>
    <select name="account_head_id[]" class="form-control form-control-sm accountHead">
      ${headOptions}
    </select>
  </td>
  <td>
    <select name="account_id[]" class="form-control form-control-sm accountSub" disabled>
      <option value="" disabled selected>Select Account</option>
    </select>
  </td>
  <td>
    <input type="number" step="0.01" name="account_amount[]" class="form-control form-control-sm accountAmount" value="" disabled>
  </td>
  <td>
    <button type="button" class="btn btn-sm btn-danger removeAccountRow">X</button>
  </td>
</tr>`;
            $('#accountsTable tbody').append(newRow);

        });

        // --- Remove Row ---
        $(document).on('click', '.removeAccountRow', function() {
            $(this).closest('tr').remove();
            recalcAccountsTotal();
        });

        // --- Load Accounts on Head Change ---
        // Toggle Account and Amount required/enabled depending on Account Head selection
        $(document).on('change', '.accountHead', function() {
            const $row = $(this).closest('tr');
            const headId = $(this).val();
            const $accountSelect = $row.find('.accountSub');
            const $amountInput = $row.find('.accountAmount');

            // If no head selected -> disable & clear account+amount
            if (!headId || headId.toString().trim() === '') {
                $accountSelect.prop('disabled', true).prop('required', false)
                    .html('<option value="" disabled selected>Select Account</option>');
                $amountInput.prop('disabled', true).prop('required', false).val('');
                if (typeof recalcAccountsTotal === 'function') recalcAccountsTotal();
                return;
            }

            // Head selected -> enable account and amount (will be required)
            $accountSelect.prop('disabled', false).prop('required', true);
            $amountInput.prop('disabled', false).prop('required', true).attr('min', '0.01');

            // Fetch accounts for this head (AJAX)
            $.ajax({
                url: "/get-accounts-by-head/" + headId,
                type: "GET",
                dataType: 'json',
                success: function(res) {
                    // keep current account value (if any) to try to re-select after fill
                    const currentAcc = $accountSelect.val();
                    let html = '<option value="" disabled>Select Account</option>';
                    if (Array.isArray(res) && res.length) {
                        res.forEach(acc => {
                            // Use === comparison with strings to avoid type issues
                            const selected = String(acc.id) === String(currentAcc) ? ' selected' : '';
                            html += `<option value="${acc.id}"${selected}>${acc.title}</option>`;
                        });
                    }
                    $accountSelect.html(html);
                    // if previously no account chosen, set placeholder selected
                    if (!currentAcc) {
                        $accountSelect.prepend('<option value="" disabled selected>Select Account</option>');
                        $accountSelect.val('');
                    }
                    if (typeof recalcAccountsTotal === 'function') recalcAccountsTotal();
                },
                error: function() {
                    // on error: disable account and clear
                    $accountSelect.prop('disabled', true).prop('required', false)
                        .html('<option value="" disabled selected>Select Account</option>');
                    $amountInput.prop('disabled', true).prop('required', false).val('');
                    if (typeof recalcAccountsTotal === 'function') recalcAccountsTotal();
                }
            });
        });


        // --- Enable Amount field only when Account is selected ---
        $(document).on('change', '.accountSub', function() {
            const $row = $(this).closest('tr');
            const accountId = $(this).val();
            const $amountInput = $row.find('.accountAmount');
            
            // If account selected, enable amount field
            if (accountId && accountId.toString().trim() !== '') {
                $amountInput.prop('disabled', false).prop('required', true).attr('min', '0.01');
            } else {
                // If no account selected, disable amount and clear value
                $amountInput.prop('disabled', true).prop('required', false).val('');
                if (typeof recalcAccountsTotal === 'function') recalcAccountsTotal();
            }
        });

        // --- Recalc Accounts Total ---
        $(document).on('input', '.accountAmount', function() {
            recalcAccountsTotal();
        });

        function recalcAccountsTotal() {
            let total = 0;
            $('.accountAmount').each(function() {
                total += parseFloat($(this).val()) || 0;
            });
            $('#accountsTotal').val(total.toFixed(2));

            // propagate to overallDiscount (existing behavior)
            $('#overallDiscount').val(total.toFixed(2));
            recalcSummary();
        }

        $(document).on('input', '.accountAmount', recalcAccountsTotal);

        // init first row and summary
        recalcRow($('#purchaseItems tr:first'));
        recalcSummary();


    });

    $('#purchaseForm').on('submit', function(e) {
        // remove any item rows that do not have a product selected
        $('#purchaseItems tr').each(function() {
            const pid = $(this).find('.product-select').val() || '';
            if (!pid.toString().trim()) {
                $(this).remove();
            }
        });

        // Safe guard: if all rows were removed (because empty), add one back
        if ($('#purchaseItems tr').length === 0) {
            if (typeof appendBlankRow === 'function') {
                appendBlankRow(); 
            }
        }

        // after removal, check if we still have at least one valid row
        const validRows = $('#purchaseItems .product-select').filter(function() {
             return $(this).val();
        }).length;

        if (validRows === 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'No item selected',
                text: 'Please add at least one valid item before saving.'
            });
            return false;
        }

        // optionally, still re-run client recalc to ensure totals are accurate
        recalcSummary();
        return true; // allow submit
    });


    // ========== VENDOR / TYPE HANDLING ==========

    function loadVendors(type, selectedVendorId = null) {
        let $vendorSelect = $('select[name="vendor_id"]');
        $vendorSelect.empty().append('<option disabled selected>Loading...</option>');

        // Map 'vendor', 'customer', 'walking customer' keys to correct route parameters if needed
        // Assuming your 'party.list' route accepts 'type' as 'vendor' or 'customer'
        
        let apiType = type;
        if(type === 'walkin') apiType = 'customer'; // usually walkin is handled as customer or separate

        $.get('/party/list', { type: apiType }, function(data) {
            $vendorSelect.empty().append('<option value="" disabled selected>Select</option>');
            if(Array.isArray(data)) {
                data.forEach(function(item) {
                     let isSel = (selectedVendorId && String(item.id) === String(selectedVendorId)) ? 'selected' : '';
                     $vendorSelect.append(`<option value="${item.id}" ${isSel}>${item.text}</option>`);
                });
            }
        }).fail(function() {
             $vendorSelect.empty().append('<option value="" disabled>Error loading</option>');
        });
    }

    $(document).on('change', 'select[name="vendor_type"]', function() {
        // When user manually changes type, load vendors and reset selection
        let type = $(this).val();
        loadVendors(type, null);
    });

    $(function() {
        // ========== EDIT MODE: Restore Vendor Only ==========
        // (Items and Allocations are handled by the IIFE restore logic above)
        
        // Data from server
        const purchase = @json($purchase);
        const oldInputExists = @json(!empty(old('vendor_type')));

        if (oldInputExists) {
             const type = $('select[name="vendor_type"]').val();
             const vId = $('select[name="vendor_id"]').val() || "{{ old('vendor_id') }}";
             if(type) loadVendors(type, vId);
             return;
        }

        // 1. Restore Vendor Selection from DB
        // Determine type based on purchasable_type or default 'vendor'
        let vendorType = 'vendor';
        if (purchase.purchasable_type) {
            const types = purchase.purchasable_type.toLowerCase();
            if (types.includes('customer')) vendorType = 'customer';
            else if (types.includes('walking')) vendorType = 'walkin';
        }
        
        // Update select and trigger load
        $('select[name="vendor_type"]').val(vendorType);
        
        // Manually load vendors to ensure we select the correct one
        const vendorId = purchase.purchasable_id || purchase.vendor_id;
        loadVendors(vendorType, vendorId);
    }); // End Document Ready
    </script>
@endsection