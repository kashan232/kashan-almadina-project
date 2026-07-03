@extends('admin_panel.layout.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
@include('admin_panel.vochers._compact_voucher_styles', ['accentColor' => '#10b981'])
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid stock-hold-page">
            
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
                                <i class="fa fa-hashtag me-1"></i> <span id="jvidBadgeText">{{ $receipt->id ? $receipt->jvid : 'Auto-Generated' }}</span>
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

                <!-- Footer Remarks -->
                <div class="row g-2 align-items-end mb-2">
                    <div class="col-md-12">
                        <div class="alert alert-info py-1 px-2 mb-0" style="font-size: 10px;">
                            <i class="fa fa-info-circle me-1"></i> Ensure Debit and Credit totals are balanced before posting.
                        </div>
                    </div>
                </div>
                @include('admin_panel.vochers._standard_voucher_buttons', [
                    'printRoute' => 'journalVoucher.print',
                    'listRoute' => 'all-journal-vochers',
                    'newRoute' => 'journal-vochers',
                    'showUnpost' => false,
                ])
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
                    $('#journalForm').addClass('form-locked');
                    $('#editInvoiceBtn, #postBtn').prop('disabled', false);
                    $('#realPrintBtn').attr('href', '{{ route("journalVoucher.print", ":id") }}'.replace(':id', res.id)).removeClass('pe-none opacity-50');
                    $('#deleteBtn').prop('disabled', false);
                    showAlert('Draft saved successfully!');
                }
            })
            .fail(xhr => { showAlert('Error saving draft.', 'danger'); });
    });

    $('#editInvoiceBtn').on('click', function() {
        if ($(this).prop('disabled')) return;
        $('#journalForm').removeClass('form-locked');
        $(this).prop('disabled', true);
        $('#postBtn').prop('disabled', true);
        $('#saveDraftBtn').prop('disabled', false);
    });

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

    $(window).on('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.which == 83) { // Ctrl+S (Save)
            e.preventDefault();
            if(!$('#saveDraftBtn').prop('disabled')) $('#saveDraftBtn').click();
        }
        if ((e.ctrlKey || e.metaKey) && e.which == 13) { // Ctrl+Enter (Post)
            e.preventDefault();
            if(!$('#postBtn').prop('disabled')) $('#postBtn').click();
        }
        if ((e.ctrlKey || e.metaKey) && e.which == 69) { // Ctrl+E (Edit)
            e.preventDefault();
            if(!$('#editInvoiceBtn').prop('disabled')) $('#editInvoiceBtn').click();
        }
        if ((e.ctrlKey || e.metaKey) && e.which == 80) { // Ctrl+P (Print)
            e.preventDefault();
            const href = $('#realPrintBtn').attr('href');
            if (href && href !== 'javascript:void(0)') window.open(href, '_blank');
        }
        if ((e.ctrlKey || e.metaKey) && e.which == 68) { // Ctrl+D (Delete draft)
            e.preventDefault();
            if(!$('#deleteBtn').prop('disabled')) handleCancel();
        }
        if ((e.ctrlKey || e.metaKey) && e.which == 77) { // Ctrl+M (New)
            e.preventDefault();
            window.location.href = $('#newInvoiceBtn').attr('href');
        }
        if ((e.ctrlKey || e.metaKey) && e.which == 76) { // Ctrl+L (List)
            e.preventDefault();
            window.location.href = $('#listBtn').attr('href');
        }
        if (e.which == 27) { // Esc (Cancel)
            e.preventDefault();
            window.location.href = $('#exitBtn').attr('href');
        }
    });

    calc();
});

function handleCancel() {
    let id = $('#receipt_id').val();
    if (!id) {
        window.location.href = "{{ route('all-journal-vochers') }}";
        return;
    }
    Swal.fire({ title: 'Delete this draft?', icon: 'warning', showCancelButton: true }).then((res) => {
        if(res.isConfirmed) {
            let form = $('<form>', {action: '{{ route("journal.vochers.cancel", ":id") }}'.replace(':id', id), method: 'POST'})
                .append($('<input>', {type: 'hidden', name: '_token', value: '{{ csrf_token() }}'}))
                .append($('<input>', {type: 'hidden', name: '_method', value: 'DELETE'}));
            $('body').append(form);
            form.submit();
        }
    });
}
</script>
@endsection
