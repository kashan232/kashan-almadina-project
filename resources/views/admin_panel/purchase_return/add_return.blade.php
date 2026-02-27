@extends('admin_panel.layout.app')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container .select2-selection--single { height: 31px !important; border: 1px solid #ced4da; }
    .select2-container .select2-selection--single .select2-selection__rendered { line-height: 31px !important; padding-left: 8px; }
    .select2-container .select2-selection--single .select2-selection__arrow { height: 31px !important; }
    th { font-weight: 500 !important; font-size: 13px; }
    .card { border-radius: 8px; }
    .manual-only { display: none; }
</style>

@section('content')
<div class="main-content bg-white">
    <div class="main-content-inner">
        <div class="container-fluid pt-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="page-title ml-4">Create Purchase Return</h6>
                    <span class="badge bg-danger ms-3" style="font-size:14px;">Return No: {{ $nextInvoice }}</span>
                </div>
                <div class="d-flex gap-2">
                    <div class="btn-group btn-group-sm" role="group">
                        <input type="radio" class="btn-check" name="return_mode" id="mode_invoice" value="invoice" checked autocomplete="off">
                        <label class="btn btn-outline-primary" for="mode_invoice">Invoice Return</label>

                        <input type="radio" class="btn-check" name="return_mode" id="mode_manual" value="manual" autocomplete="off">
                        <label class="btn btn-outline-primary" for="mode_manual">Manual Return</label>
                    </div>
                    <a href="{{ route('purchase.return.home') }}" class="btn btn-sm btn-dark px-3">
                         Return List
                    </a>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form id="returnForm" action="{{ route('purchase.return.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="purchase_id" id="purchase_id">
                        
                        <!-- Header Selection -->
                        <div class="row g-3 mb-4 p-3 bg-light rounded shadow-sm">
                            <div class="col-md-3 invoice-only" id="invoice_col">
                                <label class="form-label small fw-bold text-muted">Select Purchase Invoice</label>
                                <select id="purchase_invoice_select" class="form-select form-select-sm select2">
                                    <option value="">Select Invoice</option>
                                    @foreach($purchases as $p)
                                        <option value="{{ $p->invoice_no }}">{{ $p->invoice_no }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2 manual-only" id="vendor_type_col">
                                <label class="form-label small fw-bold text-muted">Party Type</label>
                                <select name="vendor_type" id="vendor_type_select" class="form-select form-select-sm">
                                    <option value="vendor">Vendor</option>
                                    <option value="customer">Customer</option>
                                </select>
                            </div>

                            <div class="col-md-3 manual-only" id="party_col">
                                <label class="form-label small fw-bold text-muted">Select Party</label>
                                <select name="party_id" id="party_select" class="form-select form-select-sm select2">
                                    <option value="">Select Party</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted">Return Date</label>
                                <input name="current_date" value="{{ date('Y-m-d') }}" type="date" class="form-control form-control-sm" required>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted">Warehouse</label>
                                <select name="warehouse_id" id="warehouse_select" class="form-select form-select-sm select2" required>
                                    <option value="">Select Warehouse</option>
                                    @foreach($warehouses as $w)
                                        <option value="{{ $w->id }}">{{ $w->warehouse_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 invoice-only" id="display_col">
                                <label class="form-label small fw-bold text-muted">Loaded Party</label>
                                <input id="party_name_display" type="text" class="form-control form-control-sm bg-white" readonly placeholder="Auto-fill">
                            </div>

                            <div class="col-md-12 manual-only mt-2" id="manual_search_col">
                                <label class="form-label small fw-bold text-muted">Search & Add Product</label>
                                <select id="manual_product_search" class="form-select form-select-sm"></select>
                            </div>
                        </div>

                        <!-- Items Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm text-center align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width: 250px;">Product</th>
                                        <th>Purchase Price</th>
                                        <th>Retail Price</th>
                                        <th>Disc (%)</th>
                                        <th>Disc Amt</th>
                                        <th class="invoice-only">Orig Qty</th>
                                        <th>Return Qty</th>
                                        <th>Row Total</th>
                                        <th>X</th>
                                    </tr>
                                </thead>
                                <tbody id="purchaseItems">
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">No invoice selected yet.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Bottom Section -->
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded h-100">
                                    <label class="form-label fw-bold">Return Remarks</label>
                                    <textarea name="remarks" class="form-control" rows="4" placeholder="Reason for return..."></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-0 bg-light">
                                    <div class="card-body">
                                        <table class="table table-sm table-borderless mb-0">
                                            <tr>
                                                <th class="text-secondary">Subtotal</th>
                                                <td><input type="text" id="subtotal" name="subtotal" class="form-control form-control-sm text-end bg-white" readonly value="0"></td>
                                            </tr>
                                            <tr>
                                                <th class="text-secondary">Total Discount</th>
                                                <td><input type="text" id="overallDiscount" name="discount" class="form-control form-control-sm text-end bg-white" readonly value="0"></td>
                                            </tr>
                                            <tr>
                                                <th class="text-secondary">WHT</th>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" step="0.01" id="whtAmount" name="wht" class="form-control text-end" value="0">
                                                        <span class="input-group-text">PKR</span>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr class="border-top">
                                                <th class="h5 fw-bold pt-3">Net Return Amount</th>
                                                <td class="pt-3"><input type="text" id="netAmount" name="net_amount" class="form-control form-control-lg fw-bold text-end text-danger bg-white" readonly value="0"></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 justify-content-end mt-4">
                            <button type="submit" class="btn btn-outline-success btn-lg px-5 shadow-sm" id="submitBtn" disabled>
                                <i class="fa fa-save me-1"></i> Save Return
                            </button>
                            <button type="button" class="btn btn-primary btn-lg px-5 shadow-sm" disabled title="Post from list after saving">
                                <i class="fa fa-send me-1"></i> Post
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@if(session('error'))
<script>
    Swal.fire({ icon: 'error', title: 'Error', text: @json(session('error')), confirmButtonColor: '#d33' });
</script>
@endif

@if (session('success'))
<script>
    Swal.fire({ icon: 'success', title: 'Success', text: @json(session('success')), confirmButtonColor: '#3085d6' });
</script>
@endif
<script>
$(document).ready(function() {
    $('.select2').select2({ width: '100%' });

    const vendors = @json($vendors);
    const customers = @json($customers);

    // Mode Switching
    $('input[name="return_mode"]').on('change', function() {
        let mode = $(this).val();
        if (mode === 'manual') {
            $('.manual-only').show();
            $('.invoice-only').hide();
            $('#purchase_id').val('');
            $('#purchaseItems').empty();
            if ($('#purchaseItems tr').length === 0) {
                 $('#purchaseItems').html('<tr><td colspan="9" class="text-center text-muted py-4">Add products manually.</td></tr>');
            }
            $('#submitBtn').attr('disabled', false);
            updatePartyList();
        } else {
            $('.manual-only').hide();
            $('.invoice-only').show();
            $('#purchaseItems').html('<tr><td colspan="9" class="text-center text-muted py-4">No invoice selected yet.</td></tr>');
            $('#submitBtn').attr('disabled', true);
        }
        recalcSummary();
    });

    $('#vendor_type_select').on('change', function() {
        updatePartyList();
    });

    function updatePartyList() {
        let type = $('#vendor_type_select').val();
        let list = (type === 'vendor') ? vendors : customers;
        let html = '<option value="">Select Party</option>';
        list.forEach(item => {
            html += `<option value="${item.id}">${item.name || item.customer_name}</option>`;
        });
        $('#party_select').html(html).trigger('change');
    }

    // Manual Product Search
    $('#manual_product_search').select2({
        placeholder: "Search Product to add...",
        width: '100%',
        ajax: {
            url: "{{ route('search-products') }}",
            dataType: 'json',
            delay: 250,
            data: params => ({ q: params.term }),
            processResults: data => ({
                results: data.map(item => ({
                    id: item.id,
                    text: item.name,
                    price: item.purchase_net_amount,
                    retail: item.purchase_retail_price
                }))
            })
        }
    }).on('select2:select', function(e) {
        let data = e.params.data;
        
        // Prevent Adding same product twice
        let existing = false;
        $('#purchaseItems input[name="product_id[]"]').each(function() {
            if ($(this).val() == data.id) {
                existing = true;
                return false;
            }
        });

        if (existing) {
            Swal.fire({
                icon: 'warning',
                title: 'Already Added',
                text: 'This product is already in the list.',
                timer: 2000,
                showConfirmButton: false
            });
            $(this).val(null).trigger('change');
            return;
        }
        
        if ($('#purchaseItems .text-muted').length > 0) $('#purchaseItems').empty();
        
        appendRow({
            product_id: data.id,
            product_name: data.text,
            price: data.price,
            retail_price: data.retail,
            discount_percent: 0,
            item_discount: 0,
            qty: 1
        }, true);

        // Clear search box and keep it focused for next product
        $(this).val(null).trigger('change');
    });

    // Invoice Selection
    $('#purchase_invoice_select').on('change', function() {
        let inv = $(this).val();
        if (!inv) return;

        $.get("{{ url('/purchase-returns/get-purchase') }}/" + inv, function(res) {
            $('#purchaseItems').empty();
            $('#purchase_id').val(res.purchase.id);
            $('#party_name_display').val(res.party_name);
            $('#warehouse_select').val(res.warehouse_id).trigger('change');

            if (res.items.length === 0) {
                $('#purchaseItems').html('<tr><td colspan="9" class="text-danger p-3">This purchase has no items!</td></tr>');
                $('#submitBtn').attr('disabled', true);
            } else {
                res.items.forEach(item => {
                    appendRow(item, false);
                });
                $('#submitBtn').attr('disabled', false);
            }
            recalcSummary();
        });
    });

    function appendRow(item, isManual) {
        let discAmt = item.qty > 0 ? (item.item_discount / item.qty).toFixed(2) : 0;
        let html = `
        <tr>
            <td>
                <input type="text" class="form-control form-control-sm bg-white" value="${item.product_name}" readonly title="${item.product_name}">
                <input type="hidden" name="product_id[]" value="${item.product_id}">
            </td>
            <td><input type="number" step="0.01" name="price[]" class="form-control form-control-sm price text-end" value="${item.price}"></td>
            <td><input type="number" step="0.01" name="retail_price[]" class="form-control form-control-sm retail_price text-end bg-light" value="${item.retail_price}" readonly></td>
            <td><input type="number" step="0.01" name="discount_percent[]" class="form-control form-control-sm discount_percent text-center" value="${item.discount_percent}"></td>
            <td><input type="number" step="0.01" name="item_disc_amount[]" class="form-control form-control-sm disc_amount text-end bg-light" value="${discAmt}" readonly></td>
            <td class="invoice-only"><input type="text" class="form-control form-control-sm bg-light text-center" value="${item.qty}" readonly></td>
            <td><input type="number" name="qty[]" class="form-control form-control-sm quantity text-center" value="${isManual ? 1 : item.qty}" ${isManual ? '' : 'max="'+item.qty+'"'} min="0"></td>
            <td><input type="text" name="line_total[]" class="form-control form-control-sm row-total text-end bg-white" readonly value="0"></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="fa fa-times"></i></button></td>
        </tr>`;
        $('#purchaseItems').append(html);
        if ($('input[name="return_mode"]:checked').val() === 'manual') {
            $('.invoice-only').hide();
        }
        recalcRow($('#purchaseItems tr:last'));
    }

    $(document).on('click', '.remove-row', function() {
        $(this).closest('tr').remove();
        recalcSummary();
    });

    $(document).on('input', '.quantity, .price, .discount_percent, #whtAmount', function() {
        let row = $(this).closest('tr');
        if (row.length) recalcRow(row);
        recalcSummary();
    });

    function recalcRow($row) {
        let qty = parseFloat($row.find('.quantity').val()) || 0;
        let price = parseFloat($row.find('.price').val()) || 0;
        let retail = parseFloat($row.find('.retail_price').val()) || 0;
        let discPercent = parseFloat($row.find('.discount_percent').val()) || 0;

        let discBase = (retail > 0) ? retail : price;
        let perUnitDisc = (discBase * discPercent) / 100;
        
        let netUnitPrice = price - perUnitDisc;
        let total = netUnitPrice * qty;

        $row.find('.disc_amount').val(perUnitDisc.toFixed(2));
        $row.find('.row-total').val(total.toFixed(2));
    }

    function recalcSummary() {
        let subtotal = 0;
        let discount = 0;

        $('#purchaseItems tr').each(function() {
            let qty = parseFloat($(this).find('.quantity').val()) || 0;
            let price = parseFloat($(this).find('.price').val()) || 0;
            let discAmt = parseFloat($(this).find('.disc_amount').val()) || 0;
            
            subtotal += (price * qty);
            discount += (discAmt * qty);
        });

        let wht = parseFloat($('#whtAmount').val()) || 0;
        let net = subtotal - discount - wht;

        $('#subtotal').val(subtotal.toFixed(2));
        $('#overallDiscount').val(discount.toFixed(2));
        $('#netAmount').val(net.toFixed(2));
    }
});
</script>
@endsection
