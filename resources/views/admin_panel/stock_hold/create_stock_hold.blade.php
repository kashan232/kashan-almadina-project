@extends('admin_panel.layout.app')

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
        font-size: 100px; color: rgba(220, 53, 69, 0.14); font-weight: 900; pointer-events: none; z-index: 1000;
        text-transform: uppercase; border: 10px solid rgba(220, 53, 69, 0.14); padding: 20px 40px; border-radius: 20px; display: none;
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
            <div class="d-flex justify-content-between align-items-center page-top-bar bg-white rounded shadow-sm border mb-2">
                <div style="min-width:80px;"></div>
                <div class="d-flex align-items-center gap-2 justify-content-center flex-grow-1">
                    <h6 class="page-title mb-0 fw-bold text-primary">
                        <i class="fas fa-hand-holding me-2"></i>Stock Hold Management
                        @if($isViewMode)
                            <span class="badge bg-info text-white px-2 py-1 rounded ms-1" style="font-size:10px;"><i class="fa fa-eye me-1"></i> View Only</span>
                        @endif
                    </h6>
                    <span id="statusBadge" class="badge {{ $isPosted ? 'bg-success text-white' : (isset($voucher) ? 'bg-info text-white' : 'bg-warning text-dark') }} px-3 py-1 rounded-pill shadow-sm" style="font-size:11px;">
                        <i class="fa {{ $isPosted ? 'fa-check' : 'fa-pencil' }} me-1"></i>
                        {{ isset($voucher) ? $voucher->status : 'New Hold' }}
                    </span>
                    <span id="idBadge" class="badge bg-primary px-3 py-1 rounded-pill shadow-sm" style="{{ isset($voucher) ? '' : 'display:none;' }} font-size:11px;">
                        <i class="fa fa-tag me-1"></i> ID: {{ isset($voucher) ? $voucher->id : 'NEW' }}
                    </span>
                </div>
                <div class="d-flex align-items-center justify-content-end" style="min-width:115px;">
                    <a href="{{ route('stock-hold-list') }}" id="listBtn" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1" style="font-size:.78rem;">
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

                {{-- Header Details Card --}}
                <div class="card shadow-sm mb-2">
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-2">
                                <label class="form-label">Entry Date</label>
                                <input type="date" name="entry_date" class="form-control input-sm" value="{{ isset($voucher) ? $voucher->date : date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Entry Time</label>
                                <input type="time" name="entry_time" class="form-control input-sm" value="{{ $entryTime }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Hold No.</label>
                                <input type="text" id="voucher_no" class="form-control input-sm fw-bold text-primary bg-light" value="{{ isset($voucher) ? $voucher->display_no : 'Auto-Generated' }}" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Type</label>
                                <select name="vendor_type" id="vendor_type" class="form-select input-sm" required>
                                    <option value="" disabled {{ isset($voucher) ? '' : 'selected' }}>Select Type</option>
                                    <option value="vendor" {{ (isset($voucher) && $voucher->party_type == 'vendor') ? 'selected' : '' }}>Vendor</option>
                                    <option value="customer" {{ (isset($voucher) && $voucher->party_type == 'customer') ? 'selected' : '' }}>Customer</option>
                                    <option value="walkin" {{ (isset($voucher) && $voucher->party_type == 'walkin') ? 'selected' : '' }}>Walkin Customer</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Select Party</label>
                                <select name="vendor_id" id="vendor_id" class="form-select select2" required>
                                    <option value="">Select Party</option>
                                    @if(isset($voucher) && $voucher->party_id)
                                        <option value="{{ $voucher->party_id }}" selected>{{ $partyLabel }}</option>
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Invoice (Optional)</label>
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
                                <label class="form-label">Location / Warehouse</label>
                                <select name="warehouse_id" id="warehouse_id" class="form-select select2" required>
                                    @if(auth()->user()->canAccessShop())
                                        <option value="0" {{ (isset($voucher) && (string)$voucher->warehouse_id === '0') ? 'selected' : '' }}>🏠 Shop</option>
                                    @endif
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}" {{ (isset($voucher) && $voucher->warehouse_id == $wh->id) ? 'selected' : '' }}>{{ $wh->warehouse_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Remarks</label>
                                <input type="text" name="remarks" class="form-control input-sm" value="{{ $voucher->remarks ?? '' }}" placeholder="Any special notes...">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Items Table Card --}}
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-boxes me-2 text-primary"></i>Items List</h6>
                            <span class="badge bg-secondary rounded-pill px-2 py-1" style="font-size:10px;">Total Items: <span id="total_items_badge">{{ isset($voucher) ? count($voucher->items) : 0 }}</span></span>
                        </div>
                        @if(!$isViewMode)
                        <div>
                            <button type="button" id="addRowBtn" class="btn btn-sm btn-primary rounded-pill px-3 py-1">
                                <i class="fa fa-plus me-1"></i> Add Row <kbd style="font-size:9px;opacity:.8;margin-left:3px;">Ctrl+I</kbd>
                            </button>
                        </div>
                        @endif
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0" id="itemsTable" style="width:100%; table-layout: fixed;">
                                <colgroup>
                                    <col style="width: 5%;">
                                    <col style="width: 12%;">
                                    <col style="width: 48%;">
                                    <col style="width: 13%;">
                                    <col style="width: 15%;">
                                    <col style="width: 7%;">
                                </colgroup>
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center">#</th>
                                        <th>Item ID</th>
                                        <th>Product Description</th>
                                        <th class="text-center">Sale Qty</th>
                                        <th class="text-center">Hold Qty</th>
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
                                                </td>
                                                <td>
                                                    <select name="product_id[]" class="form-select form-select-sm product-select" {{ $isViewMode ? 'disabled' : '' }}>
                                                        <option value="{{ $item->product_id }}" selected>{{ $item->product_id }} - {{ $item->product->name ?? 'Product' }}</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="number" name="sale_qty[]" class="form-control form-control-sm text-center input-readonly" value="{{ (float) $item->sale_qty }}" readonly>
                                                </td>
                                                <td>
                                                    <input type="number" name="hold_qty[]" class="form-control form-control-sm text-center hold-qty-input fw-bold text-primary" value="{{ (float) $item->hold_qty }}" step="any" min="0.01" required {{ $isViewMode ? 'readonly' : '' }}>
                                                </td>
                                                <td class="text-center">
                                                    @if(!$isViewMode)
                                                        <button type="button" class="btn btn-xs btn-outline-danger remove-row" title="Remove Row"><i class="fa fa-times"></i></button>
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
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top py-2">
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
var allProducts = @json($products->map(fn($p) => ['id' => (string)$p->id, 'name' => $p->name]));
var productsMap = {};
allProducts.forEach(function(p) { productsMap[p.id] = p.name; });

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

    // Initialize initial empty row for new form if table is empty
    if (!@json(isset($voucher)) && $('#itemRows tr').length === 0) {
        addNewRow();
    } else {
        updateRowNumbers();
    }

    function buildProductOptions(selectedId) {
        var html = '<option value="">-- Select Product --</option>';
        allProducts.forEach(function(p) {
            var sel = (String(p.id) === String(selectedId)) ? 'selected' : '';
            html += '<option value="' + p.id + '" ' + sel + '>' + p.id + ' - ' + $('<div>').text(p.name).html() + '</option>';
        });
        return html;
    }

    function addNewRow(pid, saleQty, holdQty) {
        pid = pid ? String(pid) : '';
        saleQty = saleQty !== undefined ? saleQty : 0;
        holdQty = holdQty !== undefined ? holdQty : 1;

        var rowIdx = $('#itemRows tr').length;
        var pName = pid && productsMap[pid] ? productsMap[pid] : '';

        var trHtml = `<tr data-row-idx="${rowIdx}">
            <td class="text-center fw-bold text-secondary row-num">${rowIdx + 1}</td>
            <td>
                <input type="text" class="form-control form-control-sm text-center item-id-input" value="${pid}" placeholder="ID" ${isViewMode ? 'readonly' : ''}>
            </td>
            <td>
                <select name="product_id[]" class="form-select form-select-sm product-select" ${isViewMode ? 'disabled' : ''}>
                    ${buildProductOptions(pid)}
                </select>
            </td>
            <td>
                <input type="number" name="sale_qty[]" class="form-control form-control-sm text-center input-readonly" value="${saleQty}" readonly>
            </td>
            <td>
                <input type="number" name="hold_qty[]" class="form-control form-control-sm text-center hold-qty-input fw-bold text-primary" value="${holdQty}" step="any" min="0.01" ${isViewMode ? 'readonly' : ''}>
            </td>
            <td class="text-center">
                ${!isViewMode ? '<button type="button" class="btn btn-xs btn-outline-danger remove-row" title="Remove Row"><i class="fa fa-times"></i></button>' : ''}
            </td>
        </tr>`;

        var $tr = $(trHtml);
        $('#itemRows').append($tr);
        $tr.find('.product-select').select2({ width: '100%' });
        updateRowNumbers();
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
                $tr.find('.hold-qty-input').focus().select();
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

    // Enter key on Hold Qty input creates new row if last row
    $(document).on('keydown', '.hold-qty-input', function(e) {
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

    // Remove row
    $(document).on('click', '.remove-row', function() {
        $(this).closest('tr').remove();
        updateRowNumbers();
    });

    function updateRowNumbers() {
        var count = 0;
        var totalSaleQty = 0;
        var totalHoldQty = 0;

        $('#itemRows tr').each(function(idx) {
            $(this).find('.row-num').text(idx + 1);
            var pid = $(this).find('.product-select').val();
            if (pid) {
                count++;
                var sQty = parseFloat($(this).find('[name="sale_qty[]"]').val()) || 0;
                var hQty = parseFloat($(this).find('.hold-qty-input').val()) || 0;
                totalSaleQty += sQty;
                totalHoldQty += hQty;
            }
        });

        $('#total_items_badge').text(count);
        $('#total_sale_qty').text(totalSaleQty % 1 === 0 ? totalSaleQty : totalSaleQty.toFixed(2));
        $('#total_hold_qty').text(totalHoldQty % 1 === 0 ? totalHoldQty : totalHoldQty.toFixed(2));
    }

    $(document).on('input change', '.hold-qty-input, [name="sale_qty[]"]', function() {
        updateRowNumbers();
    });

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
                $inv.append('<option value="' + item.id + '" data-is_order="' + (item.is_sale_order || 0) + '">' + item.text + '</option>');
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

        var $selectedOpt = $(this).find('option:selected');
        if ($selectedOpt.data('is_order') == 1) {
            Swal.fire({
                icon: 'warning',
                title: 'Order Mode Sale',
                text: 'Yeh Sale Form Order Mode par hai, isko Manual Stock Hold me select nahi kiya ja sakta!'
            });
            $(this).val('');
            $('#sale_id').val('');
            $('#itemRows').empty();
            updateRowNumbers();
            return;
        }

        $('#sale_id').val(id);
        $('#itemRows').empty();
        $.get("{{ url('stock-holds/invoice') }}/" + id + "/items", function(items) {
            if(items.length > 0 && items[0].warehouse_id !== undefined && items[0].warehouse_id !== null) {
                $('#warehouse_id').val(items[0].warehouse_id).trigger('change');
            }
            items.forEach(item => {
                var saleQty = item.qty || item.quantity || 0;
                addNewRow(item.product_id, saleQty, saleQty);
            });
            updateRowNumbers();
        });
    });

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
        updateRowNumbers();

        if($('#itemRows tr').length === 0) { 
            addNewRow();
            showToast('Add at least one item with product selected', 'error'); 
            return; 
        }

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

    // Keyboard Shortcuts
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
