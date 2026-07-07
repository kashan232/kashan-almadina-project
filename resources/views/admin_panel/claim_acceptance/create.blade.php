@extends('admin_panel.layout.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .stock-hold-page.container-fluid { padding: .15rem .3rem !important; }
    .stock-hold-page .main-content-inner { padding: 0 !important; }
    .stock-hold-page .main-container { padding: .3rem .4rem !important; font-size: .82rem; max-width: 98%; }
    .stock-hold-page .page-top-bar { margin-bottom: .2rem !important; padding: .25rem .4rem !important; }
    .stock-hold-page .page-top-bar .page-title { font-size: .85rem !important; }
    .stock-hold-page .page-top-bar .badge { font-size: 10px !important; padding: .15rem .45rem !important; }
    .stock-hold-page .page-top-bar .gap-3 { gap: .3rem !important; }
    .stock-hold-page .mb-4, .stock-hold-page .mb-3 { margin-bottom: .25rem !important; }
    .stock-hold-page .mb-2 { margin-bottom: .15rem !important; }
    .stock-hold-page .mt-2, .stock-hold-page .mt-3 { margin-top: .15rem !important; }
    .stock-hold-page .row.g-3, .stock-hold-page .row.g-2 { --bs-gutter-x: .3rem; --bs-gutter-y: .12rem; }
    .stock-hold-page .card { margin-bottom: .2rem !important; }
    .stock-hold-page .card-body { padding: .35rem .45rem !important; }
    .stock-hold-page .card-body.p-0 { padding: 0 !important; }
    .stock-hold-page .card-header { padding: .2rem .45rem !important; }
    .stock-hold-page .card-header.py-3 { padding-top: .2rem !important; padding-bottom: .2rem !important; }
    .stock-hold-page .card-header h6 { font-size: .78rem !important; margin: 0 !important; }
    .stock-hold-page .card.border-0.bg-light { padding: .25rem .35rem !important; margin-bottom: .12rem !important; }
    .stock-hold-page .card-footer { padding: .35rem .45rem !important; }
    .stock-hold-page .form-label { margin-bottom: 0 !important; font-size: .7rem !important; line-height: 1.1; }
    .stock-hold-page .form-label.mb-1 { margin-bottom: 0 !important; }
    .stock-hold-page .form-control, .stock-hold-page .form-select { height: 24px !important; min-height: 24px !important; padding: .05rem .35rem !important; font-size: .76rem !important; }
    .stock-hold-page .select2-container--default .select2-selection--single { height: 24px !important; padding: 0 4px !important; font-size: .76rem !important; border: 1px solid #dee2e6 !important; }
    .stock-hold-page .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 22px !important; }
    .stock-hold-page .select2-container--default .select2-selection--single .select2-selection__arrow { height: 22px !important; }
    .stock-hold-page .table thead th { background: #f8f9fa !important; text-align: center; font-size: .7rem; padding: 1px 3px !important; white-space: nowrap; }
    .stock-hold-page .table td { vertical-align: middle; padding: 1px 3px !important; font-size: .76rem; }
    .stock-hold-page .table .form-control { height: 22px !important; min-height: 22px !important; padding: 0 3px !important; font-size: .72rem !important; }
    .stock-hold-page .table tfoot td.py-3 { padding: .15rem .3rem !important; }
    .stock-hold-page #addItemBtn { height: 22px; padding: 0 .45rem; font-size: .72rem; line-height: 1.1; }
    .stock-hold-page .bottom-bar-btns { gap: .3rem !important; }
    .stock-hold-page .bottom-bar-btns .btn { padding: .2rem .55rem !important; font-size: .76rem !important; }
    .stock-hold-page .alert.mb-2 { margin-bottom: .12rem !important; padding: .3rem .45rem; font-size: .78rem; }
    .stock-hold-page .btr-search-card { padding: .2rem .35rem !important; }
    .stock-hold-page .btr-search-card .fa-barcode { font-size: 1rem !important; }
    .stock-hold-page .summary-card .card-body { padding: .35rem .45rem !important; }
    .stock-hold-page .summary-card hr { margin: .25rem 0 !important; }
    .stock-hold-page .total-highlight { font-size: .95rem !important; }
    .stock-hold-page .summary-card .fs-5 { font-size: .85rem !important; }

    .posted-watermark {
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg);
        font-size: 8rem; color: rgba(220, 53, 69, 0.1); font-weight: 900; text-transform: uppercase;
        pointer-events: none; z-index: 1000; display: none; border: 10px solid rgba(220, 53, 69, 0.1); padding: 20px 50px; border-radius: 20px;
    }
    .posted-watermark.show { display: block; }

    .form-locked { position: relative; opacity: 0.8; }
    .form-locked input, .form-locked select, .form-locked textarea, .form-locked #addItemBtn, .form-locked .remove-row, .form-locked .select2-container {
        pointer-events: none !important; background-color: #e9ecef !important; cursor: not-allowed !important;
    }
    .form-locked #saveDraftBtn { display: none !important; }
    .form-locked #editInvoiceBtn, .form-locked #newInvoiceBtn, .form-locked #realPrintBtn,
    .form-locked #postBtn, .form-locked #exitBtn, .form-locked #deleteBtn {
        pointer-events: auto !important; opacity: 1 !important;
    }

    .ajax-valid-error { color: #dc3545; font-size: 0.75rem; font-weight: 700; margin-bottom: 2px; display: block; }
</style>

@php
    $isViewMode = isset($viewMode) && $viewMode;
    $isPosted = isset($voucher) && $voucher->status === 'Posted';
    $formLockClass = ($isViewMode || $isPosted || isset($voucher)) ? 'form-locked' : '';
    if ($isViewMode) {
        $formLockClass .= ' view-mode';
    }
@endphp

<div class="container-fluid stock-hold-page">
    <div class="main-container bg-white border shadow-sm mx-auto rounded-3 position-relative">
        
        <div id="alertBox" class="alert d-none mb-2" role="alert"></div>

        <div class="d-flex justify-content-between align-items-center page-top-bar bg-light rounded shadow-sm border">
            <div class="d-flex align-items-center gap-3">
                <h6 class="page-title mb-0 fw-bold text-primary"><i class="fa fa-check-square-o me-2"></i>Claim Acceptance
                    @if($isViewMode)
                        <span class="badge bg-info px-2 py-1 rounded ms-1" style="font-size:10px;"><i class="fa fa-eye"></i> View Only</span>
                    @endif
                </h6>
                <span id="statusBadge" class="badge {{ isset($voucher) && $voucher->status == 'Posted' ? 'bg-success' : 'bg-warning text-dark' }} px-3 py-2 rounded-pill shadow-sm">
                    <i class="fa {{ isset($voucher) && $voucher->status == 'Posted' ? 'fa-check-circle' : 'fa-pencil' }} me-1"></i> 
                    {{ strtoupper(isset($voucher) ? $voucher->status : 'DRAFT') }}
                </span>
                <span class="badge bg-primary px-3 py-2 rounded-pill shadow-sm">
                    <i class="fa fa-tag me-1"></i> <span id="voucherNoText">{{ isset($voucher) ? $voucher->voucher_no : 'Auto-Generated' }}</span>
                </span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('claim-acceptance.index') }}" id="listBtn" class="btn btn-sm btn-outline-secondary rounded-pill px-3 shadow-sm">
                    <i class="fa fa-list me-1"></i> View All
                </a>
            </div>
        </div>

        <form id="acceptanceForm" action="{{ $isViewMode ? '#' : route('claim-acceptance.ajax-save') }}" method="POST" autocomplete="off" class="{{ trim($formLockClass) }}">
            @csrf
            <input type="hidden" name="id" id="voucher_id" value="{{ $voucher->id ?? '' }}">
            <input type="hidden" name="action" id="formAction" value="save">

            <div class="row g-3 mb-2">
                <div class="col-md-2">
                    <div class="card border-0 bg-light p-2 shadow-sm h-100">
                        <label class="form-label text-muted small fw-bold mb-1">Entry Date <span class="text-danger">*</span></label>
                        <input type="date" name="entry_date" class="form-control form-control-sm" value="{{ $voucher->entry_date ?? date('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card border-0 bg-light p-2 shadow-sm h-100">
                        <label class="form-label text-muted small fw-bold mb-1">Entry Time <span class="text-danger">*</span></label>
                        <input type="time" name="entry_time" class="form-control form-control-sm" value="{{ $voucher->entry_time ?? date('H:i') }}" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card border-0 bg-light p-2 shadow-sm h-100">
                        <label class="form-label text-muted small fw-bold mb-1">Claim Date <span class="text-danger">*</span></label>
                        <input type="date" name="date" class="form-control form-control-sm" value="{{ $voucher->date ?? date('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card border-0 bg-light p-2 shadow-sm h-100">
                        <label class="form-label text-muted small fw-bold mb-1"><span class="text-danger"><i class="fa fa-minus-circle"></i></span> Claim From <span class="text-danger">*</span></label>
                        <select name="from_warehouse_id" class="form-select form-select-sm" required>
                            <option value="">Select Source...</option>
                            @foreach($customerWarehouses as $w)
                                <option value="{{ $w->id }}" @selected(isset($voucher) && $voucher->from_warehouse_id == $w->id)>{{ $w->warehouse_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card border-0 bg-light p-2 shadow-sm h-100">
                        <label class="form-label text-muted small fw-bold mb-1"><span class="text-success"><i class="fa fa-plus-circle"></i></span> Accept In <span class="text-danger">*</span></label>
                        <select name="to_warehouse_id" class="form-select form-select-sm" required>
                            <option value="">Select Dest...</option>
                            @foreach($companyWarehouses as $w)
                                <option value="{{ $w->id }}" @selected(isset($voucher) && $voucher->to_warehouse_id == $w->id)>{{ $w->warehouse_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card border-0 bg-light p-2 shadow-sm h-100">
                        <label class="form-label text-muted small fw-bold mb-1">Party Type <span class="text-danger">*</span></label>
                        <select name="party_type" id="party_type" class="form-select form-select-sm" required>
                            <option value="vendor" @selected(isset($voucher) && $voucher->party_type == 'vendor')>Vendor</option>
                            <option value="customer" @selected(isset($voucher) && $voucher->party_type == 'customer')>Customer</option>
                            <option value="walkin" @selected(isset($voucher) && $voucher->party_type == 'walkin')>Walking customer</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 bg-light p-2 shadow-sm h-100">
                        <label class="form-label text-muted small fw-bold mb-1">Party Details <span class="text-danger">*</span></label>
                        <select name="party_id" id="party_id" class="form-select select2" required>
                            @if(isset($voucher))
                                @php
                                    $partyName = '';
                                    if($voucher->party_type == 'vendor') $partyName = $voucher->vendor->id . ' - ' . ($voucher->vendor->name ?? 'N/A');
                                    else $partyName = $voucher->customer->id . ' - ' . ($voucher->customer->customer_name ?? 'N/A');
                                @endphp
                                <option value="{{ $voucher->party_id }}" selected>{{ $partyName }}</option>
                            @endif
                        </select>
                    </div>
                </div>
            </div>

            <div class="card border shadow-sm mb-2">
                <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fa fa-list-ul me-2"></i>Claim Item Details</h6>
                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" id="addItemBtn">
                        <i class="fa fa-plus me-1"></i> Add Row
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered mb-0" id="itemsTable">
                            <thead>
                                <tr>
                                    <th width="15%">BTR #</th>
                                    <th width="10%">Item ID</th>
                                    <th width="50%">Product / Item Description</th>
                                    <th width="15%">Quantity</th>
                                    <th width="5%">Act</th>
                                </tr>
                            </thead>
                            <tbody id="itemRows">
                                @if(isset($voucher))
                                    @foreach($voucher->items as $it)
                                    <tr>
                                        <td><input type="text" name="btr_no[]" class="form-control form-control-sm text-center" value="{{ $it->btr_no }}"></td>
                                        <td><input type="text" class="form-control form-control-sm text-center item-id-display" value="{{ $it->product_id }}"></td>
                                        <td>
                                            <select name="product_id[]" class="form-select select2 product-select" required>
                                                <option value="{{ $it->product_id }}" selected>{{ $it->product->name ?? 'N/A' }}</option>
                                            </select>
                                        </td>
                                        <td><input type="number" name="quantity[]" class="form-control form-control-sm text-center quantity-input" value="{{ (float)$it->quantity }}" step="any" required></td>
                                        <td class="text-center"><button type="button" class="btn text-danger btn-xs remove-row"><i class="fa fa-trash"></i></button></td>
                                    </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td><input type="text" name="btr_no[]" class="form-control form-control-sm text-center" placeholder="BTR#"></td>
                                        <td><input type="text" class="form-control form-control-sm text-center item-id-display" placeholder="ID"></td>
                                        <td>
                                            <select name="product_id[]" class="form-select select2 product-select" required>
                                                <option value="">Search Item...</option>
                                            </select>
                                        </td>
                                        <td><input type="number" name="quantity[]" class="form-control form-control-sm text-center quantity-input" value="1" step="any" required></td>
                                        <td class="text-center"><button type="button" class="btn text-danger btn-xs remove-row"><i class="fa fa-trash"></i></button></td>
                                    </tr>
                                @endif
                            </tbody>
                            <tfoot class="table-light">
                                <tr class="fw-bold">
                                    <td colspan="3" class="text-end py-1">GRAND TOTAL:</td>
                                    <td class="text-center py-1 bg-primary bg-opacity-10">
                                        <span id="grandTotalQty" class="fw-bold text-primary fs-6">0</span>
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-2">
                <div class="col-md-12">
                    <div class="card border-0 bg-light p-2 shadow-sm">
                        <label class="form-label text-muted small fw-bold mb-1">Remarks</label>
                        <input type="text" name="remarks" class="form-control form-control-sm" placeholder="General remarks..." value="{{ $voucher->remarks ?? '' }}">
                    </div>
                </div>
            </div>

            <div class="card-footer bg-white border-top mt-1">
                <div class="d-flex flex-wrap justify-content-center w-100 bottom-bar-btns">
                    <button type="button" id="saveDraftBtn" class="btn btn-primary px-3 fw-bold shadow-sm" {{ ($isViewMode || $isPosted) ? 'disabled' : '' }}>
                        <u>S</u>ave <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>
                    </button>
                    <button type="button" id="editInvoiceBtn" class="btn btn-warning px-3 fw-bold text-dark shadow-sm" {{ ($isViewMode && !$isPosted) ? '' : ((isset($voucher) && !$isPosted && !$isViewMode) ? '' : 'disabled') }}>
                        <u>E</u>dit <kbd style="font-size:10px;opacity:.8;margin-left:4px;color:#fff;">Ctrl+E</kbd>
                    </button>
                    <button type="button" id="postBtn" class="btn btn-success px-3 fw-bold shadow-sm" {{ ($isViewMode || $isPosted) ? 'disabled' : '' }}>
                        <u>P</u>ost <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+&crarr;</kbd>
                    </button>
                    <button type="button" id="deleteBtn" class="btn btn-danger px-3 fw-bold shadow-sm" {{ ($isViewMode || !isset($voucher) || $isPosted) ? 'disabled' : '' }}>
                        <u>D</u>elete <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+D</kbd>
                    </button>
                    <a href="{{ isset($voucher) ? route('claim-acceptance.print', $voucher->id) : 'javascript:void(0)' }}" id="realPrintBtn" target="_blank" class="btn btn-info px-3 fw-bold text-dark shadow-sm {{ !isset($voucher) ? 'pe-none opacity-50' : '' }}">
                        <u>P</u>rint <kbd style="font-size:10px;opacity:.8;margin-left:4px;color:#fff;">Ctrl+P</kbd>
                    </a>
                    <a href="{{ route('claim-acceptance.index') }}" id="exitBtn" class="btn btn-secondary px-3 fw-bold shadow-sm text-white">
                        E<u>x</u>it <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Esc</kbd>
                    </a>
                    <a href="{{ route('claim-acceptance.create') }}" id="newInvoiceBtn" class="btn btn-dark px-3 fw-bold shadow-sm text-white">
                        <u>N</u>ew <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+M</kbd>
                    </a>
                </div>
            </div>
        </form>

        @if(isset($voucher))
        <form id="deleteForm" action="{{ route('claim-acceptance.destroy', $voucher->id) }}" method="POST" class="d-none">
            @csrf @method('DELETE')
        </form>
        @endif

        <div class="posted-watermark {{ ($isViewMode && $isPosted) || ($isPosted && !$isViewMode) ? 'show' : '' }}" id="postedWatermark">Posted</div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    var _saveInFlight = false;
    var _postInFlight = false;
    var saveBtnHtml = '<u>S</u>ave <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>';
    var postBtnHtml = '<u>P</u>ost <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+&crarr;</kbd>';

    var isViewMode = {{ $isViewMode ? 'true' : 'false' }};
    var isPostedVoucher = {{ $isPosted ? 'true' : 'false' }};

    // Initialization functions
    function initProductSelect($row) {
        $row.find('.product-select').select2({
            width: '100%',
            ajax: {
                url: "{{ url('search-products') }}",
                dataType: 'json', delay: 250,
                data: (params) => ({ q: params.term }),
                processResults: function(data) {
                    return {
                        results: data.map(function(item) {
                            return {
                                id: item.id,
                                text: item.name || item.text
                            };
                        })
                    };
                }
            }
        }).on('select2:select', function(e) {
            var data = e.params.data;
            $row.find('.item-id-display').val(data.id);
            $row.find('.quantity-input').focus().select();
            autoAddRow($row);
        });
    }

    function autoAddRow($row) {
        if ($row.is(':last-child')) {
            setTimeout(() => $('#addItemBtn').click(), 100);
        }
    }

    // Party Search logic
    $('#party_id').select2({
        width: '100%',
        ajax: {
            url: "{{ route('claim-acceptance.party-list') }}",
            dataType: 'json', delay: 250,
            data: (params) => ({ q: params.term, type: $('#party_type').val() }),
            processResults: (data) => ({ results: data })
        }
    });

    $('#party_type').on('change', () => $('#party_id').val(null).trigger('change'));

    // Row management
    $('#addItemBtn').on('click', function() {
        var lastBtr = $('#itemRows tr:last').find('input[name="btr_no[]"]').val() || '';
        var row = `<tr>
            <td><input type="text" name="btr_no[]" class="form-control form-control-sm text-center" placeholder="BTR#" value="${lastBtr}"></td>
            <td><input type="text" class="form-control form-control-sm text-center item-id-display" placeholder="ID"></td>
            <td><select name="product_id[]" class="form-select select2 product-select" required><option value="">Search Item...</option></select></td>
            <td><input type="number" name="quantity[]" class="form-control form-control-sm text-center quantity-input" value="1" step="any" required></td>
            <td class="text-center"><button type="button" class="btn text-danger btn-xs remove-row"><i class="fa fa-trash"></i></button></td>
        </tr>`;
        $('#itemRows').append(row);
        initProductSelect($('#itemRows tr:last'));
    });

    $(document).on('click', '.remove-row', function() {
        if ($('#itemRows tr').length > 1) {
            $(this).closest('tr').remove();
            calculateGrandTotal();
        }
    });

    // Item ID keyboard logic
    $(document).on('keydown', '.item-id-display', function(e) {
        if (e.which == 13 || e.which == 9) { // Enter or Tab
            e.preventDefault();
            var $row = $(this).closest('tr');
            var id = $(this).val();
            if (!id) return;
              $row.find('.item-id-display').addClass('loading-indicator');
              $.get("{{ url('search-products') }}", { q: id }, (res) => {
                  $row.find('.item-id-display').removeClass('loading-indicator');
                  if (res && res.length > 0) {
                      let item = res.find(i => String(i.id) === String(id)) || res.find(i => i.name.toLowerCase() === String(id).toLowerCase());
                      if (!item && res.length === 1) item = res[0];
                      
                      if (item) {
                          var prodName = item.name || item.text || 'N/A';
                          $row.find('.product-select').html(`<option value="${item.id}" selected>${prodName}</option>`).trigger('change');
                          $row.find('.item-id-display').val(item.id);
                          $row.find('.quantity-input').focus().select();
                          autoAddRow($row);
                      } else {
                          alert('Product not found!');
                      }
                  } else {
                      alert('Product not found!');
                  }
              });
        }
    });

    function calculateGrandTotal() {
        let total = 0;
        $('.quantity-input').each(function() { total += parseFloat($(this).val()) || 0; });
        $('#grandTotalQty').text(total.toFixed(2));
    }
    $(document).on('input', '.quantity-input', calculateGrandTotal);

    // Initial setup
    $('#itemRows tr').each(function() { initProductSelect($(this)); });
    calculateGrandTotal();

    // 💾 Storage & Locking Logic
    function showAlert(msg, type = 'success') {
        let $box = $('#alertBox');
        $box.removeClass('d-none alert-success alert-danger').addClass('alert-' + type).html(msg).fadeIn();
        setTimeout(() => $box.fadeOut(() => $box.addClass('d-none')), 3000);
    }

    function lockForm() {
        $('#acceptanceForm').addClass('form-locked');
        $('#editInvoiceBtn, #postBtn, #deleteBtn').prop('disabled', false);
        $('#saveDraftBtn').prop('disabled', true);
        $('#statusBadge').html('<i class="fa fa-pencil"></i> DRAFT');
    }

    function unlockForm() {
        if ("{{ isset($voucher) && $voucher->status == 'Posted' }}") return;
        $('#acceptanceForm').removeClass('form-locked');
        $('#editInvoiceBtn').prop('disabled', true);
        $('#postBtn, #deleteBtn').prop('disabled', false);
        $('#saveDraftBtn').prop('disabled', false);
    }

    if (isViewMode) {
        $('#acceptanceForm').addClass('form-locked view-mode');
        $('#saveDraftBtn, #postBtn, #deleteBtn').prop('disabled', true);
        $('#editInvoiceBtn').prop('disabled', isPostedVoucher);
        if (isPostedVoucher) {
            $('#postedWatermark').addClass('show');
        }
    } else if ("{{ isset($voucher) && $voucher->status != 'Posted' }}") {
        lockForm();
    }

    function save(act) {
        if (_saveInFlight || _postInFlight) return;
        $('#itemRows tr').each(function() {
            if (!$(this).find('.product-select').val()) $(this).remove();
        });

        if (!$('#itemRows tr').length) {
            showAlert('Please add at least one valid item', 'danger');
            $('#addItemBtn').click();
            return;
        }

        $('#formAction').val(act);
        var btn = act === 'post' ? '#postBtn' : '#saveDraftBtn';
        if (act === 'post') _postInFlight = true; else _saveInFlight = true;
        $(btn).prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i>');

        $.ajax({
            url: $('#acceptanceForm').attr('action'),
            type: 'POST',
            data: $('#acceptanceForm').serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                if (res.success) {
                    if (act === 'post') {
                        let postUrl = "{{ route('claim-acceptance.post', ':id') }}".replace(':id', res.id);
                        let f = $('<form>', {action: postUrl, method: 'POST'});
                        f.append($('<input>', {type: 'hidden', name: '_token', value: '{{ csrf_token() }}'}));
                        $('body').append(f); f.submit();
                    } else {
                        $('#voucher_id').val(res.id);
                        window.history.replaceState(null, null, "{{ url('claim-acceptance/edit') }}/" + res.id);
                        $('#realPrintBtn').attr('href', "{{ url('claim-acceptance/print') }}/" + res.id).removeClass('pe-none opacity-50');
                        if (!$('#deleteForm').length) {
                            $('body').append('<form id="deleteForm" action="{{ url('claim-acceptance/destroy') }}/' + res.id + '" method="POST" class="d-none"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="DELETE"></form>');
                        } else {
                            $('#deleteForm').attr('action', "{{ url('claim-acceptance/destroy') }}/" + res.id);
                        }
                        $('#deleteBtn').prop('disabled', false);
                        lockForm();
                        showAlert('Draft saved successfully!');
                    }
                }
            },
            error: function(xhr) {
                showAlert(xhr.responseJSON ? xhr.responseJSON.message : 'Error saving', 'danger');
            },
            complete: function() {
                if (act === 'post') _postInFlight = false; else _saveInFlight = false;
                if (!$('#acceptanceForm').hasClass('form-locked') || act === 'post') {
                    $(btn).prop('disabled', false).html(act === 'post' ? postBtnHtml : saveBtnHtml);
                }
            }
        });
    }

    $('#saveDraftBtn').on('click', function(e) { e.preventDefault(); if (!$(this).prop('disabled')) save('save'); });
    $('#postBtn').on('click', function(e) { e.preventDefault(); if (!$(this).prop('disabled')) save('post'); });
    $('#editInvoiceBtn').on('click', function() {
        if (isViewMode && !isPostedVoucher) {
            window.location.href = "{{ isset($voucher) ? route('claim-acceptance.edit', $voucher->id) : '#' }}";
            return;
        }
        if (!$(this).prop('disabled')) unlockForm();
    });

    $('#deleteBtn').on('click', function() {
        if ($(this).prop('disabled') || !$('#deleteForm').length) return;
        if(!confirm('Delete this Claim Acceptance permanently?')) return;
        $('#deleteForm').submit();
    });

    $('#realPrintBtn').on('click', function(e) {
        var href = $(this).attr('href');
        if (!href || href === 'javascript:void(0)') {
            e.preventDefault();
            showAlert('Save first', 'danger');
        }
    });

    document.addEventListener('keydown', function(e) {
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
            else showAlert('Save first', 'danger');
        }
        if (e.ctrlKey && (e.key === 'e' || e.key === 'E')) {
            e.preventDefault();
            if (!$('#editInvoiceBtn').prop('disabled')) $('#editInvoiceBtn').click();
        }
        if (e.ctrlKey && (e.key === 'd' || e.key === 'D')) {
            e.preventDefault();
            if (!$('#deleteBtn').prop('disabled')) $('#deleteBtn').click();
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

    // Auto-focus
    setTimeout(() => $('#itemRows tr:first input[name="btr_no[]"]').focus(), 500);
});
</script>
@endsection
