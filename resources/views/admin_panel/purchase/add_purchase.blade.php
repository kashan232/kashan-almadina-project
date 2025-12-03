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
                            <div class="flex-grow-1 ">
                                <h6 class="page-title ml-4">Create Purchase</h6>
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

                                        <form action="{{ route('store.Purchase') }}" method="POST">
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
                                                        <td><input name="current_date" value="{{ date('Y-m-d') }}"
                                                                type="date" class="form-control form-control-sm" required>
                                                        </td>
                                                        <td><input name="dc_date" value="{{ date('Y-m-d') }}"
                                                                type="date" class="form-control form-control-sm">
                                                        </td>
                                                        <td>
                                                            <select name="vendor_type" class="form-control form-control-sm">
                                                                <option>Select</option>
                                                                <option value="vendor" selected>Vendor</option>
                                                                <option value="customer">Customer</option>
                                                                <option value="walkin">Walkin Customer</option>
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
                                                            <select name="warehouse_id"
                                                                class="form-control form-control-sm">
                                                                <option disabled selected>Select</option>
                                                                @foreach ($Warehouse as $ware)
                                                                <option value="{{ $ware->id }}">
                                                                    {{ $ware->warehouse_name }}
                                                                </option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input name="bilty_no" type="text"
                                                                class="form-control form-control-sm" style="width:90px;">
                                                        </td>
                                                        <td><input name="remarks" type="text"
                                                                class="form-control form-control-sm"></td>
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
                                                            <input type="text" class="form-control form-control-sm productSearch" placeholder="Search product..." autocomplete="off">
                                                            <ul class="searchResults list-group mt-1"></ul>
                                                        </td>

                                                        <td class="uom border">
                                                            <input type="text" name="brand[]" class="form-control form-control-sm" readonly>
                                                        </td>
                                                        <td>
                                                            <input type="number" step="0.01" name="price[]" class="form-control form-control-sm price">
                                                        </td>
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
                                                                    <td><input type="text" id="subtotal" name="subtotal"
                                                                            class="form-control form-control-sm" value="0" readonly></td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Discount</th>
                                                                    <td><input type="number" step="0.01" id="overallDiscount" name="discount"
                                                                            class="form-control form-control-sm" value="0"></td>
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
                                                                    <th>Net</th>
                                                                    <td><input type="text" id="netAmount" name="net_amount"
                                                                            class="form-control form-control-sm fw-bold" value="0" readonly></td>
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

<script>
    // Prevent Enter key from submitting form in product search
    $(document).on('keydown', '.productSearch', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault(); // stops form submission
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        const cancelBtn = document.getElementById('cancelBtn');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function() {
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'This will cancel your changes!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, go back!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '';
                    }
                });
            });
        }
    });
</script>

{{-- Item Row Autocomplete + Add/Remove --}}
<!-- Make sure jQuery and Bootstrap Typeahead are included -->

<script>
    $(document).ready(function() {

        function num(n) {
            return isNaN(parseFloat(n)) ? 0 : parseFloat(n);
        }

        function recalcRow($row) {
            const qty = num($row.find('.quantity').val());

            const priceInput = num($row.find('.price').val());
            const purchaseNet = priceInput > 0 ? priceInput : num($row.find('.purchase_net_amount').val());

            const purchaseRetail = num($row.find('.purchase_retail_price').val());
            const discPercent = num($row.find('.item_disc').val());

            // per-unit discount
            const perUnitDisc = (purchaseRetail * (discPercent / 100));

            // per-unit amount after discount
            let perUnitAmount = purchaseNet - perUnitDisc;
            if (perUnitAmount < 0) perUnitAmount = 0;

            // total
            let total = perUnitAmount * qty;

            // set values
            $row.find('.disc_amount').val((perUnitDisc * qty).toFixed(2));
            $row.find('.row-amount').val(perUnitAmount.toFixed(2)); // ✅ per-item amount
            $row.find('.row-total').val(total.toFixed(2));
        }

        $('#overallDiscount, #whtValue, #whtType').on('input change', function() {
            recalcSummary();
        });

        function recalcSummary() {
            let sub = 0;
            $('#purchaseItems .row-total').each(function() {
                sub += num($(this).val());
            });
            $('#subtotal').val(sub.toFixed(2));

            const oDisc = num($('#overallDiscount').val());
            let wht = num($('#whtValue').val());
            const whtType = $('#whtType').val();

            if (whtType === 'percent') {
                wht = (sub - oDisc) * (wht / 100); // % of taxable amount
            }

            const net = (sub - oDisc - wht);
            $('#netAmount').val(net.toFixed(2));
        }


        function appendBlankRow() {
            const newRow = `
      <tr>
        <td>
            <input type="hidden" name="product_id[]" class="product_id">
            <input type="text" class="form-control form-control-sm productSearch" placeholder="Enter product name..." autocomplete="off">
            <ul class="searchResults list-group mt-1"></ul>
        </td>
        <td class="uom border"><input type="text" name="brand[]" class="form-control form-control-sm" readonly></td>
        <td><input type="number" step="0.01" name="price[]" class="form-control form-control-sm price" readonly></td>
        <td><input type="text" name="retail_price_show[]" class="form-control form-control-sm retail_price_show" readonly></td>
        <td>
          <div class="input-group">
            <input type="number" step="0.01" min="0" name="item_disc[]" class="form-control form-control-sm item_disc" placeholder="%">
            <input type="text" name="item_disc_amount[]" class="form-control form-control-sm disc_amount" readonly placeholder="Disc Amt">
          </div>
          <input type="hidden" name="purchase_retail_price[]" class="purchase_retail_price">
          <input type="hidden" name="purchase_net_amount[]" class="purchase_net_amount">
        </td>
        <td class="qty"><input type="number" name="qty[]" class="form-control form-control-sm quantity" value="1" min="1"></td>
       <td><input type="text" name="amount[]" class="form-control form-control-sm row-amount" readonly></td>
        <td class="total border"><input type="text" name="total[]" class="form-control form-control-sm row-total" readonly></td>
        <td><button type="button" class="btn btn-sm btn-danger remove-row">X</button></td>
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
            // set purchase_net as visible price
            $row.find('.price').val(parseFloat($li.data('price-net') || 0).toFixed(2));
            $row.find('.retail_price_show').val(parseFloat($li.data('price-retail') || 0).toFixed(2)); // ✅ show retail price

            // set hidden values
            $row.find('.purchase_net_amount').val(parseFloat($li.data('price-net') || 0).toFixed(2));
            $row.find('.purchase_retail_price').val(parseFloat($li.data('price-retail') || 0).toFixed(2));
            $row.find('.product_id').val($li.data('product-id'));

            // reset qty & discount percent
            $row.find('.quantity').val(1);
            $row.find('.item_disc').val(0);
            $row.find('.disc_amount').val('0.00');

            // recalc
            recalcRow($row);
            recalcSummary();

            // clear results
            $row.find('.searchResults').empty();

            // append new blank row and focus
            appendBlankRow();
            $('#purchaseItems tr:last .productSearch').focus();
        });

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
        $('#purchaseItems').on('click', '.remove-row', function() {
            $(this).closest('tr').remove();
            recalcSummary();
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
        </tr>`;
            $('#accountsTable tbody').append(newRow);
        });

        // --- Remove Row ---
        $(document).on('click', '.removeAccountRow', function() {
            $(this).closest('tr').remove();
            recalcAccountsTotal();
        });

        // --- Load Accounts on Head Change ---
        $(document).on('change', '.accountHead', function() {
            let headId = $(this).val();
            let $subSelect = $(this).closest('tr').find('.accountSub');

            if (!headId) {
                $subSelect.html('<option value="" disabled selected>Select Account</option>');
                return;
            }

            $.ajax({
                url: "{{ url('/get-accounts-by-head') }}/" + headId,
                type: "GET",
                success: function(res) {
                    let html = '<option value="" disabled selected>Select Account</option>';
                    res.forEach(acc => {
                        html += `<option value="${acc.id}">${acc.title}</option>`;
                    });
                    $subSelect.html(html);
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

            // --- ALSO update Totals card Discount ---
            $('#overallDiscount').val(total.toFixed(2));

            // recalc net summary after updating discount
            recalcSummary();
        }


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