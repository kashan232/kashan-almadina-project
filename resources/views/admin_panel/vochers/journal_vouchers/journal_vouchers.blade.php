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
    .table thead th { background: #f8f9fa !important; text-align: center; font-size: 0.7rem; padding: 8px !important; white-space: nowrap; }
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
                <h5 class="page-title mb-0 fw-bold text-success"><i class="fa fa-book me-2"></i>Journal Voucher</h5>
                <span id="statusBadge" class="badge {{ $receipt->status == 'posted' ? 'bg-success' : 'bg-warning text-dark' }} px-3 py-2 rounded-pill shadow-sm">
                    <i class="fa {{ $receipt->status == 'posted' ? 'fa-check-circle' : 'fa-pencil' }} me-1"></i> 
                    {{ strtoupper($receipt->status ?: 'DRAFT') }}
                </span>
                <span class="badge bg-primary px-3 py-2 rounded-pill shadow-sm">
                    <i class="fa fa-tag me-1"></i> <span id="jvidBadgeText">{{ $receipt->jvid ?: $nextJvid }}</span>
                </span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('all-journal-vochers') }}" id="listBtn" class="btn btn-sm btn-outline-secondary rounded-pill px-3 shadow-sm">
                    <i class="fa fa-list me-1"></i> View All
                </a>
            </div>
        </div>

        <form id="journalForm" autocomplete="off" class="{{ ($receipt->id && $receipt->status == 'posted') ? 'form-locked' : '' }}">
            @csrf
            <input type="hidden" name="id" id="receipt_id" value="{{ $receipt->id }}">

            <div class="row g-3 mb-4">
                <div class="col-md-2">
                    <div class="card border-0 bg-light p-2 shadow-sm h-100">
                        <label class="form-label text-muted small fw-bold mb-1">Entry Date</label>
                        <input type="date" name="entry_date" class="form-control form-control-sm" value="{{ $receipt->entry_date ?: date('Y-m-d') }}">
                    </div>
                </div>
                <div class="col-md-10">
                    <div class="card border-0 bg-light p-2 shadow-sm h-100">
                        <label class="form-label text-muted small fw-bold mb-1">General Remarks</label>
                        <input type="text" name="remarks" class="form-control form-control-sm" placeholder="Remarks..." value="{{ $receipt->remarks }}">
                    </div>
                </div>
            </div>

            <div class="card border shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fa fa-list-ul me-2"></i>Journal Entries</h6>
                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" id="btnAddRow" {{ $receipt->status == 'posted' ? 'disabled' : '' }}>
                        <i class="fa fa-plus me-1"></i> Add Row <kbd class="ms-1 small opacity-75">Alt+A</kbd>
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered mb-0" id="voucherTable">
                            <thead>
                                <tr>
                                    <th width="15%">Narration</th>
                                    <th width="15%">Party Type <span class="text-danger">*</span></th>
                                    <th width="12%">Code <span class="text-danger">*</span></th>
                                    <th width="20%">Party Name <span class="text-danger">*</span></th>
                                    <th width="12%">Debit</th>
                                    <th width="12%">Credit</th>
                                    <th width="6%">Act</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $narrs = json_decode($receipt->narration_id, true) ?? ['',''];
                                    $pTypes = json_decode($receipt->party_type, true) ?? ['',''];
                                    $pIds = json_decode($receipt->party_id, true) ?? ['',''];
                                    $drCr = json_decode($receipt->dr_cr, true) ?? ['DR','CR'];
                                    $debits = json_decode($receipt->debit, true) ?? ['',''];
                                    $credits = json_decode($receipt->credit, true) ?? ['',''];
                                @endphp
                                @foreach($pTypes as $idx => $type)
                                <tr class="entry-row">
                                    <td>
                                        <select name="narration_id[]" class="form-select narrationSelect">
                                            <option value="">Narration...</option>
                                            @foreach($narrationsList as $lid => $lname)
                                                <option value="{{ $lid }}" {{ ($narrs[$idx] ?? '') == $lid ? 'selected' : '' }}>{{ $lname }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select name="party_type[]" class="form-select form-select-sm rowPartyType" required>
                                            <option value="">Select Type...</option>
                                            @foreach($AccountHeads as $head)
                                                <option value="{{ $head->id }}" {{ ($pTypes[$idx] ?? '') == $head->id ? 'selected' : '' }}>{{ $head->name }}</option>
                                            @endforeach
                                            <option value="vendor" {{ ($pTypes[$idx] ?? '') == 'vendor' ? 'selected' : '' }}>Vendor</option>
                                            <option value="customer" {{ ($pTypes[$idx] ?? '') == 'customer' ? 'selected' : '' }}>Customer</option>
                                            <option value="walkin" {{ ($pTypes[$idx] ?? '') == 'walkin' ? 'selected' : '' }}>Walkin</option>
                                        </select>
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm text-center rowPartyCode" placeholder="Code" value="{{ is_numeric($pIds[$idx] ?? '') ? (DB::table('accounts')->where('id', $pIds[$idx])->value('account_code') ?: $pIds[$idx]) : ($pIds[$idx] ?? '') }}"></td>
                                    <td>
                                        <select name="party_id[]" class="form-select form-select-sm rowPartyName" data-selected="{{ $pIds[$idx] ?? '' }}" required>
                                            <option value="">Select Party...</option>
                                        </select>
                                    </td>
                                    <td><input type="number" step="0.01" name="debit[]" class="form-control form-control-sm text-end row-debit" value="{{ $debits[$idx] ?? '' }}"></td>
                                    <td><input type="number" step="0.01" name="credit[]" class="form-control form-control-sm text-end row-credit" value="{{ $credits[$idx] ?? '' }}"></td>
                                    <td class="text-center"><button type="button" class="btn text-danger btn-xs removeRow"><i class="fa fa-trash"></i></button></td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr class="fw-bold">
                                    <td colspan="4" class="text-end py-3">GRAND TOTALS:</td>
                                    <td class="text-end py-3 bg-primary bg-opacity-10">
                                        <input type="text" name="total_debit" id="totalDebit" class="form-control form-control-sm text-end border-0 bg-transparent fw-bold text-primary fs-6" readonly value="{{ $receipt->total_debit ?? '0.00' }}">
                                    </td>
                                    <td class="text-end py-3 bg-danger bg-opacity-10">
                                        <input type="text" name="total_credit" id="totalCredit" class="form-control form-control-sm text-end border-0 bg-transparent fw-bold text-danger fs-6" readonly value="{{ $receipt->total_credit ?? '0.00' }}">
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
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

                <a href="{{ $receipt->id ? route('journalVoucher.print', $receipt->id) : 'javascript:void(0)' }}" id="previewPrintBtn" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill px-4 shadow-sm {{ !$receipt->id ? 'disabled' : '' }}">
                    <i class="fa fa-print me-1"></i> Print Preview <kbd class="ms-1 small opacity-75">Ctrl+P</kbd>
                </a>
                <a href="{{ route('journal-vochers') }}" class="btn btn-sm btn-info text-dark fw-bold rounded-pill px-4 shadow-sm">
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
    function initRowSelectors($row) {
        $row.find('.narrationSelect').select2({ placeholder: "Narration...", tags: true, width: '100%' });
        $row.find('.rowPartyName').select2({ placeholder: "Select Party...", allowClear: true, width: '100%' });
        
        // Trigger party load for existing rows if needed
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
                if(selected) {
                    let code = $sub.find('option:selected').attr('data-code');
                    $row.find('.rowPartyCode').val(code || '');
                }
            });
        }
    });

    $(document).on('change', '.rowPartyName', function() {
        let code = $(this).find('option:selected').attr('data-code');
        $(this).closest('tr').find('.rowPartyCode').val(code || $(this).val() || '');
    });

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

    // Removed rowDrCr change handler

    function calc() {
        let dr = 0, cr = 0;
        $('.row-debit').each(function() { dr += parseFloat($(this).val()) || 0; });
        $('.row-credit').each(function() { cr += parseFloat($(this).val()) || 0; });
        $('#totalDebit').val(dr.toFixed(2));
        $('#totalCredit').val(cr.toFixed(2));
    }
    $(document).on('input', '.row-debit, .row-credit', calc);

    $('#btnAddRow').click(function() {
        let row = `<tr class="entry-row">
            <td><select name="narration_id[]" class="form-select narrationSelect"><option value="">Narration...</option>@foreach($narrationsList as $lid => $lname)<option value="{{ $lid }}">{{ $lname }}</option>@endforeach</select></td>
            <td><select name="party_type[]" class="form-select form-select-sm rowPartyType" required><option value="">Select Type...</option>@foreach($AccountHeads as $head)<option value="{{ $head->id }}">{{ $head->name }}</option>@endforeach<option value="vendor">Vendor</option><option value="customer">Customer</option><option value="walkin">Walkin</option></select></td>
            <td><input type="text" class="form-control form-control-sm text-center rowPartyCode" placeholder="Code" required></td>
            <td><select name="party_id[]" class="form-select form-select-sm rowPartyName" required><option value="">Select Party...</option></select></td>
            <td><input type="number" step="0.01" name="debit[]" class="form-control form-control-sm text-end row-debit"></td>
            <td><input type="number" step="0.01" name="credit[]" class="form-control form-control-sm text-end row-credit"></td>
            <td class="text-center"><button type="button" class="btn text-danger btn-xs removeRow"><i class="fa fa-trash"></i></button></td>
        </tr>`;
        $('#voucherTable tbody').append(row);
        initRowSelectors($('#voucherTable tbody tr').last());
    });

    $(document).on('click', '.removeRow', function() { if($('#voucherTable tbody tr').length > 1) { $(this).closest('tr').remove(); calc(); } });

    // Initialize existing rows
    $('#voucherTable tbody tr').each(function() { initRowSelectors($(this)); });

    // 💾 AJAX Save
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
                    showAlert('Journal Voucher saved.');
                }
            })
            .fail(xhr => {
                let msg = 'Error saving voucher.';
                if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                showAlert(msg, 'danger');
            });
    });

    $('#editBtn').click(function() { $('#journalForm').removeClass('form-locked'); $(this).hide(); });

    $(window).on('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && (e.which == 83 || e.keyCode == 83)) { 
            e.preventDefault(); 
            if (!$('#saveDraftBtn').is(':disabled') && $('#saveDraftBtn').is(':visible')) { $('#saveDraftBtn').click(); }
            return false;
        }
        if ((e.ctrlKey || e.metaKey) && (e.which == 13 || e.keyCode == 13)) { 
            e.preventDefault(); 
            if (!$('#postBtn').is(':disabled') && $('#postBtn').is(':visible')) { $('#postBtn').trigger('click'); }
        }
        if ((e.ctrlKey || e.metaKey) && (e.which == 69 || e.keyCode == 69)) { 
            e.preventDefault(); 
            $('#editBtn').click(); 
        }
        if (e.altKey && (e.which == 65 || e.keyCode == 65)) { 
            e.preventDefault(); 
            $('#btnAddRow').click(); 
        }
        if ((e.ctrlKey || e.metaKey) && (e.which == 80 || e.keyCode == 80)) { 
            e.preventDefault(); 
            let printUrl = $('#previewPrintBtn').attr('href');
            if(printUrl && printUrl !== 'javascript:void(0)') window.open(printUrl, '_blank');
        }
    });

    $('#postBtn').click(function() {
        if(!confirm('Post this Journal Voucher?')) return;
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
});
</script>
@endsection
