@extends('admin_panel.layout.app')

@section('content')

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .stock-hold-page.container-fluid { padding: .35rem .5rem !important; }
    .stock-hold-page .main-content-inner { padding: 0 !important; }
    .stock-hold-page .page-top-bar { margin-bottom: .4rem !important; padding: .4rem .6rem !important; }
    .stock-hold-page .page-top-bar .page-title { font-size: .95rem !important; }
    .stock-hold-page .page-top-bar .badge { font-size: 11px !important; padding: .25rem .6rem !important; }
    .stock-hold-page .card { margin-bottom: .4rem !important; border-radius: .5rem !important; border: 1px solid #e5e7eb; }
    .stock-hold-page .card-body { padding: .55rem .7rem !important; }
    .stock-hold-page .card-footer { padding: .5rem .7rem !important; }
    .stock-hold-page .row.g-2 { --bs-gutter-x: .5rem; --bs-gutter-y: .35rem; }
    .stock-hold-page .form-label { margin-bottom: .15rem !important; font-size: .75rem !important; font-weight: 600; color: #475569; }
    .stock-hold-page .input-sm,
    .stock-hold-page .form-control,
    .stock-hold-page .form-select { height: 28px !important; min-height: 28px !important; padding: .15rem .45rem !important; font-size: .8rem !important; border-radius: 4px; }
    .stock-hold-page .select2-container .select2-selection--single { height: 28px !important; border: 1px solid #ced4da; border-radius: 4px; }
    .stock-hold-page .select2-container .select2-selection--single .select2-selection__rendered { line-height: 26px !important; padding-left: 6px !important; font-size: .8rem !important; }
    .stock-hold-page .select2-container .select2-selection--single .select2-selection__arrow { height: 26px !important; }
    
    .stock-hold-page .table-responsive { max-height: 380px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 6px; }
    .stock-hold-page .table th { vertical-align: middle !important; padding: 6px 8px !important; font-size: .78rem !important; text-transform: uppercase; letter-spacing: .3px; }
    .stock-hold-page .table td { vertical-align: middle !important; padding: 3px 5px !important; font-size: .8rem !important; }
    .stock-hold-page .table .form-control, 
    .stock-hold-page .table .form-select { height: 26px !important; min-height: 26px !important; padding: 2px 6px !important; font-size: .78rem !important; }
    
    .stock-hold-page .bottom-bar-btns { gap: .4rem !important; }
    .stock-hold-page .bottom-bar-btns .btn { padding: .3rem .75rem !important; font-size: .8rem !important; font-weight: 600; border-radius: 6px; }
    
    .input-readonly { background-color: #f8fafc !important; }
    .hold-pill-card { padding: .2rem .5rem !important; }

    .form-locked { position: relative; opacity: 0.85; }
    .form-locked .card-body { pointer-events: none !important; }
    .form-locked input, .form-locked .select2-container--default .select2-selection--single, .form-locked select, .form-locked textarea { 
        background-color: #e9ecef !important; cursor: not-allowed !important; 
    }
    .form-locked .remove-row, .form-locked #addRowBtn, .form-locked #saveDraftBtn { display: none !important; }
    .form-locked #editInvoiceBtn, .form-locked #newInvoiceBtn, .form-locked #realPrintBtn,
    .form-locked #postBtn, .form-locked #exitBtn, .form-locked #deleteBtn {
        pointer-events: auto !important; opacity: 1 !important;
    }

    .form-locked.view-mode #saveDraftBtn,
    .form-locked.view-mode #editInvoiceBtn,
    .form-locked.view-mode #postBtn,
    .form-locked.view-mode #deleteBtn {
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
        font-size: 100px; color: rgba(40, 167, 69, 0.14); font-weight: 900; pointer-events: none; z-index: 1000;
        text-transform: uppercase; border: 10px solid rgba(40, 167, 69, 0.14); padding: 20px 40px; border-radius: 20px; display: none;
    }
    .posted-watermark.show { display: block; }
</style>

@php
    $isViewMode = isset($viewMode) && $viewMode;
    $isEditMode = isset($voucher) && !$isViewMode;
    $isPosted = isset($voucher) && $voucher->status === 'Posted';
    $formClass = 'position-relative';
    if ($isViewMode || $isPosted || $isEditMode) {
        $formClass .= ' form-locked';
    }
    if ($isViewMode) {
        $formClass .= ' view-mode';
    }
    $partyLabel = '';
    if (isset($voucher)) {
        $partyLabel = $voucher->party_type === 'vendor'
            ? ($voucher->partyVendor->name ?? 'N/A')
            : ($voucher->partyCustomer->customer_name ?? 'N/A');
    }
@endphp
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid stock-hold-page">

            {{-- TOP BAR --}}
            <div class="d-flex justify-content-between align-items-center page-top-bar bg-white rounded shadow-sm border mb-2">
                <div style="min-width:80px;"></div>
                <div class="d-flex align-items-center gap-2 justify-content-center flex-grow-1">
                    <h6 class="page-title mb-0 fw-bold text-success">
                        <i class="fas fa-box-open me-2"></i>Stock Release Management
                        @if($isViewMode)
                            <span class="badge bg-info text-white px-2 py-1 rounded ms-1" style="font-size:10px;"><i class="fa fa-eye me-1"></i> View Only</span>
                        @endif
                    </h6>
                    <span id="statusBadge" class="badge {{ $isPosted ? 'bg-success text-white' : (isset($voucher) ? 'bg-info text-white' : 'bg-warning text-dark') }} px-3 py-1 rounded-pill shadow-sm" style="font-size:11px;">
                        <i class="fa {{ $isPosted ? 'fa-check' : 'fa-pencil' }} me-1"></i>
                        {{ isset($voucher) ? $voucher->status : 'New Release' }}
                    </span>
                    <span id="idBadge" class="badge bg-primary px-3 py-1 rounded-pill shadow-sm" style="{{ isset($voucher) ? '' : 'display:none;' }} font-size:11px;">
                        <i class="fa fa-tag me-1"></i> ID: {{ isset($voucher) ? $voucher->id : 'NEW' }}
                    </span>
                </div>
                <div class="d-flex align-items-center justify-content-end" style="min-width:115px;">
                    <a href="{{ route('stock-relase-list') }}" id="listBtn" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1" style="font-size:.78rem;">
                        <i class="fa fa-list me-1"></i> List <kbd style="font-size:9px;opacity:.7;margin-left:4px;">Ctrl+L</kbd>
                    </a>
                </div>
            </div>

            <form action="{{ $isViewMode ? '#' : (isset($voucher) ? route('stock-holds.release.update', $voucher->id) : route('stock-holds.release.bulk_store')) }}" method="POST" id="stockReleaseForm" class="{{ $formClass }}">
                @csrf
                <input type="hidden" name="action" id="formAction" value="save">
                <input type="hidden" name="id" id="voucher_id" value="{{ $voucher->id ?? '' }}">
                <input type="hidden" name="hold_voucher_id" id="hold_voucher_id" value="{{ $voucher->hold_voucher_id ?? '' }}">
                <div class="posted-watermark {{ $isPosted ? 'show' : '' }}" id="postedWatermark">Posted</div>

                {{-- Header Details Card --}}
                <div class="card shadow-sm mb-2">
                    <div class="card-body">
                        <div class="row g-2 mb-2 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label">Release Date</label>
                                <input type="date" name="entry_date" class="form-control input-sm" value="{{ isset($voucher) ? $voucher->date : date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Release No.</label>
                                <input type="text" id="release_no" class="form-control input-sm fw-bold text-success bg-light" value="{{ isset($voucher) ? $voucher->display_no : 'Auto-Generated' }}" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Deliver From <span class="text-danger">*</span></label>
                                <select name="warehouse_id" id="warehouse_id" class="form-select input-sm" required>
                                    @if(auth()->user()->canAccessShop())
                                        <option value="0" {{ (isset($voucher) && (string)$voucher->warehouse_id === '0') ? 'selected' : '' }}>🏠 Shop Stock</option>
                                    @endif
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}" {{ (isset($voucher) && $voucher->warehouse_id == $wh->id) ? 'selected' : '' }}>📦 {{ $wh->warehouse_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Remarks</label>
                                <input type="text" name="remarks" class="form-control input-sm" value="{{ $voucher->remarks ?? '' }}" placeholder="Optional release notes...">
                            </div>
                        </div>

                        <div class="row g-2 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label text-primary">Party Type <span class="text-danger">*</span></label>
                                <select name="vendor_type" id="vendor_type" class="form-select input-sm" required>
                                    <option value="" disabled {{ isset($voucher) ? '' : 'selected' }}>Select Type...</option>
                                    <option value="vendor" {{ (isset($voucher) && $voucher->party_type == 'vendor') ? 'selected' : '' }}>Vendor</option>
                                    <option value="customer" {{ (isset($voucher) && $voucher->party_type == 'customer') ? 'selected' : '' }}>Customer</option>
                                    <option value="walkin" {{ (isset($voucher) && $voucher->party_type == 'walkin') ? 'selected' : '' }}>Walking Customer</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-primary">Code / ID</label>
                                <input type="text" id="party_code_input" class="form-control input-sm text-center fw-bold text-danger" value="{{ $voucher->party_id ?? '' }}" placeholder="ID">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-primary">Party Name <span class="text-danger">*</span></label>
                                <select name="vendor_id" id="vendor_id" class="form-select select2" required>
                                    <option value="">Select Party...</option>
                                    @if(isset($voucher) && $voucher->party_id)
                                        <option value="{{ $voucher->party_id }}" selected>{{ $partyLabel }}</option>
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-primary border-opacity-25 bg-primary bg-opacity-10 hold-pill-card rounded-3 mb-0">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fa fa-search text-primary ms-1"></i>
                                        <div class="flex-grow-1">
                                            <label class="form-label x-small fw-bold text-primary mb-0" style="font-size:10px;">PULL FROM EXISTING HOLD / CLAIM</label>
                                            <select id="hold_select" class="form-select select2" disabled>
                                                <option value="">Choose Party First...</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="claim_id" id="form_claim_id">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Items Table Card --}}
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-boxes me-2 text-success"></i>Release Items List</h6>
                            <span class="badge bg-secondary rounded-pill px-2 py-1" style="font-size:10px;">Total Items: <span id="total_items_badge">0</span></span>
                        </div>
                        <div>
                            <button type="button" id="addRowBtn" class="btn btn-sm btn-success rounded-pill px-3 py-1">
                                <i class="fa fa-plus me-1"></i> Add Row <kbd style="font-size:9px;opacity:.8;margin-left:3px;">Ctrl+I</kbd>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0" id="itemsTable" style="width:100%; table-layout: fixed;">
                                <colgroup>
                                    <col style="width: 5%;">
                                    <col style="width: 12%;">
                                    <col style="width: 44%;">
                                    <col style="width: 11%;">
                                    <col style="width: 11%;">
                                    <col style="width: 12%;">
                                    <col style="width: 5%;">
                                </colgroup>
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center">#</th>
                                        <th>Item ID</th>
                                        <th>Product Description</th>
                                        <th class="text-center">Sale Qty</th>
                                        <th class="text-center">Hold Qty</th>
                                        <th class="text-center">Release Qty</th>
                                        <th class="text-center">Act</th>
                                    </tr>
                                </thead>
                                <tbody id="itemRows">
                                    @if(isset($voucher) && count($voucher->items) > 0)
                                        @foreach($voucher->items as $idx => $item)
                                            <tr data-row-idx="{{ $idx }}">
                                                <td class="text-center fw-bold text-secondary row-num">{{ $idx + 1 }}</td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm text-center item-id-input" value="{{ $item->product_id }}" placeholder="ID" {{ $isViewMode ? 'readonly' : '' }}>
                                                    <input type="hidden" name="product_id[]" class="product-id-hidden" value="{{ $item->product_id }}">
                                                    <input type="hidden" name="hold_id[]" class="hold-id-hidden" value="{{ $item->hold_id }}">
                                                </td>
                                                <td>
                                                    <select class="form-select form-select-sm product-select" {{ $isViewMode ? 'disabled' : '' }}>
                                                        <option value="">Select Item...</option>
                                                        @foreach($products as $p)
                                                            <option value="{{ $p->id }}" {{ $item->product_id == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="number" name="sale_qty[]" class="form-control form-control-sm text-center sale-qty-input bg-light" value="{{ (float) $item->sale_qty }}" readonly>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control form-control-sm text-center hold-qty-input bg-light" value="{{ (float) ($item->hold->hold_qty ?? 0) }}" readonly>
                                                </td>
                                                <td>
                                                    <input type="number" name="release_qty[]" class="form-control form-control-sm text-center fw-bold text-success release-qty-input" value="{{ (float) $item->release_qty }}" step="any" min="0" {{ $isViewMode ? 'readonly' : '' }}>
                                                </td>
                                                <td class="text-center">
                                                    @if(!$isViewMode)
                                                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1 remove-row" style="font-size:11px;"><i class="fa fa-times"></i></button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                                <tfoot class="table-light border-top">
                                    <tr>
                                        <th colspan="3" class="text-end fw-bold">Grand Total:</th>
                                        <th class="text-center fw-bold text-dark fs-6" id="total_sale_qty">0</th>
                                        <th class="text-center fw-bold text-primary fs-6" id="total_hold_qty">0</th>
                                        <th class="text-center fw-bold text-success fs-6" id="total_release_qty">0</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top py-2">
                        <div class="d-flex flex-wrap justify-content-center w-100 bottom-bar-btns">
                            <button type="button" id="saveDraftBtn" class="btn btn-primary px-3 fw-bold shadow-sm">
                                <u>S</u>ave <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>
                            </button>
                            <button type="button" id="editInvoiceBtn" class="btn btn-warning px-3 fw-bold text-dark shadow-sm" disabled>
                                <u>E</u>dit <kbd style="font-size:10px;opacity:.8;margin-left:4px;color:#fff;">Ctrl+E</kbd>
                            </button>
                            <button type="button" id="postBtn" class="btn btn-success px-3 fw-bold shadow-sm">
                                <u>P</u>ost <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+&crarr;</kbd>
                            </button>
                            <button type="button" id="deleteBtn" class="btn btn-danger px-3 fw-bold shadow-sm" disabled title="Delete not available">
                                <u>D</u>elete <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+D</kbd>
                            </button>
                            <a href="javascript:void(0)" id="realPrintBtn" class="btn btn-info px-3 fw-bold text-dark shadow-sm">
                                <u>P</u>rint <kbd style="font-size:10px;opacity:.8;margin-left:4px;color:#fff;">Ctrl+P</kbd>
                            </a>
                            <a href="{{ route('stock-relase-list') }}" id="exitBtn" class="btn btn-secondary px-3 fw-bold shadow-sm text-white">
                                E<u>x</u>it <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Esc</kbd>
                            </a>
                            <a href="{{ route('stock-holds.release.add') }}" id="newInvoiceBtn" class="btn btn-dark px-3 fw-bold shadow-sm text-white">
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
var allProducts = @json(isset($products) ? $products->map(fn($p) => ['id' => (string)$p->id, 'name' => $p->name]) : []);
var productsMap = {};
allProducts.forEach(function(p) { productsMap[p.id] = p.name; });

$(document).ready(function() {
    function showToast(msg, type = 'success') {
        var icon = type === 'success' ? 'fa-check-circle' : 'fa-times-circle';
        var color = type === 'success' ? '#28a745' : '#dc3545';
        var $toast = $('<div>').css({
            position: 'fixed', top: '20px', right: '20px', zIndex: 9999,
            background: color, color: '#fff', padding: '12px 20px', borderRadius: '8px',
            boxShadow: '0 4px 15px rgba(0,0,0,.2)', display: 'flex', alignItems: 'center', gap: '8px',
            maxWidth: '420px', lineHeight: '1.35'
        }).html('<i class="fa ' + icon + '"></i> ' + $('<div>').text(msg).html());
        $('body').append($toast);
        setTimeout(function() { $toast.fadeOut(400, function(){ $(this).remove(); }); }, 3000);
    }

    // Party Selection Logic
    $('#vendor_id').select2({ width: '100%', placeholder: 'Select Party...' });
    
    $('#vendor_type').on('change', function() {
        let type = $(this).val();
        let $partySelect = $('#vendor_id');
        $partySelect.html('<option value="">Loading...</option>');
        
        if (!type) {
            $partySelect.html('<option value="">Select Party...</option>');
            return;
        }

        $.get('{{ route("party.list") }}?type=' + type, function(data) {
            $partySelect.html('<option value="">Select Party...</option>');
            data.forEach(item => {
                $partySelect.append(`<option value="${item.id}">${item.text}</option>`);
            });
            $partySelect.trigger('change');
        });
        
        resetSelections();
    });

    // Code/ID Sync
    $('#party_code_input').on('keydown', function(e) {
        if (e.key === 'Enter' || e.key === 'Tab') {
            if (e.key === 'Enter') e.preventDefault();
            let val = $(this).val();
            if (val) {
                let $option = $('#vendor_id option').filter(function() { return $(this).val() == val; });
                if ($option.length > 0) {
                    $('#vendor_id').val(val).trigger('change');
                    $('#hold_select').select2('open');
                } else {
                    showToast('Party ID not found!', 'error');
                }
            }
        }
    });

    $('#vendor_id').on('change', function() {
        let val = $(this).val();
        $('#party_code_input').val(val || '');
        if (val) {
            $('#hold_select').prop('disabled', false).html('<option value="">Select Record...</option>');
            initHoldSelect();
        } else {
            resetSelections();
        }
        $('#itemRows').empty();
        addNewRow();
        updateCount();
    });

    function resetSelections() {
        $('#hold_select').prop('disabled', true).html('<option value="">Choose Party First</option>').trigger('change');
        $('#itemRows').empty();
        addNewRow();
        updateCount();
    }

    // Initialize 1 empty row on page load if empty
    if ($('#itemRows tr').length === 0) {
        addNewRow();
    }

    function buildProductOptions(selectedId) {
        var html = '<option value="">-- Select Product --</option>';
        allProducts.forEach(function(p) {
            var sel = (String(p.id) === String(selectedId)) ? 'selected' : '';
            html += '<option value="' + p.id + '" ' + sel + '>' + p.id + ' - ' + $('<div>').text(p.name).html() + '</option>';
        });
        return html;
    }

    function addNewRow(pid, saleQty, holdQty, releaseQty, holdId) {
        pid = pid ? String(pid) : '';
        saleQty = saleQty !== undefined ? saleQty : 0;
        holdQty = holdQty !== undefined ? holdQty : 0;
        releaseQty = releaseQty !== undefined ? releaseQty : 1;
        holdId = holdId || '';

        var rowIdx = $('#itemRows tr').length;

        var trHtml = `<tr data-row-idx="${rowIdx}">
            <td class="text-center fw-bold text-secondary row-num">${rowIdx + 1}</td>
            <td>
                <input type="text" class="form-control form-control-sm text-center item-id-input" value="${pid}" placeholder="ID">
                <input type="hidden" name="hold_id[]" value="${holdId}">
            </td>
            <td>
                <select name="product_id[]" class="form-select form-select-sm product-select">
                    ${buildProductOptions(pid)}
                </select>
            </td>
            <td>
                <input type="number" name="sale_qty[]" class="form-control form-control-sm text-center input-readonly" value="${saleQty}" readonly>
            </td>
            <td>
                <input type="number" name="hold_qty[]" class="form-control form-control-sm text-center input-readonly" value="${holdQty}" readonly>
            </td>
            <td>
                <input type="number" name="release_qty[]" class="form-control form-control-sm text-center release-qty-input fw-bold text-success" value="${releaseQty}" step="any" min="0.01" required>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-xs btn-outline-danger remove-row" title="Remove Row"><i class="fa fa-times"></i></button>
            </td>
        </tr>`;

        var $tr = $(trHtml);
        $('#itemRows').append($tr);
        $tr.find('.product-select').select2({ width: '100%' });
        updateCount();
        return $tr;
    }

    $('#addRowBtn').on('click', function() {
        var $newRow = addNewRow();
        $newRow.find('.item-id-input').focus();
    });

    // Item ID Input change / keyup handling
    $(document).on('change blur keyup', '.item-id-input', function(e) {
        if (e.type === 'keyup' && e.key !== 'Enter') return;
        var $tr = $(this).closest('tr');
        var pid = $.trim($(this).val());
        var $sel = $tr.find('.product-select');
        
        if (pid && productsMap[pid]) {
            if ($sel.val() !== pid) {
                $sel.val(pid).trigger('change.select2');
            }
            if ($tr.is(':last-child')) {
                addNewRow();
            }
            if (e.type === 'keyup' || e.type === 'change') {
                $tr.find('.release-qty-input').focus().select();
            }
        } else if (pid && !productsMap[pid]) {
            showToast('Product ID ' + pid + ' not found', 'error');
            $sel.val('').trigger('change.select2');
        }
    });

    // Product Select change handling
    $(document).on('change', '.product-select', function() {
        var $tr = $(this).closest('tr');
        var pid = $(this).val();
        var $idInput = $tr.find('.item-id-input');
        
        if (pid) {
            $idInput.val(pid);
            if ($tr.is(':last-child')) {
                addNewRow();
            }
        } else {
            $idInput.val('');
        }
    });

    // Enter key on Release Qty input creates new row if last row
    $(document).on('keydown', '.release-qty-input', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            var $tr = $(this).closest('tr');
            if ($tr.is(':last-child')) {
                var $newRow = addNewRow();
                $newRow.find('.item-id-input').focus();
            } else {
                $tr.next('tr').find('.item-id-input').focus();
            }
        }
    });

    // Hold / Claim Filtering (Unified)
    function initHoldSelect() {
        $('#hold_select').select2({
            width: '100%',
            placeholder: 'Search Hold / Claim Record...',
            ajax: {
                url: "{{ route('stock-holds.list.json') }}",
                dataType: 'json', delay: 250,
                data: function(params) { 
                    return { 
                        q: params.term,
                        party_type: $('#vendor_type').val(),
                        party_id: $('#vendor_id').val(),
                        include_claims: 1
                    }; 
                },
                processResults: function(data) { return { results: data }; }
            }
        });
    }

    // Selection Details
    $('#hold_select').on('change', function() {
        var val = $(this).val();
        if(!val) return;
        
        var parts = val.split(':');
        var type = parts[0];
        var id = parts[1];
        
        if(type === 'claim') {
            $('#hold_voucher_id').val('');
            $('#form_claim_id').val(id);
            $.get("{{ url('customer-claims-release/details') }}/" + id, function(res) {
                $('#warehouse_id').val(res.warehouse_id);
                $('#itemRows').empty();
                addNewRow(res.product_id, res.hold_qty, res.hold_qty, res.hold_qty, res.hold_id || '');
                updateCount();
            });
        } else {
            $('#hold_voucher_id').val(id);
            $('#form_claim_id').val('');
            $.get("{{ url('stock-holds/voucher') }}/" + id + "/details", function(res) {
                $('#warehouse_id').val(res.warehouse_id);
                $('#itemRows').empty();
                res.items.forEach(item => {
                    var remaining = item.remaining_qty ?? item.release_qty ?? item.hold_qty;
                    addNewRow(item.product_id, item.sale_qty, item.hold_qty, remaining, item.hold_id || '');
                });
                updateCount();
            });
        }
    });

    // Remove row
    $(document).on('click', '.remove-row', function() { 
        $(this).closest('tr').remove(); 
        updateCount(); 
    });

    $(document).on('input change', '.release-qty-input, [name="sale_qty[]"], [name="hold_qty[]"]', function() { 
        updateCount(); 
    });

    function updateCount() {
        var count = 0;
        var totalSaleQty = 0;
        var totalHoldQty = 0;
        var totalReleaseQty = 0;

        $('#itemRows tr').each(function(idx) {
            $(this).find('.row-num').text(idx + 1);
            var pid = $(this).find('.product-select').val();
            if (pid) {
                count++;
                totalSaleQty += parseFloat($(this).find('[name="sale_qty[]"]').val()) || 0;
                totalHoldQty += parseFloat($(this).find('[name="hold_qty[]"]').val()) || 0;
                totalReleaseQty += parseFloat($(this).find('.release-qty-input').val()) || 0;
            }
        });

        $('#total_items_badge').text(count);
        $('#total_sale_qty').text(totalSaleQty % 1 === 0 ? totalSaleQty : totalSaleQty.toFixed(2));
        $('#total_hold_qty').text(totalHoldQty % 1 === 0 ? totalHoldQty : totalHoldQty.toFixed(2));
        $('#total_release_qty').text(totalReleaseQty % 1 === 0 ? totalReleaseQty : totalReleaseQty.toFixed(2));
    }

    var _savedVoucherId = @json(isset($voucher) ? (string)$voucher->id : null);
    var _saveInFlight = false;
    var _postInFlight = false;
    var saveBtnHtml = '<u>S</u>ave <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>';
    var postBtnHtml = '<u>P</u>ost <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+&crarr;</kbd>';
    var isViewMode = @json($isViewMode);

    if (_savedVoucherId) {
        $('#realPrintBtn').attr('href', '/stock-release/print/' + _savedVoucherId).attr('target', '_blank');
        if (@json($isPosted)) {
            setReleaseFormPostedState(_savedVoucherId, '/stock-release/print/' + _savedVoucherId);
        } else if (!isViewMode) {
            $('#editInvoiceBtn, #postBtn').prop('disabled', false);
            $('#saveDraftBtn').prop('disabled', true);
        }
    }

    function setReleaseFormPostedState(voucherId, printUrl) {
        _savedVoucherId = voucherId;
        $('#stockReleaseForm').addClass('form-locked view-mode');
        $('#postedWatermark').addClass('show');
        $('#statusBadge').removeClass('bg-warning bg-info').addClass('bg-success text-white').html('<i class="fa fa-check"></i> Posted');
        $('#saveDraftBtn, #editInvoiceBtn, #postBtn, #deleteBtn').prop('disabled', true);
        $('#postBtn').html(postBtnHtml);
        $('#realPrintBtn').attr('href', printUrl || ('/stock-release/print/' + voucherId)).attr('target', '_blank');
    }

    function isReleasePostedView() {
        return $('#stockReleaseForm').hasClass('view-mode');
    }

    function serializeForm() {
        var data = $('#stockReleaseForm').serializeArray();
        ['vendor_id', 'warehouse_id', 'vendor_type', 'hold_voucher_id', 'claim_id', 'action'].forEach(function(name) {
            var val = $('[name="' + name + '"]').val() || '';
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

    // Save Logic
    function save(act) {
        if (_saveInFlight || _postInFlight) return;
        $('#formAction').val(act);
        
        // Auto-remove empty rows where no product is selected before saving
        $('#itemRows tr').each(function() {
            var pid = $(this).find('.product-select').val();
            if (!pid) {
                $(this).remove();
            }
        });
        updateCount();

        if($('#itemRows tr').length === 0) { 
            addNewRow();
            showToast('Please add at least one item with product selected', 'error'); 
            return; 
        }

        var $form = $('#stockReleaseForm');
        if(!$form[0].checkValidity()) { $form[0].reportValidity(); return; }

        var btn = act === 'post' ? '#postBtn' : '#saveDraftBtn';
        if (act === 'post') _postInFlight = true; else _saveInFlight = true;
        $(btn).prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i>');

        $.ajax({
            url: $form.attr('action'), type: 'POST', data: serializeForm(),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                if(res.success) {
                    _savedVoucherId = res.id;
                    if (res.voucher_no) $('#release_no').val(res.voucher_no);
                    $('#realPrintBtn').attr('href', '/stock-release/print/' + res.id).attr('target', '_blank');
                    $form.attr('action', '/stock-release/update/' + res.id);

                    if(res.status === 'Posted') {
                        setReleaseFormPostedState(res.id, '/stock-release/print/' + res.id);
                        showToast('Stock Released Successfully!', 'success');
                    } else {
                        $('#statusBadge').removeClass('bg-warning').addClass('bg-info text-white').html('<i class="fa fa-pencil"></i> Draft Saved');
                        $('#stockReleaseForm').addClass('form-locked');
                        $('#editInvoiceBtn, #postBtn').prop('disabled', false);
                        showToast('Release Saved as Draft — Ctrl+E to edit');
                    }
                } else { showToast(res.message || 'Error saving release', 'error'); }
            },
            error: function(xhr) {
                var msg = 'Server Error';
                if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                showToast(msg, 'error');
            },
            complete: function() {
                if (act === 'post') _postInFlight = false; else _saveInFlight = false;
                if (!$('#stockReleaseForm').hasClass('form-locked')) {
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
            url: '/stock-release/post/' + _savedVoucherId,
            type: 'POST',
            data: { _token: $('input[name="_token"]').val() },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                var printUrl = (res && res.print_url) ? res.print_url : ('/stock-release/print/' + _savedVoucherId);
                setReleaseFormPostedState(_savedVoucherId, printUrl);
                showToast('Stock Released Successfully!', 'success');
            },
            error: function(xhr) {
                var msg = 'Post failed';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                showToast(msg, 'error');
                _postInFlight = false;
                $('#postBtn').prop('disabled', false).html(postBtnHtml);
            }
        });
    }

    $('#saveDraftBtn').on('click', function(e) { e.preventDefault(); if (!$(this).prop('disabled')) save('save'); });
    $('#postBtn').on('click', function(e) {
        e.preventDefault();
        if ($(this).prop('disabled')) return;
        if ($('#stockReleaseForm').hasClass('form-locked') && _savedVoucherId) doPost();
        else save('post');
    });
    $('#editInvoiceBtn').on('click', function() {
        if ($(this).prop('disabled')) return;
        $('#stockReleaseForm').removeClass('form-locked');
        $(this).prop('disabled', true);
        $('#postBtn').prop('disabled', true);
        $('#saveDraftBtn').prop('disabled', false).html(saveBtnHtml);
    });

    $('#realPrintBtn').on('click', function(e) {
        var href = $(this).attr('href');
        if (!href || href === 'javascript:void(0)') {
            e.preventDefault();
            showToast('Save as Draft first', 'error');
        }
    });

    // Keyboard Shortcuts
    document.addEventListener('keydown', function(e) {
        if (isReleasePostedView()) {
            if (e.key === 'Escape') { e.preventDefault(); window.location.href = $('#exitBtn').attr('href'); }
            if (e.ctrlKey && (e.key === 'm' || e.key === 'M')) { e.preventDefault(); window.location.href = $('#newInvoiceBtn').attr('href'); }
            if (e.ctrlKey && (e.key === 'p' || e.key === 'P')) {
                e.preventDefault();
                var href = $('#realPrintBtn').attr('href');
                if (href && href !== 'javascript:void(0)') window.open(href, '_blank');
            }
            return;
        }
        if (e.ctrlKey && (e.key === 'i' || e.key === 'I')) {
            e.preventDefault();
            $('#addRowBtn').click();
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
            else showToast('Save as Draft first', 'error');
        }
        if (e.ctrlKey && (e.key === 'e' || e.key === 'E')) {
            e.preventDefault();
            if (!$('#editInvoiceBtn').prop('disabled')) $('#editInvoiceBtn').click();
        }
        if (e.ctrlKey && (e.key === 'm' || e.key === 'M')) {
            e.preventDefault();
            window.location.href = $('#newInvoiceBtn').attr('href');
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
