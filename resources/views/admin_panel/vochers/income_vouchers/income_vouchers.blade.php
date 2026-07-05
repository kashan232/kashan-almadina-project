@extends('admin_panel.layout.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
@include('admin_panel.vochers._compact_voucher_styles', ['accentColor' => '#10b981'])
</style>
@php
    $isViewMode = isset($viewMode) && $viewMode;
    $isPosted = ($receipt->status ?? '') === 'posted';
@endphp

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
                                <h6 class="mb-0 fw-bold text-dark">
                                    <i class="fa fa-line-chart me-2 text-success"></i>
                                    {{ $isViewMode ? 'View Income Voucher' : 'Income Voucher' }}
                                </h6>
                            </div>
                            <span id="statusBadge" class="badge {{ ($isPosted || $isViewMode) ? 'bg-success' : 'bg-warning text-dark' }} rounded-pill px-3 py-1" style="font-size: 10px;">
                                {{ $isViewMode ? 'POSTED' : strtoupper($receipt->status ?: 'DRAFT') }}
                            </span>
                            <span class="badge bg-light text-primary border rounded-pill px-3 py-1" style="font-size: 10px;">
                                <i class="fa fa-hashtag me-1"></i> <span id="ividBadgeText">{{ $receipt->id ? $receipt->ivid : 'Auto-Generated' }}</span>
                            </span>
                        </div>
                        <div class="d-flex gap-1">
                            <a href="{{ route('all-income-vochers') }}" id="listBtn" class="btn btn-primary btn-sm px-3 text-white" style="font-size: 11px;">
                                <i class="fa fa-list me-1"></i> View Registry <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+L</kbd>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <form id="incomeForm" autocomplete="off" class="{{ ($isViewMode || ($receipt->id && $isPosted)) ? 'form-locked' : '' }}{{ $isViewMode ? ' view-mode' : '' }}">
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
                            <div class="col-md-3">
                                <label class="form-label">Main Account Head <span class="text-danger">*</span></label>
                                <select name="account_head" id="account_head" class="form-select form-select-sm select2">
                                    <option value="">Select Head...</option>
                                    @foreach($AccountHeads as $head)
                                        <option value="{{ $head->id }}" {{ $receipt->account_head == $head->id ? 'selected' : '' }}>{{ $head->name }}</option>
                                    @endforeach
                                    <option value="vendor" {{ $receipt->account_head == 'vendor' ? 'selected' : '' }}>Vendor</option>
                                    <option value="customer" {{ $receipt->account_head == 'customer' ? 'selected' : '' }}>Customer</option>
                                    <option value="walkin" {{ $receipt->account_head == 'walkin' ? 'selected' : '' }}>Walkin Customer</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">Code</label>
                                <input type="text" id="account_code_input" class="form-control form-control-sm text-center fw-bold text-success" placeholder="Code" value="">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Account (Deposit To) <span class="text-danger">*</span></label>
                                <select name="account_id" id="account_id" class="form-select form-select-sm select2" data-selected="{{ $receipt->account_id }}">
                                    <option value="">Select Account...</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Voucher Rows Table -->
                <div class="card form-card mb-2">
                    <div class="card-header bg-white py-1 d-flex justify-content-between align-items-center border-bottom">
                        <span class="fw-bold text-muted small text-uppercase"><i class="fa fa-list-ul me-1"></i> Income Details</span>
                        <button type="button" class="btn btn-primary btn-xs px-3 rounded-pill" id="btnAddRow" {{ ($isPosted || $isViewMode) ? 'disabled' : '' }}>
                            <i class="fa fa-plus me-1"></i> Add Line
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover table-bordered mb-0" id="voucherTable">
                                <thead>
                                    <tr>
                                        <th width="20%">Narration / Description</th>
                                        <th width="15%">Party Type</th>
                                        <th width="10%" class="text-center">Code/ID</th>
                                        <th width="25%">Source Party Name</th>
                                        <th width="12%">Reference#</th>
                                        <th width="13%" class="text-end">Amount</th>
                                        <th width="5%" class="text-center">Act</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $narrs = json_decode($receipt->narration_id, true) ?? [''];
                                        $types = json_decode($receipt->party_type, true) ?? [''];
                                        $pIds = json_decode($receipt->party_id, true) ?? [''];
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
                                            <select name="party_type[]" class="form-select form-select-sm rowPartyType select2">
                                                <option value="">Select Type...</option>
                                                @foreach($AccountHeads as $head)
                                                    <option value="{{ $head->id }}" {{ ($types[$idx] ?? '') == $head->id ? 'selected' : '' }}>{{ $head->name }}</option>
                                                @endforeach
                                                <option value="vendor" {{ ($types[$idx] ?? '') == 'vendor' ? 'selected' : '' }}>Vendor</option>
                                                <option value="customer" {{ ($types[$idx] ?? '') == 'customer' ? 'selected' : '' }}>Customer</option>
                                                <option value="walkin" {{ ($types[$idx] ?? '') == 'walkin' ? 'selected' : '' }}>Walkin Customer</option>
                                            </select>
                                        </td>
                                        <td><input type="text" name="row_party_code[]" class="form-control form-control-sm text-center fw-bold text-danger rowPartyCode" placeholder="Code"></td>
                                        <td>
                                            <select name="party_id[]" class="form-select form-select-sm rowPartySelect select2" data-selected="{{ $pIds[$idx] ?? '' }}">
                                                <option value="">Select Party...</option>
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
                                        <td colspan="5" class="text-end py-2 text-muted small">TOTAL INCOME AMOUNT</td>
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
                <div class="row g-2 align-items-end mb-2">
                    <div class="col-md-12">
                        <div class="card form-card mb-0">
                            <div class="card-body p-2">
                                <label class="form-label">General Remarks / Note</label>
                                <input type="text" name="remarks" class="form-control form-control-sm" placeholder="Type additional voucher notes here..." value="{{ $receipt->remarks }}">
                            </div>
                        </div>
                    </div>
                </div>
                @include('admin_panel.vochers._standard_voucher_buttons', [
                    'printRoute' => 'incomeVoucher.print',
                    'listRoute' => 'all-income-vochers',
                    'newRoute' => 'income-vochers',
                    'showUnpost' => !$isViewMode,
                ])
            </form>

            @if($isPosted || $isViewMode)
                <div class="posted-watermark" id="postedWatermark">Posted</div>
            @endif
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@include('admin_panel.vochers._voucher_validation_js')
<script>
$(document).ready(function() {
    var isViewMode = @json($isViewMode);

    if (isViewMode) {
        $('#saveDraftBtn, #editInvoiceBtn, #postBtn, #deleteBtn, #unpostBtn').prop('disabled', true);
        $('#incomeForm select, #incomeForm input, #incomeForm textarea').prop('disabled', true);
        $('#btnAddRow').prop('disabled', true);
    }

    function initSelectors($container = $('body')) {
        $container.find('.select2').select2({ width: '100%' });
        $container.find('.narrationSelect').select2({ placeholder: "Narration...", tags: true, width: '100%' });
        $container.find('.rowPartySelect').select2({ placeholder: "Select Party...", allowClear: true, width: '100%' });
        $('#account_id').select2({ placeholder: "Select Account...", allowClear: true, width: '100%' });
    }
    initSelectors();

    // 🏦 Header Account Logic
    $('#account_head').change(function() {
        let id = $(this).val();
        let $sub = $('#account_id');
        let selected = $sub.data('selected');
        $('#account_code_input').val('');
        $sub.html('<option value="">Loading...</option>');
        if(id) {
            let url = (['vendor','customer','walkin'].includes(id)) ? '{{ route("party.list") }}?type=' + id : '{{ url("get-accounts-by-head") }}/' + id;
            $.get(url, function(res) {
                $sub.html('<option value="">Select Account...</option>');
                let hasSelected = false;
                res.forEach(a => {
                    let code = a.account_code || '';
                    let sel = (a.id == selected) ? 'selected' : '';
                    if (a.id == selected) hasSelected = true;
                    $sub.append(`<option value="${a.id}" data-code="${code}" ${sel}>${a.title || a.text}</option>`);
                });
                if (hasSelected && selected) {
                    $sub.val(String(selected));
                    let code = $sub.find('option:selected').attr('data-code');
                    $('#account_code_input').val(code || selected);
                }
                $sub.trigger('change');
                $sub.data('selected', ''); // Clear so it only auto-selects on first load
            });
        }
    });
    $('#account_head').each(function() { if ($(this).val()) $(this).trigger('change'); });

    $('#account_id').on('change', function() {
        let code = $(this).find('option:selected').attr('data-code');
        $('#account_code_input').val(code || $(this).val() || '');
    });

    $(document).on('blur keydown', '#account_code_input', function(e) {
        if(e.type === 'keydown' && e.which != 13 && e.which != 9) return;
        let val = $(this).val();
        if(val) {
            let optById = $('#account_id option').filter(function() { return $(this).val() == val; });
            if(optById.length > 0) { $('#account_id').val(val).trigger('change'); return; }
            let optByCode = $('#account_id option').filter(function() { return $(this).attr('data-code') == val; });
            if(optByCode.length > 0) { $('#account_id').val(optByCode.val()).trigger('change'); }
        }
    });

    // 👤 Row Party Logic
    $(document).on('change', '.rowPartyType', function() {
        let type = $(this).val();
        let $row = $(this).closest('tr');
        let $select = $row.find('.rowPartySelect');
        let selected = $select.data('selected');
        $row.find('.rowPartyCode').val('');
        $select.html('<option value="">Loading...</option>');
        if(type) {
            let url = (['vendor','customer','walkin'].includes(type)) ? '{{ route("party.list") }}?type=' + type : '{{ url("get-accounts-by-head") }}/' + type;
            $.get(url, function(res) {
                $select.html('<option value="">Select Party...</option>');
                res.forEach(i => {
                    let code = i.account_code || '';
                    $select.append(`<option value="${i.id}" data-code="${code}" ${i.id == selected ? 'selected' : ''}>${i.text || i.title}</option>`);
                });
                if(selected) {
                    let code = $select.find('option:selected').attr('data-code');
                    $row.find('.rowPartyCode').val(code || selected);
                }
            });
        }
    });

    $('.rowPartyType').each(function() { if ($(this).val()) $(this).trigger('change'); });

    $(document).on('change', '.rowPartySelect', function() {
        let code = $(this).find('option:selected').attr('data-code');
        $(this).closest('tr').find('.rowPartyCode').val(code || $(this).val() || '');
    });

    $(document).on('blur keydown', '.rowPartyCode', function(e) {
        if(e.type === 'keydown' && e.which != 13 && e.which != 9) return;
        let $row = $(this).closest('tr');
        let val = $(this).val();
        if(val) {
            let $sel = $row.find('.rowPartySelect');
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
            <td><select name="narration_id[]" class="form-select form-select-sm narrationSelect"><option value="">Narration...</option>@foreach($narrationsList as $lid => $lname)<option value="{{ $lid }}">{{ $lname }}</option>@endforeach</select></td>
            <td><select name="party_type[]" class="form-select form-select-sm rowPartyType select2"><option value="">Select Type...</option>@foreach($AccountHeads as $head)<option value="{{ $head->id }}">{{ $head->name }}</option>@endforeach<option value="vendor">Vendor</option><option value="customer">Customer</option><option value="walkin">Walkin</option></select></td>
            <td><input type="text" name="row_party_code[]" class="form-control form-control-sm text-center fw-bold text-danger rowPartyCode" placeholder="Code"></td>
            <td><select name="party_id[]" class="form-select form-select-sm rowPartySelect select2"><option value="">Select Party...</option></select></td>
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
        VoucherFieldValidation.clearErrors($('#incomeForm'));
        $.post('{{ route("income.vochers.ajax-save") }}', $('#incomeForm').serialize())
            .done(res => {
                if(res.success) {
                    $('#receipt_id').val(res.id); $('#ividBadgeText').text(res.ivid);
                    $('#incomeForm').addClass('form-locked');
                    $('#editInvoiceBtn, #postBtn').prop('disabled', false);
                    $('#realPrintBtn').attr('href', '{{ route("incomeVoucher.print", ":id") }}'.replace(':id', res.id)).removeClass('pe-none opacity-50');
                    $('#deleteBtn').prop('disabled', false);
                    showAlert('Draft saved and form locked.');
                }
            })
            .fail(xhr => {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    VoucherFieldValidation.applyErrors($('#incomeForm'), xhr.responseJSON.errors);
                } else {
                    showAlert((xhr.responseJSON && xhr.responseJSON.message) || 'Please fix errors.', 'danger');
                }
            });
    });

    $('#editInvoiceBtn').on('click', function() {
        if ($(this).prop('disabled')) return;
        $('#incomeForm').removeClass('form-locked');
        $(this).prop('disabled', true);
        $('#postBtn').prop('disabled', true);
        $('#saveDraftBtn').prop('disabled', false);
    });

    $('#postBtn').click(function() {
        VoucherFieldValidation.clearErrors($('#incomeForm'));
        $.post('{{ route("income.vochers.ajax-save") }}', $('#incomeForm').serialize())
            .done(res => {
                if (!res.success) return;
                let id = res.id || $('#receipt_id').val();
                if (!id) return;
                let f = $('<form>', {action: '{{ route("income.vochers.post", ":id") }}'.replace(':id', id), method: 'POST'});
                f.append($('<input>', {type: 'hidden', name: '_token', value: '{{ csrf_token() }}'}));
                $('body').append(f); f.submit();
            })
            .fail(xhr => {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    VoucherFieldValidation.applyErrors($('#incomeForm'), xhr.responseJSON.errors);
                }
            });
    });

    $('#realPrintBtn').on('click', function(e) {
        var href = $(this).attr('href');
        if (!href || href === 'javascript:void(0)') { e.preventDefault(); showAlert('Save first', 'danger'); }
    });

    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && (e.key === 's' || e.key === 'S')) { e.preventDefault(); e.stopImmediatePropagation(); if (!$('#saveDraftBtn').prop('disabled')) $('#saveDraftBtn').click(); }
        if (e.ctrlKey && e.key === 'Enter') { e.preventDefault(); e.stopImmediatePropagation(); if (!$('#postBtn').prop('disabled')) $('#postBtn').click(); }
        if (e.ctrlKey && (e.key === 'p' || e.key === 'P')) { e.preventDefault(); var href = $('#realPrintBtn').attr('href'); if (href && href !== 'javascript:void(0)') window.open(href, '_blank'); else showAlert('Save first', 'danger'); }
        if (e.ctrlKey && (e.key === 'e' || e.key === 'E')) { e.preventDefault(); if (!$('#editInvoiceBtn').prop('disabled')) $('#editInvoiceBtn').click(); }
        if (e.ctrlKey && (e.key === 'd' || e.key === 'D')) { e.preventDefault(); if (!$('#deleteBtn').prop('disabled')) handleCancel(); }
        if (e.ctrlKey && (e.key === 'm' || e.key === 'M')) { e.preventDefault(); window.location.href = $('#newInvoiceBtn').attr('href'); }
        if (e.ctrlKey && (e.key === 'l' || e.key === 'L')) { e.preventDefault(); window.location.href = $('#listBtn').attr('href'); }
        if (e.key === 'Escape') {
            if ($('.modal.show').length) { $('.modal.show').modal('hide'); }
            else if (!$('#deleteBtn').prop('disabled')) { e.preventDefault(); window.location.href = $('#exitBtn').attr('href'); }
        }
    }, true);

});

function handleCancel() {
    let id = $('#receipt_id').val();
    if (!id) { window.location.href = "{{ route('all-income-vochers') }}"; }
    else {
        Swal.fire({ title: 'Delete this draft?', icon: 'warning', showCancelButton: true }).then((res) => {
            if(res.isConfirmed) {
                let form = $('<form>', {action: '{{ route("income.vochers.cancel", ":id") }}'.replace(':id', id), method: 'POST'})
                    .append($('<input>', {type: 'hidden', name: '_token', value: '{{ csrf_token() }}'}))
                    .append($('<input>', {type: 'hidden', name: '_method', value: 'DELETE'}));
                $('body').append(form); form.submit();
            }
        });
    }
}
</script>
@endsection
