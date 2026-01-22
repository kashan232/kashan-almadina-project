@extends('admin_panel.layout.app')
<style>
    .searchResults {
        position: absolute;
        z-index: 9999;
        width: 100%;
        overflow-y: auto;
        background: #fff;
        /* border: 1px solid #ddd; */
    }

    .search-result-item.active {
        background: #007bff;
        color: white;
    }

    th {
        font-weight: 500 !important;
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
                                <h6 class="page-title ml-4">Create Purchase</h6>
                                <span class="badge bg-primary ms-3" style="font-size:14px;">Invoice: {{ $nextInvoice }}</span>
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
                                        @if ($errors->any())
                                        <div class="alert alert-danger">
                                            <ul>
                                                @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                        @endif
                                        @if (session('success'))
                                        <div class="alert alert-success alert-dismissible fade show"
                                            role="alert">
                                            <strong>Success!</strong> {{ session('success') }}
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"
                                                aria-label="Close"></button>
                                        </div>
                                        @endif

                                        <form id="purchaseForm" action="{{ route('store.Purchase') }}" method="POST">
                                            @csrf
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
                                                            <input name="current_date" value="{{ old('current_date', date('Y-m-d')) }}"
                                                                type="date" class="form-control form-control-sm" required>
                                                        </td>

                                                        </td>
                                                        <td><input name="dc_date" value="{{ date('Y-m-d') }}"
                                                                type="date" class="form-control form-control-sm">
                                                        </td>
                                                        <td>
                                                            <select name="vendor_type" class="form-control form-control-sm" id="vendor_type_select">
                                                                <option value="" {{ old('vendor_type') ? '' : 'selected' }} disabled>Select</option>

                                                                <option value="vendor" {{ old('vendor_type') == 'vendor' ? 'selected' : '' }}>Vendor</option>
                                                                <option value="customer" {{ old('vendor_type') == 'customer' ? 'selected' : '' }}>Customer</option>
                                                                <option value="walkin" {{ old('vendor_type') == 'walkin' ? 'selected' : '' }}>Walkin Customer</option>
                                                            </select>
                                                        </td>

                                                        <td>
                                                            <select name="vendor_id" class="form-control form-control-sm" style="width:105px;">
                                                                <option disabled selected>Select</option>
                                                            </select>
                                                        </td>
                                                        <td><input name="dc" type="text"
                                                                class="form-control form-control-sm" style="width:90px;"></td>
                                                        <td>
                                                            <select name="warehouse_id" class="form-control form-control-sm">
                                                                <option value="" disabled {{ old('warehouse_id') ? '' : 'selected' }}>Select</option>
                                                                @foreach ($Warehouse as $ware)
                                                                <option value="{{ $ware->id }}" {{ (string)old('warehouse_id') === (string)$ware->id ? 'selected' : '' }}>
                                                                    {{ $ware->warehouse_name }}
                                                                </option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input name="bilty_no" type="text"
                                                                class="form-control form-control-sm" style="width:90px;">
                                                        </td>
                                                        <td><input name="remarks" type="text" value="{{ old('remarks') }}" class="form-control form-control-sm"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <table
                                                class="table table-bordered table-sm text-center align-middle mt-2">
                                                <thead class="table-light">
                                                    <tr>
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
                                                        <td>
                                                            <input type="hidden" name="product_id[]" class="product_id">
                                                            <input type="hidden" name="product_name[]" class="product_name_hidden">
                                                            <input type="text" class="form-control form-control-sm productSearch" placeholder="Search product..." autocomplete="off">
                                                            <ul class="searchResults list-group mt-1"></ul>
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
                                                            <input type="number" name="qty[]" class="form-control form-control-sm quantity" value="1" min="1">
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
                                                                            <select name="account_id[]" class="form-control form-control-sm accountSub">
                                                                                <option value="" disabled selected>Select Account</option>
                                                                            </select>
                                                                        </td>
                                                                        <td>
                                                                            <input type="number" step="0.01" name="account_amount[]" class="form-control form-control-sm accountAmount" value="0">
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
                                                                    <td><input type="text" id="subtotal" name="subtotal" class="form-control form-control-sm" value="{{ old('subtotal', 0) }}" readonly></td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Discount</th>
                                                                    <td><input type="number" step="0.01" id="overallDiscount" name="discount" class="form-control form-control-sm" value="{{ old('discount', 0) }}">
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <th>WHT</th>
                                                                    <td>
                                                                        <div class="input-group">
                                                                            <input type="number" step="0.01" id="whtValue" name="wht" class="form-control form-control-sm" value="0">
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
                                                                    <td><input type="text" id="netAmount" name="net_amount" class="form-control form-control-sm fw-bold" value="{{ old('net_amount', 0) }}" readonly></td>
                                                                </tr>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-primary btn-sm w-100">Submit
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

@if ($errors->any())
<script>
    let errorMessages = `{!! implode('<br>', $errors->all()) !!}`;
    Swal.fire({
        icon: 'error',
        title: 'Validation Error',
        html: errorMessages,
        timer: 3000,
        showConfirmButton: false
    });
</script>
@endif

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

@if ($errors->any())
<script>
    Swal.fire({
        icon: 'error',
        title: 'Oops...',
        html: {
            !!json_encode(implode('<br>', $errors - > all())) !!
        },
        confirmButtonColor: '#d33',
    });
</script>
@endif


@if ($errors->any())


<script>
    Swal.fire({
        icon: 'error',
        title: 'Oops...',
        html: {
            !!json_encode(implode('<br>', $errors - > all())) !!
        },
        confirmButtonColor: '#d33',
    });
</script>
@endif

<script>
    (function($) {
        // throttle / single Swal helper
        let lastSwalAt = 0;

        function showOneToast(options = {}) {
            const now = Date.now();
            const throttleMs = options.throttleMs || 1200; // change if you want longer
            // if Swal is visible OR recently shown, skip
            if (typeof Swal !== 'undefined' && Swal.isVisible && Swal.isVisible()) return;
            if (now - lastSwalAt < throttleMs) return;
            lastSwalAt = now;

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: options.icon || 'info',
                    title: options.title || '',
                    text: options.text || '',
                    timer: options.timer || 1400,
                    showConfirmButton: options.showConfirmButton || false,
                });
            } else {
                // fallback to alert once
                if (now - (window._lastAlertAt || 0) > throttleMs) {
                    alert((options.title ? (options.title + '\n') : '') + (options.text || ''));
                    window._lastAlertAt = now;
                }
            }
        }

        // flag to coordinate pointerdown->blur->click
        let isSelectingSuggestion = false;

        // set flag on pointerdown (fires before blur)
        $(document).on('pointerdown', '.searchResults .search-result-item', function() {
            isSelectingSuggestion = true;
            // clear after short delay to allow click handlers to run
            setTimeout(() => {
                isSelectingSuggestion = false;
            }, 400);
        });

        // Product search key handlers (Tab/Enter) — use showOneToast instead of direct Swal calls
        $(document).on('keydown', '.productSearch', function(e) {
            const $input = $(this);
            const $row = $input.closest('tr');
            const $box = $row.find('.searchResults');

            if (e.key === 'Tab') {
                const pid = $row.find('.product_id').val() || '';
                const $items = $box.children('.search-result-item');
                const hasActive = $items.length && $items.filter('.active').length;

                if (!pid.toString().trim() && !hasActive) {
                    e.preventDefault();
                    $input.addClass('invalid-product');
                    showOneToast({
                        icon: 'warning',
                        title: 'Please select a product',
                        text: 'Choose from suggestions or clear the field.',
                        timer: 1400
                    });
                    setTimeout(() => $input.focus(), 0);
                    return;
                }

                if (!pid.toString().trim() && hasActive) {
                    e.preventDefault();
                    const $active = $items.filter('.active').first();
                    if ($active.length) {
                        // mark selecting to avoid blur race
                        isSelectingSuggestion = true;
                        $active.trigger('click');
                        setTimeout(() => {
                            isSelectingSuggestion = false;
                        }, 400);
                    }
                    setTimeout(() => focusPriceOrQty($row), 0);
                    return;
                }
                // else allow tab normally
            }

            if (e.key === 'Enter') {
                const $items = $box.children('.search-result-item');
                if ($items.length) {
                    e.preventDefault();
                    const idx = $items.index($items.filter('.active'));
                    if (idx >= 0) {
                        isSelectingSuggestion = true;
                        $items.eq(idx).trigger('click');
                        setTimeout(() => {
                            isSelectingSuggestion = false;
                        }, 400);
                    } else if ($items.length === 1) {
                        isSelectingSuggestion = true;
                        $items.eq(0).trigger('click');
                        setTimeout(() => {
                            isSelectingSuggestion = false;
                        }, 400);
                    }
                    setTimeout(() => $row.find('.quantity').focus(), 0);
                } else {
                    e.preventDefault();
                    showOneToast({
                        icon: 'info',
                        title: 'No suggestions',
                        text: 'No matching product found.',
                        timer: 900
                    });
                    $input.focus();
                }
            }
        });





        function focusPriceOrQty($row) {
            const $price = $row.find('.price');
            const $qty = $row.find('.quantity');
            if ($price.length && !$price.prop('readonly') && !$price.prop('disabled')) {
                $price.focus();
                try {
                    $price.select();
                } catch (e) {}
                return;
            }
            if ($qty.length) {
                $qty.focus();
                try {
                    $qty.select();
                } catch (e) {}
            }
        }

        // Click select suggestion — idempotent fill (won't double-fill)
        $(document).on('click', '.search-result-item', function(e) {
            const $li = $(this);
            const $row = $li.closest('tr');

            // visible value
            $row.find('.productSearch').val($li.data('product-name'));
            // hidden field to be posted -> old() will keep it after validation fail
            $row.find('input[name="product_name[]"]').val($li.data('product-name'));

            $row.find('input[name="brand[]"]').val($li.data('product-brand') || '');
            $row.find('.price').val(parseFloat($li.data('price-net') || 0).toFixed(2));
            $row.find('.retail_price_show').val(parseFloat($li.data('price-retail') || 0).toFixed(2));

            $row.find('.purchase_net_amount').val(parseFloat($li.data('price-net') || 0).toFixed(2));
            $row.find('.purchase_retail_price').val(parseFloat($li.data('price-retail') || 0).toFixed(2));
            $row.find('.product_id').val($li.data('product-id'));

            $row.find('.quantity').val(1);
            $row.find('.item_disc').val(0);
            $row.find('.disc_amount').val('0.00');

            if (typeof recalcRow === 'function') recalcRow($row);
            if (typeof recalcSummary === 'function') recalcSummary();

            $row.find('.searchResults').empty();
            setTimeout(() => focusPriceOrQty($row), 0);
        });


        // When selecting active suggestion via Tab/Enter
        // wherever you have code that triggers selection like $active.trigger('click');
        // just ensure you call focusPriceOrQty afterwards, e.g.:
        $active.trigger('click');
        setTimeout(() => focusPriceOrQty($row), 0);

        // blur: do not force refocus if selecting suggestion; show throttled toast instead of multiple modals
        $(document).on('blur', '.productSearch', function() {
            const $input = $(this);
            const $row = $input.closest('tr');

            // if user is clicking a suggestion, skip
            if (isSelectingSuggestion) return;

            setTimeout(() => {
                const pid = $row.find('.product_id').val() || '';
                const txt = $input.val() || '';
                if (txt.toString().trim().length > 0 && !pid.toString().trim()) {
                    // show one toast only (throttled)
                    showOneToast({
                        icon: 'info',
                        title: 'Select product',
                        text: 'Please select a product from the list, or clear the input to skip.',
                        timer: 1200
                    });
                    // do not force focus immediately to avoid flicker (user may click elsewhere)
                }
            }, 160);
        });

    })(jQuery);
</script>

{{-- Item Row Autocomplete + Add/Remove --}}
<!-- Make sure jQuery and Bootstrap Typeahead are included -->

<script>
    (function() {
        // restore old arrays from server (Blade -> JS)
        const oldProducts = @json(old('product_id', []));
        const oldPrices = @json(old('price', []));
        const oldQtys = @json(old('qty', []));
        const oldItemDiscs = @json(old('item_disc', []));
        const oldDiscAmounts = @json(old('item_disc_amount', []));
        const oldRetailPrices = @json(old('purchase_retail_price', []));
        const oldPurchaseNet = @json(old('purchase_net_amount', []));
        const oldRowAmounts = @json(old('total', [])); // or row-total if you used that
        const oldProductNames = @json(old('product_name', [])); // optional, if you send product names

        // account allocations
        const oldAccHeads = @json(old('account_head_id', []));
        const oldAccIds = @json(old('account_id', []));
        const oldAccAmounts = @json(old('account_amount', []));

        // helper: create a product row HTML (same structure as appendBlankRow)
        function makeRowHtml(data) {
            // data: { product_id, product_name, brand, price, retail_show, item_disc, disc_amount, qty, row_amount, row_total, purchase_retail, purchase_net }
            return `
      <tr>
        <td>
          <input type="hidden" name="product_id[]" class="product_id" value="${data.product_id || ''}">
<input type="hidden" name="product_name[]" class="product_name_hidden" value="${(data.product_name || '')}">
<input type="text" class="form-control form-control-sm productSearch" placeholder="Search product..." autocomplete="off" value="${(data.product_name||'')}">
          <ul class="searchResults list-group mt-1"></ul>
        </td>
        <td class="uom border">
          <input type="text" name="brand[]" class="form-control form-control-sm" readonly value="${data.brand || ''}">
        </td>
        <td>
          <input type="number" step="0.01" name="price[]" class="form-control form-control-sm price" value="${data.price || ''}">
        </td>
        <td>
          <input type="text" name="retail_price_show[]" class="form-control form-control-sm retail_price_show" readonly value="${data.retail_show || ''}">
        </td>
        <td>
          <div class="input-group">
            <input type="number" step="0.01" min="0" name="item_disc[]" class="form-control form-control-sm item_disc" placeholder="%" value="${data.item_disc || ''}">
            <input type="text" name="item_disc_amount[]" class="form-control form-control-sm disc_amount" readonly placeholder="Disc Amt" value="${data.disc_amount || ''}">
          </div>
          <input type="hidden" name="purchase_retail_price[]" class="purchase_retail_price" value="${data.purchase_retail || ''}">
          <input type="hidden" name="purchase_net_amount[]" class="purchase_net_amount" value="${data.purchase_net || ''}">
        </td>
        <td>
          <input type="number" name="qty[]" class="form-control form-control-sm quantity" value="${data.qty || 1}" min="1">
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
                const rowData = {
                    product_id: oldProducts[i] ?? '',
                    product_name: (oldProductNames[i] ?? ''), // optional
                    brand: '', // you didn't submit brand[] in code, if you do, parse similarly
                    price: oldPrices[i] ?? '',
                    retail_show: oldRetailPrices[i] ?? '',
                    item_disc: oldItemDiscs[i] ?? '',
                    disc_amount: oldDiscAmounts[i] ?? '',
                    purchase_retail: oldRetailPrices[i] ?? '',
                    purchase_net: oldPurchaseNet[i] ?? '',
                    qty: oldQtys[i] ?? 1,
                    row_amount: '', // will be recalculated
                    row_total: oldRowAmounts[i] ?? ''
                };

                $('#purchaseItems').append(makeRowHtml(rowData));
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

            // focus last product search to allow quick continue
            setTimeout(function() {
                $('#purchaseItems tr:last .productSearch').focus();
            }, 80);
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

                // build row (server-side options not available here, we keep select with value)
                const row = `
        <tr>
          <td>
            <select name="account_head_id[]" class="form-control form-control-sm accountHead">
              <option value="" disabled ${head? '': 'selected'}>Select Head</option>
              @foreach ($AccountHeads as $headOpt)
                <option value="{{ $headOpt->id }}" ${ head == {{ $headOpt->id }} ? 'selected' : '' }>{{ $headOpt->name }}</option>
              @endforeach
            </select>
          </td>
          <td>
            <select name="account_id[]" class="form-control form-control-sm accountSub">
              <option value="${acc}" selected>${acc ? acc : 'Select Account'}</option>
            </select>
          </td>
          <td>
            <input type="number" step="0.01" name="account_amount[]" class="form-control form-control-sm accountAmount" value="${amt || 0}">
          </td>
          <td>
            <button type="button" class="btn btn-sm btn-danger removeAccountRow">X</button>
          </td>
        </tr>
      `;
                $('#accountsTable tbody').append(row);
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
            // safe selectors
            const qty = num($row.find('.quantity').val());
            const priceInput = num($row.find('.price').val()); // user-editable price (if any)
            const purchaseNet = num($row.find('.purchase_net_amount').val()); // hidden value from product
            const purchaseRetail = num($row.find('.purchase_retail_price').val()); // retail used for % discount calc
            const discPercent = num($row.find('.item_disc').val());

            // Base price to use for calculations:
            // prefer explicit price input (if provided), otherwise fallback to purchaseNet
            const baseUnit = priceInput > 0 ? priceInput : purchaseNet;

            // per-unit discount based on retail (if retail present), otherwise based on baseUnit
            // you can choose which base the percent applies to; here prefer retail if >0 else baseUnit
            const discBase = (purchaseRetail > 0) ? purchaseRetail : baseUnit;
            const perUnitDisc = discBase * (discPercent / 100);

            // unit amount after discount
            let perUnitAmount = baseUnit - perUnitDisc;
            if (perUnitAmount < 0) perUnitAmount = 0;

            // totals
            const rowAmount = perUnitAmount; // per unit final showing
            const rowTotal = perUnitAmount * qty;

            // set values (format to 2 decimals)
            $row.find('.disc_amount').val((perUnitDisc * qty).toFixed(2)); // total discount amount for that row
            $row.find('.row-amount').val(rowAmount.toFixed(2));
            $row.find('.row-total').val(rowTotal.toFixed(2));

            // keep hidden fields in sync
            $row.find('.purchase_retail_price').val(purchaseRetail.toFixed ? purchaseRetail.toFixed(2) : purchaseRetail);
            $row.find('.purchase_net_amount').val(purchaseNet.toFixed ? purchaseNet.toFixed(2) : purchaseNet);
        }


        $('#overallDiscount, #whtValue, #whtType').on('input change', function() {
            recalcSummary();
        });

        $('#purchaseItems').on('input', '.quantity, .item_disc, .price', function() {
            const $row = $(this).closest('tr');
            recalcRow($row);
            recalcSummary();
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
            const pid = $last.find('.product_id').val() || '';
            return pid.toString().trim().length > 0;
        }
        $(document).on('keydown', '.price', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                $(this).closest('tr').find('.quantity').focus();
            }
        });


        function appendBlankRow() {
            // only append if last row has product selected (prevents extra empties)
            if ($('#purchaseItems tr').length > 0 && !lastRowHasProduct()) {
                // focus the product field of last row to encourage selection
                $('#purchaseItems tr:last .productSearch').focus();
                return;
            }

            const newRow = `
      <tr>
        <!-- Product -->
         <td>
      <input type="hidden" name="product_id[]" class="product_id">
      <input type="hidden" name="product_name[]" class="product_name_hidden" value="">
      <input type="text" class="form-control form-control-sm productSearch" placeholder="Enter product name..." autocomplete="off" value="">
      <ul class="searchResults list-group mt-1"></ul>
    </td>

        <!-- Brand -->
        <td class="uom border">
            <input type="text" name="brand[]" class="form-control form-control-sm" readonly>
        </td>

        <!-- Price -->
        <td>
            <input type="number" step="0.01" name="price[]" class="form-control form-control-sm price">
        </td>

        <!-- Retail Price (visible) -->
        <td>
            <input type="text" name="retail_price_show[]" class="form-control form-control-sm retail_price_show" readonly>
        </td>

        <!-- Discount (percent) + Discount Amount (visible) + hidden purchase prices -->
        <td>
          <div class="input-group">
            <input type="number" step="0.01" min="0" name="item_disc[]" class="form-control form-control-sm item_disc" placeholder="%">
            <input type="text" name="item_disc_amount[]" class="form-control form-control-sm disc_amount" readonly placeholder="Disc Amt">
          </div>
          <input type="hidden" name="purchase_retail_price[]" class="purchase_retail_price">
          <input type="hidden" name="purchase_net_amount[]" class="purchase_net_amount">
        </td>

        <!-- Quantity -->
        <td class="qty">
            <input type="number" name="qty[]" class="form-control form-control-sm quantity" value="1" min="1">
        </td>

        <!-- Amount (unit after discount shown) -->
        <td>
            <input type="text" name="amount[]" class="form-control form-control-sm row-amount" readonly>
        </td>

        <!-- Total (amount * qty) -->
        <td class="total border">
            <input type="text" name="total[]" class="form-control form-control-sm row-total" readonly>
        </td>

        <!-- Remove -->
        <td>
            <button type="button" class="btn btn-sm btn-danger remove-row">X</button>
        </td>
      </tr>`;
            $('#purchaseItems').append(newRow);
        }

        // ---------- Product Search (AJAX) ----------
        $(document).on('keyup', '.productSearch', function(e) {
            const $input = $(this);
            const q = $input.val().trim();
            const $row = $input.closest('tr');
            const $box = $row.find('.searchResults');

            // keyboard nav
            const isNavKey = ['ArrowDown', 'ArrowUp', 'Enter'].includes(e.key);
            if (isNavKey && $box.children('.search-result-item').length) {
                const $items = $box.children('.search-result-item');
                let idx = $items.index($items.filter('.active'));
                if (e.key === 'ArrowDown') {
                    idx = (idx + 1) % $items.length;
                    $items.removeClass('active');
                    $items.eq(idx).addClass('active');
                    e.preventDefault();
                    return;
                }
                if (e.key === 'ArrowUp') {
                    idx = (idx <= 0 ? $items.length - 1 : idx - 1);
                    $items.removeClass('active');
                    $items.eq(idx).addClass('active');
                    e.preventDefault();
                    return;
                }
                if (e.key === 'Enter') {
                    if (idx >= 0) {
                        $items.eq(idx).trigger('click');
                    } else if ($items.length === 1) {
                        $items.eq(0).trigger('click');
                    }
                    e.preventDefault();
                    return;
                }
            }

            if (q.length === 0) {
                $box.empty();
                return;
            }

            $.ajax({
                url: "{{ route('search-products') }}",
                type: 'GET',
                data: {
                    q
                },
                success: function(data) {
                    let html = '';
                    (data || []).forEach(p => {
                        const brand = p.brand ?? '';
                        const net = p.purchase_net_amount ?? 0;
                        const retail = p.purchase_retail_price ?? 0;
                        const name = p.name ?? '';
                        const id = p.id ?? '';

                        html += `
      <li class="list-group-item search-result-item"
          tabindex="0"
          data-product-id="${id}"
          data-product-name="${name}"
          data-product-brand="${brand}"
          data-price-net="${net}"
          data-price-retail="${retail}">
        ${name} — ${brand} — Rs. ${net}
      </li>`;
                    });

                    $box.html(html);
                    $box.children('.search-result-item').first().addClass('active');
                },
                error: function() {
                    $box.empty();
                }
            });
        });

        // Click/Enter select suggestion
        $(document).on('click', '.search-result-item', function() {
            const $li = $(this);
            const $row = $li.closest('tr');

            $row.find('.productSearch').val($li.data('product-name'));
            $row.find('input[name="brand[]"]').val($li.data('product-brand') || '');
            $row.find('.price').val(parseFloat($li.data('price-net') || 0).toFixed(2));
            $row.find('.retail_price_show').val(parseFloat($li.data('price-retail') || 0).toFixed(2));

            $row.find('.purchase_net_amount').val(parseFloat($li.data('price-net') || 0).toFixed(2));
            $row.find('.purchase_retail_price').val(parseFloat($li.data('price-retail') || 0).toFixed(2));
            $row.find('.product_id').val($li.data('product-id'));

            $row.find('.quantity').val(1);
            $row.find('.item_disc').val(0);
            $row.find('.disc_amount').val('0.00');

            recalcRow($row);
            recalcSummary();

            $row.find('.searchResults').empty();
            $row.find('.quantity').focus();
        });


        $(document).on('keydown', '#purchaseItems .quantity', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const $currentRow = $(this).closest('tr');
                const productIdVal = $currentRow.find('.product_id').val() || '';
                if (!productIdVal) {
                    $currentRow.find('.productSearch').focus();
                    return;
                }
                const isLast = $currentRow.is(':last-child');
                if (isLast) {
                    appendBlankRow();
                    $('#purchaseItems tr:last .productSearch').focus();
                } else {
                    $currentRow.next().find('.productSearch').focus();
                }
            }
        });


        $(document).on('keydown', '#purchaseItems input', function(e) {
            // allow Enter inside product suggestion navigation (handled elsewhere)
            const isProductSearch = $(this).hasClass('productSearch');
            const tag = e.target.tagName.toLowerCase();
            if (e.key === 'Enter' && !isProductSearch) {
                // prevent default form submit
                e.preventDefault();
                // custom behaviour: move focus sensibly
                const $row = $(this).closest('tr');

                // prefer to move focus: price -> qty -> next product
                if ($(this).hasClass('price')) {
                    // if price, move to qty
                    $row.find('.quantity').focus();
                    $row.find('.quantity').select && $row.find('.quantity').select();
                    return false;
                }
                if ($(this).hasClass('quantity')) {
                    // if qty and last row then append and focus next product
                    const isLast = $row.is(':last-child');
                    if (isLast) {
                        if (typeof appendBlankRow === 'function') appendBlankRow();
                        $('#purchaseItems tr:last .productSearch').focus();
                    } else {
                        $row.next().find('.productSearch').focus();
                    }
                    return false;
                }

                // default fallback: focus next focusable in the row
                let $next = $row.find('input,select,button').filter(':visible').toArray();
                let idx = $next.indexOf(e.target);
                if (idx >= 0 && idx < $next.length - 1) {
                    $($next[idx + 1]).focus();
                }
                return false;
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
            let newRow = `
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
                url: "{{ url('/get-accounts-by-head') }}/" + headId,
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
            const pid = $(this).find('.product_id').val() || '';
            // if product id blank OR productSearch text blank, remove row
            const name = $(this).find('.productSearch').val() || '';
            if (!pid.toString().trim() && !name.toString().trim()) {
                $(this).remove();
            }
        });

        // after removal, check if we still have at least one valid row
        if ($('#purchaseItems .product_id').filter(function() {
                return $(this).val().toString().trim() !== '';
            }).length === 0) {
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


    $(document).on('change', 'select[name="vendor_type"]', function() {
        let type = $(this).val().toLowerCase();
        let $vendorSelect = $('select[name="vendor_id"]');

        $vendorSelect.empty().append('<option disabled selected>Loading...</option>');

        $.get('{{ route("party.list") }}?type=' + type, function(data) {
            $vendorSelect.empty().append('<option disabled selected>Select</option>');
            data.forEach(function(item) {
                $vendorSelect.append('<option value="' + item.id + '">' + item.text + '</option>');
            });
        });
    });

    $(document).on('change', 'select[name="vendor_id"]', function() {
        let id = $(this).val();
        let type = $('select[name="vendor_type"]').val().toLowerCase();

        if (!id) return;

        $.get('{{ route("customers.show", ["id" => "__ID__"]) }}'.replace('__ID__', id) + '?type=' + type, function(d) {
            $('#address').val(d.address || '');
            $('#tel').val(d.mobile || '');
            $('#remarks').val(d.remarks || '');
            $('#previousBalance').val((+d.previous_balance || 0).toFixed(2));
        });
    });
</script>
@endsection