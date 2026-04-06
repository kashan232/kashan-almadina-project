@extends('admin_panel.layout.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container--default .select2-selection--single {
        height: 31px !important;
        padding: 2px 5px !important;
        font-size: 0.85rem !important;
        border: 1px solid #dee2e6 !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 25px !important; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 30px !important; }

    .main-container { font-size: .85rem; max-width: 1400px; }
    .form-control, .form-select, .btn { font-size: .85rem; padding: .4rem .6rem; }

    .table thead th {
        background: #f8f9fa !important;
        text-align: center;
        font-size: 0.75rem;
        padding: 8px !important;
        white-space: nowrap;
    }
    .table td { vertical-align: middle; padding: 4px !important; }

    .posted-watermark {
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg);
        font-size: 8rem; color: rgba(220, 53, 69, 0.1); font-weight: 900; text-transform: uppercase;
        pointer-events: none; z-index: 1000; display: none; border: 10px solid rgba(220, 53, 69, 0.1); padding: 20px 50px; border-radius: 20px;
    }

    .form-locked {
        pointer-events: none !important;
    }
    .form-locked #editBtn, .form-locked #previewPrintBtn, .form-locked #listBtn, .form-locked #newBtn {
        pointer-events: auto !important;
    }
    .form-locked input, .form-locked select, .form-locked textarea, .form-locked .select2-selection {
        background-color: #f8f9fa !important;
    }
</style>

<div class="container-fluid py-4">
    <div class="main-container bg-white border shadow-sm mx-auto p-4 rounded-3 position-relative" style="max-width: 98%;">
        
        <div id="alertBox" class="alert d-none mb-3" role="alert"></div>

        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4 bg-light p-3 rounded shadow-sm border">
            <div class="d-flex align-items-center gap-3">
                <h5 class="page-title mb-0 fw-bold text-primary"><i class="fa fa-file-text-o me-2"></i>Payment Voucher</h5>
                <span id="statusBadge" class="badge {{ $receipt->status == 'posted' ? 'bg-success' : 'bg-warning text-dark' }} px-3 py-2 rounded-pill shadow-sm">
                    <i class="fa {{ $receipt->status == 'posted' ? 'fa-check-circle' : 'fa-pencil' }} me-1"></i> 
                    {{ ucfirst($receipt->status) }}
                </span>
                <span class="badge bg-primary px-3 py-2 rounded-pill shadow-sm">
                    <i class="fa fa-tag me-1"></i> <span id="pvidBadgeText">{{ $receipt->pvid }}</span>
                </span>
            </div>

            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('all-Payment-vochers') }}" id="listBtn" class="btn btn-sm btn-outline-secondary rounded-pill px-3 shadow-sm">
                    <i class="fa fa-list me-1"></i> View All
                </a>
            </div>
        </div>

        <form id="paymentForm" autocomplete="off" class="{{ ($receipt->id || $receipt->status == 'posted') ? 'form-locked' : '' }}">
            @csrf
            <input type="hidden" name="id" id="receipt_id" value="{{ $receipt->id }}">

            <!-- Header Grid -->
            <div class="row g-3 mb-4">
                <div class="col-md-2">
                    <div class="card border-0 bg-light p-2 shadow-sm h-100">
                        <label class="form-label text-muted small fw-bold mb-1">PVID</label>
                        <input type="text" class="form-control form-control-sm border-0 fw-bold text-primary bg-transparent" value="{{ $receipt->pvid }}" readonly>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card border-0 bg-light p-2 shadow-sm h-100">
                        <label class="form-label text-muted small fw-bold mb-1">Payment Date</label>
                        <input type="date" name="receipt_date" class="form-control form-control-sm" value="{{ $receipt->receipt_date ?? now()->toDateString() }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card border-0 bg-light p-2 shadow-sm h-100">
                        <label class="form-label text-muted small fw-bold mb-1">Entry Date</label>
                        <input type="date" name="entry_date" class="form-control form-control-sm bg-transparent" value="{{ $receipt->entry_date ?? now()->toDateString() }}" readonly>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-light p-2 shadow-sm h-100">
                        <label class="form-label text-muted small fw-bold mb-1">Account Head <span class="text-danger">*</span></label>
                        <select name="account_head" id="account_head" class="form-select form-select-sm">
                            <option value="">Select Head...</option>
                            @foreach($AccountHeads as $head)
                            <option value="{{ $head->id }}" {{ ($receipt->row_account_head == $head->id) ? 'selected' : '' }}>{{ $head->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-light p-2 shadow-sm h-100">
                        <div class="row g-1">
                            <div class="col-4 text-center">
                                <label class="form-label text-muted small fw-bold mb-1">Code <span class="text-danger">*</span></label>
                                <input type="text" id="account_code_input" class="form-control form-control-sm border-danger fw-bold text-danger text-center" placeholder="Code">
                            </div>
                            <div class="col-8">
                                <label class="form-label text-muted small fw-bold mb-1">Account <span class="text-danger">*</span></label>
                                <select name="account_id" id="account_id" class="form-select form-select-sm" data-selected="{{ $receipt->row_account_id }}">
                                    <option value="">Select Account...</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="card border-0 bg-light p-2 shadow-sm h-100">
                        <label class="form-label text-muted small fw-bold mb-1">Remarks</label>
                        <input type="text" name="remarks" class="form-control form-control-sm" placeholder="Enter general remarks..." value="{{ $receipt->remarks }}">
                    </div>
                </div>
            </div>

            <!-- Table Section -->
            <div class="card border shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fa fa-list-ul me-2"></i>Payment Details</h6>
                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" id="btnAddRow" {{ $receipt->status == 'posted' ? 'disabled' : '' }}>
                        <i class="fa fa-plus me-1"></i> Add Row
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="voucherTable">
                            <thead>
                                <tr>
                                    <th style="width: 15%;">Narration</th>
                                    <th style="width: 10%;">Ref#</th>
                                    <th style="width: 12%;">Party Type</th>
                                    <th style="width: 10%;">Code / ID</th>
                                    <th style="width: 18%;">Party Name</th>
                                    <th style="width: 8%;">Discount</th>
                                    <th style="width: 7%;">KG</th>
                                    <th style="width: 8%;">Rate</th>
                                    <th style="width: 10%;">Amount</th>
                                    <th style="width: 2%;">Act</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $nIds = json_decode($receipt->narration_id, true) ?? [null];
                                    $refs = json_decode($receipt->reference_no, true) ?? [];
                                    $types = json_decode($receipt->type, true) ?? [];
                                    $parties = json_decode($receipt->party_id, true) ?? [];
                                    $discounts = json_decode($receipt->discount_value, true) ?? [];
                                    $kgs = json_decode($receipt->kg, true) ?? [];
                                    $rates = json_decode($receipt->rate, true) ?? [];
                                    $amounts = json_decode($receipt->amount, true) ?? [];
                                @endphp

                                @foreach($nIds as $index => $nId)
                                <tr>
                                    <td>
                                        <select name="narration_id[]" class="form-select narrationSelect">
                                            <option value="">Narration...</option>
                                            @foreach($narrations as $id => $name)
                                            <option value="{{ $id }}" {{ $nId == $id ? 'selected' : '' }}>{{ $name }}</option>
                                            @endforeach
                                            @if($nId && !isset($narrations[$nId]))
                                                <option value="{{ $nId }}" selected>{{ $nId }}</option>
                                            @endif
                                        </select>
                                    </td>
                                    <td><input name="reference_no[]" type="text" class="form-control form-control-sm" value="{{ $refs[$index] ?? '' }}"></td>
                                    <td>
                                        <select name="party_type[]" class="form-select form-select-sm rowPartyType">
                                            <option value="">Type...</option>
                                            <option value="vendor" {{ ($types[$index] ?? '') == 'vendor' ? 'selected' : '' }}>Vendor</option>
                                            <option value="customer" {{ ($types[$index] ?? '') == 'customer' ? 'selected' : '' }}>Customer</option>
                                            @foreach($AccountHeads as $head)
                                            <option value="{{ $head->id }}" {{ ($types[$index] ?? '') == $head->id ? 'selected' : '' }}>{{ $head->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" name="row_party_id_input[]" class="form-control form-control-sm text-center rowPartyCode" placeholder="Code/ID" value="{{ $parties[$index] ?? '' }}"></td>
                                    <td>
                                        <select name="row_party_id[]" class="form-select form-select-sm rowPartySelect" data-selected="{{ $parties[$index] ?? '' }}">
                                            <option value="">Select Party...</option>
                                        </select>
                                    </td>
                                    <td><input name="discount_value[]" type="number" step="any" class="form-control form-control-sm text-end discount" value="{{ $discounts[$index] ?? 0 }}"></td>
                                    <td><input name="kg[]" type="number" step="any" class="form-control form-control-sm text-center kg" value="{{ $kgs[$index] ?? '' }}"></td>
                                    <td><input name="rate[]" type="number" step="any" class="form-control form-control-sm text-end rate" value="{{ $rates[$index] ?? '' }}"></td>
                                    <td><input name="amount[]" type="text" class="form-control form-control-sm text-end fw-bold amount" value="{{ $amounts[$index] ?? '' }}"></td>
                                    <td class="text-center">
                                        <button type="button" class="btn text-danger btn-xs removeRow"><i class="fa fa-trash"></i></button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light fw-bold text-end">
                                <tr>
                                    <td colspan="8" class="py-3">GRAND TOTAL:</td>
                                    <td class="bg-primary bg-opacity-10 py-3">
                                        <input type="text" id="totalAmount" name="total_amount" class="form-control form-control-sm text-end fw-bold border-0 bg-transparent text-primary fs-6" readonly value="{{ $receipt->total_amount ?? '0.00' }}">
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Footer Buttons -->
            <div class="d-flex gap-2 justify-content-end align-items-center mt-4 pt-3 border-top">
                @if($receipt->status == 'draft')
                <button type="button" id="saveDraftBtn" class="btn btn-sm btn-warning text-dark fw-bold rounded-pill px-4 shadow-sm">
                    <i class="fa fa-save me-1"></i> Save Draft <kbd class="ms-1 small opacity-75">Ctrl+S</kbd>
                </button>
                <button type="button" id="postBtn" class="btn btn-sm btn-primary text-dark fw-bold rounded-pill px-4 shadow-sm">
                    <i class="fa fa-send me-1"></i> Save Post <kbd class="ms-1 small opacity-75">Ctrl+&#8629;</kbd>
                </button>
                <button type="button" id="editBtn" class="btn btn-sm btn-warning text-dark fw-bold rounded-pill px-4 shadow-sm" style="{{ $receipt->id ? 'display:block' : 'display:none' }}">
                    <i class="fa fa-pencil me-1"></i> Edit <kbd class="ms-1 small opacity-75">Ctrl+E</kbd>
                </button>
                @endif
                
                @if($receipt->status == 'posted')
                <button type="button" id="unpostBtn" class="btn btn-sm btn-danger text-dark fw-bold rounded-pill px-4 shadow-sm">
                    <i class="fa fa-undo me-1"></i> Unpost
                </button>
                @endif

                <a href="{{ $receipt->id ? route('PaymentVoucher.print', $receipt->id) : 'javascript:void(0)' }}" id="previewPrintBtn" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill px-4 shadow-sm {{ !$receipt->id ? 'disabled' : '' }}">
                    <i class="fa fa-print me-1"></i> Print Preview <kbd class="ms-1 small opacity-75">Ctrl+P</kbd>
                </a>

                <a href="{{ route('Payment-vochers') }}" class="btn btn-sm btn-info text-dark fw-bold rounded-pill px-4 shadow-sm">
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
        $container.find('.rowPartySelect').select2({ placeholder: "Select Party...", allowClear: true, width: '100%' });
    }
    initSelectors();

    // 🏦 Account Logic
    function syncAccountIdToCodeField() {
        let val = $('#account_code_input').val();
        if (val) {
            // Find by primary ID
            let optById = $('#account_id option').filter(function() { return $(this).val() == val; });
            if (optById.length > 0) {
                $('#account_id').val(val).trigger('change');
                return;
            }
            // Find by Account Code
            let optByCode = $('#account_id option').filter(function() { return $(this).attr('data-code') == val; });
            if (optByCode.length > 0) {
                $('#account_id').val(optByCode.val()).trigger('change');
            }
        }
    }

    $('#account_code_input').on('keydown', function(e) { 
        if(e.which == 13 || e.which == 9) {
            if(e.which == 13) e.preventDefault();
            syncAccountIdToCodeField();
        }
    });
    $('#account_code_input').on('blur', function() { 
        syncAccountIdToCodeField();
    });

    $('#account_id').on('change', function() {
        let code = $(this).find('option:selected').attr('data-code');
        $('#account_code_input').val(code || $(this).val() || '');
    });

    $('#account_head').on('change', function() {
        let id = $(this).val();
        let $sub = $('#account_id');
        let selected = $sub.data('selected');
        
        // 🔄 Reset Code and Sub-account
        $('#account_code_input').val('');
        $sub.empty().append('<option value="">Loading...</option>');

        if(id) {
            $.get('{{ url("get-accounts-by-head") }}/' + id, res => {
                $sub.empty().append('<option value="">Select Account...</option>');
                res.forEach(a => $sub.append(`<option value="${a.id}" data-code="${a.account_code}" ${a.id == selected ? 'selected' : ''}>${a.title} (${a.account_code})</option>`));
                if(selected) {
                    let code = $sub.find('option:selected').attr('data-code');
                    $('#account_code_input').val(code || selected);
                }
            });
        }
    }).trigger('change');

    // 👤 Party Row Logic
    $(document).on('change', '.rowPartyType', function() {
        let type = $(this).val();
        let $row = $(this).closest('tr');
        let $select = $row.find('.rowPartySelect');
        let selected = $select.data('selected');

        // 🔄 Reset Code and Select
        $row.find('.rowPartyCode').val('');
        $select.empty().append('<option value="">Loading...</option>');

        if(type) {
            let url = (['vendor','customer'].includes(type)) ? '{{ route("party.list") }}?type=' + type : '{{ url("get-accounts-by-head") }}/' + type;
            $.get(url, res => {
                $select.empty().append('<option value="">Select Party...</option>');
                res.forEach(i => {
                    let code = i.account_code || '';
                    $select.append(`<option value="${i.id}" data-code="${code}" ${i.id == selected ? 'selected' : ''}>${i.text || i.title} ${code ? '('+code+')' : ''}</option>`);
                });
                if(selected) {
                    let code = $select.find('option:selected').attr('data-code');
                    $row.find('.rowPartyCode').val(code || selected);
                }
            });
        } else { $select.empty().append('<option value="">Select Party...</option>'); }
    });
    $('.rowPartyType').trigger('change');

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
            // Try ID
            let optById = $sel.find('option').filter(function() { return $(this).val() == val; });
            if(optById.length > 0) { $sel.val(val).trigger('change'); return; }
            // Try Code
            let optByCode = $sel.find('option').filter(function() { return $(this).attr('data-code') == val; });
            if(optByCode.length > 0) { $sel.val(optByCode.val()).trigger('change'); }
        }
    });

    // ➕ Table
    $('#btnAddRow').click(function() {
        let row = `<tr>
            <td><select name="narration_id[]" class="form-select narrationSelect"><option value="">Narration...</option>@foreach($narrations as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></td>
            <td><input name="reference_no[]" type="text" class="form-control form-control-sm"></td>
            <td><select name="party_type[]" class="form-select form-select-sm rowPartyType"><option value="">Type...</option><option value="vendor">Vendor</option><option value="customer">Customer</option>@foreach($AccountHeads as $head)<option value="{{ $head->id }}">{{ $head->name }}</option>@endforeach</select></td>
            <td><input type="text" name="row_party_id_input[]" class="form-control form-control-sm text-center rowPartyCode" placeholder="Code/ID"></td>
            <td><select name="row_party_id[]" class="form-select form-select-sm rowPartySelect"><option value="">Select Party...</option></select></td>
            <td><input name="discount_value[]" type="number" step="any" class="form-control form-control-sm text-end discount" value="0"></td>
            <td><input name="kg[]" type="number" step="any" class="form-control form-control-sm text-center kg"></td>
            <td><input name="rate[]" type="number" step="any" class="form-control form-control-sm text-end rate"></td>
            <td><input name="amount[]" type="text" class="form-control form-control-sm text-end fw-bold amount"></td>
            <td class="text-center"><button type="button" class="btn text-danger btn-xs removeRow"><i class="fa fa-trash"></i></button></td>
        </tr>`;
        $('#voucherTable tbody').append(row);
        let $new = $('#voucherTable tbody tr').last();
        initSelectors($new);
    });
    $(document).on('click', '.removeRow', function() { if($('#voucherTable tbody tr').length > 1) { $(this).closest('tr').remove(); calc(); } });

    // 🧮 Math
    function calc() {
        let t = 0;
        $('.amount').each(function() { t += parseFloat($(this).val()) || 0; });
        $('#totalAmount').val(t.toFixed(2));
    }
    $(document).on('input', '.kg, .rate, .discount, .amount', function() {
        let $r = $(this).closest('tr');
        let k = parseFloat($r.find('.kg').val()) || 0, rt = parseFloat($r.find('.rate').val()) || 0, d = parseFloat($r.find('.discount').val()) || 0;
        if(k > 0 && rt > 0) $r.find('.amount').val(((k * rt) - d).toFixed(2));
        calc();
    });

    // 💾 AJAX
    function showAlert(msg, type = 'success') {
        let $box = $('#alertBox');
        $box.removeClass('d-none alert-success alert-danger').addClass('alert-' + type).html(msg).fadeIn();
        setTimeout(() => $box.fadeOut(() => $box.addClass('d-none')), 3000);
    }

    function saveDraft(silent = false) {
        $('.ajax-valid-error').remove();
        return $.post('{{ route("Payment.vochers.ajax-save") }}', $('#paymentForm').serialize())
            .done(res => {
                if(res.success) {
                    $('#receipt_id').val(res.id); $('#pvidBadgeText').text(res.pvid);
                    $('#previewPrintBtn').attr('href', '{{ route("PaymentVoucher.print", ":id") }}'.replace(':id', res.id)).removeClass('disabled');
                    $('#deleteBtn, #editBtn').show();
                    $('#paymentForm').addClass('form-locked');
                    if(!silent) showAlert('<i class="fa fa-check-circle me-1"></i> Draft saved successfully! Form is now locked.');
                }
            })
            .fail(xhr => {
                if(xhr.status == 422) {
                    let errs = xhr.responseJSON.errors;
                    Object.keys(errs).forEach(k => {
                        let $el = (k == 'account_id') ? $('#account_id').next('.select2') : $(`[name="${k}"], #${k}`);
                        if(k.includes('row_party_id')) $el = $('[name="'+k+'"]').next('.select2');
                        $el.after(`<div class="text-danger small fw-bold ajax-valid-error mb-1"><i class="fa fa-exclamation-circle"></i> ${errs[k][0]}</div>`);
                    });
                    showAlert('<i class="fa fa-exclamation-triangle me-1"></i> Please fix validation errors.', 'danger');
                } else {
                    showAlert('<i class="fa fa-times-circle me-1"></i> An error occurred while saving.', 'danger');
                }
            });
    }

    $('#saveDraftBtn').click(() => saveDraft());
    $('#editBtn').click(function() { $('#paymentForm').removeClass('form-locked'); $(this).hide(); });
    $('#postBtn').click(function() {
        if(!confirm('Post this voucher?')) return;
        saveDraft(true).done(res => {
            if(res.success) {
                let f = $('<form>', {action: '{{ route("Payment.vochers.post", ":id") }}'.replace(':id', res.id), method: 'POST'});
                f.append($('<input>', {type: 'hidden', name: '_token', value: '{{ csrf_token() }}'}));
                $('body').append(f); f.submit();
            }
        });
    });
    $('#unpostBtn').click(function() {
        if(!confirm('Unpost?')) return;
        let f = $('<form>', {action: '{{ route("Payment.vochers.unpost", ":id") }}'.replace(':id', $('#receipt_id').val()), method: 'POST'});
        f.append($('<input>', {type: 'hidden', name: '_token', value: '{{ csrf_token() }}'}));
        $('body').append(f); f.submit();
    });
    $('#deleteBtn').click(function() {
        if(!confirm('Delete?')) return;
        let f = $('<form>', {action: '{{ route("Payment.vochers.cancel", ":id") }}'.replace(':id', $('#receipt_id').val()), method: 'POST'});
        f.append($('<input>', {type: 'hidden', name: '_token', value: '{{ csrf_token() }}'}));
        f.append($('<input>', {type: 'hidden', name: '_method', value: 'DELETE'}));
        $('body').append(f); f.submit();
    });

    $(document).on('keydown', e => {
        if(e.ctrlKey && e.which == 83) { e.preventDefault(); $('#saveDraftBtn').click(); }
        if(e.ctrlKey && e.which == 13) { e.preventDefault(); $('#postBtn').click(); }
        if(e.ctrlKey && e.which == 69) { e.preventDefault(); $('#editBtn').click(); }
        if(e.ctrlKey && e.which == 80) { if(!$('#previewPrintBtn').hasClass('disabled')) window.open($('#previewPrintBtn').attr('href'), '_blank'); e.preventDefault(); }
        if(e.altKey && e.which == 65) { e.preventDefault(); $('#btnAddRow').click(); }
    });
});
</script>
@endsection