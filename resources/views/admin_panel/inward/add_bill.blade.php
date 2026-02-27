@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <div class="row p-3">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>Add Bill For Good received note #{{ $gatepass->id }}</h3>
                    <a href="{{ route('InwardGatepass.home') }}" class="btn btn-secondary">Back</a>
                </div>

                <div class="col-lg-12 col-md-12 mb-30">
                    <div class="card">
                        <div class="card-body">
                            @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                            @endif
                            @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif

                            <form action="{{ route('store.bill', $gatepass->id) }}" method="POST">
                                @csrf

                                <input type="hidden" name="inward_id" value="{{ $gatepass->id }}">
                                <!-- Gatepass Info -->
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
                                                    type="date" class="form-control form-control-sm" required>
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
                                                    class="form-control form-control-sm" style="width:90px;" required></td>
                                            <td>
                                                <select name="warehouse_id"
                                                    class="form-control form-control-sm" required>
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
                                                    class="form-control form-control-sm" style="width:90px;" required>
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
                                        @foreach($gatepass->items as $item)
                                        @php
                                        $product = $item->product;
                                        $price = $product->latestPrice;
                                        @endphp
                                        <tr>
                                            <td>
                                                <input type="hidden" name="product_id[]" value="{{ $product->id }}">
                                                <input type="text" class="form-control form-control-sm" value="{{ $product->name }}" readonly>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm" value="{{ $product->brand->name ?? '-' }}" readonly>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm" value="{{ $price->purchase_net_amount ?? 0 }}" readonly>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm" value="{{ $price->purchase_retail_price ?? 0 }}" readonly>
                                            </td>
                                            <td>
                                                <div class="input-group">
                                                    <input type="number" step="0.01" min="0" name="item_disc[]" class="form-control form-control-sm item_disc" placeholder="%">
                                                    <input type="text" name="item_disc_amount[]" class="form-control form-control-sm disc_amount" readonly placeholder="Disc Amt">
                                                </div>
                                                <input type="hidden" name="purchase_retail_price[]" class="purchase_retail_price" value="{{ $price->purchase_retail_price ?? 0 }}">
                                                <input type="hidden" name="purchase_net_amount[]" class="purchase_net_amount" value="{{ $price->purchase_net_amount ?? 0 }}">
                                            </td>
                                            <td>
                                                <input type="number" name="qty[]" class="form-control form-control-sm quantity" value="{{ $item->qty }}" readonly>
                                            </td>
                                            <td>
                                                <input type="number" name="amount[]" class="form-control form-control-sm amount"
                                                    value="{{ $price->purchase_net_amount ?? 0 }}">
                                            </td>
                                            <td>
                                                <input type="text" name="total[]" class="form-control form-control-sm row-total" readonly>
                                            </td>
                                            <td><button type="button" class="btn btn-sm btn-danger remove-row">X</button></td>
                                        </tr>
                                        @endforeach

                                        {{-- ✅ Blank row for first product search --}}
                                        <tr>
                                            <td>
                                                <input type="hidden" name="product_id[]" class="product_id">
                                                <input type="text" class="form-control form-control-sm productSearch" placeholder="Enter product name..." autocomplete="off">
                                                <ul class="searchResults list-group mt-1"></ul>
                                            </td>
                                            <td><input type="text" name="brand[]" class="form-control form-control-sm" readonly></td>
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
                                            <td><input type="number" name="qty[]" class="form-control form-control-sm quantity" value="1" min="1"></td>
                                            <td>
                                                <input type="number" name="amount[]" class="form-control form-control-sm amount" value="0" step="0.01">
                                            </td>

                                            <td><input type="text" name="total[]" class="form-control form-control-sm row-total" readonly></td>
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
                                                                <select name="account_head_id[]" class="form-control form-control-sm accountHead" required>
                                                                    <option value="" disabled selected>Select Head</option>
                                                                    @foreach ($AccountHeads as $head)
                                                                    <option value="{{ $head->id }}">{{ $head->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <select name="account_id[]" class="form-control form-control-sm accountSub" required>
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
                                                                <input type="number" step="0.01" id="extraCost" name="wht"
                                                                    class="form-control form-control-sm" value="0">
                                                                <select id="whtType" class="form-select form-select-sm" style="max-width:80px;">
                                                                    <option value="pkr" selected>PKR</option>
                                                                    <option value="percent">%</option>
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

                                <div class="d-flex gap-2 mt-4">
                                    <button type="submit" class="btn btn-success btn-lg w-100">
                                        <i class="fa fa-save me-1"></i> Save Purchase
                                    </button>
                                    <button type="button" class="btn btn-primary btn-lg w-100" disabled title="Post from list after saving">
                                        <i class="fa fa-send me-1"></i> Post
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
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
            const purchaseNet = num($row.find('.purchase_net_amount').val() || $row.find('.price').val());
            const purchaseRetail = num($row.find('.purchase_retail_price').val());
            const discPercent = num($row.find('.item_disc').val());
            const amount = num($row.find('.amount').val()); // ✅ New amount input

            // discount amount = retail * % * qty
            const discAmount = (purchaseRetail * (discPercent / 100)) * qty;

            // agar amount fill hai toh wahi use karo, warna qty * net price
            let rowTotal = amount > 0 ? (amount * qty) - discAmount : (purchaseNet * qty) - discAmount;
            if (rowTotal < 0) rowTotal = 0;

            $row.find('.disc_amount').val(discAmount.toFixed(2));
            $row.find('.row-total').val(rowTotal.toFixed(2));
        }

        function recalcSummary() {
            let sub = 0;
            $('#purchaseItems .row-total').each(function() {
                sub += num($(this).val());
            });
            $('#subtotal').val(sub.toFixed(2));

            const oDisc = num($('#overallDiscount').val());

            // ✅ WHT handle (PKR ya %)
            let whtVal = num($('#extraCost').val());
            let whtType = $('#whtType').val();
            if (whtType === 'percent') {
                whtVal = (sub * whtVal / 100);
            }

            // 🔴 Pehle yeh galat tha (sub - oDisc + whtVal)
            // ✅ Sahi: WHT bhi discount ki tarah minus hoga
            const net = (sub - oDisc - whtVal);

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
        <td><input type="number" name="amount[]" class="form-control form-control-sm amount" value="0" step="0.01"></td>
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
        $('#purchaseItems tr').each(function() {
            recalcRow($(this));
        });
        recalcSummary();


        $('#addAccountRow').on('click', function() {
            let newRow = `
        <tr>
            <td>
                <select name="account_head_id[]" class="form-control form-control-sm accountHead" required>
                    <option value="" disabled selected>Select Head</option>
                    @foreach ($AccountHeads as $head)
                        <option value="{{ $head->id }}">{{ $head->name }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <select name="account_id[]" class="form-control form-control-sm accountSub" required>
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

            // ✅ Accounts total ko discount me dal do
            $('#overallDiscount').val(total.toFixed(2));

            recalcSummary(); // summary bhi refresh
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