@extends('admin_panel.layout.app')

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .stock-hold-page.container-fluid { padding: .25rem .4rem !important; }
    .stock-hold-page .main-content-inner { padding: 0 !important; }
    .stock-hold-page .page-top-bar { margin-bottom: .35rem !important; padding: .35rem .5rem !important; }
    .stock-hold-page .page-top-bar .page-title { font-size: .9rem !important; }
    .stock-hold-page .page-top-bar .badge { font-size: 11px !important; padding: .2rem .55rem !important; }
    .stock-hold-page .card { margin-bottom: .35rem !important; }
    .stock-hold-page .card-body { padding: .45rem .55rem !important; }
    .stock-hold-page .card-footer { padding: .45rem .55rem !important; }
    .stock-hold-page .row.g-2 { --bs-gutter-x: .4rem; --bs-gutter-y: .25rem; }
    .stock-hold-page .form-label { margin-bottom: .1rem !important; font-size: .72rem !important; line-height: 1.1; }
    .stock-hold-page .input-sm,
    .stock-hold-page .form-control,
    .stock-hold-page .form-select { height: 26px !important; min-height: 26px !important; padding: .1rem .4rem !important; font-size: .78rem !important; }
    .stock-hold-page .select2-container .select2-selection--single { height: 26px !important; border: 1px solid #ced4da; }
    .stock-hold-page .select2-container .select2-selection--single .select2-selection__rendered { line-height: 24px !important; padding-left: 6px !important; font-size: .78rem !important; }
    .stock-hold-page .select2-container .select2-selection--single .select2-selection__arrow { height: 24px !important; }
    .stock-hold-page .table td, .stock-hold-page .table th { vertical-align: middle !important; padding: 2px 4px !important; font-size: .78rem !important; }
    .stock-hold-page .table .form-control { height: 24px !important; min-height: 24px !important; padding: 1px 4px !important; font-size: .75rem !important; }
    .stock-hold-page .manual-search-card .card-body { padding: .35rem .55rem !important; }
    .stock-hold-page #addItemBtn { height: 26px; padding: 0 .65rem; font-size: .75rem; line-height: 1.2; }
    .stock-hold-page .bottom-bar-btns { gap: .35rem !important; }
    .stock-hold-page .bottom-bar-btns .btn { padding: .25rem .65rem !important; font-size: .78rem !important; }
    
    .form-locked { position: relative; opacity: 0.8; }
    .form-locked .card-body { pointer-events: none !important; }
    .form-locked input, .form-locked .select2-container--default .select2-selection--single, .form-locked select, .form-locked textarea { 
        background-color: #e9ecef !important; cursor: not-allowed !important; 
    }
    .form-locked .remove-row, .form-locked #addItemBtn, .form-locked #saveDraftBtn { display: none !important; }
    .form-locked #editInvoiceBtn, .form-locked #newInvoiceBtn, .form-locked #realPrintBtn,
    .form-locked #postBtn, .form-locked #exitBtn, .form-locked #deleteBtn {
        pointer-events: auto !important; opacity: 1 !important;
    }

    .form-locked.view-mode #deleteBtn {
        display: inline-block !important;
        pointer-events: auto !important;
        opacity: 1 !important;
        cursor: pointer !important;
    }

    .form-locked.view-mode #saveDraftBtn,
    .form-locked.view-mode #editInvoiceBtn,
    .form-locked.view-mode #postBtn {
        display: inline-block !important;
        pointer-events: none !important;
        opacity: 0.55 !important;
        cursor: not-allowed !important;
    }

    .form-locked.view-mode #realPrintBtn,
    .form-locked.view-mode #exitBtn,
    .form-locked.view-mode #newInvoiceBtn {
        pointer-events: auto !important;
        opacity: 1 !important;
        display: inline-block !important;
    }
    
    .posted-watermark {
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg);
        font-size: 100px; color: rgba(255, 0, 0, 0.1); font-weight: bold; pointer-events: none; z-index: 1000;
        text-transform: uppercase; border: 10px solid rgba(255, 0, 0, 0.1); padding: 20px; border-radius: 20px; display: none;
    }
    .posted-watermark.show { display: block; }
</style>

@section('content')
@php
    $isViewMode = isset($viewMode) && $viewMode;
    $isEditMode = isset($voucher) && !$isViewMode;
    $isPosted = isset($voucher) && $voucher->status === 'Posted';
    $entryTime = date('H:i');
    if (isset($voucher) && $voucher->items->isNotEmpty() && $voucher->items->first()->entry_time) {
        $entryTime = substr((string) $voucher->items->first()->entry_time, 0, 5);
    }
    $partyLabel = '';
    if (isset($voucher)) {
        $partyLabel = $voucher->party_type === 'vendor'
            ? ($voucher->partyVendor->name ?? 'N/A')
            : ($voucher->partyCustomer->customer_name ?? 'N/A');
    }
    $formClass = 'position-relative';
    if ($isViewMode || $isPosted || $isEditMode) {
        $formClass .= ' form-locked';
    }
    if ($isViewMode) {
        $formClass .= ' view-mode';
    }
@endphp
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid stock-hold-page">
            
            {{-- TOP BAR --}}
            <div class="d-flex justify-content-between align-items-center page-top-bar bg-light rounded shadow-sm">
                <div style="min-width:80px;"></div>
                <div class="d-flex align-items-center gap-2 justify-content-center flex-grow-1">
                    <h6 class="page-title mb-0 fw-bold">Stock Hold Management
                        @if($isViewMode)
                            <span class="badge bg-info px-2 py-1 rounded ms-1" style="font-size:10px;"><i class="fa fa-eye"></i> View Only</span>
                        @endif
                    </h6>
                    <span id="statusBadge" class="badge {{ $isPosted ? 'bg-success text-white' : (isset($voucher) ? 'bg-info text-white' : 'bg-warning text-dark') }} px-3 py-2 rounded-pill shadow-sm" style="font-size:12px;">
                        <i class="fa {{ $isPosted ? 'fa-check' : 'fa-pencil' }} me-1"></i>
                        {{ isset($voucher) ? $voucher->status : 'New Hold' }}
                    </span>
                    <span id="idBadge" class="badge bg-primary px-3 py-2 rounded-pill shadow-sm" style="{{ isset($voucher) ? '' : 'display:none;' }} font-size:12px;">
                        <i class="fa fa-tag me-1"></i> ID: {{ isset($voucher) ? $voucher->id : 'NEW' }}
                    </span>
                </div>
                <div class="d-flex align-items-center justify-content-end" style="min-width:115px;">
                    <a href="{{ route('stock-hold-list') }}" id="listBtn" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="fa fa-list me-1"></i> List <kbd style="font-size:9px;opacity:.7;margin-left:4px;">Ctrl+L</kbd>
                    </a>
                </div>
            </div>

            <div id="formErrorAlert" class="alert alert-danger alert-dismissible py-2 px-3 mb-2 d-none" role="alert">
                <i class="fa fa-exclamation-triangle me-2"></i><span id="formErrorText"></span>
                <button type="button" class="btn-close btn-close-sm" id="formErrorDismiss" aria-label="Close"></button>
            </div>

            <form action="{{ $isViewMode ? '#' : (isset($voucher) ? route('stock-holds.update', $voucher->id) : route('stock-holds.store')) }}" method="POST" id="stockHoldForm" class="{{ $formClass }}">
                @csrf
                <input type="hidden" name="action" id="formAction" value="save">
                <input type="hidden" name="id" id="voucher_id" value="{{ $voucher->id ?? '' }}">
                <input type="hidden" name="sale_id" id="sale_id" value="{{ $voucher->sale_id ?? '' }}">
                <div class="posted-watermark {{ $isPosted ? 'show' : '' }}" id="postedWatermark">Posted</div>

                {{-- Header Details --}}
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Entry Date</label>
                                <input type="date" name="entry_date" class="form-control input-sm" value="{{ isset($voucher) ? $voucher->date : date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Entry Time</label>
                                <input type="time" name="entry_time" class="form-control input-sm" value="{{ $entryTime }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Hold No.</label>
                                <input type="text" id="voucher_no" class="form-control input-sm fw-bold text-primary bg-light" value="{{ isset($voucher) ? $voucher->display_no : 'Auto-Generated' }}" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Type</label>
                                <select name="vendor_type" id="vendor_type" class="form-select input-sm" required>
                                    <option value="" disabled {{ isset($voucher) ? '' : 'selected' }}>Select Type</option>
                                    <option value="vendor" {{ (isset($voucher) && $voucher->party_type == 'vendor') ? 'selected' : '' }}>Vendor</option>
                                    <option value="customer" {{ (isset($voucher) && $voucher->party_type == 'customer') ? 'selected' : '' }}>Customer</option>
                                    <option value="walkin" {{ (isset($voucher) && $voucher->party_type == 'walkin') ? 'selected' : '' }}>Walkin Customer</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Select Party</label>
                                <select name="vendor_id" id="vendor_id" class="form-select select2" required>
                                    <option value="">Select Party</option>
                                    @if(isset($voucher) && $voucher->party_id)
                                        <option value="{{ $voucher->party_id }}" selected>{{ $partyLabel }}</option>
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Invoice (Optional)</label>
                                <select id="invoice_id" class="form-select input-sm">
                                    <option value="">Select Invoice</option>
                                    @if(isset($voucher) && $voucher->sale_id && $voucher->sale)
                                        <option value="{{ $voucher->sale_id }}" selected>
                                            {{ $voucher->sale->invoice_no }}
                                            @if($voucher->sale->created_at)
                                                ({{ $voucher->sale->created_at->format('Y-m-d') }})
                                            @endif
                                        </option>
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Location</label>
                                <select name="warehouse_id" id="warehouse_id" class="form-select select2" required>
                                    @if(auth()->user()->canAccessShop())
                                        <option value="0" {{ (isset($voucher) && (string)$voucher->warehouse_id === '0') ? 'selected' : '' }}>🏠 Shop</option>
                                    @endif
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}" {{ (isset($voucher) && $voucher->warehouse_id == $wh->id) ? 'selected' : '' }}>{{ $wh->warehouse_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-9">
                                <label class="form-label small fw-bold">Remarks</label>
                                <input type="text" name="remarks" class="form-control input-sm" value="{{ $voucher->remarks ?? '' }}" placeholder="Any special notes...">
                            </div>
                        </div>
                    </div>
                </div>

                @if(!$isViewMode)
                {{-- MANUAL SEARCH BOX --}}
                <div class="card shadow-sm manual-search-card bg-light border-primary border-opacity-25">
                    <div class="card-body">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-10">
                                <label class="form-label small fw-bold text-primary mb-0">Manual Product Search (to add extra items)</label>
                                <select id="manual_product_search" class="form-select select2">
                                    <option value="">Search for a product...</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}" data-name="{{ $p->name }}">{{ $p->id }} - {{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="button" id="addItemBtn" class="btn btn-primary btn-sm w-100 rounded-pill">
                                    <i class="fa fa-plus me-1"></i> Add Item
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Items Table --}}
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped mb-0" id="itemsTable">
                                <thead class="bg-light">
                                    <tr>
                                        <th style="width:80px;">Item ID</th>
                                        <th>Product Description</th>
                                        <th style="width:120px;">Sale Qty</th>
                                        <th style="width:120px;">Hold Qty</th>
                                        <th style="width:50px;">Act</th>
                                    </tr>
                                </thead>
                                <tbody id="itemRows">
                                    @if(isset($voucher))
                                        @foreach($voucher->items as $item)
                                            <tr>
                                                <td>{{ $item->product_id }} <input type="hidden" name="product_id[]" value="{{ $item->product_id }}"></td>
                                                <td>{{ $item->product->name ?? 'Product' }}</td>
                                                <td><input type="number" name="sale_qty[]" class="form-control input-sm text-center" value="{{ (float) $item->sale_qty }}" readonly></td>
                                                <td><input type="number" name="hold_qty[]" class="form-control input-sm text-center hold-qty-input" value="{{ (float) $item->hold_qty }}" step="any" {{ $isViewMode ? 'readonly' : '' }}></td>
                                                <td class="text-center">@if(!$isViewMode)<button type="button" class="btn btn-sm btn-danger remove-row">X</button>@endif</td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-end">Total Hold Items:</th>
                                        <th class="text-center"><span id="total_items_badge" class="badge bg-secondary">{{ isset($voucher) ? count($voucher->items) : 0 }}</span></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <div class="d-flex flex-wrap justify-content-center w-100 bottom-bar-btns">
                            <button type="button" id="saveDraftBtn" class="btn btn-primary px-3 fw-bold shadow-sm" {{ ($isViewMode || $isPosted) ? 'style=display:none;' : '' }}>
                                <u>S</u>ave <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>
                            </button>
                            <button type="button" id="editInvoiceBtn" class="btn btn-warning px-3 fw-bold text-dark shadow-sm" {{ ($isViewMode || $isPosted) ? 'disabled' : (isset($voucher) ? '' : 'disabled') }}>
                                <u>E</u>dit <kbd style="font-size:10px;opacity:.8;margin-left:4px;color:#fff;">Ctrl+E</kbd>
                            </button>
                            <button type="button" id="postBtn" class="btn btn-success px-3 fw-bold shadow-sm" {{ ($isViewMode || $isPosted) ? 'disabled' : '' }}>
                                <u>P</u>ost <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+&crarr;</kbd>
                            </button>
                            <button type="button" id="deleteBtn" class="btn btn-danger px-3 fw-bold shadow-sm" {{ isset($voucher) && !$isPosted && !$isViewMode ? '' : 'disabled' }} title="{{ ($isPosted || $isViewMode) ? 'Delete from Edit screen (draft only)' : 'Delete this hold' }}">
                                <u>D</u>elete <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+D</kbd>
                            </button>
                            <a href="{{ isset($voucher) ? route('stock-holds.print', $voucher->id) : 'javascript:void(0)' }}" {{ isset($voucher) ? 'target=_blank' : '' }} id="realPrintBtn" class="btn btn-info px-3 fw-bold text-dark shadow-sm">
                                <u>P</u>rint <kbd style="font-size:10px;opacity:.8;margin-left:4px;color:#fff;">Ctrl+P</kbd>
                            </a>
                            <a href="{{ route('stock-hold-list') }}" id="exitBtn" class="btn btn-secondary px-3 fw-bold shadow-sm text-white">
                                E<u>x</u>it <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Esc</kbd>
                            </a>
                            <a href="{{ route('create-stock-hold') }}" id="newInvoiceBtn" class="btn btn-dark px-3 fw-bold shadow-sm text-white">
                                <u>N</u>ew <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+M</kbd>
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2').select2({ width: '100%' });
    var _savedVoucherId = @json(isset($voucher) ? $voucher->id : null);
    var _saveInFlight = false;
    var _postInFlight = false;
    var _deleteInFlight = false;
    var isViewMode = @json($isViewMode ?? false);
    var isPosted = @json($isPosted ?? false);
    var _selectedPartyId = @json(isset($voucher) ? (string) $voucher->party_id : null);
    var saveBtnHtml = '<u>S</u>ave <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>';
    var postBtnHtml = '<u>P</u>ost <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+&crarr;</kbd>';
    var deleteBtnHtml = '<u>D</u>elete <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+D</kbd>';

    if (_savedVoucherId) {
        $('#realPrintBtn').attr('href', '/stock-holds/print/' + _savedVoucherId).attr('target', '_blank');
    }

    if (isViewMode || isPosted) {
        $('#stockHoldForm select').prop('disabled', true);
    }

    function loadParties(type, selectId, loadInvoices) {
        if (!type) return;
        $.get("{{ route('stock-holds.party.list') }}", { type: type }, function(res) {
            var $p = $('#vendor_id').empty().append('<option value="">Select Party</option>');
            res.forEach(function(item) {
                $p.append('<option value="' + item.id + '">' + item.text + '</option>');
            });
            if (selectId) {
                $p.val(String(selectId));
            }
            $p.trigger('change.select2');
            if (loadInvoices && selectId) {
                loadInvoiceList(selectId, type, $('#sale_id').val() || null);
            }
        });
    }

    function loadInvoiceList(partyId, type, selectInvoiceId) {
        if (!partyId) return;
        $.get("{{ url('stock-holds/party') }}/" + partyId + "/invoices", { type: type }, function(res) {
            var $inv = $('#invoice_id').empty().append('<option value="">Select Invoice</option>');
            res.forEach(function(item) {
                $inv.append('<option value="' + item.id + '">' + item.text + '</option>');
            });
            if (selectInvoiceId) {
                $inv.val(String(selectInvoiceId));
            }
        });
    }

    if (_selectedPartyId && !isViewMode) {
        loadParties($('#vendor_type').val(), _selectedPartyId, true);
    } else if (_selectedPartyId && isViewMode) {
        $('#vendor_id').trigger('change.select2');
    }

    function setHoldFormPostedState(voucherId, printUrl) {
        _savedVoucherId = voucherId;
        $('#voucher_id').val(voucherId);
        $('#stockHoldForm').addClass('form-locked view-mode');
        $('#postedWatermark').addClass('show');
        $('#statusBadge').removeClass('bg-warning bg-info').addClass('bg-success text-white').html('<i class="fa fa-check"></i> Posted');
        $('#saveDraftBtn, #editInvoiceBtn, #postBtn, #deleteBtn').prop('disabled', true);
        $('#postBtn').html(postBtnHtml);
        $('#realPrintBtn').attr('href', printUrl || ('/stock-holds/print/' + voucherId)).attr('target', '_blank');
    }

    function isHoldPostedView() {
        return isViewMode || isPosted || $('#stockHoldForm').hasClass('view-mode');
    }

    function serializeForm() {
        var data = $('#stockHoldForm').serializeArray();
        ['vendor_id', 'warehouse_id', 'vendor_type', 'sale_id'].forEach(function(name) {
            var val = $('[name="' + name + '"]').val() || $('#' + name).val() || '';
            var found = false;
            for (var i = 0; i < data.length; i++) {
                if (data[i].name === name) {
                    data[i].value = val;
                    found = true;
                    break;
                }
            }
            if (!found) data.push({ name: name, value: val });
        });
        return $.param(data);
    }

    function doDelete() {
        if (_deleteInFlight || !_savedVoucherId || isPosted || isViewMode) return;
        if (!confirm('Delete this stock hold? This cannot be undone.')) return;
        _deleteInFlight = true;
        $('#deleteBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i>');
        $.ajax({
            url: '/stock-holds/delete/' + _savedVoucherId,
            type: 'POST',
            data: { _token: $('input[name="_token"]').val() },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                if (res.success) {
                    showToast(res.message || 'Deleted successfully', 'success');
                    setTimeout(function() { window.location.href = "{{ route('stock-hold-list') }}"; }, 800);
                } else {
                    showFormError(res.message || 'Delete failed');
                    _deleteInFlight = false;
                    $('#deleteBtn').prop('disabled', false).html(deleteBtnHtml);
                }
            },
            error: function(xhr) {
                showFormError(extractAjaxError(xhr));
                _deleteInFlight = false;
                $('#deleteBtn').prop('disabled', false).html(deleteBtnHtml);
            }
        });
    }

    $('#deleteBtn').on('click', function(e) {
        e.preventDefault();
        if (!$(this).prop('disabled')) doDelete();
    });

    function showToast(msg, type = 'success') {
        var icon = type === 'success' ? 'fa-check-circle' : 'fa-times-circle';
        var color = type === 'success' ? '#28a745' : '#dc3545';
        var duration = type === 'success' ? 3000 : 6000;
        var $toast = $('<div>').css({
            position: 'fixed', top: '20px', right: '20px', zIndex: 9999,
            background: color, color: '#fff', padding: '12px 20px', borderRadius: '8px',
            boxShadow: '0 4px 15px rgba(0,0,0,.2)', display: 'flex', alignItems: 'center', gap: '8px',
            maxWidth: '420px', lineHeight: '1.35'
        }).html('<i class="fa ' + icon + '"></i> <span>' + $('<div>').text(msg).html() + '</span>');
        $('body').append($toast);
        setTimeout(function() { $toast.fadeOut(400, function(){ $(this).remove(); }); }, duration);
    }

    function extractAjaxError(xhr) {
        if (xhr.responseJSON) {
            if (xhr.responseJSON.message) return xhr.responseJSON.message;
            if (xhr.responseJSON.errors) {
                return Object.values(xhr.responseJSON.errors).flat().join(', ');
            }
        }
        return 'Server Error';
    }

    function showFormError(msg) {
        $('#formErrorText').text(msg);
        $('#formErrorAlert').removeClass('d-none');
        showToast(msg, 'error');
        $('html, body').animate({ scrollTop: $('#formErrorAlert').offset().top - 80 }, 200);
    }

    function clearFormError() {
        $('#formErrorAlert').addClass('d-none');
        $('#formErrorText').text('');
    }

    $('#formErrorDismiss').on('click', function() { clearFormError(); });

    // Party List Loading
    $('#vendor_type').on('change', function() {
        var type = $(this).val();
        if (isViewMode || isPosted) return;
        loadParties(type, null, false);
    });

    // Invoice List Loading
    $('#vendor_id').on('change', function() {
        var id = $(this).val();
        var type = $('#vendor_type').val();
        if(!id || isViewMode || isPosted) return;
        loadInvoiceList(id, type, null);
    });

    // Invoice Item Loading
    $('#invoice_id').on('change', function() {
        var id = $(this).val();
        if(!id || isViewMode || isPosted) return;
        $('#sale_id').val(id);
        $('#itemRows').empty();
        $.get("{{ url('stock-holds/invoice') }}/" + id + "/items", function(items) {
            // Auto-select warehouse from the first item
            if(items.length > 0 && items[0].warehouse_id !== undefined && items[0].warehouse_id !== null) {
                $('#warehouse_id').val(items[0].warehouse_id).trigger('change');
            }
            items.forEach(item => {
                var saleQty = item.qty || item.quantity || 0;
                addRow(item.product_id, item.item_name || 'Product', saleQty, saleQty);
            });
        });
    });

    function addRow(pid, name, saleQty = 0, holdQty = 1) {
        var row = `<tr>
            <td>${pid} <input type="hidden" name="product_id[]" value="${pid}"></td>
            <td>${name}</td>
            <td><input type="number" name="sale_qty[]" class="form-control input-sm text-center" value="${saleQty}" readonly></td>
            <td><input type="number" name="hold_qty[]" class="form-control input-sm text-center hold-qty-input" value="${holdQty}" step="any"></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-row">X</button></td>
        </tr>`;
        $('#itemRows').append(row);
        updateCount();
    }

    $(document).on('click', '.remove-row', function() { $(this).closest('tr').remove(); updateCount(); });
    function updateCount() { $('#total_items_badge').text($('#itemRows tr').length); }

    $('#manual_product_search').select2();

    $('#addItemBtn').on('click', function() {
        var $opt = $('#manual_product_search').find(':selected');
        var id = $opt.val();
        var name = $opt.data('name');
        
        if(!id) { showToast('Select a product first', 'error'); return; }
        addRow(id, name, 0, 1);
        $('#manual_product_search').val('').trigger('change');
    });

    function save(act) {
        if (_saveInFlight || _postInFlight) return;
        $('#formAction').val(act);
        if($('#itemRows tr').length === 0) { showToast('Add at least one item', 'error'); return; }
        var $form = $('#stockHoldForm');
        if(!$form[0].checkValidity()) { $form[0].reportValidity(); return; }

        var btn = act === 'post' ? '#postBtn' : '#saveDraftBtn';
        if (act === 'post') _postInFlight = true; else _saveInFlight = true;
        clearFormError();
        $(btn).prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i>');

        $.ajax({
            url: $form.attr('action'), type: 'POST', data: _savedVoucherId ? serializeForm() : $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                if(res.success) {
                    clearFormError();
                    _savedVoucherId = res.id;
                    $('#voucher_id').val(res.id);
                    if (res.voucher_no) $('#voucher_no').val(res.voucher_no);
                    $('#idBadge').text('ID: ' + res.id).show();
                    $('#realPrintBtn').attr('href', '/stock-holds/print/' + res.id).attr('target', '_blank');
                    $form.attr('action', '/stock-holds/update/' + res.id);

                    if(res.status === 'Posted') {
                        setHoldFormPostedState(res.id, '/stock-holds/print/' + res.id);
                        showToast('Stock Hold Posted!', 'success');
                    } else {
                        $('#statusBadge').removeClass('bg-warning').addClass('bg-info text-white').html('<i class="fa fa-pencil"></i> Unposted');
                        $('#stockHoldForm').addClass('form-locked');
                        $('#editInvoiceBtn, #postBtn, #deleteBtn').prop('disabled', false);
                        showToast('Draft Saved — Ctrl+E to edit');
                    }
                } else { showFormError(res.message || 'Unable to save stock hold.'); }
            },
            error: function(xhr) { showFormError(extractAjaxError(xhr)); },
            complete: function() {
                if (act === 'post') _postInFlight = false; else _saveInFlight = false;
                if (!$('#stockHoldForm').hasClass('form-locked') || act === 'post') {
                    $(btn).prop('disabled', false).html(act === 'post' ? postBtnHtml : saveBtnHtml);
                }
            }
        });
    }

    function doPost() {
        if (_postInFlight || !_savedVoucherId) return;
        _postInFlight = true;
        $('#postBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i>');
        $.ajax({
            url: '/stock-holds/post/' + _savedVoucherId,
            type: 'POST',
            data: { _token: $('input[name="_token"]').val() },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                var printUrl = (res && res.print_url) ? res.print_url : ('/stock-holds/print/' + _savedVoucherId);
                setHoldFormPostedState(_savedVoucherId, printUrl);
                showToast('Stock Hold Posted!', 'success');
            },
            error: function(xhr) {
                showFormError(extractAjaxError(xhr));
                _postInFlight = false;
                $('#postBtn').prop('disabled', false).html(postBtnHtml);
            }
        });
    }

    $('#saveDraftBtn').on('click', function(e) { e.preventDefault(); if (!$(this).prop('disabled')) save('save'); });
    $('#postBtn').on('click', function(e) {
        e.preventDefault();
        if ($(this).prop('disabled')) return;
        if ($('#stockHoldForm').hasClass('form-locked') && _savedVoucherId) doPost();
        else save('post');
    });
    $('#editInvoiceBtn').on('click', function() {
        if ($(this).prop('disabled')) return;
        $('#stockHoldForm').removeClass('form-locked');
        $(this).prop('disabled', true);
        $('#postBtn').prop('disabled', true);
        $('#saveDraftBtn').prop('disabled', false).show().html(saveBtnHtml);
    });

    $('#realPrintBtn').on('click', function(e) {
        var href = $(this).attr('href');
        if (!href || href === 'javascript:void(0)') {
            e.preventDefault();
            showToast('Save first', 'error');
        }
    });

    document.addEventListener('keydown', function(e) {
        if (isHoldPostedView()) {
            if (e.key === 'Escape') { e.preventDefault(); window.location.href = $('#exitBtn').attr('href'); }
            if (e.ctrlKey && (e.key === 'm' || e.key === 'M')) { e.preventDefault(); window.location.href = $('#newInvoiceBtn').attr('href'); }
            if (e.ctrlKey && (e.key === 'p' || e.key === 'P')) {
                e.preventDefault();
                var href = $('#realPrintBtn').attr('href');
                if (href && href !== 'javascript:void(0)') window.open(href, '_blank');
            }
            return;
        }
        if (e.ctrlKey && (e.key === 's' || e.key === 'S')) {
            e.preventDefault(); e.stopImmediatePropagation();
            if (!_saveInFlight && !$('#saveDraftBtn').prop('disabled')) $('#saveDraftBtn').click();
        }
        if (e.ctrlKey && e.key === 'Enter') {
            e.preventDefault(); e.stopImmediatePropagation();
            if (!_postInFlight && !$('#postBtn').prop('disabled')) $('#postBtn').click();
        }
        if (e.ctrlKey && (e.key === 'p' || e.key === 'P')) {
            e.preventDefault();
            var href = $('#realPrintBtn').attr('href');
            if (href && href !== 'javascript:void(0)') window.open(href, '_blank');
            else showToast('Save first', 'error');
        }
        if (e.ctrlKey && (e.key === 'e' || e.key === 'E')) {
            e.preventDefault();
            if (!$('#editInvoiceBtn').prop('disabled')) $('#editInvoiceBtn').click();
        }
        if (e.ctrlKey && (e.key === 'm' || e.key === 'M')) {
            e.preventDefault();
            window.location.href = $('#newInvoiceBtn').attr('href');
        }
        if (e.ctrlKey && (e.key === 'd' || e.key === 'D')) {
            e.preventDefault();
            if (!$('#deleteBtn').prop('disabled')) $('#deleteBtn').click();
        }
        if (e.ctrlKey && (e.key === 'l' || e.key === 'L')) {
            e.preventDefault();
            window.location.href = $('#listBtn').attr('href');
        }
        if (e.key === 'Escape') {
            e.preventDefault();
            window.location.href = $('#exitBtn').attr('href');
        }
    }, true);
});
</script>
@endsection
