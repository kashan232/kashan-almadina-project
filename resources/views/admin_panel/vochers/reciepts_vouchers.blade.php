@extends('admin_panel.layout.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
@include('admin_panel.vochers._compact_voucher_styles', ['accentColor' => '#3b82f6'])
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
                                <h6 class="mb-0 fw-bold text-dark"><i class="fa fa-file-text-o me-2 text-primary"></i>Receipt Voucher</h6>
                            </div>
                            <span id="statusBadge" class="badge {{ $receipt->status == 'posted' ? 'bg-success' : 'bg-warning text-dark' }} rounded-pill px-3 py-1" style="font-size: 10px;">
                                {{ strtoupper($receipt->status ?: 'DRAFT') }}
                            </span>
                            <span class="badge bg-light text-primary border rounded-pill px-3 py-1" style="font-size: 10px;">
                                <i class="fa fa-hashtag me-1"></i> <span id="rvidBadgeText">{{ $receipt->id ? $receipt->rvid : 'Auto-Generated' }}</span>
                            </span>
                        </div>
                        <div class="d-flex gap-1">
                            <a href="{{ route('all-recepit-vochers') }}" id="listBtn" class="btn btn-outline-secondary btn-sm rounded-pill px-3" style="font-size: 11px;">
                                <i class="fa fa-list me-1"></i> View Registry
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <form id="receiptForm" autocomplete="off" class="{{ ($receipt->id && $receipt->status == 'posted') ? 'form-locked' : '' }}">
                @csrf
                <input type="hidden" name="id" id="receipt_id" value="{{ $receipt->id }}">

                <!-- Voucher Header Fields -->
                <div class="card form-card mb-2">
                    <div class="card-body p-2">
                        <div class="row g-2">
                            <div class="col-md-2">
                                <label class="form-label">Entry Date</label>
                                <input type="date" name="entry_date" id="entry_date" class="form-control form-control-sm" value="{{ $receipt->entry_date ?? now()->toDateString() }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Entry Time</label>
                                <input type="time" name="entry_time" id="entry_time" class="form-control form-control-sm" value="{{ $receipt->entry_time ?? date('H:i') }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Receipt Date</label>
                                <input type="date" name="receipt_date" id="receipt_date" class="form-control form-control-sm" value="{{ $receipt->receipt_date ?? now()->toDateString() }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Party Type <span class="text-danger">*</span></label>
                                <select name="vendor_type" id="vendor_type" class="form-select form-select-sm select2">
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
                        <span class="fw-bold text-muted small text-uppercase"><i class="fa fa-list-ul me-1"></i> Receipt Details</span>
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
                                        $narrations_json = json_decode($receipt->narration_id, true) ?? [null];
                                        $references = json_decode($receipt->reference_no, true) ?? [];
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
                                    @foreach($narrations_json as $index => $nId)
                                    <tr>
                                        <td>
                                            <select name="narration_id[]" class="form-select form-select-sm narrationSelect">
                                                <option value="">Narration...</option>
                                                @foreach($narrations as $id => $name)
                                                <option value="{{ $id }}" {{ $nId == $id ? 'selected' : '' }}>{{ $name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input name="reference_no[]" type="text" class="form-control form-control-sm" value="{{ $references[$index] ?? '' }}" placeholder="Ref#"></td>
                                        <td>
                                            <select name="row_account_head[]" class="form-select form-select-sm rowAccountHead select2">
                                                <option value="">Select Head...</option>
                                                @foreach($AccountHeads as $head)
                                                <option value="{{ $head->id }}" {{ ($rowHeads[$index] ?? '') == $head->id ? 'selected' : '' }}>{{ $head->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="text" class="form-control form-control-sm text-center fw-bold text-danger rowAccountCode" placeholder="Code"></td>
                                        <td>
                                            <select name="row_account_id[]" class="form-select form-select-sm rowAccountSub select2" data-selected="{{ $rowAccounts[$index] ?? '' }}">
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
                                            <input type="text" name="total_amount" id="totalAmount" class="form-control form-control-sm text-end border-0 bg-transparent fw-bold text-dark fs-5 py-0" readonly value="{{ $receipt->total_amount ?? '0.00' }}">
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
                                <input type="text" name="remarks" id="remarks" class="form-control form-control-sm" placeholder="Type additional voucher notes here..." value="{{ $receipt->remarks }}">
                            </div>
                        </div>
                    </div>
                </div>
                @include('admin_panel.vochers._standard_voucher_buttons', [
                    'printRoute' => 'receiptVoucher.print',
                    'listRoute' => 'all-recepit-vochers',
                    'newRoute' => 'recepit-vochers',
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
        $container.find('.discountAccountSub').select2({ placeholder: "Sub Head...", allowClear: true, width: '100%' });
        $container.find('#vendor_id').select2({ placeholder: "Select Party...", allowClear: true, width: '100%' });
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
    $('#vendor_id').on('change', function() { let val = $(this).val(); $('#party_code_input').val(val || ''); });

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
            data.forEach(item => {
                let sel = (item.id == selectedId) ? 'selected' : '';
                $partySelect.append(`<option value="${item.id}" data-code="${item.account_code || ''}" ${sel}>${item.text || item.title}</option>`);
            });
            $partySelect.trigger('change');
            if (selectedId) $('#party_code_input').val(selectedId);
        });
    }).trigger('change');

    // 🏦 Row Account Logic
    $(document).on('change', '.rowAccountHead', function() {
        let $row = $(this).closest('tr');
        let headId = $(this).val();
        let $subSelect = $row.find('.rowAccountSub');
        let selected = $subSelect.data('selected');
        $row.find('.rowAccountCode').val('');
        $subSelect.html('<option value="">Loading...</option>');
        if (headId) {
            $.get('{{ url("get-accounts-by-head") }}/' + headId, function(res) {
                $subSelect.html('<option value="">Select Account</option>');
                res.forEach(acc => {
                    let sel = (acc.id == selected) ? 'selected' : '';
                    $subSelect.append(`<option value="${acc.id}" data-code="${acc.account_code}" ${sel}>${acc.title}</option>`);
                });
                if(selected) { let code = $subSelect.find('option:selected').data('code'); $row.find('.rowAccountCode').val(code || ''); }
            });
        }
    });

    // Trigger on page load
    $('.rowAccountHead').each(function() { if ($(this).val()) $(this).trigger('change'); });
    $('.discountAccountHead').each(function() { if ($(this).val()) $(this).trigger('change'); });

    // 🏷️ Voucher Level Discount Head & Sub Head Logic
    $(document).on('change', '#discount_head', function() {
        let headId = $(this).val();
        let $subSelect = $('#discount_account_id');
        let selected = $subSelect.data('selected');
        $subSelect.html('<option value="">Loading...</option>');
        if (headId) {
            $.get('{{ url("get-accounts-by-head") }}/' + headId, function(res) {
                $subSelect.html('<option value="">Select Sub Head Account...</option>');
                res.forEach(acc => {
                    let sel = (acc.id == selected) ? 'selected' : '';
                    $subSelect.append(`<option value="${acc.id}" ${sel}>${acc.title}</option>`);
                });
                $subSelect.data('selected', '');
                $subSelect.trigger('change');
            });
        } else {
            $subSelect.html('<option value="">Select Sub Head Account...</option>').trigger('change');
        }
    });

    if ($('#discount_head').val()) {
        $('#discount_head').trigger('change');
    }

    $(document).on('change', '.rowAccountSub', function() { let code = $(this).find('option:selected').data('code'); $(this).closest('tr').find('.rowAccountCode').val(code || ''); });

    $(document).on('blur keydown', '.rowAccountCode', function(e) {
        if(e.type === 'keydown' && e.which != 13 && e.which != 9) return;
        let code = $(this).val();
        if (code) {
            let $row = $(this).closest('tr');
            let $subSelect = $row.find('.rowAccountSub');
            let $opt = $subSelect.find('option').filter(function() { return $(this).attr('data-code') == code; });
            if ($opt.length > 0) $subSelect.val($opt.val()).trigger('change');
        }
    });

    // ➕ Table Actions
    function calculateTotals() {
        let grandTotal = 0;
        let grandKg = 0;
        $('.amount').each(function() { grandTotal += parseFloat($(this).val()) || 0; });
        $('.kg').each(function() { grandKg += parseFloat($(this).val()) || 0; });

        $('#totalAmount').val(grandTotal.toFixed(2));
        $('#totalKg').val(grandKg.toFixed(2));
    }

    $(document).on('input', '.kg, .rate', function() {
        let $tr = $(this).closest('tr');
        let kg = parseFloat($tr.find('.kg').val()) || 0, rate = parseFloat($tr.find('.rate').val()) || 0;
        let $amount = $tr.find('.amount');
        
        if (kg > 0 || rate > 0) {
            let gross = (kg > 0) ? (kg * rate) : rate;
            $amount.val(Math.max(0, gross).toFixed(2));
        }
        
        calculateTotals();
    });

    $(document).on('input', '.amount, #discount_value', function() {
        calculateTotals();
    });

    $(document).on('keydown', '.amount', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $('#btnAddRow').click();
            $('#voucherTable tbody tr').last().find('.narrationSelect').focus();
        }
    });


    $('#btnAddRow').click(function() {
        let newRow = `<tr>
            <td><select name="narration_id[]" class="form-select form-select-sm narrationSelect"><option value="">Narration...</option>@foreach($narrations as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></td>
            <td><input name="reference_no[]" type="text" class="form-control form-control-sm" placeholder="Ref#"></td>
            <td><select name="row_account_head[]" class="form-select form-select-sm rowAccountHead select2"><option value="">Select Head...</option>@foreach($AccountHeads as $head) <option value="{{ $head->id }}">{{ $head->name }}</option>@endforeach</select></td>
            <td><input type="text" class="form-control form-control-sm text-center fw-bold text-danger rowAccountCode" placeholder="Code"></td>
            <td><select name="row_account_id[]" class="form-select form-select-sm rowAccountSub select2"><option value="">Select Account...</option></select></td>
            <td><input name="kg[]" type="number" step="any" class="form-control form-control-sm text-center kg"></td>
            <td><input name="rate[]" type="number" step="any" class="form-control form-control-sm text-end rate"></td>
            <td><input name="amount[]" type="text" class="form-control form-control-sm text-end fw-bold amount"></td>
            <td class="text-center"><button type="button" class="btn btn-xs btn-outline-danger removeRow"><i class="fa fa-times"></i></button></td>
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

    function saveDraft(silent = false) {
        VoucherFieldValidation.clearErrors($('#receiptForm'));
        return $.post('{{ route("recepit.vochers.ajax-save") }}', $('#receiptForm').serialize())
            .done(function(res) {
            if(res.success) {
                if (!$('#receipt_id').val()) { $('#receipt_id').val(res.id); $('#rvidBadgeText').text(res.rvid); }
                if(!silent) showAlert(res.message, 'success');
                $('#receiptForm').addClass('form-locked');
                $('#editInvoiceBtn, #postBtn').prop('disabled', false);
                $('#realPrintBtn').attr('href', '{{ route("receiptVoucher.print", ":id") }}'.replace(':id', res.id)).removeClass('pe-none opacity-50');
                $('#deleteBtn').prop('disabled', false);
            }
        })
            .fail(function(xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    VoucherFieldValidation.applyErrors($('#receiptForm'), xhr.responseJSON.errors);
                } else {
                    showAlert((xhr.responseJSON && xhr.responseJSON.message) || 'Save failed.', 'danger');
                }
            });
    }

    $('#saveDraftBtn').on('click', function(e) { e.preventDefault(); if (!$(this).prop('disabled')) saveDraft(); });
    $('#editInvoiceBtn').on('click', function() {
        if ($(this).prop('disabled')) return;
        $('#receiptForm').removeClass('form-locked');
        $(this).prop('disabled', true);
        $('#postBtn').prop('disabled', true);
        $('#saveDraftBtn').prop('disabled', false);
    });

    $('#postBtn').on('click', function() {
        if ($(this).prop('disabled')) return;
        let btn = $(this);
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i>');
        saveDraft(true).done(function(res) {
            if(res && res.success) {
                let postId = $('#receipt_id').val() || res.id;
                let form = $('<form>', {action: '{{ route("recepit.vochers.post", ":id") }}'.replace(':id', postId), method: 'POST'})
                    .append($('<input>', {type: 'hidden', name: '_token', value: '{{ csrf_token() }}'}));
                $('body').append(form); form.submit();
            } else {
                btn.prop('disabled', false).html('<u>P</u>ost <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+&crarr;</kbd>');
            }
        }).fail(function() {
            btn.prop('disabled', false).html('<u>P</u>ost <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+&crarr;</kbd>');
        });
    });

    $('#unpostBtn').on('click', function() {
        if (!confirm('Unpost this receipt voucher?')) return;
        let id = $('#receipt_id').val();
        let form = $('<form>', {action: '{{ route("recepit.vochers.unpost", ":id") }}'.replace(':id', id), method: 'POST'})
            .append($('<input>', {type: 'hidden', name: '_token', value: '{{ csrf_token() }}'}));
        $('body').append(form); form.submit();
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

    // Full Grid Navigation (Arrows Up/Down/Left/Right)
    $(document).on('keydown', '#voucherTable input', function(e) {
        if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].indexOf(e.key) === -1) return;
        
        var $this = $(this);
        var $row = $this.closest('tr');
        var $inputs = $row.find('input:visible:not([readonly])'); 
        var currentIndex = $inputs.index($this);

        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault(); // Stop page scroll
            var classList = $this.attr('class').split(' ');
            var ignore = ['form-control', 'form-control-sm', 'text-end', 'text-center', 'input-readonly', 'fw-bold', 'loading-indicator'];
            var specificClass = classList.find(c => ignore.indexOf(c) === -1);
            
            if (specificClass) {
                var $targetRow = (e.key === 'ArrowDown') ? $row.next('tr') : $row.prev('tr');
                var $target = $targetRow.find('.' + specificClass);
                if ($target.length) {
                    $target.focus().select();
                }
            }
        } else if (e.key === 'ArrowRight') {
            // Only jump if at the end of input
            if (this.selectionStart === this.value.length) {
                var $next = $inputs.eq(currentIndex + 1);
                if ($next.length) {
                    e.preventDefault();
                    $next.focus().select();
                }
            }
        } else if (e.key === 'ArrowLeft') {
            // Only jump if at the start of input
            if (this.selectionStart === 0) {
                var $prev = $inputs.eq(currentIndex - 1);
                if ($prev.length && currentIndex > 0) {
                    e.preventDefault();
                    $prev.focus().select();
                }
            }
        }
    });

    calculateTotals();
});

function handleCancel() {
    let id = $('#receipt_id').val();
    if (!id) { window.location.href = "{{ route('all-recepit-vochers') }}"; }
    else {
        Swal.fire({ title: 'Delete this draft?', icon: 'warning', showCancelButton: true }).then((res) => {
            if(res.isConfirmed) {
                let form = $('<form>', {action: '{{ route("recepit.vochers.cancel", ":id") }}'.replace(':id', id), method: 'POST'})
                    .append($('<input>', {type: 'hidden', name: '_token', value: '{{ csrf_token() }}'}))
                    .append($('<input>', {type: 'hidden', name: '_method', value: 'DELETE'}));
                $('body').append(form); form.submit();
            }
        });
    }
}
</script>
@endsection