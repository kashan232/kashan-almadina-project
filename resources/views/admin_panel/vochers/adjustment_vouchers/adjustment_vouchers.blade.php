@extends('admin_panel.layout.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    /* Ultra-High Density Form Design */
    .main-content-inner { background: #f4f7fa; min-height: 100vh; }
    .form-card { border-radius: 8px; border: none; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); margin-bottom: 1rem; }
    
    /* Input Styling */
    .form-control-sm, .form-select-sm { 
        font-size: 11px !important; 
        height: 28px !important; 
        padding: 0.2rem 0.5rem !important; 
        border-radius: 4px !important;
        border: 1px solid #dee2e6 !important;
    }
    .form-label { font-size: 10px !important; font-weight: 700 !important; color: #64748b !important; text-transform: uppercase; margin-bottom: 2px !important; }
    
    /* Select2 High Density Overrides */
    .select2-container--default .select2-selection--single {
        height: 28px !important; font-size: 11px !important; border-radius: 4px !important; border: 1px solid #dee2e6 !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 26px !important; padding-left: 8px !important; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 26px !important; }

    /* Table Density */
    #voucherTable { font-size: 11px !important; }
    #voucherTable thead th { 
        padding: 2px 8px !important; 
        font-size: 10.5px !important; 
        height: 24px !important;
        background: #f8fafc !important;
        color: #475569 !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        border-bottom: 2px solid #e2e8f0 !important;
    }
    #voucherTable tbody td { padding: 4px 6px !important; vertical-align: middle !important; border-bottom: 1px solid #f1f5f9 !important; }
    
    /* Watermark & Locked State */
    .posted-watermark {
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg);
        font-size: 6rem; color: rgba(220, 53, 69, 0.05); font-weight: 900; text-transform: uppercase;
        pointer-events: none; z-index: 1000; border: 8px solid rgba(220, 53, 69, 0.05); padding: 10px 40px; border-radius: 15px;
    }
    .form-locked { pointer-events: none !important; }
    .form-locked input, .form-locked select, .form-locked textarea, .form-locked button:not(#editBtn):not(#previewPrintBtn):not(#newBtn):not(#listBtn) {
        background-color: #f8fafc !important; opacity: 0.7 !important;
    }

    .btn-mini { padding: 0px 4px; font-size: 9px; height: 18px; display: inline-flex; align-items: center; justify-content: center; }
    .ajax-valid-error { color: #dc3545; font-size: 9px; font-weight: 700; margin-top: 1px; display: block; }
    
    .header-info-box { background: #fff; border-left: 3px solid #3b82f6; padding: 4px 10px; border-radius: 4px; }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-1">
            
            <div id="alertBox" class="alert d-none py-2 mb-2" role="alert" style="font-size: 12px;"></div>

            <!-- Page Header Card -->
            <div class="card form-card mb-2">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-3">
                            <div class="header-info-box">
                                <h6 class="mb-0 fw-bold text-dark"><i class="fa fa-adjust me-2 text-primary"></i>Adjustment Voucher</h6>
                            </div>
                            <span id="statusBadge" class="badge {{ $receipt->status == 'posted' ? 'bg-success' : 'bg-warning text-dark' }} rounded-pill px-3 py-1" style="font-size: 10px;">
                                {{ strtoupper($receipt->status ?: 'DRAFT') }}
                            </span>
                            <span class="badge bg-light text-primary border rounded-pill px-3 py-1" style="font-size: 10px;">
                                <i class="fa fa-hashtag me-1"></i> <span id="avidBadgeText">{{ $receipt->id ? $receipt->avid : 'Auto-Generated' }}</span>
                            </span>
                        </div>
                        <div class="d-flex gap-1">
                            <a href="{{ route('all-adjustment-vochers') }}" id="listBtn" class="btn btn-secondary btn-sm rounded-pill px-3" style="font-size: 11px;">
                                <i class="fa fa-list me-1"></i> View Registry
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <form id="adjustmentForm" autocomplete="off" class="{{ ($receipt->id && $receipt->status == 'posted') ? 'form-locked' : '' }}">
                @csrf
                <input type="hidden" name="id" id="receipt_id" value="{{ $receipt->id }}">

                <!-- Voucher Header Fields -->
                <div class="card form-card mb-2">
                    <div class="card-body p-2">
                        <div class="row g-2">
                            <div class="col-md-2">
                                <label class="form-label">Entry Date</label>
                                <input type="date" name="entry_date" class="form-control form-control-sm" value="{{ $receipt->entry_date ?: date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Entry Time</label>
                                <input type="time" name="entry_time" class="form-control form-control-sm" value="{{ $receipt->entry_time ?: date('H:i') }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Party Type <span class="text-danger">*</span></label>
                                <select name="party_type" id="party_type_header" class="form-select form-select-sm select2">
                                    <option value="">Select Type...</option>
                                    @foreach($AccountHeads as $head)
                                        <option value="{{ $head->id }}" {{ $receipt->party_type == $head->id ? 'selected' : '' }}>{{ $head->name }}</option>
                                    @endforeach
                                    <option value="vendor" {{ $receipt->party_type == 'vendor' ? 'selected' : '' }}>Vendor</option>
                                    <option value="customer" {{ $receipt->party_type == 'customer' ? 'selected' : '' }}>Customer</option>
                                    <option value="walkin" {{ $receipt->party_type == 'walkin' ? 'selected' : '' }}>Walkin Customer</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">ID/Code</label>
                                <input type="text" id="party_code_input" class="form-control form-control-sm text-center fw-bold text-primary" placeholder="Code" value="">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Source Party Name (Debit) <span class="text-danger">*</span></label>
                                <select name="party_id" id="party_id_header" class="form-select form-select-sm select2" data-selected="{{ $receipt->party_id }}">
                                    <option value="">Select Party...</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Voucher Rows Table -->
                <div class="card form-card mb-2">
                    <div class="card-header bg-white py-1 d-flex justify-content-between align-items-center border-bottom">
                        <span class="fw-bold text-muted small text-uppercase"><i class="fa fa-list-ul me-1"></i> Adjustment Details</span>
                        <button type="button" class="btn btn-primary btn-xs px-3 rounded-pill" id="btnAddRow" {{ $receipt->status == 'posted' ? 'disabled' : '' }}>
                            <i class="fa fa-plus me-1"></i> Add Line
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover table-bordered mb-0" id="voucherTable">
                                <thead>
                                    <tr>
                                        <th width="18%">Narration / Description</th>
                                        <th width="15%">Account Head / Party Type</th>
                                        <th width="8%" class="text-center">Code</th>
                                        <th width="28%">Destination Account (Credit)</th>
                                        <th width="12%">Reference#</th>
                                        <th width="14%" class="text-end">Amount</th>
                                        <th width="5%" class="text-center">Act</th>
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
                                            <select name="narration_id[]" class="form-select form-select-sm narrationSelect">
                                                <option value="">Narration...</option>
                                                @foreach($narrationsList as $lid => $lname)
                                                    <option value="{{ $lid }}" {{ ($nId == $lid) ? 'selected' : '' }}>{{ $lname }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="account_head[]" class="form-select form-select-sm rowAccountHead select2">
                                                <option value="">Select Head...</option>
                                                @foreach($AccountHeads as $head)
                                                    <option value="{{ $head->id }}" {{ ($accHeads[$idx] ?? '') == $head->id ? 'selected' : '' }}>{{ $head->name }}</option>
                                                @endforeach
                                                <option value="vendor" {{ ($accHeads[$idx] ?? '') == 'vendor' ? 'selected' : '' }}>Vendor</option>
                                                <option value="customer" {{ ($accHeads[$idx] ?? '') == 'customer' ? 'selected' : '' }}>Customer</option>
                                                <option value="walkin" {{ ($accHeads[$idx] ?? '') == 'walkin' ? 'selected' : '' }}>Walkin</option>
                                            </select>
                                        </td>
                                        <td><input type="text" name="row_account_code[]" class="form-control form-control-sm text-center fw-bold text-danger rowAccountCode" placeholder="Code"></td>
                                        <td>
                                            <select name="account_id[]" class="form-select form-select-sm rowAccountSelect select2" data-selected="{{ $accIds[$idx] ?? '' }}">
                                                <option value="">Select Account...</option>
                                            </select>
                                        </td>
                                        <td><input type="text" name="reference_no[]" class="form-control form-control-sm" value="{{ $refs[$idx] ?? '' }}" placeholder="Ref#"></td>
                                        <td><input type="number" step="0.01" name="amount[]" class="form-control form-control-sm text-end fw-bold row-amount" value="{{ $amounts[$idx] ?? '' }}" placeholder="0.00"></td>
                                        <td class="text-center"><button type="button" class="btn text-danger btn-xs removeRow p-0"><i class="fa fa-trash-o fs-6"></i></button></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr class="fw-bold">
                                        <td colspan="5" class="text-end py-2 text-muted small">TOTAL ADJUSTMENT AMOUNT</td>
                                        <td class="text-end py-1">
                                            <input type="text" name="total_amount" id="totalAmount" class="form-control form-control-sm text-end border-0 bg-transparent fw-bold text-primary fs-6 py-0" readonly value="{{ $receipt->total_amount }}">
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Footer Remarks & Actions -->
                <div class="row g-2 align-items-end mb-4">
                    <div class="col-md-7">
                        <div class="card form-card mb-0">
                            <div class="card-body p-2">
                                <label class="form-label">General Remarks / Note</label>
                                <input type="text" name="remarks" class="form-control form-control-sm" placeholder="Type additional voucher notes here..." value="{{ $receipt->remarks }}">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="d-flex gap-1 justify-content-end mb-1">
                            @if($receipt->status != 'posted')
                                <button type="button" id="saveDraftBtn" class="btn btn-warning btn-sm fw-bold rounded-pill px-4 shadow-sm" style="font-size: 11px;">
                                    <i class="fa fa-save me-1"></i> Save Draft [Ctrl+S]
                                </button>
                                <button type="button" id="postBtn" class="btn btn-primary btn-sm fw-bold rounded-pill px-4 shadow-sm" style="font-size: 11px;">
                                    <i class="fa fa-send me-1"></i> Post Voucher [Ctrl+Enter]
                                </button>
                            @endif
                            
                            <button type="button" id="editBtn" class="btn btn-warning btn-sm fw-bold rounded-pill px-4 shadow-sm" style="{{ ($receipt->id && $receipt->status != 'posted') ? 'display:block' : 'display:none' }}; font-size: 11px;">
                                <i class="fa fa-pencil me-1"></i> Unlock Edit [Ctrl+E]
                            </button>

                            <a href="{{ $receipt->id ? route('adjustmentVoucher.print', $receipt->id) : 'javascript:void(0)' }}" id="previewPrintBtn" target="_blank" class="btn btn-dark btn-sm rounded-pill px-3 shadow-sm {{ !$receipt->id ? 'disabled' : '' }}" style="font-size: 11px;">
                                <i class="fa fa-print"></i> Print [Ctrl+P]
                            </a>
                            <a href="{{ route('adjustment-vochers') }}" id="newBtn" class="btn btn-info btn-sm text-dark fw-bold rounded-pill px-3 shadow-sm" style="font-size: 11px;">
                                <i class="fa fa-plus"></i> New [Ctrl+M]
                            </a>
                            <button type="button" id="deleteBtn" class="btn btn-danger btn-sm fw-bold rounded-pill px-3 shadow-sm" style="{{ !$receipt->id ? 'display:none' : '' }}; font-size: 11px;">
                                <i class="fa fa-trash"></i>
                            </button>
                            <a href="{{ route('all-adjustment-vochers') }}" id="cancelBtn" class="btn btn-secondary btn-sm fw-bold rounded-pill px-3 shadow-sm" style="font-size: 11px;">
                                <i class="fa fa-times"></i> Cancel [Esc]
                            </a>
                        </div>
                    </div>
                </div>
            </form>

            @if($receipt->status == 'posted')
                <div class="posted-watermark" id="postedWatermark">Posted</div>
            @endif
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    function initSelectors($container = $('body')) {
        $container.find('.select2').select2({ width: '100%' });
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
        let typeId = $(this).val();
        let $row = $(this).closest('tr');
        let $sub = $row.find('.rowAccountSelect');
        let selected = $sub.data('selected');
        $row.find('.rowAccountCode').val('');
        $sub.html('<option value="">Loading...</option>');
        if(typeId) {
            let url = (['vendor','customer','walkin'].includes(typeId)) ? '{{ route("party.list") }}?type=' + typeId : '{{ url("get-accounts-by-head") }}/' + typeId;
            $.get(url, function(res) {
                $sub.html('<option value="">Select Account...</option>');
                res.forEach(i => {
                    let code = i.account_code || i.id || '';
                    $sub.append(`<option value="${i.id}" data-code="${code}" ${i.id == selected ? 'selected' : ''}>${i.text || i.title}</option>`);
                });
                if(selected) { let code = $sub.find('option:selected').attr('data-code'); $row.find('.rowAccountCode').val(code || ''); }
            });
        }
    });

    $('.rowAccountHead').each(function() { if ($(this).val()) $(this).trigger('change'); });

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
        $('#totalAmount').val(t.toLocaleString('en-US', {minimumFractionDigits: 2}));
    }
    $(document).on('input', '.row-amount', calc);

    $('#btnAddRow').click(function() {
        let row = `<tr>
            <td><select name="narration_id[]" class="form-select form-select-sm narrationSelect"><option value="">Narration...</option>@foreach($narrationsList as $lid => $lname)<option value="{{ $lid }}">{{ $lname }}</option>@endforeach</select></td>
            <td><select name="account_head[]" class="form-select form-select-sm rowAccountHead select2"><option value="">Select Head...</option>@foreach($AccountHeads as $head)<option value="{{ $head->id }}">{{ $head->name }}</option>@endforeach<option value="vendor">Vendor</option><option value="customer">Customer</option><option value="walkin">Walkin</option></select></td>
            <td><input type="text" name="row_account_code[]" class="form-control form-control-sm text-center fw-bold text-danger rowAccountCode" placeholder="Code"></td>
            <td><select name="account_id[]" class="form-select form-select-sm rowAccountSelect select2"><option value="">Select Account...</option></select></td>
            <td><input type="text" name="reference_no[]" class="form-control form-control-sm" placeholder="Ref#"></td>
            <td><input type="number" step="0.01" name="amount[]" class="form-control form-control-sm text-end fw-bold row-amount" placeholder="0.00"></td>
            <td class="text-center"><button type="button" class="btn text-danger btn-xs removeRow p-0"><i class="fa fa-trash-o fs-6"></i></button></td>
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
        Swal.fire({
            title: 'Post Voucher?', text: 'Once posted, accounting entries will be finalized.', icon: 'question', showCancelButton: true
        }).then((res) => {
            if(res.isConfirmed) {
                $('#saveDraftBtn').click();
                setTimeout(() => {
                    let id = $('#receipt_id').val();
                    if(id) {
                        let f = $('<form>', {action: '{{ route("adjustment.vochers.post", ":id") }}'.replace(':id', id), method: 'POST'});
                        f.append($('<input>', {type: 'hidden', name: '_token', value: '{{ csrf_token() }}'}));
                        $('body').append(f); f.submit();
                    }
                }, 1000);
            }
        });
    });

    $(window).on('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && (e.which == 83 || e.keyCode == 83)) { e.preventDefault(); $('#saveDraftBtn').click(); return false; } // Ctrl + S
        if ((e.ctrlKey || e.metaKey) && (e.which == 13 || e.keyCode == 13)) { e.preventDefault(); $('#postBtn').click(); return false; } // Ctrl + Enter
        if ((e.ctrlKey || e.metaKey) && (e.which == 69 || e.keyCode == 69)) { e.preventDefault(); $('#editBtn').click(); return false; } // Ctrl + E
        if ((e.ctrlKey || e.metaKey) && (e.which == 80 || e.keyCode == 80)) { e.preventDefault(); if(!$('#previewPrintBtn').hasClass('disabled')) { window.open($('#previewPrintBtn').attr('href'), '_blank'); } return false; } // Ctrl + P
        if ((e.ctrlKey || e.metaKey) && (e.which == 77 || e.keyCode == 77)) { e.preventDefault(); window.location.href = $('#newBtn').attr('href'); return false; } // Ctrl + M
        if (e.which == 27 || e.keyCode == 27) { e.preventDefault(); window.location.href = $('#cancelBtn').attr('href'); return false; } // Esc
    });

    $('#deleteBtn').click(function() {
        Swal.fire({ title: 'Delete permanently?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33' }).then((res) => {
            if(res.isConfirmed) {
                let id = $('#receipt_id').val();
                let f = $('<form>', {action: '{{ route("adjustment.vochers.cancel", ":id") }}'.replace(':id', id), method: 'POST'});
                f.append($('<input>', {type: 'hidden', name: '_token', value: '{{ csrf_token() }}'}));
                f.append($('<input>', {type: 'hidden', name: '_method', value: 'DELETE'}));
                $('body').append(f); f.submit();
            }
        });
    });
});
</script>
@endsection
