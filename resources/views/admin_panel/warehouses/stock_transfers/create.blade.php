@extends('admin_panel.layout.app')

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .purchase-page.container-fluid { padding: .2rem .35rem !important; }
    .purchase-page .main-content-inner { padding: 0 !important; }
    .purchase-page .form-label { margin-bottom: 0 !important; font-size: .7rem !important; }
    .purchase-page .input-sm,
    .purchase-page .form-control-sm,
    .purchase-page .form-select-sm { height: 24px !important; min-height: 24px !important; font-size: .75rem !important; padding: .1rem .35rem !important; }
    .purchase-page .select2-container--default .select2-selection--single { height: 24px !important; font-size: .75rem !important; border: 1px solid #ced4da; }
    .purchase-page .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 22px !important; padding-left: 6px !important; }
    .purchase-page .select2-container--default .select2-selection--single .select2-selection__arrow { height: 22px !important; }
    .purchase-page .table td, .purchase-page .table th { padding: 2px 4px !important; vertical-align: middle; font-size: .75rem; }
    .purchase-page .card-header { padding: .35rem .5rem !important; }
    .purchase-page .card-body { padding: .5rem !important; }
    .purchase-page .row.g-2, .purchase-page .row.g-3 { --bs-gutter-x: .5rem; --bs-gutter-y: .35rem; }
    .purchase-page .bottom-bar { margin-top: .4rem !important; padding: .75rem !important; }
    .purchase-page .page-head { padding: .35rem .5rem !important; margin-bottom: .35rem !important; }
    .purchase-page .btn-xs { padding: 1px 4px; font-size: .7rem; line-height: 1.2; }
    .posted-watermark {
        position: absolute; top: 50%; left: 50%;
        transform: translate(-50%, -50%) rotate(-30deg);
        font-size: 8rem; color: rgba(220, 53, 69, 0.1); font-weight: 900;
        pointer-events: none; z-index: 1000; text-transform: uppercase;
        display: none; border: 10px solid rgba(220, 53, 69, 0.1);
        padding: 20px 50px; border-radius: 20px;
    }
    .posted-watermark.show { display: block; }
    .form-locked input,
    .form-locked .select2-container--default .select2-selection--single,
    .form-locked .select2-container,
    .form-locked select,
    .form-locked textarea {
        pointer-events: none !important; opacity: 0.85 !important;
        background-color: #f1f3f5 !important; cursor: not-allowed !important;
    }
    .form-locked .remove-row,
    .form-locked #saveDraftBtn { display: none !important; }
    .form-locked #editInvoiceBtn,
    .form-locked #newInvoiceBtn,
    .form-locked #realPrintBtn,
    .form-locked #postBtn,
    .form-locked #exitBtn,
    .form-locked #deleteBtn {
        pointer-events: auto !important; opacity: 1 !important;
    }
</style>

@section('content')
<div class="main-content purchase-page">
    <div class="main-content-inner">
        <div class="container-fluid purchase-page-inner">

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show py-1 mb-1">
                    <i class="fa fa-check-circle me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show py-1 mb-1">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="d-flex justify-content-between align-items-center page-head bg-light rounded border shadow-sm">
                <div class="d-flex align-items-center gap-2">
                    <h6 class="mb-0 fw-bold" style="font-size:.85rem;">Add Stock Transfer</h6>
                    <span id="statusBadge" class="badge bg-warning text-dark px-2 py-0" style="font-size:10px;">Draft</span>
                    <span id="idBadge" class="badge bg-primary px-2 py-0" style="font-size:10px;">ID: NEW</span>
                </div>
                <a href="{{ route('stock_transfers.index') }}" id="listBtn" class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-0" style="font-size:.7rem;">
                    <i class="fa fa-list"></i> List
                    <kbd style="font-size:8px;opacity:.8;margin-left:3px;">Ctrl+L</kbd>
                </a>
            </div>

            <form action="{{ route('stock_transfers.store') }}" method="POST" id="transferForm" class="position-relative">
                @csrf
                
                <div class="posted-watermark" id="postedWatermark">Posted</div>

                <div class="card shadow-sm mb-2">
                    <div class="card-header bg-white">
                        <h6 class="mb-0 fw-bold text-muted" style="font-size:.8rem;"><i class="fa fa-info-circle me-1"></i> Transfer Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            {{-- Entry Date --}}
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted">Entry Date</label>
                                <input name="entry_date" value="{{ date('Y-m-d') }}" type="date" class="form-control form-control-sm" required>
                            </div>
                            {{-- Entry Time --}}
                            <div class="col-md-2">
                                <label class="form-label small fw-bold text-muted">Entry Time</label>
                                <input name="entry_time" value="{{ date('H:i') }}" type="time" class="form-control form-control-sm" required>
                            </div>

                            {{-- From Warehouse --}}
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">From Location <span class="text-danger">*</span></label>
                                <select name="from_warehouse_id" id="from_warehouse_id" class="form-select select2" required>
                                    <option value="" disabled selected>Select Location</option>
                                    @if(auth()->user()->canAccessShop())
                                        <option value="shop">Shop</option>
                                    @endif
                                    @foreach ($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}">{{ $warehouse->warehouse_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- To Warehouse --}}
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">To Warehouse <span class="text-danger">*</span></label>
                                <select name="to_warehouse_id" id="to_warehouse_id" class="form-select select2" required>
                                    <option value="" disabled selected>Select Warehouse</option>
                                    @foreach ($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}">{{ $warehouse->warehouse_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- To Shop --}}
                            <div class="col-md-1 d-flex align-items-end pb-1">
                                @if(auth()->user()->canAccessShop())
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="to_shop" value="1" id="toShop">
                                        <label class="form-check-label fw-bold small" for="toShop">To Shop</label>
                                    </div>
                                @endif
                            </div>

                            {{-- Remarks --}}
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Remarks</label>
                                <input type="text" name="remarks" class="form-control input-sm" placeholder="Optional note...">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-2">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped mb-0" id="itemsTable">
                                <thead class="bg-light">
                                    <tr>
                                        <th style="width:80px;">Item ID</th>
                                        <th style="width:300px;">Product Description</th>
                                        <th style="width:130px;">Available Stock</th>
                                        <th style="width:120px;">Qty to Transfer</th>
                                        <th style="width:50px;" class="text-center"><span style="font-size:9px;font-weight:normal;color:#888;"><kbd style="font-size:8px;padding:0 2px;">Ctrl+I</kbd></span></th>
                                    </tr>
                                </thead>
                                <tbody id="transferItems"></tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-end">Total Qty:</th>
                                        <th>
                                            <input type="text" id="total_qty" class="form-control input-sm text-center fw-bold" readonly value="0">
                                        </th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 justify-content-center bg-light bottom-bar rounded-2 border shadow-sm w-100">
                    <button type="button" id="saveDraftBtn" class="btn btn-primary px-3 fw-bold shadow-sm">
                        <u>S</u>ave <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>
                    </button>
                    <button type="button" id="editInvoiceBtn" class="btn btn-warning px-3 fw-bold text-dark shadow-sm" disabled>
                        <u>E</u>dit <kbd style="font-size:10px;opacity:.8;margin-left:4px;color:#fff;">Ctrl+E</kbd>
                    </button>
                    <button type="button" id="postBtn" class="btn btn-success px-3 fw-bold shadow-sm" disabled>
                        <u>P</u>ost <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+&crarr;</kbd>
                    </button>
                    <button type="button" id="deleteBtn" class="btn btn-danger px-3 fw-bold shadow-sm" disabled title="Delete not available">
                        <u>D</u>elete <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+D</kbd>
                    </button>
                    <a href="javascript:void(0)" id="realPrintBtn" class="btn btn-info px-3 fw-bold text-dark shadow-sm">
                        <u>P</u>rint <kbd style="font-size:10px;opacity:.8;margin-left:4px;color:#fff;">Ctrl+P</kbd>
                    </a>
                    <a href="{{ route('stock_transfers.index') }}" id="exitBtn" class="btn btn-secondary px-3 fw-bold shadow-sm text-white">
                        E<u>x</u>it <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Esc</kbd>
                    </a>
                    <a href="{{ route('stock_transfers.create') }}" id="newInvoiceBtn" class="btn btn-dark px-3 fw-bold shadow-sm text-white">
                        <u>N</u>ew <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+M</kbd>
                    </a>
                </div>

            </form>

        </div>
    </div>
</div>

{{-- Print Preview Modal --}}
<div class="modal fade" id="printPreviewModal" tabindex="-1" aria-labelledby="printPreviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="printPreviewLabel">
                    <i class="fa fa-eye me-2"></i> Stock Transfer Preview
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="printPreviewBody" style="font-family:'Poppins',sans-serif; font-size:13px;">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i> Close
                </button>
                <button type="button" class="btn btn-dark rounded-pill px-4" onclick="window.print()">
                    <i class="fa fa-print me-1"></i> Print
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {

    $('.select2').select2({ width: '100%' });

    var _savedTransferId = null;
    var _saveInFlight = false;
    var _postInFlight = false;

    function setFormLocked(isLocked) {
        if (isLocked) {
            $('#transferForm').addClass('form-locked');
            $('#transferForm .select2').prop('disabled', true);
        } else {
            $('#transferForm').removeClass('form-locked');
            $('#transferForm .select2').prop('disabled', false).trigger('change.select2');
        }
    }

    function showToast(msg, type) {
        type = type || 'success';
        var icon  = type === 'success' ? 'fa-check-circle' : 'fa-times-circle';
        var color = type === 'success' ? '#28a745' : '#dc3545';
        var $toast = $('<div>').css({
            position: 'fixed', top: '20px', right: '20px', zIndex: 9999,
            background: color, color: '#fff', padding: '12px 20px', borderRadius: '8px',
            boxShadow: '0 4px 15px rgba(0,0,0,.2)', fontSize: '14px', fontWeight: '500',
            display: 'flex', alignItems: 'center', gap: '8px', minWidth: '280px'
        }).html('<i class="fa ' + icon + '"></i> ' + msg);
        $('body').append($toast);
        setTimeout(function() { $toast.fadeOut(400, function(){ $(this).remove(); }); }, 3500);
    }

    function recalcTotals() {
        var total = 0;
        $('.quantity').each(function() { total += parseFloat($(this).val()) || 0; });
        $('#total_qty').val(total.toFixed(2));
    }

    function fetchStock($row, productId) {
        var warehouseId = $('#from_warehouse_id').val();
        if (!warehouseId || !productId) { $row.find('.stock').val(''); return; }
        $.get("{{ route('warehouse.stock.quantity') }}", { warehouse_id: warehouseId, product_id: productId })
            .done(function(res) { 
                $row.find('.stock').val(res.quantity); 
            })
            .fail(function() { $row.find('.stock').val(0); });
    }

    function initProductSelect($row) {
        $row.find('.product-select').select2({
            placeholder: 'Search Product', width: '100%',
            ajax: {
                url: "{{ route('search-productsinwar') }}", dataType: 'json', delay: 100,
                data: function(params) { return { q: params.term }; },
                processResults: function(data, params) {
                    const term = (params.term || '').toLowerCase();
                    const results = data.map(function(i) { 
                        return { id: i.id, text: i.id + ' - ' + i.name, name: i.name }; 
                    });

                    // Prioritize exact matches
                    results.sort((a, b) => {
                        if (String(a.id) === term || a.name.toLowerCase() === term) return -1;
                        if (String(b.id) === term || b.name.toLowerCase() === term) return 1;
                        return 0;
                    });

                    return { results };
                }
            },
            minimumInputLength: 1
        }).on('select2:select', function(e) {
            var data = e.params.data;
            $row.find('.item-id-input').val(data.id);
            fetchStock($row, data.id);
            recalcTotals();
            if ($row.is('#transferItems tr:last-child')) { appendBlankRow(false); }
            setTimeout(function() { $row.find('.quantity').focus().select(); }, 60);
        });
    }

    function appendBlankRow(focus = true) {
        var html = `<tr>
            <td><input type="text" class="form-control input-sm item-id-input" placeholder="ID"></td>
            <td><select name="product_id[]" class="form-control product-select" style="width:100%;"><option value="">Select Product</option></select></td>
            <td><input type="number" name="available_stock[]" class="form-control input-sm stock" readonly></td>
            <td><input type="number" name="quantity[]" class="form-control input-sm quantity" value="1" step="any" min="0.01"></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-row">X</button></td>
        </tr>`;
        var $row = $(html);
        $('#transferItems').append($row);
        initProductSelect($row);
        if (focus) { setTimeout(function() { $row.find('.item-id-input').focus(); }, 60); }
        return $row;
    }

    appendBlankRow();

    $(document).on('keydown', '.item-id-input', function(e) {
        if ((e.key === 'Enter' || e.key === 'Tab') && !e.shiftKey) {
            var $row = $(this).closest('tr');
            var val  = $(this).val().trim();
            if (!val) { e.preventDefault(); $row.find('.product-select').select2('open'); return; }
            e.preventDefault();
            $.ajax({
                url: "{{ route('search-productsinwar') }}", data: { q: val },
                success: function(res) {
                    // Match prioritization: exact ID -> case-insensitive exact name -> first result if only 1
                    var item = res.find(function(i) { return i.id.toString() === val; })
                             || res.find(function(i) { return i.name.toLowerCase() === val.toLowerCase(); });
                    
                    if (!item && res.length === 1) {
                        item = res[0];
                    }

                    if (item) {
                        var option = new Option(item.id + ' - ' + item.name, item.id, true, true);
                        $row.find('.product-select').empty().append(option).val(item.id).trigger('change.select2');
                        fetchStock($row, item.id);
                        recalcTotals();
                        if ($row.is('#transferItems tr:last-child')) { appendBlankRow(false); }
                        setTimeout(function() { $row.find('.quantity').focus().select(); }, 60);
                    } else { $row.find('.product-select').select2('open'); }
                }
            });
        }
    });

    $(document).on('click', '.remove-row', function() {
        if ($('#transferItems tr').length > 1) { $(this).closest('tr').remove(); recalcTotals(); }
    });

    $(document).on('input', '.quantity', recalcTotals);

    $('#from_warehouse_id').on('change', function() {
        $('#transferItems').empty(); appendBlankRow(); recalcTotals();
    });

    function ajaxSaveDraft(callback) {
        if (_saveInFlight) return;
        $('#transferItems tr').each(function() { if (!$(this).find('.product-select').val()) { $(this).remove(); } });
        recalcTotals();
        if ($('#transferItems tr').length === 0) { appendBlankRow(); showToast('❌ Add at least one item.', 'error'); return; }

        var $form = $('#transferForm');
        if (!$form[0].checkValidity()) { $form[0].reportValidity(); return; }

        _saveInFlight = true;
        $('#saveDraftBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Saving...');

        var url = _savedTransferId ? "/stock_transfers/" + _savedTransferId : "{{ route('stock_transfers.store') }}";
        var data = $form.serializeArray();
        if(_savedTransferId) data.push({name: '_method', value: 'PUT'});

        $.ajax({
            url: url, type: 'POST', data: $.param(data),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                if (res.success) {
                    _savedTransferId = res.id;
                    showToast('✅ Saved successfully!');
                    $('#statusBadge').removeClass('bg-warning text-dark').addClass('bg-info text-white').text('Unposted');
                    $('#idBadge').text('ID: ' + res.id);
                    $('#realPrintBtn').attr('href', '/stock_transfers/' + res.id + '/print').attr('target', '_blank');
                    $('#editInvoiceBtn').prop('disabled', false);
                    $('#postBtn').prop('disabled', false);
                    setFormLocked(true);
                    showToast('🔒 Form Locked — Ctrl+E to Edit', 'success');
                    if (typeof callback === 'function') callback(res.id);
                } else { showToast(res.message, 'error'); }
            },
            complete: function() {
                _saveInFlight = false;
                if (!$('#transferForm').hasClass('form-locked')) {
                    $('#saveDraftBtn').prop('disabled', false)
                        .html('<u>S</u>ave <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>');
                }
            }
        });
    }

    function doPost() {
        if (_postInFlight) return;
        if (!_savedTransferId) { showToast('⚠️ پہلے Save کریں!', 'error'); return; }
        postById(_savedTransferId);
    }

    function postById(id) {
        let isCashier = {{ auth()->user()->hasRole('Cashier') ? 'true' : 'false' }};
        let title = isCashier ? 'Send for Approval?' : 'Post Stock Transfer?';
        let text = isCashier ? 'This transfer will be sent to the admin for approval.' : 'Are you sure you want to post this transfer? Stock will be updated immediately.';

        Swal.fire({
            title: title, text: text, icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33', confirmButtonText: 'Yes, proceed!'
        }).then((result) => {
            if (result.isConfirmed) {
                _postInFlight = true;
                $('#postBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Posting...');
                $.ajax({
                    url: '/stock_transfers/' + id + '/post', type: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(res) {
                        if (res.success) {
                            showToast('✅ Posted Successfully!');
                            $('#statusBadge').removeClass('bg-info').addClass('bg-success text-white').text('Posted');
                            $('#postedWatermark').addClass('show');
                            setFormLocked(true);
                            $('#postBtn, #editInvoiceBtn').prop('disabled', true);
                            setTimeout(function() { window.location.href = "{{ route('stock_transfers.index') }}"; }, 1500);
                        } else {
                            showToast(res.message, 'error');
                            _postInFlight = false;
                            $('#postBtn').prop('disabled', false)
                                .html('<u>P</u>ost <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+&crarr;</kbd>');
                        }
                    },
                    error: function() {
                        _postInFlight = false;
                        $('#postBtn').prop('disabled', false)
                            .html('<u>P</u>ost <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+&crarr;</kbd>');
                    }
                });
            }
        });
    }

    $('#saveDraftBtn').on('click', function() { ajaxSaveDraft(); });
    $('#postBtn').on('click', function() { doPost(); });
    $('#editInvoiceBtn').on('click', function() {
        if ($(this).prop('disabled')) return;
        setFormLocked(false);
        $(this).prop('disabled', true);
        $('#saveDraftBtn').prop('disabled', false)
            .html('<u>S</u>ave <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>');
        showToast('🔓 Form Unlocked for Editing', 'success');
    });

    $('#realPrintBtn').on('click', function(e) {
        var href = $(this).attr('href');
        if (!href || href === 'javascript:void(0)' || href.indexOf('stock_transfers') === -1) {
            e.preventDefault();
            showPreviewModal();
        }
    });

    function showPreviewModal() {
        var fromWh  = $('#from_warehouse_id option:selected').text();
        var toWh    = $('#to_warehouse_id option:selected').text();
        if ($('#toShop').is(':checked')) toWh = 'Shop';
        var remarks = $('input[name="remarks"]').val();
        var rows = '', totalQty = 0, serial = 1;

        $('#transferItems tr').each(function() {
            var pid   = $(this).find('.item-id-input').val();
            var ptxt  = $(this).find('.product-select option:selected').text();
            var qty   = parseFloat($(this).find('.quantity').val()) || 0;
            if (!pid || ptxt.includes('Select Product') || !qty) return;
            totalQty += qty;
            rows += `<tr><td style="border:1px solid #ddd;padding:6px;">${serial++}</td><td style="border:1px solid #ddd;padding:6px;">${pid}</td><td style="border:1px solid #ddd;padding:6px;">${ptxt.split(' - ').slice(1).join(' - ') || ptxt}</td><td style="border:1px solid #ddd;padding:6px;text-align:center;">${qty.toFixed(2)}</td></tr>`;
        });

        var html = `
            <div style="border:1px solid #eee;padding:20px;max-width:780px;margin:auto;">
                <div style="display:flex;justify-content:space-between;border-bottom:2px solid #000;padding-bottom:10px;margin-bottom:16px;">
                    <div><div style="font-size:22px;font-weight:700;">Al-Madina Traders</div><div style="color:#555;font-size:12px;">Stock Transfer Voucher</div></div>
                    <div style="text-align:right;font-size:12px;"><div><strong>Transfer #:</strong> ${_savedTransferId || 'NEW'}<br><strong>Status:</strong> ${_savedTransferId ? 'UNPOSTED' : 'DRAFT'}</div></div>
                </div>
                <div style="text-align:center; margin-bottom:20px; padding:10px; background:#f8f9fa; border-radius:8px;">
                    <span style="font-size:15px; font-weight:600;">${fromWh}</span><span style="margin:0 16px; font-size:20px; font-weight:700;">→</span><span style="font-size:15px; font-weight:600;">${toWh}</span>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; font-size:12px;">
                    <div><div><span style="font-weight:600;width:120px;display:inline-block;">Date:</span>{{ date('d-M-Y') }}</div></div>
                    <div><div><span style="font-weight:600;width:120px;display:inline-block;">Prepared By:</span>{{ auth()->user()->name }}</div></div>
                </div>
                ${remarks ? '<p style="font-size:12px;"><strong>Remarks:</strong> ' + remarks + '</p>' : ''}
                <table style="width:100%;border-collapse:collapse;margin-bottom:20px;">
                    <thead><tr style="background:#f2f2f2;"><th style="border:1px solid #ddd;padding:7px;width:40px;">S#</th><th style="border:1px solid #ddd;padding:7px;width:80px;">Item ID</th><th style="border:1px solid #ddd;padding:7px;">Product</th><th style="border:1px solid #ddd;padding:7px;width:80px;text-align:center;">Qty</th></tr></thead>
                    <tbody>${rows}</tbody>
                    <tfoot><tr style="font-weight:700;background:#f9f9f9;"><td colspan="3" style="border:1px solid #ddd;padding:7px;text-align:right;">Total Qty:</td><td style="border:1px solid #ddd;padding:7px;text-align:center;">${totalQty.toFixed(2)}</td></tr></tfoot>
                </table>
                <div style="margin-top:50px;display:flex;justify-content:space-between;">
                    <div style="border-top:1px solid #000;width:130px;text-align:center;padding-top:5px;font-size:12px;">Prepared By</div>
                    <div style="border-top:1px solid #000;width:130px;text-align:center;padding-top:5px;font-size:12px;">Authorized By</div>
                </div>
            </div>`;
        $('#printPreviewBody').html(html);
        new bootstrap.Modal(document.getElementById('printPreviewModal')).show();
    }

    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && (e.key === 's' || e.key === 'S')) {
            e.preventDefault(); e.stopImmediatePropagation();
            if (!_saveInFlight && !$('#saveDraftBtn').prop('disabled') && $('#saveDraftBtn').is(':visible')) $('#saveDraftBtn').click();
        }
        if (e.ctrlKey && (e.key === 'e' || e.key === 'E')) {
            e.preventDefault();
            if (!$('#editInvoiceBtn').prop('disabled')) $('#editInvoiceBtn').click();
        }
        if (e.ctrlKey && e.key === 'Enter') {
            e.preventDefault(); e.stopImmediatePropagation();
            if (!_postInFlight && !$('#postBtn').prop('disabled')) $('#postBtn').click();
        }
        if (e.ctrlKey && (e.key === 'p' || e.key === 'P')) {
            e.preventDefault();
            var href = $('#realPrintBtn').attr('href');
            if (href && href !== 'javascript:void(0)' && href.indexOf('stock_transfers') !== -1) window.open(href, '_blank');
            else showPreviewModal();
        }
        if (e.ctrlKey && (e.key === 'l' || e.key === 'L')) { e.preventDefault(); window.location.href = $('#listBtn').attr('href'); }
        if (e.ctrlKey && (e.key === 'm' || e.key === 'M')) { e.preventDefault(); window.location.href = $('#newInvoiceBtn').attr('href'); }
        if (e.ctrlKey && (e.key === 'i' || e.key === 'I')) {
            e.preventDefault();
            if (!$('#transferForm').hasClass('form-locked')) appendBlankRow(true);
        }
        if (e.key === 'Escape') {
            if ($('.modal.show').length) { $('.modal.show').modal('hide'); }
            else { e.preventDefault(); window.location.href = $('#exitBtn').attr('href'); }
        }
    }, true);
});
</script>
@endsection
