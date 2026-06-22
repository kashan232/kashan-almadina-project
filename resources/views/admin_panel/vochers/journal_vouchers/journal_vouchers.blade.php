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
    
    .header-info-box { background: #fff; border-left: 3px solid #10b981; padding: 4px 10px; border-radius: 4px; }
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
                                <h6 class="mb-0 fw-bold text-dark"><i class="fa fa-book me-2 text-success"></i>Journal Voucher</h6>
                            </div>
                            <span id="statusBadge" class="badge {{ $receipt->status == 'posted' ? 'bg-success' : 'bg-warning text-dark' }} rounded-pill px-3 py-1" style="font-size: 10px;">
                                {{ strtoupper($receipt->status ?: 'DRAFT') }}
                            </span>
                            <span class="badge bg-light text-primary border rounded-pill px-3 py-1" style="font-size: 10px;">
                                <i class="fa fa-hashtag me-1"></i> <span id="jvidBadgeText">{{ $receipt->jvid ?: $nextJvid }}</span>
                            </span>
                        </div>
                        <div class="d-flex gap-1">
                            <a href="{{ route('all-journal-vochers') }}" id="listBtn" class="btn btn-secondary btn-sm rounded-pill px-3" style="font-size: 11px;">
                                <i class="fa fa-list me-1"></i> View Registry
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <form id="journalForm" autocomplete="off" class="{{ ($receipt->id && $receipt->status == 'posted') ? 'form-locked' : '' }}">
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
                            <div class="col-md-8">
                                <label class="form-label">General Remarks / Memo</label>
                                <input type="text" name="remarks" class="form-control form-control-sm" placeholder="General voucher notes..." value="{{ $receipt->remarks }}">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Voucher Rows Table -->
                <div class="card form-card mb-2">
                    <div class="card-header bg-white py-1 d-flex justify-content-between align-items-center border-bottom">
                        <span class="fw-bold text-muted small text-uppercase"><i class="fa fa-list-ul me-1"></i> Journal Entries</span>
                        <button type="button" class="btn btn-primary btn-xs px-3 rounded-pill" id="btnAddRow" {{ $receipt->status == 'posted' ? 'disabled' : '' }}>
                            <i class="fa fa-plus me-1"></i> Add Row
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover table-bordered mb-0" id="voucherTable">
                                <thead>
                                    <tr>
                                        <th width="20%">Narration / Memo</th>
                                        <th width="15%">Account Head</th>
                                        <th width="8%" class="text-center">Code</th>
                                        <th width="25%">Party / Account Name</th>
                                        <th width="12%" class="text-end">Debit</th>
                                        <th width="12%" class="text-end">Credit</th>
                                        <th width="3%" class="text-center">Act</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $narrs = json_decode($receipt->narration_id, true) ?? ['',''];
                                        $pTypes = json_decode($receipt->party_type, true) ?? ['',''];
                                        $pIds = json_decode($receipt->party_id, true) ?? ['',''];
                                        $debits = json_decode($receipt->debit, true) ?? ['',''];
                                        $credits = json_decode($receipt->credit, true) ?? ['',''];
                                    @endphp
                                    @foreach($pTypes as $idx => $type)
                                    <tr class="entry-row">
                                        <td>
                                            <select name="narration_id[]" class="form-select form-select-sm narrationSelect">
                                                <option value="">Narration...</option>
                                                @foreach($narrationsList as $lid => $lname)
                                                    <option value="{{ $lid }}" {{ ($narrs[$idx] ?? '') == $lid ? 'selected' : '' }}>{{ $lname }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="party_type[]" class="form-select form-select-sm rowPartyType select2" required>
                                                <option value="">Select Head...</option>
                                                @foreach($AccountHeads as $head)
                                                    <option value="{{ $head->id }}" {{ ($pTypes[$idx] ?? '') == $head->id ? 'selected' : '' }}>{{ $head->name }}</option>
                                                @endforeach
                                                <option value="vendor" {{ ($pTypes[$idx] ?? '') == 'vendor' ? 'selected' : '' }}>Vendor</option>
                                                <option value="customer" {{ ($pTypes[$idx] ?? '') == 'customer' ? 'selected' : '' }}>Customer</option>
                                                <option value="walkin" {{ ($pTypes[$idx] ?? '') == 'walkin' ? 'selected' : '' }}>Walkin</option>
                                            </select>
                                        </td>
                                        <td><input type="text" class="form-control form-control-sm text-center fw-bold text-danger rowPartyCode" placeholder="Code" value="{{ is_numeric($pIds[$idx] ?? '') ? (DB::table('accounts')->where('id', $pIds[$idx])->value('account_code') ?: $pIds[$idx]) : ($pIds[$idx] ?? '') }}"></td>
                                        <td>
                                            <select name="party_id[]" class="form-select form-select-sm rowPartyName select2" data-selected="{{ $pIds[$idx] ?? '' }}" required>
                                                <option value="">Select Party...</option>
                                            </select>
                                        </td>
                                        <td><input type="number" step="0.01" name="debit[]" class="form-control form-control-sm text-end fw-bold row-debit" value="{{ $debits[$idx] ?? '' }}" placeholder="0.00"></td>
                                        <td><input type="number" step="0.01" name="credit[]" class="form-control form-control-sm text-end fw-bold row-credit" value="{{ $credits[$idx] ?? '' }}" placeholder="0.00"></td>
                                        <td class="text-center"><button type="button" class="btn text-danger btn-xs removeRow p-0"><i class="fa fa-trash-o fs-6"></i></button></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr class="fw-bold">
                                        <td colspan="4" class="text-end py-2 text-muted small">GRAND TOTALS (BALANCED)</td>
                                        <td class="text-end py-1">
                                            <input type="text" name="total_debit" id="totalDebit" class="form-control form-control-sm text-end border-0 bg-transparent fw-bold text-primary fs-6 py-0" readonly value="{{ $receipt->total_debit ?? '0.00' }}">
                                        </td>
                                        <td class="text-end py-1">
                                            <input type="text" name="total_credit" id="totalCredit" class="form-control form-control-sm text-end border-0 bg-transparent fw-bold text-danger fs-6 py-0" readonly value="{{ $receipt->total_credit ?? '0.00' }}">
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
                        <div class="alert alert-info py-1 px-2 mb-0" style="font-size: 10px;">
                            <i class="fa fa-info-circle me-1"></i> Ensure Debit and Credit totals are balanced before posting.
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="d-flex gap-1 justify-content-end mb-1">
                            @if($receipt->status != 'posted')
                                <button type="button" id="saveDraftBtn" class="btn btn-warning btn-sm fw-bold rounded-pill px-3 shadow-sm" style="font-size: 11px;">
                                    <i class="fa fa-save me-1"></i> Save Draft <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>
                                </button>
                                <button type="button" id="postBtn" class="btn btn-primary btn-sm fw-bold rounded-pill px-3 shadow-sm" style="font-size: 11px;">
                                    <i class="fa fa-send me-1"></i> Post <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+Enter</kbd>
                                </button>
                            @endif
                            
                            <button type="button" id="editBtn" class="btn btn-secondary btn-sm fw-bold rounded-pill px-3 shadow-sm" style="{{ ($receipt->id && $receipt->status != 'posted') ? 'display:block' : 'display:none' }}; font-size: 11px;">
                                <i class="fa fa-pencil me-1"></i> Edit <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+E</kbd>
                            </button>

                            <a href="{{ $receipt->id ? route('journalVoucher.print', $receipt->id) : 'javascript:void(0)' }}" id="previewPrintBtn" target="_blank" class="btn btn-dark btn-sm rounded-pill px-3 shadow-sm {{ !$receipt->id ? 'disabled' : '' }}" style="font-size: 11px;">
                                <i class="fa fa-print"></i> Print <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+P</kbd>
                            </a>
                            <a href="{{ route('journal-vochers') }}" id="newBtn" class="btn btn-info btn-sm text-dark fw-bold rounded-pill px-3 shadow-sm" style="font-size: 11px;">
                                <i class="fa fa-plus"></i> New <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+M</kbd>
                            </a>
                            <button type="button" id="deleteBtn" class="btn btn-danger btn-sm fw-bold rounded-pill px-3 shadow-sm" style="{{ !$receipt->id ? 'display:none' : '' }}; font-size: 11px;">
                                <i class="fa fa-times"></i> Cancel <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Esc</kbd>
                            </button>
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
    function initRowSelectors($row) {
        $row.find('.select2').select2({ width: '100%' });
        $row.find('.narrationSelect').select2({ placeholder: "Narration...", tags: true, width: '100%' });
        $row.find('.rowPartyName').select2({ placeholder: "Select Party...", allowClear: true, width: '100%' });
        
        if($row.find('.rowPartyType').val()) {
            $row.find('.rowPartyType').trigger('change');
        }
    }

    $(document).on('change', '.rowPartyType', function() {
        let typeId = $(this).val();
        let $row = $(this).closest('tr');
        let $sub = $row.find('.rowPartyName');
        let selected = $sub.data('selected');
        $row.find('.rowPartyCode').val('');
        $sub.html('<option value="">Loading...</option>');
        if(typeId) {
            let url = (['vendor','customer','walkin'].includes(typeId)) ? '{{ route("party.list") }}?type=' + typeId : '{{ url("get-accounts-by-head") }}/' + typeId;
            $.get(url, function(res) {
                $sub.html('<option value="">Select Party...</option>');
                res.forEach(i => {
                    let code = i.account_code || i.id || '';
                    $sub.append(`<option value="${i.id}" data-code="${code}" ${i.id == selected ? 'selected' : ''}>${i.text || i.title}</option>`);
                });
                if(selected) { let code = $sub.find('option:selected').attr('data-code'); $row.find('.rowPartyCode').val(code || ''); }
            });
        }
    });

    $(document).on('change', '.rowPartyName', function() { let code = $(this).find('option:selected').attr('data-code'); $(this).closest('tr').find('.rowPartyCode').val(code || $(this).val() || ''); });

    $(document).on('blur keydown', '.rowPartyCode', function(e) {
        if(e.type === 'keydown' && e.which != 13 && e.which != 9) return;
        let $row = $(this).closest('tr');
        let val = $(this).val();
        if(val) {
            let $sel = $row.find('.rowPartyName');
            let optById = $sel.find('option').filter(function() { return $(this).val() == val; });
            if(optById.length > 0) { $sel.val(val).trigger('change'); return; }
            let optByCode = $sel.find('option').filter(function() { return $(this).attr('data-code') == val; });
            if(optByCode.length > 0) { $sel.val(optByCode.val()).trigger('change'); }
        }
    });

    function calc() {
        let dr = 0, cr = 0;
        $('.row-debit').each(function() { dr += parseFloat($(this).val()) || 0; });
        $('.row-credit').each(function() { cr += parseFloat($(this).val()) || 0; });
        $('#totalDebit').val(dr.toLocaleString('en-US', {minimumFractionDigits: 2}));
        $('#totalCredit').val(cr.toLocaleString('en-US', {minimumFractionDigits: 2}));
    }
    $(document).on('input', '.row-debit, .row-credit', calc);

    $('#btnAddRow').click(function() {
        let row = `<tr class="entry-row">
            <td><select name="narration_id[]" class="form-select form-select-sm narrationSelect"><option value="">Narration...</option>@foreach($narrationsList as $lid => $lname)<option value="{{ $lid }}">{{ $lname }}</option>@endforeach</select></td>
            <td><select name="party_type[]" class="form-select form-select-sm rowPartyType select2" required><option value="">Select Type...</option>@foreach($AccountHeads as $head)<option value="{{ $head->id }}">{{ $head->name }}</option>@endforeach<option value="vendor">Vendor</option><option value="customer">Customer</option><option value="walkin">Walkin</option></select></td>
            <td><input type="text" class="form-control form-control-sm text-center fw-bold text-danger rowPartyCode" placeholder="Code"></td>
            <td><select name="party_id[]" class="form-select form-select-sm rowPartyName select2" required><option value="">Select Party...</option></select></td>
            <td><input type="number" step="0.01" name="debit[]" class="form-control form-control-sm text-end fw-bold row-debit" placeholder="0.00"></td>
            <td><input type="number" step="0.01" name="credit[]" class="form-control form-control-sm text-end fw-bold row-credit" placeholder="0.00"></td>
            <td class="text-center"><button type="button" class="btn text-danger btn-xs removeRow p-0"><i class="fa fa-trash-o fs-6"></i></button></td>
        </tr>`;
        $('#voucherTable tbody').append(row);
        initRowSelectors($('#voucherTable tbody tr').last());
    });

    $(document).on('click', '.removeRow', function() { if($('#voucherTable tbody tr').length > 1) { $(this).closest('tr').remove(); calc(); } });

    $('#voucherTable tbody tr').each(function() { initRowSelectors($(this)); });

    // 💾 Storage Logic
    function showAlert(msg, type = 'success') {
        let $box = $('#alertBox');
        $box.removeClass('d-none alert-success alert-danger').addClass('alert-' + type).html(msg).fadeIn();
        setTimeout(() => $box.fadeOut(() => $box.addClass('d-none')), 3000);
    }

    $('#saveDraftBtn').click(function() {
        $('.ajax-valid-error').remove();
        $.post('{{ route("journal.vochers.ajax-save") }}', $('#journalForm').serialize())
            .done(res => {
                if(res.success) {
                    $('#receipt_id').val(res.id); $('#jvidBadgeText').text(res.jvid);
                    $('#journalForm').addClass('form-locked'); $('#editBtn').show();
                    $('#previewPrintBtn').attr('href', '{{ route("journalVoucher.print", ":id") }}'.replace(':id', res.id)).removeClass('disabled');
                    showAlert('Draft saved successfully!');
                }
            })
            .fail(xhr => { showAlert('Error saving draft.', 'danger'); });
    });

    $('#editBtn').click(function() { $('#journalForm').removeClass('form-locked'); $(this).hide(); });

    $('#postBtn').click(function() {
        let dr = parseFloat($('#totalDebit').val().replace(/,/g, '')) || 0;
        let cr = parseFloat($('#totalCredit').val().replace(/,/g, '')) || 0;
        if(Math.abs(dr - cr) > 0.01) { Swal.fire({ title: 'Unbalanced!', text: 'Debit and Credit must be equal.', icon: 'error' }); return; }

        $('#saveDraftBtn').trigger('click');
        setTimeout(() => {
            let id = $('#receipt_id').val();
            if(id) {
                let f = $('<form>', {action: '{{ route("journal.vochers.post", ":id") }}'.replace(':id', id), method: 'POST'});
                f.append($('<input>', {type: 'hidden', name: '_token', value: '{{ csrf_token() }}'}));
                $('body').append(f); f.submit();
            }
        }, 1000);
    });

    $('#deleteBtn').click(function() {
        Swal.fire({ title: 'Delete permanently?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33' }).then((res) => {
            if(res.isConfirmed) {
                let id = $('#receipt_id').val();
                let f = $('<form>', {action: '{{ route("journal.vochers.cancel", ":id") }}'.replace(':id', id), method: 'POST'});
                f.append($('<input>', {type: 'hidden', name: '_token', value: '{{ csrf_token() }}'}));
                f.append($('<input>', {type: 'hidden', name: '_method', value: 'DELETE'}));
                $('body').append(f); f.submit();
            }
        });
    });

    $(window).on('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.which == 83) { // Ctrl+S (Save)
            e.preventDefault(); 
            if($('#saveDraftBtn').is(':visible')) $('#saveDraftBtn').click(); 
        }
        if ((e.ctrlKey || e.metaKey) && e.which == 13) { // Ctrl+Enter (Post)
            e.preventDefault(); 
            if($('#postBtn').is(':visible')) $('#postBtn').click(); 
        }
        if ((e.ctrlKey || e.metaKey) && e.which == 69) { // Ctrl+E (Edit)
            e.preventDefault(); 
            if($('#editBtn').is(':visible')) $('#editBtn').click(); 
        }
        if ((e.ctrlKey || e.metaKey) && e.which == 80) { // Ctrl+P (Print)
            e.preventDefault(); 
            if(!$('#previewPrintBtn').hasClass('disabled')) {
                window.open($('#previewPrintBtn').attr('href'), '_blank');
            }
        }
        if ((e.ctrlKey || e.metaKey) && e.which == 77) { // Ctrl+M (New)
            e.preventDefault(); 
            window.location.href = $('#newBtn').attr('href');
        }
        if (e.which == 27) { // Esc (Cancel)
            e.preventDefault();
            if($('#deleteBtn').is(':visible')) $('#deleteBtn').click();
        }
    });

    calc();
});
</script>
@endsection
