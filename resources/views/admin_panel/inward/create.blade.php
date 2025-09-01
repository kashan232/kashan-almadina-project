@extends('admin_panel.layout.app')

@section('content')
    <style>
        /* Table aur container ka overflow visible rakho */

        /* --- Search result dropdown --- */

        .searchResults {
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            max-height: 200px;
            /* overflow-y: auto; */
            background: #fff;
            border: 1px solid #ccc;
            z-index: 999999 !important;
        }

        /* --- Table ke andar overflow na cut ho --- */
        .table-responsive,
        .table,
        .table-bordered,
        #gatepassItems {
            overflow: visible !important;
            position: relative !important;
        }

        /* --- Compact remove button --- */
        .remove-row {
            min-height: 30px;
            min-width: 30px;
            padding: 2px 6px;
            font-size: 14px;
        }
    </style>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container">
                <div class="row">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="page-title">Add Inward Gatepass</h5>
                        <a href="{{ route('InwardGatepass.home') }}" class="btn btn-danger">Back</a>
                    </div>

                    <div class="col-lg-12 col-md-12 mb-30">
                        <div class="card">
                            <div class="card-body">

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
                                    <div class="alert alert-success">{{ session('success') }}</div>
                                @endif

                                <form action="{{ route('store.InwardGatepass') }}" method="POST" id="gatepassForm">
                                    @csrf

                                    <!-- Top fields -->
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-3">
                                            <label>Date</label>
                                            <input type="date" name="gatepass_date" class="form-control"
                                                value="{{ old('gatepass_date', date('Y-m-d')) }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label>Branch</label>
                                            <select name="branch_id" class="form-control select2">
                                                <option value="">Select One</option>
                                                @foreach ($branches as $item)
                                                    <option value="{{ $item->id }}"
                                                        {{ old('branch_id') == $item->id ? 'selected' : '' }}>
                                                        {{ $item->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label>Warehouse</label>
                                            <select name="warehouse_id" class="form-control select2">
                                                <option value="">Select One</option>
                                                @foreach ($warehouses as $item)
                                                    <option value="{{ $item->id }}"
                                                        {{ old('warehouse_id') == $item->id ? 'selected' : '' }}>
                                                        {{ $item->warehouse_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label>Vendor</label>
                                            <select name="vendor_id" class="form-control select2">
                                                <option value="">Select One</option>
                                                @foreach ($vendors as $item)
                                                    <option value="{{ $item->id }}"
                                                        {{ old('vendor_id') == $item->id ? 'selected' : '' }}>
                                                        {{ $item->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label>Transport Name</label>
                                            <input type="text" name="transport_name" class="form-control"
                                                value="{{ old('transport_name') }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label>Bilty No</label>
                                            <input type="text" name="bilty_no" class="form-control"
                                                value="{{ old('bilty_no') }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label>Note</label>
                                            <input type="text" name="note" class="form-control"
                                                value="{{ old('note') }}">
                                        </div>
                                    </div>

                                    <!-- Product Table -->
                                    <!-- Items Table -->
                                    <div style="max-height: 400px;  position: relative; overflow-x: visible !important;">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr class="text-center">
                                                    <th>Product</th>
                                                    <th>Item Code</th>
                                                    <th>Brand</th>
                                                    <th>Unit</th>
                                                    <th>Qty</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="gatepassItems">
                                                <tr>
                                                      <td>
                                                                    <input type="hidden" name="product_id[]"
                                                                        class="product_id">
                                                                    <input type="text"
                                                                        class="form-control form-control-sm productSearch"
                                                                        placeholder="Search product..." autocomplete="off">
                                                                    <ul class="searchResults list-group mt-1"></ul>
                                                                </td>
                                                    <td><input type="text" name="item_code[]" class="form-control"
                                                            readonly></td>
                                                    <td><input type="text" name="brand[]" class="form-control" readonly>
                                                    </td>
                                                    <td><input type="text" name="unit[]" class="form-control" readonly>
                                                    </td>
                                                    <td><input type="number" name="qty[]" class="form-control quantity"
                                                            min="1" value="1"></td>
                                                    <td class="text-end">
                                                        <button type="button"
                                                            class="btn btn-sm btn-danger remove-row">X</button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100 mt-3">Submit Gatepass</button>
                                </form>

                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
 <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Success & Error Messages --}}
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
                html: {!! json_encode(implode('<br>', $errors->all())) !!},
                confirmButtonColor: '#d33',
            });
        </script>
    @endif

    {{-- Cancel Button Confirmation --}}
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

            // ---------- Helpers ----------
            function num(n) {
                return isNaN(parseFloat(n)) ? 0 : parseFloat(n);
            }

            function recalcRow($row) {
                const qty = num($row.find('.quantity').val());
                const price = num($row.find('.price').val());
                const disc = num($row.find('.item_disc').val()); // absolute PKR per item
                let total = (qty * price) - disc;
                if (total < 0) total = 0;
                $row.find('.row-total').val(total.toFixed(2));
            }

            function recalcSummary() {
                let sub = 0;
                $('#purchaseItems .row-total').each(function() {
                    sub += num($(this).val());
                });
                $('#subtotal').val(sub.toFixed(2));

                const oDisc = num($('#overallDiscount').val());
                const xCost = num($('#extraCost').val());
                const net = (sub - oDisc + xCost);
                $('#netAmount').val(net.toFixed(2));
            }

            function appendBlankRow() {
                const newRow = `
      <tr>
        <td>
            <input type="hidden" name="product_id[]" class="product_id">
          <input type="text" class="form-control productSearch" placeholder="Enter product name..." autocomplete="off">
          <ul class="searchResults list-group mt-1"></ul>
        </td>
        <td class="uom border">
            <input type="hidden"  name="brand[]" class="product_id">
            <input type="text" class="form-control" readonly>
        </td>
        <td><input type="number" step="0.01" name="price[]" class="form-control price" value="" ></td>
        <td><input type="number" step="0.01" name="item_disc[]" class="form-control item_disc" value=""></td>
        <td class="qty"><input type="number" name="qty[]" class="form-control quantity" value="1" min="1"></td>
        <td class="total border"><input type="text" name="total[]" class="form-control row-total" readonly></td>
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

                // Keyboard navigation (Arrow Up/Down + Enter)
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

                // Normal fetch
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
                        console.log(data);

                        let html = '';
                       (data || []).forEach(p => {
    const brand = p.brand?.name ?? '';
    const price = p.latest_price?.purchase_net_amount ?? 0;
    const name = p.name ?? '';
    const id = p.id ?? '';

    html += `
      <li class="list-group-item search-result-item"
          tabindex="0"
          data-product-id="${id}"
          data-product-name="${name}"
          data-product-brand="${brand}"
          data-price="${price}">
        ${name} - Rs. ${brand}
      </li>`;
});

                        $box.html(html);

                        // first item active for quick Enter
                        $box.children('.search-result-item').first().addClass('active');
                    },
                    error: function() {
                        $box.empty();
                    }
                });
            });

            // Click/Enter on suggestion
            $(document).on('click', '.search-result-item', function() {
                const $li = $(this);
                const $row = $li.closest('tr');

                $row.find('.productSearch').val($li.data('product-name'));
                // $row.find('.item_code input').val($li.data('product-code'));
                $row.find('.uom input').val($li.data('product-uom'));
                // $row.find('.unit input').val($li.data('product-unit'));
                $row.find('.price').val($li.data('price'));

                $row.find('.product_id').val($li.data('product-id'));

                // reset qty & discount for fresh calc
                $row.find('.quantity').val(1);
                $row.find('.item_disc').val(0);

                recalcRow($row);
                recalcSummary();

                // clear results
                $row.find('.searchResults').empty();

                // append new blank row and focus its search
                appendBlankRow();
                $('#purchaseItems tr:last .productSearch').focus();
            });

            // Also allow keyboard Enter selection when list focused
            $(document).on('keydown', '.searchResults .search-result-item', function(e) {
                if (e.key === 'Enter') {
                    $(this).trigger('click');
                }
            });

            // Row calculations
            $('#purchaseItems').on('input', '.quantity, .price, .item_disc', function() {
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

            // init first row values
            recalcRow($('#purchaseItems tr:first'));
            recalcSummary();
        });
    </script>



