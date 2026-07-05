@extends('admin_panel.layout.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
@include('admin_panel.vochers._compact_voucher_styles', ['accentColor' => '#ef4444'])
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
                                <h6 class="mb-0 fw-bold text-dark"><i class="fa fa-money me-2 text-danger"></i>Expense Voucher</h6>
                            </div>
                            <span id="statusBadge" class="badge {{ $receipt->status == 'posted' ? 'bg-success' : 'bg-warning text-dark' }} rounded-pill px-3 py-1" style="font-size: 10px;">
                                {{ strtoupper($receipt->status ?: 'DRAFT') }}
                            </span>
                            <span class="badge bg-light text-primary border rounded-pill px-3 py-1" style="font-size: 10px;">
                                <i class="fa fa-hashtag me-1"></i> <span id="evidBadgeText">{{ $receipt->id ? $receipt->evid : 'Auto-Generated' }}</span>
                            </span>
                        </div>
                        <div class="d-flex gap-1">
                            <a href="{{ route('all-expense-vochers') }}" id="listBtn" class="btn btn-outline-secondary btn-sm rounded-pill px-3" style="font-size: 11px;">
                                <i class="fa fa-list me-1"></i> View Registry
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <form id="expenseForm" autocomplete="off" class="{{ ($receipt->id && $receipt->status == 'posted') ? 'form-locked' : '' }}">
                @csrf
                <input type="hidden" name="id" id="receipt_id" value="{{ $receipt->id }}">

                <!-- Voucher Header Fields -->
                <div class="card form-card mb-2">
                    <div class="card-body p-2">
                        <div class="row g-2">
                            <div class="col-md-2">
                                <label class="form-label">Entry Date</label>
                                <input type="date" name="entry_date" id="entry_date" class="form-control form-control-sm" value="{{ $receipt->entry_date ?: date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Party Type <span class="text-danger">*</span></label>
                                <select name="vendor_type" id="vendor_type" class="form-select form-select-sm select2">
                                    <option value="">Select Type...</option>
                                    @foreach($AccountHeads as $head)
                                        <option value="{{ $head->id }}" {{ $receipt->type == $head->id ? 'selected' : '' }}>{{ $head->name }}</option>
                                    @endforeach
                                    <option value="vendor" {{ $receipt->type == 'vendor' ? 'selected' : '' }}>Vendor</option>
                                    <option value="customer" {{ $receipt->type == 'customer' ? 'selected' : '' }}>Customer</option>
                                    <option value="walkin" {{ $receipt->type == 'walkin' ? 'selected' : '' }}>Walkin Customer</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">ID/Code</label>
                                <input type="text" id="party_code_input" class="form-control form-control-sm text-center fw-bold text-danger" placeholder="Code" value="{{ $receipt->party_id }}">
                            </div>
                            <div class="col-md-7">
                                <label class="form-label">Source Party Name <span class="text-danger">*</span></label>
                                <select name="vendor_id" id="vendor_id" class="form-select form-select-sm select2" data-selected-id="{{ $receipt->party_id }}">
                                    <option value="">Select Party...</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Voucher Rows Table -->
                <div class="card form-card mb-2">
                    <div class="card-header bg-white py-1 d-flex justify-content-between align-items-center border-bottom">
                        <span class="fw-bold text-muted small text-uppercase"><i class="fa fa-list-ul me-1"></i> Voucher Details</span>
                        <button type="button" class="btn btn-primary btn-xs px-3 rounded-pill" id="btnAddRow" {{ $receipt->status == 'posted' ? 'disabled' : '' }}>
                            <i class="fa fa-plus me-1"></i> Add Line
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover table-bordered mb-0" id="voucherTable">
                                <thead>
                                    <tr>
                                        <th width="22%">Narration / Description</th>
                                        <th width="18%">Account Head</th>
                                        <th width="8%" class="text-center">Code</th>
                                        <th width="28%">Destination Account (Deposit To)</th>
                                        <th width="14%" class="text-end">Amount</th>
                                        <th width="5%" class="text-center">Act</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $narrs = json_decode($receipt->narration_id, true) ?? [''];
                                        $rowHeads = json_decode($receipt->row_account_head, true) ?? [''];
                                        $rowAccs = json_decode($receipt->row_account_id, true) ?? [''];
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
                                            <select name="row_account_head[]" class="form-select form-select-sm rowAccountHead select2">
                                                <option value="">Select Head...</option>
                                                @foreach($AccountHeads as $head)
                                                    <option value="{{ $head->id }}" {{ ($rowHeads[$idx] ?? '') == $head->id ? 'selected' : '' }}>{{ $head->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="text" class="form-control form-control-sm text-center fw-bold text-danger account-id-lookup" placeholder="Code" value="{{ $rowAccs[$idx] ?? '' }}"></td>
                                        <td>
                                            <select name="row_account_id[]" class="form-select form-select-sm rowAccountSub select2" data-selected="{{ $rowAccs[$idx] ?? '' }}">
                                                <option value="">Select Account...</option>
                                            </select>
                                        </td>
                                        <td><input type="number" step="0.01" name="amount[]" class="form-control form-control-sm text-end fw-bold row-amount" value="{{ $amounts[$idx] ?? '' }}" placeholder="0.00"></td>
                                        <td class="text-center"><button type="button" class="btn text-danger btn-xs removeRow p-0"><i class="fa fa-trash-o fs-6"></i></button></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr class="fw-bold">
                                        <td colspan="4" class="text-end py-2 text-muted small">TOTAL VOUCHER AMOUNT</td>
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
                    'printRoute' => 'ExpenseVoucher.print',
                    'listRoute' => 'all-expense-vochers',
                    'newRoute' => 'expense-vochers',
                    'showUnpost' => true,
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
@include('admin_panel.vochers._voucher_validation_js')
<script>
$(document).ready(function() {
    function initSelectors($container = $('body')) {
        $container.find('.select2').select2({ width: '100%' });
        $container.find('.narrationSelect').select2({ placeholder: "Narration...", tags: true, width: '100%' });
        $container.find('.rowAccountSub').select2({ placeholder: "Select Account...", allowClear: true, width: '100%' });
        $('#vendor_id').select2({ placeholder: "Select Party...", allowClear: true, width: '100%' });
    }
    initSelectors();

    // 👤 Header Party Logic
    function syncPartyIdToSelect2() {
        let val = $('#party_code_input').val();
        if (val) {
            let optById = $('#vendor_id option').filter(function() { return $(this).val() == val; });
            if (optById.length > 0) { $('#vendor_id').val(val).trigger('change'); return; }
            let optByCode = $('#vendor_id option').filter(function() { return $(this).attr('data-code') == val; });
            if (optByCode.length > 0) { $('#vendor_id').val(optByCode.val()).trigger('change'); }
        }
    }
    $('#party_code_input').on('blur keydown', function(e) { if(e.type === 'keydown' && e.which != 13 && e.which != 9) return; syncPartyIdToSelect2(); });
    $('#vendor_id').on('change', function() { let code = $(this).find('option:selected').attr('data-code'); $('#party_code_input').val(code || $(this).val() || ''); });

    $('#vendor_type').change(function() {
        let type = $(this).val();
        let $partySelect = $('#vendor_id');
        let selectedId = $partySelect.data('selected-id');
        $('#party_code_input').val('');
        $partySelect.html('<option value="">Loading...</option>');
        if (!type) { $partySelect.html('<option value="">Select Type...</option>'); return; }
        let url = (['vendor','customer','walkin'].includes(type)) ? '{{ route("party.list") }}?type=' + type : '{{ url("get-accounts-by-head") }}/' + type;
        $.get(url, function(data) {
            $partySelect.html('<option value="">Select Party...</option>');
            let hasSelected = false;
            data.forEach(item => {
                let code = item.account_code || '';
                let sel = (item.id == selectedId) ? 'selected' : '';
                if(item.id == selectedId) hasSelected = true;
                $partySelect.append(`<option value="${item.id}" data-code="${code}" ${sel}>${item.text || item.title}</option>`);
            });
            if (hasSelected && selectedId) {
                $partySelect.val(selectedId);
                let code = $partySelect.find('option:selected').attr('data-code'); 
                $('#party_code_input').val(code || selectedId);
            }
            $partySelect.trigger('change');
            $partySelect.data('selected-id', ''); // Clear so it only auto-selects on first load
        });
    }).trigger('change');

    // 🏦 Row Account Logic
    $(document).on('change', '.rowAccountHead', function() {
        let headId = $(this).val();
        let $row = $(this).closest('tr');
        let $subSelect = $row.find('.rowAccountSub');
        let selected = $subSelect.data('selected');
        $row.find('.account-id-lookup').val('');
        $subSelect.html('<option value="">Loading...</option>');
        if (headId) {
            $.get('{{ url("get-accounts-by-head") }}/' + headId, function(res) {
                $subSelect.html('<option value="">Select Account</option>');
                let hasSelected = false;
                res.forEach(acc => {
                    let sel = (acc.id == selected) ? 'selected' : '';
                    if (acc.id == selected) hasSelected = true;
                    $subSelect.append(`<option value="${acc.id}" data-code="${acc.account_code}" ${sel}>${acc.title}</option>`);
                });
                if (hasSelected && selected) { 
                    $subSelect.val(String(selected));
                    let code = $subSelect.find('option:selected').attr('data-code'); 
                    $row.find('.account-id-lookup').val(code || selected); 
                }
                $subSelect.trigger('change');
                $subSelect.data('selected', ''); // Clear so it only auto-selects on first load
            });
        }
    });
    $('.rowAccountHead').each(function() { if ($(this).val()) $(this).trigger('change'); });

    $(document).on('blur keydown', '.account-id-lookup', function(e) {
        if(e.type === 'keydown' && e.which != 13 && e.which != 9) return;
        let idVal = $(this).val();
        let $row = $(this).closest('tr');
        let $select = $row.find('.rowAccountSub');
        if(idVal) {
            let optById = $select.find('option').filter(function() { return $(this).val() == idVal; });
            if(optById.length > 0) { $select.val(idVal).trigger('change'); return; }
            let optByCode = $select.find('option').filter(function() { return $(this).attr('data-code') == idVal; });
            if(optByCode.length > 0) { $select.val(optByCode.val()).trigger('change'); }
        }
    });
    $(document).on('change', '.rowAccountSub', function() { let code = $(this).find('option:selected').attr('data-code'); $(this).closest('tr').find('.account-id-lookup').val(code || $(this).val() || ''); });

    // ➕ Table Math & Action
    function calculateTotals() {
        let total = 0;
        $('.row-amount').each(function() { total += parseFloat($(this).val()) || 0; });
        $('#totalAmount').val(total.toLocaleString('en-US', {minimumFractionDigits: 2}));
    }
    $(document).on('input', '.row-amount', calculateTotals);

    $(document).on('keydown', '.row-amount', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $('#btnAddRow').click();
            $('#voucherTable tbody tr').last().find('.narrationSelect').focus();
        }
    });

    $('#btnAddRow').click(function() {
        let newRow = `<tr>
            <td><select name="narration_id[]" class="form-select form-select-sm narrationSelect"><option value="">Narration...</option>@foreach($narrationsList as $lid => $lname)<option value="{{ $lid }}">{{ $lname }}</option>@endforeach</select></td>
            <td><select name="row_account_head[]" class="form-select form-select-sm rowAccountHead select2"><option value="">Select Head...</option>@foreach($AccountHeads as $head)<option value="{{ $head->id }}">{{ $head->name }}</option>@endforeach</select></td>
            <td><input type="text" class="form-control form-control-sm text-center fw-bold text-danger account-id-lookup" placeholder="Code"></td>
            <td><select name="row_account_id[]" class="form-select form-select-sm rowAccountSub select2"><option value="">Select Account...</option></select></td>
            <td><input type="number" step="0.01" name="amount[]" class="form-control form-control-sm text-end fw-bold row-amount" placeholder="0.00"></td>
            <td class="text-center"><button type="button" class="btn text-danger btn-xs removeRow p-0"><i class="fa fa-trash-o fs-6"></i></button></td>
        </tr>`;
        $('#voucherTable tbody').append(newRow);
        initSelectors($('#voucherTable tbody tr').last());
    });
    $(document).on('click', '.removeRow', function() { if ($('#voucherTable tbody tr').length > 1) { $(this).closest('tr').remove(); calculateTotals(); } });

    // 💾 Storage Logic
    function showAlert(msg, type='info') {
        $('#alertBox').removeClass('d-none alert-success alert-danger alert-info').addClass('alert-' + type).text(msg);
        setTimeout(() => $('#alertBox').addClass('d-none'), 5000);
    }

    $('#saveDraftBtn').click(function() {
        VoucherFieldValidation.clearErrors($('#expenseForm'));
        
        let formData = $('#expenseForm').serializeArray();
        formData.forEach(item => {
            if (item.name === 'total_amount' || item.name === 'amount[]') {
                item.value = item.value.replace(/,/g, '');
            }
        });
        
        $.post('{{ route("Expense.vochers.ajax-save") }}', $.param(formData))
            .done(function(res) {
            if(res.success) {
                $('#receipt_id').val(res.id); $('#evidBadgeText').text(res.evid);
                showAlert('Draft saved successfully!', 'success');
                $('#expenseForm').addClass('form-locked');
                $('#editInvoiceBtn, #postBtn').prop('disabled', false);
                $('#realPrintBtn').attr('href', '{{ route("ExpenseVoucher.print", ":id") }}'.replace(':id', res.id)).removeClass('pe-none opacity-50');
                $('#deleteBtn').prop('disabled', false);
            }
        })
            .fail(function(xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                VoucherFieldValidation.applyErrors($('#expenseForm'), xhr.responseJSON.errors);
            } else {
                showAlert((xhr.responseJSON && xhr.responseJSON.message) || 'An error occurred while saving.', 'danger');
            }
        });
    });

    $('#postBtn').click(function() {
        VoucherFieldValidation.clearErrors($('#expenseForm'));
        let formData = $('#expenseForm').serializeArray();
        formData.forEach(item => {
            if (item.name === 'total_amount' || item.name === 'amount[]') {
                item.value = item.value.replace(/,/g, '');
            }
        });
        $.post('{{ route("Expense.vochers.ajax-save") }}', $.param(formData))
            .done(function(res) {
                if (!res.success) return;
                let id = res.id || $('#receipt_id').val();
                if (!id) return;
                let form = $('<form>', {action: '{{ route("Expense.vochers.post", ":id") }}'.replace(':id', id), method: 'POST'})
                    .append($('<input>', {type: 'hidden', name: '_token', value: '{{ csrf_token() }}'}));
                $('body').append(form); form.submit();
            })
            .fail(function(xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    VoucherFieldValidation.applyErrors($('#expenseForm'), xhr.responseJSON.errors);
                }
            });
    });

    $('#unpostBtn').click(function() {
        Swal.fire({ title: 'Unpost this voucher?', icon: 'warning', showCancelButton: true }).then((res) => {
            if(res.isConfirmed) {
                let id = $('#receipt_id').val();
                let form = $('<form>', {action: '{{ route("Expense.vochers.unpost", ":id") }}'.replace(':id', id), method: 'POST'}).append($('<input>', {type: 'hidden', name: '_token', value: '{{ csrf_token() }}'}));
                $('body').append(form); form.submit();
            }
        });
    });

    $('#editInvoiceBtn').on('click', function() {
        if ($(this).prop('disabled')) return;
        $('#expenseForm').removeClass('form-locked');
        $(this).prop('disabled', true);
        $('#postBtn').prop('disabled', true);
        $('#saveDraftBtn').prop('disabled', false);
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

    calculateTotals();
});

function handleCancel() {
    let id = $('#receipt_id').val();
    if (!id) { window.location.href = "{{ route('all-expense-vochers') }}"; }
    else {
        Swal.fire({ title: 'Delete this draft?', icon: 'warning', showCancelButton: true }).then((res) => {
            if(res.isConfirmed) {
                let form = $('<form>', {action: '{{ route("Expense.vochers.cancel", ":id") }}'.replace(':id', id), method: 'POST'})
                    .append($('<input>', {type: 'hidden', name: '_token', value: '{{ csrf_token() }}'}))
                    .append($('<input>', {type: 'hidden', name: '_method', value: 'DELETE'}));
                $('body').append(form); form.submit();
            }
        });
    }
}
</script>
@endsection