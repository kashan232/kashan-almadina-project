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
                    <a href="{{ route('purchase.return.home') }}" class="btn btn-sm btn-dark px-3" id="returnListBtn">
                         <i class="fa fa-list me-1"></i> Return List
                         <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+L</kbd>
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
                            <div class="col-md-2" id="vendor_type_col">
                                <label class="form-label small fw-bold text-muted">Party Type</label>
                                <select name="vendor_type" id="vendor_type_select" class="form-select form-select-sm">
                                    <option value="" disabled selected>Select</option>
                                    <option value="vendor">Vendor</option>
                                    <option value="customer">Customer</option>
                                    <option value="walkin">Walking Customer</option>
                                </select>
                            </div>

                            <div class="col-md-3" id="party_col">
                                <label class="form-label small fw-bold text-muted">Select Party</label>
                                <select name="party_id" id="party_select" class="form-select form-select-sm select2">
                                    <option value="">Select Party</option>
                                </select>
                            </div>

                            <div class="col-md-3 invoice-only" id="invoice_col">
                                <label class="form-label small fw-bold text-muted">Select Purchase Invoice</label>
                                <select id="purchase_invoice_select" class="form-select form-select-sm select2">
                                    <option value="">Select Invoice</option>
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

                            <div class="col-md-3 invoice-only" id="display_col" style="display:none;">
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
                                                        <input type="number" step="0.01" id="whtPercent" class="form-control text-end" placeholder="%" value="0">
                                                        <select id="whtType" class="form-select form-select-sm" style="max-width:70px;">
                                                            <option value="percent" selected>%</option>
                                                            <option value="amount">PKR</option>
                                                        </select>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="text-secondary">WHT Amount</th>
                                                <td>
                                                    <input type="text" id="whtAmount" name="wht" class="form-control form-control-sm text-end bg-white" readonly value="0">
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
                            <button type="button" class="btn btn-sm btn-warning rounded-pill px-4 shadow-sm" id="saveDraftBtn" disabled>
                                <i class="fa fa-floppy-o me-1"></i> Save Draft
                                <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>
                            </button>
                            <button type="button" id="previewPrintBtn" class="btn btn-sm btn-outline-dark rounded-pill px-4 shadow-sm" onclick="showPreviewModal()">
                                <i class="fa fa-print me-1"></i> Print Preview
                                <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+P</kbd>
                            </button>
                            <button type="button" class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm" id="postBtn" disabled title="Post from list after saving">
                                <i class="fa fa-send me-1"></i> Post
                                <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+&#8629;</kbd>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print Preview Modal -->
<div class="modal fade" id="printPreviewModal" tabindex="-1" aria-labelledby="printPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="printPreviewModalLabel">Purchase Return Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="printArea">
                <!-- Preview Content Injected Here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" onclick="printDiv('printArea')">Print</button>
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

    const allPurchases = @json($purchases);
    
    // =============================================
    //  SAVED PURCHASE STATE (after AJAX save)
    // =============================================
    var _savedReturnId = null;

    function showToast(msg, type) {
        type = type || 'success';
        var icon = type === 'success' ? 'fa-check-circle' : 'fa-times-circle';
        var color = type === 'success' ? '#28a745' : '#dc3545';
        var $toast = $('<div>').css({
            position: 'fixed', top: '20px', right: '20px', zIndex: 9999,
            background: color, color: '#fff',
            padding: '12px 20px', borderRadius: '8px',
            boxShadow: '0 4px 15px rgba(0,0,0,.2)',
            fontSize: '14px', fontWeight: '500',
            display: 'flex', alignItems: 'center', gap: '8px',
            minWidth: '280px'
        }).html('<i class="fa ' + icon + '"></i> ' + msg);
        $('body').append($toast);
        setTimeout(function() { $toast.fadeOut(400, function(){ $(this).remove(); }); }, 3500);
    }
    
    function ajaxSaveDraft() {
        if(!$('#party_select').val()) {
            showToast('⚠️ Please select a party', 'error');
            return;
        }

        var $form = $('#returnForm');
        $('#saveDraftBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Saving...');

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                if(res.success) {
                    _savedReturnId = res.id;
                    showToast('✅ Draft Saved — ' + res.message, 'success');

                    $('#postBtn')
                        .show()
                        .prop('disabled', false)
                        .removeClass('btn-primary')
                        .addClass('btn-success')
                        .html('<i class="fa fa-send me-1"></i> Post <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+↵</kbd>');
                } else {
                    showToast('❌ ' + (res.message || 'Error saving draft.'), 'error');
                }
            },
            error: function(xhr) {
                var msg = 'Save failed.';
                try { msg = JSON.parse(xhr.responseText).message || msg; } catch(e){}
                showToast('❌ ' + msg, 'error');
            },
            complete: function() {
                $('#saveDraftBtn').prop('disabled', false)
                    .html('<i class="fa fa-floppy-o me-1"></i> Save Draft <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>');
            }
        });
    }

    function doPost() {
        if(!_savedReturnId) {
            showToast('⚠️ Please save draft first before posting.', 'error');
            return;
        }
        $('#postBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Posting...');

        $.ajax({
            url: '/purchase-returns/post/' + _savedReturnId,
            type: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                showToast('✅ Return posted successfully! Redirecting...', 'success');
                setTimeout(function() {
                    window.location.href = "{{ route('purchase.return.add') }}";
                }, 2000);
            },
            error: function(xhr) {
                var msg = 'Post failed.';
                try {
                    var r = JSON.parse(xhr.responseText);
                    msg = r.message || r.error || msg;
                } catch(e) {}
                showToast('❌ ' + msg, 'error');
                $('#postBtn').prop('disabled', false)
                    .html('<i class="fa fa-send me-1"></i> Post <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+↵</kbd>');
            }
        });
    }

    $('#saveDraftBtn').on('click', function(e) { e.preventDefault(); ajaxSaveDraft(); });
    $('#postBtn').on('click', function(e) { e.preventDefault(); doPost(); });

    // --- BLOCK ENTER KEY (prevents accidental form submit on qty, price etc) ---
    $(document).on('keydown', function(e) {
        if (e.key === 'Enter') {
            var $t = $(e.target);
            // Only allow Enter in textarea
            if (!$t.is('textarea')) {
                e.preventDefault();
                return false;
            }
        }

        // --- CTRL+S = Submit form ---
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            ajaxSaveDraft();
        }

        // --- CTRL+L = Return List ---
        if (e.ctrlKey && e.key.toLowerCase() === 'l') {
            e.preventDefault();
            window.location.href = "{{ route('purchase.return.home') }}";
        }

        // --- CTRL+P = Print Preview ---
        if (e.ctrlKey && e.key.toLowerCase() === 'p') {
            e.preventDefault();
            $('#previewPrintBtn').click();
        }
    });

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
            $('#saveDraftBtn').attr('disabled', false);
        } else {
            $('.manual-only').hide();
            $('.invoice-only').show();
            $('#invoice_col').show();
            $('#purchaseItems').html('<tr><td colspan="9" class="text-center text-muted py-4">No invoice selected yet.</td></tr>');
            $('#saveDraftBtn').attr('disabled', true);
        }
        recalcSummary();
    });

    $('#vendor_type_select').on('change', function() {
        updatePartyList();
        filterInvoices();
    });

    $('#party_select').on('change', function() {
        if ($('input[name="return_mode"]:checked').val() === 'invoice') {
            filterInvoices();
        }
    });

    function updatePartyList() {
        let type = $('#vendor_type_select').val();
        let list = [];
        
        if (type === 'vendor') {
            list = vendors;
        } else if (type === 'customer') {
            list = customers; // Ensure customers include walking if not filtered out, though backend might separate them via customer_type
        } else if (type === 'walkin') {
            list = customers.filter(c => (c.customer_type || '').toLowerCase().includes('walking'));
        }

        let html = '<option value="">Select Party</option>';
        list.forEach(item => {
            html += `<option value="${item.id}">${item.name || item.customer_name}</option>`;
        });
        $('#party_select').html(html).trigger('change.select2');
    }

    function filterInvoices() {
        let type = $('#vendor_type_select').val();
        let partyId = $('#party_select').val();

        if (!type || !partyId) {
            $('#purchase_invoice_select').html('<option value="">Select Invoice</option>').trigger('change.select2');
            return;
        }

        let targetTypeClass = '';
        if (type === 'vendor') targetTypeClass = 'Vendor';
        else if (type === 'customer' || type === 'walkin') targetTypeClass = 'Customer'; // Usually App\Models\Customer

        let filtered = allPurchases.filter(p => {
            if (!p.purchasable_type) return false;
            return p.purchasable_type.endsWith(targetTypeClass) && p.purchasable_id == partyId;
        });

        let html = '<option value="">Select Invoice</option>';
        if (filtered.length === 0) {
            html += '<option value="" disabled>No invoices found</option>';
        } else {
            filtered.forEach(p => {
                html += `<option value="${p.invoice_no}">${p.invoice_no}</option>`;
            });
        }
        $('#purchase_invoice_select').html(html).trigger('change.select2');
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

            if (res.wht_type === 'percent') {
                 $('#whtType').val('percent');
                 $('#whtPercent').val(res.wht_percent > 0 ? res.wht_percent : (res.wht > 0 ? res.wht : 0));
            } else {
                 $('#whtType').val('amount');
                 $('#whtPercent').val(res.wht);
            }

            if (res.items.length === 0) {
                $('#purchaseItems').html('<tr><td colspan="9" class="text-danger p-3">This purchase has no items!</td></tr>');
                $('#saveDraftBtn').attr('disabled', true);
            } else {
                res.items.forEach(item => {
                    appendRow(item, false);
                });
                $('#saveDraftBtn').attr('disabled', false);
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

    $(document).on('input', '.quantity, .price, .discount_percent, #whtPercent, #whtType', function() {
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

        let whtVal = parseFloat($('#whtPercent').val()) || 0;
        let whtType = $('#whtType').val() || 'percent';
        let whtAmt = 0;

        if (whtType === 'percent') {
            whtAmt = Math.max(0, subtotal - discount) * whtVal / 100;
        } else {
            whtAmt = whtVal;
        }

        $('#whtAmount').val(whtAmt.toFixed(2));
        let net = subtotal - discount - whtAmt;

        $('#subtotal').val(subtotal.toFixed(2));
        $('#overallDiscount').val(discount.toFixed(2));
        $('#netAmount').val(net.toFixed(2));
    }
});

// --- Print Preview Functions ---
window.showPreviewModal = function() {
    try {
        const date = $('input[name="current_date"]').val();
        const vendorType = $('#vendor_type_select option:selected').text();
        const vendorName = $('#party_select option:selected').text() || '-';
        const invoiceNo = "{{ $nextInvoice ?? 'RET-XXX' }}"; 

        if(!vendorName || vendorName === 'Select Party') {
            alert('Please select a party first.');
            return;
        }

        let itemsHtml = '';
        $('#purchaseItems tr').each(function(index) {
            const productName = $(this).find('input[type="text"]').first().val();
            const qty = $(this).find('.quantity').val();
            const price = $(this).find('.price').val();
            const total = $(this).find('.row-total').val();

            if(productName && qty) {
                 itemsHtml += `
                    <tr>
                        <td style="padding: 4px; border: 1px solid #ddd; text-align: center;">${index + 1}</td>
                        <td style="padding: 4px; border: 1px solid #ddd;">${productName}</td>
                        <td style="padding: 4px; border: 1px solid #ddd; text-align: center;">${qty}</td>
                        <td style="padding: 4px; border: 1px solid #ddd; text-align: right;">${price}</td>
                        <td style="padding: 4px; border: 1px solid #ddd; text-align: right;">${total}</td>
                    </tr>
                 `;
            }
        });

        const subtotal = $('#subtotal').val();
        const discount = $('#overallDiscount').val();
        const net = $('#netAmount').val();
        const wht = $('#whtAmount').val();

        const html = `
            <div style="font-family: 'Segoe UI', Arial, sans-serif; color: #000; padding: 20px; border: 1px solid #ccc;">
                <div style="text-align: center; margin-bottom: 25px; border-bottom: 3px double #000; padding-bottom: 15px;">
                    <h1 style="margin: 0; font-weight: 800; text-transform: uppercase; font-size: 28px; letter-spacing: 1px;">AL Madina Traders</h1>
                    <div style="font-size: 16px; margin-top: 5px; font-weight: 500;">Deals in: UPS, Solar, Batteries & Electronics</div>
                    <div style="font-size: 15px; margin-top: 3px;"><strong>Phone:</strong> 0300-1234567, 0321-7654321</div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                    <div>
                        <h3 style="margin: 0; font-weight: bold; text-transform: uppercase; border-bottom: 2px solid #000; display: inline-block; padding-bottom: 2px; margin-bottom: 5px;">Purchase Return Receipt</h3>
                        <div style="font-size: 15px; margin-top: 8px;"><strong>Party:</strong> ${vendorName} (${vendorType})</div>
                    </div>
                    <div style="text-align: right;">
                        <h4 style="margin: 0; color: #000; font-weight: bold; font-size: 18px;">Return #${invoiceNo}</h4>
                        <div style="font-size: 15px; margin-top: 8px;"><strong>Date:</strong> ${date}</div>
                    </div>
                </div>

                <table style="width: 100%; border-collapse: collapse; font-size: 14px; margin-bottom: 20px;">
                    <thead>
                        <tr style="background: #f0f0f0; border-top: 2px solid #000; border-bottom: 2px solid #000;">
                            <th style="padding: 8px; border-right: 1px solid #ccc; width: 40px; text-align: center; font-weight: bold;">#</th>
                            <th style="padding: 8px; border-right: 1px solid #ccc; text-align: left; font-weight: bold;">Item Description</th>
                            <th style="padding: 8px; border-right: 1px solid #ccc; width: 80px; text-align: center; font-weight: bold;">Qty</th>
                            <th style="padding: 8px; border-right: 1px solid #ccc; width: 110px; text-align: right; font-weight: bold;">Rate</th>
                            <th style="padding: 8px; width: 130px; text-align: right; font-weight: bold;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${itemsHtml}
                    </tbody>
                    <tfoot>
                         <tr>
                            <td colspan="3" style="border-top: 2px solid #000; padding-top: 15px;">
                                <small style="color: #555;">Generated by System</small>
                            </td>
                            <td style="text-align: right; border-top: 2px solid #000; padding: 10px 5px; font-weight: bold;">Subtotal:</td>
                            <td style="text-align: right; border-top: 2px solid #000; padding: 10px 5px; font-weight: bold;">${subtotal}</td>
                         </tr>
                         <tr>
                            <td colspan="3" style="border: none;"></td>
                            <td style="text-align: right; padding: 5px;">Discount:</td>
                            <td style="text-align: right; padding: 5px;">${discount}</td>
                         </tr>
                           <tr>
                            <td colspan="3" style="border: none;"></td>
                            <td style="text-align: right; padding: 5px;">WHT:</td>
                            <td style="text-align: right; padding: 5px;">${wht}</td>
                         </tr>
                         <tr>
                            <td colspan="3" style="border: none;"></td>
                            <td style="text-align: right; padding: 8px 5px; font-weight: bold; font-size: 18px; border-top: 1px solid #ccc; border-bottom: 3px double #000;">Net Total:</td>
                            <td style="text-align: right; padding: 8px 5px; font-weight: bold; font-size: 18px; border-top: 1px solid #ccc; border-bottom: 3px double #000;">${net}</td>
                         </tr>
                    </tfoot>
                </table>
            </div>
        `;

        $('#printArea').html(html);

        const $modal = $('#printPreviewModal');
        if ($modal.length) {
            if (typeof $modal.modal === 'function') {
                $modal.modal('show');
            } else {
                const myModal = new bootstrap.Modal(document.getElementById('printPreviewModal'));
                myModal.show();
            }
        }
    } catch(e) {
        console.error('Error showing preview:', e);
        alert('Error showing preview: ' + e.message);
    }
};

window.printDiv = function(divId) {
    var printContents = document.getElementById(divId).innerHTML;
    var originalContents = document.body.innerHTML;
    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
    window.location.reload(); 
};
</script>
@endsection
