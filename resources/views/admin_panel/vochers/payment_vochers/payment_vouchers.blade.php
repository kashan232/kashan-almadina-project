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
                                <h6 class="mb-0 fw-bold text-dark"><i class="fa fa-file-text-o me-2 text-primary"></i>Payment Voucher</h6>
                            </div>
                            <span id="statusBadge" class="badge {{ $receipt->status == 'posted' ? 'bg-success' : 'bg-warning text-dark' }} rounded-pill px-3 py-1" style="font-size: 10px;">
                                {{ strtoupper($receipt->status ?: 'DRAFT') }}
                            </span>
                            <span class="badge bg-light text-primary border rounded-pill px-3 py-1" style="font-size: 10px;">
                                <i class="fa fa-hashtag me-1"></i> <span id="pvidBadgeText">{{ $receipt->id ? $receipt->pvid : 'Auto-Generated' }}</span>
                            </span>
                        </div>
                        <div class="d-flex gap-1">
                            <a href="{{ route('all-Payment-vochers') }}" id="listBtn" class="btn btn-outline-secondary btn-sm rounded-pill px-3" style="font-size: 11px;">
                                <i class="fa fa-list me-1"></i> View Registry
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <form id="paymentForm" autocomplete="off" class="{{ ($receipt->id && $receipt->status == 'posted') ? 'form-locked' : '' }}">
                @csrf
                <input type="hidden" name="id" id="receipt_id" value="{{ $receipt->id }}">

                <!-- Voucher Header Fields -->
                <div class="card form-card mb-2">
                    <div class="card-body p-2">
                        <div class="row g-2">
                            <div class="col-md-2">
                                <label class="form-label">Entry Date</label>
                                <input type="date" name="entry_date" class="form-control form-control-sm" value="{{ $receipt->entry_date ?? now()->toDateString() }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Entry Time</label>
                                <input type="time" name="entry_time" class="form-control form-control-sm" value="{{ $receipt->entry_time ?? date('H:i') }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Payment Date</label>
                                <input type="date" name="receipt_date" class="form-control form-control-sm" value="{{ $receipt->receipt_date ?? now()->toDateString() }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Party Type <span class="text-danger">*</span></label>
                                <select name="party_type" id="party_type" class="form-select form-select-sm select2">
                                    <option value="">Select Type...</option>
                                    <option value="vendor" {{ $receipt->type == 'vendor' ? 'selected' : '' }}>Vendor</option>
                                    <option value="customer" {{ $receipt->type == 'customer' ? 'selected' : '' }}>Customer</option>
                                    <option value="walkin" {{ $receipt->type == 'walkin' ? 'selected' : '' }}>Walkin Customer</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">ID/Code</label>
                                <input type="text" id="party_code_input" class="form-control form-control-sm text-center fw-bold text-primary" placeholder="Code" value="{{ $receipt->party_id }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Source Party Name <span class="text-danger">*</span></label>
                                <select name="party_id" id="party_id" class="form-select form-select-sm select2" data-selected="{{ $receipt->party_id }}">
                                    <option value="">Select Party...</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Voucher Rows Table -->
                <div class="card form-card mb-2">
                    <div class="card-header bg-white py-1 d-flex justify-content-between align-items-center border-bottom">
                        <span class="fw-bold text-muted small text-uppercase"><i class="fa fa-list-ul me-1"></i> Payment Details</span>
                        <button type="button" class="btn btn-primary btn-xs px-3 rounded-pill" id="btnAddRow" {{ $receipt->status == 'posted' ? 'disabled' : '' }}>
                            <i class="fa fa-plus me-1"></i> Add Line
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover table-bordered mb-0" id="voucherTable">
                                <thead>
                                    <tr>
                                        <th width="15%">Narration</th>
                                        <th width="7%">Ref#</th>
                                        <th width="14%">Account Head</th>
                                        <th width="5%" class="text-center">Code</th>
                                        <th width="16%">Destination Account</th>
                                        <th width="5%" class="text-center">KG</th>
                                        <th width="11%" class="text-end">Rate</th>
                                        <th width="24%" class="text-end">Amount</th>
                                        <th width="3%" class="text-center">Act</th>
                                     </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $nIds = json_decode($receipt->narration_id, true) ?? [null];
                                        $refs = json_decode($receipt->reference_no, true) ?? [];
                                        $rowHeads = json_decode($receipt->row_account_head, true) ?? [];
                                        $rowAccounts = json_decode($receipt->row_account_id, true) ?? [];
                                        $kgs = json_decode($receipt->kg, true) ?? [];
                                        $rates = json_decode($receipt->rate, true) ?? [];
                                        $amounts = json_decode($receipt->amount, true) ?? [];

                                        $dHeadsArr = json_decode($receipt->discount_head, true);
                                        $dAccsArr  = json_decode($receipt->discount_account_id, true);
                                        $dValsArr  = json_decode($receipt->discount_value, true);

                                        $discHeadVal = is_array($dHeadsArr) ? ($dHeadsArr[0] ?? '') : ($receipt->discount_head ?? '');
                                        $discAccVal  = is_array($dAccsArr)  ? ($dAccsArr[0] ?? '')  : ($receipt->discount_account_id ?? '');
                                        $discAmtVal  = is_array($dValsArr)  ? (float)($dValsArr[0] ?? 0) : (float)($receipt->discount_value ?? 0);
                                    @endphp
                                    @foreach($nIds as $index => $nId)
                                    <tr>
                                        <td>
                                            <select name="narration_id[]" class="form-select form-select-sm narrationSelect">
                                                <option value="">Narration...</option>
                                                @foreach($narrations as $id => $name)
                                                <option value="{{ $id }}" {{ $nId == $id ? 'selected' : '' }}>{{ $name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input name="reference_no[]" type="text" class="form-control form-control-sm" value="{{ $refs[$index] ?? '' }}" placeholder="Ref#"></td>
                                        <td>
                                            <select name="row_account_head[]" class="form-select form-select-sm rowAccountHead select2">
                                                <option value="">Select Head...</option>
                                                @foreach($AccountHeads as $head)
                                                <option value="{{ $head->id }}" {{ ($rowHeads[$index] ?? '') == $head->id ? 'selected' : '' }}>{{ $head->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="text" name="row_account_code[]" class="form-control form-control-sm text-center fw-bold text-danger rowAccountCode" placeholder="Code"></td>
                                        <td>
                                            <select name="row_account_id[]" class="form-select form-select-sm rowAccountSelect select2" data-selected="{{ $rowAccounts[$index] ?? '' }}">
                                                <option value="">Select Account...</option>
                                            </select>
                                        </td>
                                        <td><input name="kg[]" type="number" step="any" class="form-control form-control-sm text-center kg" value="{{ $kgs[$index] ?? '' }}"></td>
                                        <td><input name="rate[]" type="number" step="any" class="form-control form-control-sm text-end rate" value="{{ $rates[$index] ?? '' }}"></td>
                                        <td><input name="amount[]" type="text" class="form-control form-control-sm text-end fw-bold amount" value="{{ $amounts[$index] ?? '' }}"></td>
                                        <td class="text-center"><button type="button" class="btn btn-xs btn-outline-danger removeRow"><i class="fa fa-times"></i></button></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr class="fw-bold">
                                        <td colspan="5" class="text-end py-2 text-muted small">TOTALS</td>
                                        <td class="text-center py-1">
                                            <input type="text" id="totalKg" class="form-control form-control-sm text-center border-0 bg-transparent fw-bold text-dark fs-5 py-0" readonly value="0.00">
                                        </td>
                                        <td></td>
                                        <td class="text-end py-1">
                                            <input type="text" id="totalAmount" name="total_amount" class="form-control form-control-sm text-end border-0 bg-transparent fw-bold text-dark fs-5 py-0" readonly value="{{ $receipt->total_amount ?? '0.00' }}">
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Voucher Discount Section -->
                <div class="card form-card mb-2">
                    <div class="card-body p-2 bg-light-subtle">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-4">
                                <label class="form-label mb-1 fw-bold text-dark"><i class="fa fa-percent text-primary me-1"></i> Disc Head</label>
                                <select name="discount_head" id="discount_head" class="form-select form-select-sm select2">
                                    <option value="">Select Discount Head...</option>
                                    @foreach($AccountHeads as $head)
                                    <option value="{{ $head->id }}" {{ $discHeadVal == $head->id ? 'selected' : '' }}>{{ $head->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label mb-1 fw-bold text-dark"><i class="fa fa-university text-primary me-1"></i> Disc Sub Head (Account)</label>
                                <select name="discount_account_id" id="discount_account_id" class="form-select form-select-sm select2" data-selected="{{ $discAccVal }}">
                                    <option value="">Select Sub Head Account...</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label mb-1 fw-bold text-dark"><i class="fa fa-minus-circle text-danger me-1"></i> Disc. Amount</label>
                                <input name="discount_value" id="discount_value" type="number" step="any" class="form-control form-control-sm text-end fw-bold text-danger fs-6" value="{{ $discAmtVal > 0 ? $discAmtVal : 0 }}" placeholder="0.00">
                            </div>
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
                    'printRoute' => 'PaymentVoucher.print',
                    'listRoute' => 'all-Payment-vochers',
                    'newRoute' => 'Payment-vochers',
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
        $container.find('.rowAccountSelect').select2({ placeholder: "Select Account...", allowClear: true, width: '100%' });
        $container.find('.discountAccountSub').select2({ placeholder: "Sub Head...", allowClear: true, width: '100%' });
        $container.find('#party_id').select2({ placeholder: "Select Party...", allowClear: true, width: '100%' });
    }
    initSelectors();

    // 👤 Header Party Logic
    function syncPartyIdToCodeField() {
        let val = $('#party_code_input').val();
        if (val) {
            let optById = $('#party_id option').filter(function() { return $(this).val() == val; });
            if (optById.length > 0) { $('#party_id').val(val).trigger('change'); return; }
            let optByCode = $('#party_id option').filter(function() { return $(this).attr('data-code') == val; });
            if (optByCode.length > 0) { $('#party_id').val(optByCode.val()).trigger('change'); }
        }
    }

    $('#party_code_input').on('blur keydown', function(e) { 
        if(e.type === 'keydown' && e.which != 13 && e.which != 9) return;
        syncPartyIdToCodeField();
    });

    $('#party_id').on('change', function() {
        let code = $(this).find('option:selected').attr('data-code');
        $('#party_code_input').val(code || $(this).val() || '');
    });

    $('#party_type').on('change', function() {
        let id = $(this).val();
        let $sub = $('#party_id');
        let selected = $sub.data('selected');
        $('#party_code_input').val('');
        $sub.empty().append('<option value="">Loading...</option>');
        if(id) {
            let url = (['vendor','customer','walkin'].includes(id)) ? '{{ route("party.list") }}?type=' + id : '{{ url("get-accounts-by-head") }}/' + id;
            $.get(url, res => {
                $sub.empty().append('<option value="">Select Party...</option>');
                let hasSelected = false;
                res.forEach(i => {
                    let code = i.account_code || '';
                    let sel = (i.id == selected) ? 'selected' : '';
                    if (i.id == selected) hasSelected = true;
                    $sub.append(`<option value="${i.id}" data-code="${code}" ${sel}>${i.text || i.title}</option>`);
                });
                if (hasSelected && selected) { 
                    $sub.val(selected);
                    let code = $sub.find('option:selected').attr('data-code'); 
                    $('#party_code_input').val(code || selected); 
                }
                $sub.trigger('change');
                $sub.data('selected', ''); // Clear so it only auto-selects on first load
            });
        }
    }).trigger('change');

    // 🏦 Row Account Logic
    $(document).on('change', '.rowAccountHead', function() {
        let headId = $(this).val();
        let $row = $(this).closest('tr');
        let $select = $row.find('.rowAccountSelect');
        let selected = $select.data('selected');
        $row.find('.rowAccountCode').val('');
        $select.empty().append('<option value="">Loading...</option>');
        if(headId) {
            $.get('{{ url("get-accounts-by-head") }}/' + headId, res => {
                $select.empty().append('<option value="">Select Account...</option>');
                let hasSelected = false;
                res.forEach(a => {
                    let sel = (a.id == selected) ? 'selected' : '';
                    if (a.id == selected) hasSelected = true;
                    $select.append(`<option value="${a.id}" data-code="${a.account_code}" ${sel}>${a.title}</option>`);
                });
                if (hasSelected && selected) { 
                    $select.val(String(selected));
                    let code = $select.find('option:selected').attr('data-code'); 
                    $row.find('.rowAccountCode').val(code || selected); 
                }
                $select.trigger('change');
                $select.data('selected', ''); // Clear so it only auto-selects on first load
            });
        }
    });
    $('.rowAccountHead').trigger('change');

    // 🏷️ Voucher Level Discount Head & Sub Head Logic
    $(document).on('change', '#discount_head', function() {
        let headId = $(this).val();
        let $select = $('#discount_account_id');
        let selected = $select.data('selected');
        $select.empty().append('<option value="">Loading...</option>');
        if(headId) {
            $.get('{{ url("get-accounts-by-head") }}/' + headId, res => {
                $select.empty().append('<option value="">Select Sub Head Account...</option>');
                res.forEach(a => {
                    let sel = (a.id == selected) ? 'selected' : '';
                    $select.append(`<option value="${a.id}" ${sel}>${a.title}</option>`);
                });
                $select.data('selected', '');
                $select.trigger('change');
            });
        } else {
            $select.empty().append('<option value="">Select Sub Head Account...</option>').trigger('change');
        }
    });

    if ($('#discount_head').val()) {
        $('#discount_head').trigger('change');
    }

    $(document).on('change', '.rowAccountSelect', function() { let code = $(this).find('option:selected').attr('data-code'); $(this).closest('tr').find('.rowAccountCode').val(code || $(this).val() || ''); });

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

    // ➕ Table Actions
    $('#btnAddRow').click(function() {
        let row = `<tr>
            <td><select name="narration_id[]" class="form-select form-select-sm narrationSelect"><option value="">Narration...</option>@foreach($narrations as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></td>
            <td><input name="reference_no[]" type="text" class="form-control form-control-sm" placeholder="Ref#"></td>
            <td><select name="row_account_head[]" class="form-select form-select-sm rowAccountHead select2"><option value="">Select Head...</option>@foreach($AccountHeads as $head)<option value="{{ $head->id }}">{{ $head->name }}</option>@endforeach</select></td>
            <td><input type="text" name="row_account_code[]" class="form-control form-control-sm text-center fw-bold text-danger rowAccountCode" placeholder="Code"></td>
            <td><select name="row_account_id[]" class="form-select form-select-sm rowAccountSelect select2"><option value="">Select Account...</option></select></td>
            <td><input name="kg[]" type="number" step="any" class="form-control form-control-sm text-center kg"></td>
            <td><input name="rate[]" type="number" step="any" class="form-control form-control-sm text-end rate"></td>
            <td><input name="amount[]" type="text" class="form-control form-control-sm text-end fw-bold amount"></td>
            <td class="text-center"><button type="button" class="btn btn-xs btn-outline-danger removeRow"><i class="fa fa-times"></i></button></td>
        </tr>`;
        $('#voucherTable tbody').append(row);
        initSelectors($('#voucherTable tbody tr').last());
    });

    $(document).on('click', '.removeRow', function() { if($('#voucherTable tbody tr').length > 1) { $(this).closest('tr').remove(); calc(); } });

    function calc() {
        let t = 0, k = 0;
        $('.amount').each(function() { t += parseFloat($(this).val()) || 0; });
        $('.kg').each(function() { k += parseFloat($(this).val()) || 0; });
        $('#totalAmount').val(t.toFixed(2));
        $('#totalKg').val(k.toFixed(2));
    }

    $(document).on('input', '.kg, .rate, .amount, #discount_value', function() {
        let $r = $(this).closest('tr');
        let k = parseFloat($r.find('.kg').val()) || 0, rt = parseFloat($r.find('.rate').val()) || 0;
        if(k > 0 && rt > 0) $r.find('.amount').val((k * rt).toFixed(2));
        calc();
    });

    $(document).on('keydown', '.amount', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $('#btnAddRow').click();
            $('#voucherTable tbody tr').last().find('.narrationSelect').focus();
        }
    });

    // 💾 Storage Logic
    function showAlert(msg, type = 'success') {
        let $box = $('#alertBox');
        $box.removeClass('d-none alert-success alert-danger').addClass('alert-' + type).html(msg).fadeIn();
        setTimeout(() => $box.fadeOut(() => $box.addClass('d-none')), 3000);
    }

    function saveDraft(silent = false) {
        VoucherFieldValidation.clearErrors($('#paymentForm'));
        return $.post('{{ route("Payment.vochers.ajax-save") }}', $('#paymentForm').serialize())
            .done(res => {
                if(res.success) {
                    $('#receipt_id').val(res.id); $('#pvidBadgeText').text(res.pvid);
                    $('#paymentForm').addClass('form-locked');
                    $('#editInvoiceBtn, #postBtn').prop('disabled', false);
                    $('#realPrintBtn').attr('href', '{{ route("PaymentVoucher.print", ":id") }}'.replace(':id', res.id)).removeClass('pe-none opacity-50');
                    $('#deleteBtn').prop('disabled', false);
                    if(!silent) showAlert('<i class="fa fa-check-circle me-1"></i> Draft saved successfully!');
                }
            })
            .fail(xhr => {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    VoucherFieldValidation.applyErrors($('#paymentForm'), xhr.responseJSON.errors);
                } else {
                    showAlert((xhr.responseJSON && xhr.responseJSON.message) || 'Save failed.', 'danger');
                }
            });
    }

    $('#saveDraftBtn').click(() => saveDraft());
    $('#editInvoiceBtn').on('click', function() {
        if ($(this).prop('disabled')) return;
        $('#paymentForm').removeClass('form-locked');
        $(this).prop('disabled', true);
        $('#postBtn').prop('disabled', true);
        $('#saveDraftBtn').prop('disabled', false);
    });

    $('#postBtn').click(function() {
        Swal.fire({ title: 'Post Voucher?', icon: 'question', showCancelButton: true }).then((res) => {
            if(res.isConfirmed) {
                saveDraft(true).done(res => {
                    if(res && res.success) {
                        let f = $('<form>', {action: '{{ route("Payment.vochers.post", ":id") }}'.replace(':id', res.id), method: 'POST'}).append($('<input>', {type: 'hidden', name: '_token', value: '{{ csrf_token() }}'}));
                        $('body').append(f); f.submit();
                    }
                });
            }
        });
    });

    $('#unpostBtn').click(function() {
        Swal.fire({ title: 'Unpost this voucher?', icon: 'warning', showCancelButton: true }).then((res) => {
            if(res.isConfirmed) {
                let f = $('<form>', {action: '{{ route("Payment.vochers.unpost", ":id") }}'.replace(':id', $('#receipt_id').val()), method: 'POST'}).append($('<input>', {type: 'hidden', name: '_token', value: '{{ csrf_token() }}'}));
                $('body').append(f); f.submit();
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
    if (!id) { window.location.href = "{{ route('all-Payment-vochers') }}"; }
    else {
        Swal.fire({ title: 'Delete this draft?', icon: 'warning', showCancelButton: true }).then((res) => {
            if(res.isConfirmed) {
                let form = $('<form>', {action: '{{ route("Payment.vochers.cancel", ":id") }}'.replace(':id', id), method: 'POST'})
                    .append($('<input>', {type: 'hidden', name: '_token', value: '{{ csrf_token() }}'}))
                    .append($('<input>', {type: 'hidden', name: '_method', value: 'DELETE'}));
                $('body').append(form); form.submit();
            }
        });
    }
}
</script>
@endsection
