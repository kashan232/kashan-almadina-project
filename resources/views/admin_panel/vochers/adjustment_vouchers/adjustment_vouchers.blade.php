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

    .form-locked input, .form-locked select, .form-locked textarea, .form-locked #btnAddRow, .form-locked .removeRow, .form-locked .select2-container,
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
                <h5 class="page-title mb-0 fw-bold text-success"><i class="fa fa-adjust me-2"></i>Adjustment Voucher</h5>
                <span id="statusBadge" class="badge {{ $receipt->status == 'posted' ? 'bg-success' : 'bg-warning text-dark' }} px-3 py-2 rounded-pill shadow-sm">
                    <i class="fa {{ $receipt->status == 'posted' ? 'fa-check-circle' : 'fa-pencil' }} me-1"></i> 
                    {{ strtoupper($receipt->status ?: 'DRAFT') }}
                </span>
                <span class="badge bg-primary px-3 py-2 rounded-pill shadow-sm">
                    <i class="fa fa-tag me-1"></i> <span id="avidBadgeText">{{ $receipt->avid ?: $nextAvid }}</span>
                </span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('all-adjustment-vochers') }}" id="listBtn" class="btn btn-sm btn-outline-secondary rounded-pill px-3 shadow-sm">
                    <i class="fa fa-list me-1"></i> View All
                </a>
            </div>
        </div>

        <form id="adjustmentForm" autocomplete="off" class="{{ ($receipt->id && $receipt->status == 'posted') ? 'form-locked' : '' }}">
            @csrf
            <input type="hidden" name="id" id="receipt_id" value="{{ $receipt->id }}">

            <div class="row g-3 mb-4">
                <div class="col-md-2">
                    <div class="card border-0 bg-light p-2 shadow-sm h-100">
                        <label class="form-label text-muted small fw-bold mb-1">Entry Date</label>
                        <input type="date" name="entry_date" class="form-control form-control-sm" value="{{ $receipt->entry_date ?: date('Y-m-d') }}">
                    </div>
                </div>
                <!-- Party Selection (Moved from Rows to Header) -->
                <div class="col-md-3">
                    <div class="card border-0 bg-light p-2 shadow-sm h-100">
                        <label class="form-label text-muted small fw-bold mb-1">Party Type <span class="text-danger">*</span></label>
                        <select name="party_type" id="party_type_header" class="form-select form-select-sm">
                            <option value="">Select Type...</option>
                            @foreach($AccountHeads as $head)
                                <option value="{{ $head->id }}" {{ $receipt->party_type == $head->id ? 'selected' : '' }}>{{ $head->name }}</option>
                            @endforeach
                            <option value="vendor" {{ $receipt->party_type == 'vendor' ? 'selected' : '' }}>Vendor</option>
                            <option value="customer" {{ $receipt->party_type == 'customer' ? 'selected' : '' }}>Customer</option>
                            <option value="walkin" {{ $receipt->party_type == 'walkin' ? 'selected' : '' }}>Walkin Customer</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="card border-0 bg-light p-2 shadow-sm h-100">
                        <div class="row g-1">
                            <div class="col-3 text-center">
                                <label class="form-label text-muted small fw-bold mb-1">Code / ID <span class="text-danger">*</span></label>
                                <input type="text" id="party_code_input" class="form-control form-control-sm border-danger fw-bold text-danger text-center" placeholder="Code" value="">
                            </div>
                            <div class="col-9">
                                <label class="form-label text-muted small fw-bold mb-1">Party Name <span class="text-danger">*</span></label>
                                <select name="party_id" id="party_id_header" class="form-select form-select-sm" data-selected="{{ $receipt->party_id }}">
                                    <option value="">Select Party...</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fa fa-list-ul me-2"></i>Adjustment Details</h6>
                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" id="btnAddRow" {{ $receipt->status == 'posted' ? 'disabled' : '' }}>
                        <i class="fa fa-plus me-1"></i> Add Row
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered mb-0" id="voucherTable">
                            <thead>
                                <tr>
                                    <th width="20%">Narration</th>
                                    <th width="20%">Account Head</th>
                                    <th width="10%">Code</th>
                                    <th width="30%">Account (Deposit To)</th>
                                    <th width="10%">Ref#</th>
                                    <th width="15%">Amount</th>
                                    <th width="5%">Act</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $narrs = json_decode($receipt->narration_id, true) ?? [''];
                                    $accHeads = json_decode($receipt->account_head, true) ?? [''];
                                    $accIds = json_decode($receipt->account_id, true) ?? [''];
                                    $refs = json_decode($receipt->reference_no, true) ?? [''];
                                    $amounts = json_decode($receipt->amount, true) ?? [''];
                                @endphp
                                @foreach($narrs as $idx => $nId)
                                <tr>
                                    <td>
                                        <select name="narration_id[]" class="form-select narrationSelect">
                                            <option value="">Narration...</option>
                                            @foreach($narrationsList as $lid => $lname)
                                                <option value="{{ $lid }}" {{ ($nId == $lid) ? 'selected' : '' }}>{{ $lname }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select name="account_head[]" class="form-select form-select-sm rowAccountHead">
                                            <option value="">Select Head...</option>
                                            @foreach($AccountHeads as $head)
                                                <option value="{{ $head->id }}" {{ ($accHeads[$idx] ?? '') == $head->id ? 'selected' : '' }}>{{ $head->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" name="row_account_code[]" class="form-control form-control-sm text-center rowAccountCode" placeholder="Code" value=""></td>
                                    <td>
                                        <select name="account_id[]" class="form-select form-select-sm rowAccountSelect" data-selected="{{ $accIds[$idx] ?? '' }}">
                                            <option value="">Select Account...</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="reference_no[]" class="form-control form-control-sm" value="{{ $refs[$idx] ?? '' }}"></td>
                                    <td><input type="number" step="0.01" name="amount[]" class="form-control form-control-sm text-end row-amount" value="{{ $amounts[$idx] ?? '' }}"></td>
                                    <td class="text-center"><button type="button" class="btn text-danger btn-xs removeRow"><i class="fa fa-trash"></i></button></td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr class="fw-bold">
                                    <td colspan="5" class="text-end py-3">GRAND TOTAL:</td>
                                    <td class="text-end py-3 bg-primary bg-opacity-10">
                                        <input type="text" name="total_amount" id="totalAmount" class="form-control form-control-sm text-end border-0 bg-transparent fw-bold text-primary fs-6" readonly value="{{ $receipt->total_amount }}">
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
                        <input type="text" name="remarks" class="form-control form-control-sm" placeholder="General remarks..." value="{{ $receipt->remarks }}">
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-end mt-4 pt-4 border-top">
                @if($receipt->status != 'posted')
                <button type="button" id="saveDraftBtn" class="btn btn-sm btn-warning text-dark fw-bold rounded-pill px-4 shadow-sm">
                    <i class="fa fa-save me-1"></i> Save Draft <kbd class="ms-1 small opacity-75">Ctrl+S</kbd>
                </button>
                <button type="button" id="postBtn" class="btn btn-sm btn-primary text-dark fw-bold rounded-pill px-4 shadow-sm">
                    <i class="fa fa-send me-1"></i> Save Post <kbd class="ms-1 small opacity-75">Ctrl+&#8629;</kbd>
                </button>
                @endif
                <button type="button" id="editBtn" class="btn btn-sm btn-warning text-dark fw-bold rounded-pill px-4 shadow-sm" style="{{ ($receipt->id && $receipt->status != 'posted') ? 'display:block' : 'display:none' }}">
                    <i class="fa fa-pencil me-1"></i> Edit <kbd class="ms-1 small opacity-75">Ctrl+E</kbd>
                </button>

                <a href="{{ $receipt->id ? route('adjustmentVoucher.print', $receipt->id) : 'javascript:void(0)' }}" id="previewPrintBtn" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill px-4 shadow-sm {{ !$receipt->id ? 'disabled' : '' }}">
                    <i class="fa fa-print me-1"></i> Print Preview <kbd class="ms-1 small opacity-75">Ctrl+P</kbd>
                </a>
                <a href="{{ route('adjustment-vochers') }}" class="btn btn-sm btn-info text-dark fw-bold rounded-pill px-4 shadow-sm">
                    <i class="fa fa-plus me-1"></i> New <kbd class="ms-1 small opacity-75">Ctrl+M</kbd>
                </a>
                <button type="button" id="deleteBtn" class="btn btn-sm btn-danger text-dark fw-bold rounded-pill px-4 shadow-sm" style="{{ !$receipt->id ? 'display:none' : '' }}">
                    <i class="fa fa-trash me-1"></i> Delete
                </button>
            </div>
        </form>

        <div class="posted-watermark" id="postedWatermark" style="{{ $receipt->status == 'posted' ? 'display: block;' : '' }}">Posted</div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    function initSelectors($container = $('body')) {
        $container.find('.narrationSelect').select2({ placeholder: "Narration...", tags: true, width: '100%' });
        $container.find('.rowAccountSelect').select2({ placeholder: "Select Account...", allowClear: true, width: '100%' });
        $('#party_id_header').select2({ placeholder: "Select Party...", allowClear: true, width: '100%' });
    }
    initSelectors();

    // 👤 Header Party Logic
    $('#party_type_header').change(function() {
        let id = $(this).val();
        let $sub = $('#party_id_header');
        let selected = $sub.data('selected');
        $('#party_code_input').val('');
        $sub.html('<option value="">Loading...</option>');
        if(id) {
            let url = (['vendor','customer','walkin'].includes(id)) ? '{{ route("party.list") }}?type=' + id : '{{ url("get-accounts-by-head") }}/' + id;
            $.get(url, function(res) {
                $sub.html('<option value="">Select Party...</option>');
                res.forEach(i => {
                    let code = i.account_code || '';
                    $sub.append(`<option value="${i.id}" data-code="${code}" ${i.id == selected ? 'selected' : ''}>${i.text || i.title}</option>`);
                });
                if(selected) {
                    let code = $sub.find('option:selected').attr('data-code');
                    $('#party_code_input').val(code || selected);
                }
            });
        }
    }).trigger('change');

    $('#party_id_header').on('change', function() {
        let code = $(this).find('option:selected').attr('data-code');
        $('#party_code_input').val(code || $(this).val() || '');
    });

    $(document).on('blur keydown', '#party_code_input', function(e) {
        if(e.type === 'keydown' && e.which != 13 && e.which != 9) return;
        let val = $(this).val();
        if(val) {
            let optById = $('#party_id_header option').filter(function() { return $(this).val() == val; });
            if(optById.length > 0) { $('#party_id_header').val(val).trigger('change'); return; }
            let optByCode = $('#party_id_header option').filter(function() { return $(this).attr('data-code') == val; });
            if(optByCode.length > 0) { $('#party_id_header').val(optByCode.val()).trigger('change'); }
        }
    });

    // 🏦 Row Account Logic
    $(document).on('change', '.rowAccountHead', function() {
        let id = $(this).val();
        let $row = $(this).closest('tr');
        let $select = $row.find('.rowAccountSelect');
        let selected = $select.data('selected');
        $row.find('.rowAccountCode').val('');
        $select.html('<option value="">Loading...</option>');
        if(id) {
            $.get('{{ url("get-accounts-by-head") }}/' + id, function(res) {
                $select.html('<option value="">Select Account...</option>');
                res.forEach(a => $select.append(`<option value="${a.id}" data-code="${a.account_code}" ${a.id == selected ? 'selected' : ''}>${a.title}</option>`));
                if(selected) {
                    let code = $select.find('option:selected').attr('data-code');
                    $row.find('.rowAccountCode').val(code || selected);
                }
            });
        }
    });

    $('.rowAccountHead').each(function() {
        if ($(this).val()) $(this).trigger('change');
    });

    $(document).on('change', '.rowAccountSelect', function() {
        let code = $(this).find('option:selected').attr('data-code');
        $(this).closest('tr').find('.rowAccountCode').val(code || $(this).val() || '');
    });

    $(document).on('blur keydown', '.rowAccountCode', function(e) {
        if(e.type === 'keydown' && e.which != 13 && e.which != 9) return;
        let $row = $(this).closest('tr');
        let val = $(this).val();
        if(val) {
            let $sel = $row.find('.rowAccountSelect');
            let optById = $sel.find('option').filter(function() { return $(this).val() == val; });
            if(optById.length > 0) { $sel.val(val).trigger('change'); return; }
            let optByCode = $sel.find('option').filter(function() { return $(this).attr('data-code') == val; });
            if(optByCode.length > 0) { $sel.val(optByCode.val()).trigger('change'); }
        }
    });

    // ➕ Table Math & Action
    function calc() {
        let t = 0;
        $('.row-amount').each(function() { t += parseFloat($(this).val()) || 0; });
        $('#totalAmount').val(t.toFixed(2));
    }
    $(document).on('input', '.row-amount', calc);

    $('#btnAddRow').click(function() {
        let row = `<tr>
            <td><select name="narration_id[]" class="form-select narrationSelect"><option value="">Narration...</option>@foreach($narrationsList as $lid => $lname)<option value="{{ $lid }}">{{ $lname }}</option>@endforeach</select></td>
            <td><select name="account_head[]" class="form-select form-select-sm rowAccountHead"><option value="">Select Head...</option>@foreach($AccountHeads as $head)<option value="{{ $head->id }}">{{ $head->name }}</option>@endforeach</select></td>
            <td><input type="text" name="row_account_code[]" class="form-control form-control-sm text-center rowAccountCode" placeholder="Code"></td>
            <td><select name="account_id[]" class="form-select form-select-sm rowAccountSelect"><option value="">Select Account...</option></select></td>
            <td><input type="text" name="reference_no[]" class="form-control form-control-sm"></td>
            <td><input type="number" step="0.01" name="amount[]" class="form-control form-control-sm text-end row-amount"></td>
            <td class="text-center"><button type="button" class="btn text-danger btn-xs removeRow"><i class="fa fa-trash"></i></button></td>
        </tr>`;
        $('#voucherTable tbody').append(row);
        initSelectors($('#voucherTable tbody tr').last());
    });

    $(document).on('click', '.removeRow', function() { if($('#voucherTable tbody tr').length > 1) { $(this).closest('tr').remove(); calc(); } });

    // 💾 Storage Logic
    function showAlert(msg, type = 'success') {
        let $box = $('#alertBox');
        $box.removeClass('d-none alert-success alert-danger').addClass('alert-' + type).html(msg).fadeIn();
        setTimeout(() => $box.fadeOut(() => $box.addClass('d-none')), 3000);
    }

    $('#saveDraftBtn').click(function() {
        $('.ajax-valid-error').remove();
        $.post('{{ route("adjustment.vochers.ajax-save") }}', $('#adjustmentForm').serialize())
            .done(res => {
                if(res.success) {
                    $('#receipt_id').val(res.id); $('#avidBadgeText').text(res.avid);
                    $('#adjustmentForm').addClass('form-locked'); $('#editBtn').show();
                    $('#previewPrintBtn').attr('href', '{{ route("adjustmentVoucher.print", ":id") }}'.replace(':id', res.id)).removeClass('disabled');
                    showAlert('Draft saved and form locked.');
                }
            })
            .fail(xhr => {
                if(xhr.status == 422) {
                    let errs = xhr.responseJSON.errors;
                    Object.keys(errs).forEach(k => {
                        let $el = $(`[name="${k}"], #${k}`);
                        if(k.includes('.')) { let p = k.split('.'); $el = $(`[name="${p[0]}[]"]`).eq(p[1]); }
                        $el.after(`<div class="ajax-valid-error"><i class="fa fa-exclamation-circle"></i> ${errs[k][0]}</div>`);
                    });
                    showAlert('Please fix errors.', 'danger');
                }
            });
    });

    $('#editBtn').click(function() { $('#adjustmentForm').removeClass('form-locked'); $(this).hide(); });

    $('#postBtn').click(function() {
        $('#saveDraftBtn').click();
        setTimeout(() => {
            let id = $('#receipt_id').val();
            if(id) {
                let f = $('<form>', {action: '{{ route("adjustment.vochers.post", ":id") }}'.replace(':id', id), method: 'POST'});
                f.append($('<input>', {type: 'hidden', name: '_token', value: '{{ csrf_token() }}'}));
                $('body').append(f); f.submit();
            }
        }, 1000);
    });

    $(window).on('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && (e.which == 83 || e.keyCode == 83)) { 
            e.preventDefault(); 
            if (!$('#saveDraftBtn').is(':disabled') && $('#saveDraftBtn').is(':visible')) {
                $('#saveDraftBtn').click(); 
            }
            return false;
        }
        if ((e.ctrlKey || e.metaKey) && (e.which == 13 || e.keyCode == 13)) { 
            e.preventDefault(); 
            $('#postBtn').click(); 
        }
        if ((e.ctrlKey || e.metaKey) && (e.which == 69 || e.keyCode == 69)) { 
            e.preventDefault(); 
            $('#editBtn').click(); 
        }
        if (e.altKey && (e.which == 65 || e.keyCode == 65)) { 
            e.preventDefault(); 
            $('#btnAddRow').click(); 
        }
    });


    $('#deleteBtn').click(function() {
        if(!confirm('Delete this Adjustment Voucher permanently?')) return;
        let id = $('#receipt_id').val();
        let f = $('<form>', {action: '{{ route("adjustment.vochers.cancel", ":id") }}'.replace(':id', id), method: 'POST'});
        f.append($('<input>', {type: 'hidden', name: '_token', value: '{{ csrf_token() }}'}));
        f.append($('<input>', {type: 'hidden', name: '_method', value: 'DELETE'}));
        $('body').append(f); f.submit();
    });
});
</script>
@endsection
