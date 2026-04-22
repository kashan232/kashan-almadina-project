@extends('admin_panel.layout.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container--default .select2-selection--single {
        height: 31px !important; padding: 2px 5px !important; font-size: 0.85rem !important; border: 1px solid #dee2e6 !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 25px !important; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 30px !important; }

    .main-container { font-size: .85rem; max-width: 1400px; }
    .form-control, .form-select, .btn { font-size: .85rem; padding: .4rem .6rem; }
    .table thead th { background: #f8f9fa !important; text-align: center; font-size: 0.75rem; padding: 8px !important; white-space: nowrap; }
    .table td { vertical-align: middle; padding: 4px !important; }

    .posted-watermark {
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg);
        font-size: 8rem; color: rgba(220, 53, 69, 0.1); font-weight: 900; text-transform: uppercase;
        pointer-events: none; z-index: 1000; display: none; border: 10px solid rgba(220, 53, 69, 0.1); padding: 20px 50px; border-radius: 20px;
    }

    .form-locked input, .form-locked select, .form-locked textarea, .form-locked #addItemBtn, .form-locked .remove-row, .form-locked .select2-container,
    .form-locked .btn:not(#editBtn):not(#previewPrintBtn):not(#newBtn):not(#listBtn) {
        pointer-events: none !important; opacity: 0.8 !important; background-color: #f8f9fa !important;
    }

    .ajax-valid-error { color: #dc3545; font-size: 0.75rem; font-weight: 700; margin-bottom: 2px; display: block; }
</style>

<div class="container-fluid py-4">
    <div class="main-container bg-white border shadow-sm mx-auto p-4 rounded-3 position-relative" style="max-width: 98%;">
        
        <div id="alertBox" class="alert d-none mb-3" role="alert"></div>

        <div class="d-flex justify-content-between align-items-center mb-4 bg-light p-3 rounded shadow-sm border">
            <div class="d-flex align-items-center gap-3">
                <h5 class="page-title mb-0 fw-bold text-primary"><i class="fa fa-check-square-o me-2"></i>Claim Acceptance</h5>
                <span id="statusBadge" class="badge {{ isset($voucher) && $voucher->status == 'Posted' ? 'bg-success' : 'bg-warning text-dark' }} px-3 py-2 rounded-pill shadow-sm">
                    <i class="fa {{ isset($voucher) && $voucher->status == 'Posted' ? 'fa-check-circle' : 'fa-pencil' }} me-1"></i> 
                    {{ strtoupper(isset($voucher) ? $voucher->status : 'DRAFT') }}
                </span>
                <span class="badge bg-primary px-3 py-2 rounded-pill shadow-sm">
                    <i class="fa fa-tag me-1"></i> <span id="voucherNoText">{{ $voucher->voucher_no ?? $voucherNo }}</span>
                </span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('claim-acceptance.index') }}" id="listBtn" class="btn btn-sm btn-outline-secondary rounded-pill px-3 shadow-sm">
                    <i class="fa fa-list me-1"></i> View All
                </a>
            </div>
        </div>

        <form id="acceptanceForm" action="{{ route('claim-acceptance.ajax-save') }}" method="POST" autocomplete="off" class="{{ (isset($voucher) && $voucher->status == 'Posted') ? 'form-locked' : '' }}">
            @csrf
            <input type="hidden" name="id" id="voucher_id" value="{{ $voucher->id ?? '' }}">
            <input type="hidden" name="action" id="formAction" value="save">

            <div class="row g-3 mb-4">
                <div class="col-md-2">
                    <div class="card border-0 bg-light p-2 shadow-sm h-100">
                        <label class="form-label text-muted small fw-bold mb-1">Date <span class="text-danger">*</span></label>
                        <input type="date" name="date" class="form-control form-control-sm" value="{{ $voucher->date ?? date('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card border-0 bg-light p-2 shadow-sm h-100">
                        <label class="form-label text-muted small fw-bold mb-1">Type <span class="text-danger">*</span></label>
                        <select name="party_type" id="party_type" class="form-select form-select-sm" required>
                            <option value="vendor" @selected(isset($voucher) && $voucher->party_type == 'vendor')>Vendor</option>
                            <option value="customer" @selected(isset($voucher) && $voucher->party_type == 'customer')>Customer</option>
                            <option value="walkin" @selected(isset($voucher) && $voucher->party_type == 'walkin')>Walking customer</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-8">
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

            <div class="card border shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
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
                                    <td colspan="3" class="text-end py-3">GRAND TOTAL:</td>
                                    <td class="text-center py-3 bg-primary bg-opacity-10">
                                        <span id="grandTotalQty" class="fw-bold text-primary fs-6">0</span>
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-12">
                    <div class="card border-0 bg-light p-2 shadow-sm">
                        <label class="form-label text-muted small fw-bold mb-1">Remarks</label>
                        <input type="text" name="remarks" class="form-control form-control-sm" placeholder="General remarks..." value="{{ $voucher->remarks ?? '' }}">
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-end mt-4 pt-4 border-top">
                @if(!isset($voucher) || $voucher->status != 'Posted')
                    <button type="button" id="saveDraftBtn" class="btn btn-sm btn-warning text-dark fw-bold rounded-pill px-4 shadow-sm btn-action">
                        <i class="fa fa-save me-1"></i> Save Draft <kbd class="ms-1 small opacity-75">Ctrl+S</kbd>
                    </button>
                    <button type="button" id="postBtn" class="btn btn-sm btn-primary text-dark fw-bold rounded-pill px-4 shadow-sm btn-action">
                        <i class="fa fa-send me-1"></i> Save Post <kbd class="ms-1 small opacity-75">Ctrl+&#8629;</kbd>
                    </button>
                @endif

                <button type="button" id="editBtn" class="btn btn-sm btn-warning text-dark fw-bold rounded-pill px-4 shadow-sm" style="{{ (isset($voucher) && $voucher->status != 'Posted') ? 'display:block' : 'display:none' }}">
                    <i class="fa fa-pencil me-1"></i> Edit <kbd class="ms-1 small opacity-75">Ctrl+E</kbd>
                </button>

                <a href="{{ isset($voucher) ? route('claim-acceptance.print', $voucher->id) : 'javascript:void(0)' }}" id="previewPrintBtn" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill px-4 shadow-sm {{ !isset($voucher) ? 'disabled' : '' }}">
                    <i class="fa fa-print me-1"></i> Print Preview <kbd class="ms-1 small opacity-75">Ctrl+P</kbd>
                </a>
                <a href="{{ route('claim-acceptance.create') }}" id="newBtn" class="btn btn-sm btn-info text-dark fw-bold rounded-pill px-4 shadow-sm">
                    <i class="fa fa-plus me-1"></i> New <kbd class="ms-1 small opacity-75">Ctrl+M</kbd>
                </a>

                @if(isset($voucher) && $voucher->status != 'Posted')
                    <button type="button" id="deleteBtn" class="btn btn-sm btn-danger text-dark fw-bold rounded-pill px-4 shadow-sm">
                        <i class="fa fa-trash me-1"></i> Delete
                    </button>
                @endif
                
                <a href="{{ route('claim-acceptance.index') }}" id="cancelBtn" class="btn btn-sm btn-outline-secondary rounded-pill px-4 shadow-sm">
                    <i class="fa fa-times me-1"></i> Cancel <kbd class="ms-1 small opacity-75">Esc</kbd>
                </a>
            </div>
        </form>

        <div class="posted-watermark" id="postedWatermark" style="{{ (isset($voucher) && $voucher->status == 'Posted') ? 'display: block;' : '' }}">Posted</div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // Initialization functions
    function initProductSelect($row) {
        $row.find('.product-select').select2({
            width: '100%',
            ajax: {
                url: "{{ url('search-products') }}",
                dataType: 'json', delay: 250,
                data: (params) => ({ q: params.term }),
                processResults: (data) => ({ results: data })
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
            $.get("{{ url('products/get-by-id') }}/" + id, (res) => {
                if (res) {
                    $row.find('.product-select').html(`<option value="${res.id}" selected>${res.text}</option>`).trigger('change');
                    $row.find('.quantity-input').focus().select();
                    autoAddRow($row);
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
        $('.btn-action').hide();
        $('#editBtn').show();
        $('#previewPrintBtn').removeClass('disabled');
        $('#statusBadge').html('<i class="fa fa-pencil"></i> DRAFT');
    }

    function unlockForm() {
        if ("{{ isset($voucher) && $voucher->status == 'Posted' }}") return;
        $('#acceptanceForm').removeClass('form-locked');
        $('.btn-action').show();
        $('#editBtn').hide();
    }

    if ("{{ isset($voucher) && $voucher->status != 'Posted' }}") lockForm();

    function save(act) {
        // Clean empty rows
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
        $(btn).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

        $.ajax({
            url: $('#acceptanceForm').attr('action'),
            type: 'POST',
            data: $('#acceptanceForm').serialize(),
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
                        $('#previewPrintBtn').attr('href', "{{ url('claim-acceptance/print') }}/" + res.id).removeClass('disabled');
                        lockForm();
                        showAlert('Draft saved successfully!');
                        $(btn).prop('disabled', false).html(act === 'post' ? '<i class="fa fa-send"></i> Save Post' : '<i class="fa fa-save"></i> Save Draft');
                    }
                }
            },
            error: function(xhr) {
                showAlert(xhr.responseJSON ? xhr.responseJSON.message : 'Error saving', 'danger');
                $(btn).prop('disabled', false).html(act === 'post' ? '<i class="fa fa-send"></i> Save Post' : '<i class="fa fa-save"></i> Save Draft');
            }
        });
    }

    $('#saveDraftBtn').click(() => save('save'));
    $('#postBtn').click(() => save('post'));
    $('#editBtn').click(() => unlockForm());

    $('#deleteBtn').click(function() {
        if(!confirm('Delete this Claim Acceptance permanently?')) return;
        $('#deleteForm').submit();
    });

    // Keyboard Shortcuts
    $(window).on('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && (e.which == 83 || e.keyCode == 83)) { e.preventDefault(); $('#saveDraftBtn').click(); }
        if ((e.ctrlKey || e.metaKey) && (e.which == 13 || e.keyCode == 13)) { e.preventDefault(); $('#postBtn').click(); }
        if ((e.ctrlKey || e.metaKey) && (e.which == 69 || e.keyCode == 69)) { e.preventDefault(); $('#editBtn').click(); }
        if ((e.ctrlKey || e.metaKey) && (e.which == 76 || e.keyCode == 76)) { e.preventDefault(); window.location.href = "{{ route('claim-acceptance.index') }}"; }
        if ((e.ctrlKey || e.metaKey) && (e.which == 77 || e.keyCode == 77)) { e.preventDefault(); window.location.href = "{{ route('claim-acceptance.create') }}"; }
        if (e.key === 'Escape') { e.preventDefault(); window.location.href = "{{ route('claim-acceptance.index') }}"; }
    });

    // Auto-focus
    setTimeout(() => $('#itemRows tr:first input[name="btr_no[]"]').focus(), 500);
});
</script>
@endsection
