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
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 25px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 30px !important;
    }

    .main-container {
        font-size: .85rem;
        max-width: 1400px;
    }

    .form-control, .form-select, .btn {
        font-size: .85rem;
        padding: .4rem .6rem;
    }

    .table thead th {
        background: #f8f9fa !important;
        text-align: center;
        font-size: 0.75rem;
        padding: 8px !important;
        white-space: nowrap;
    }

    .table td {
        vertical-align: middle;
        padding: 4px !important;
    }

    .input-readonly {
        background: #f9fbff !important;
    }

    /* Watermark for Posted State */
    .posted-watermark {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-30deg);
        font-size: 8rem;
        color: rgba(220, 53, 69, 0.1);
        font-weight: 900;
        text-transform: uppercase;
        pointer-events: none;
        z-index: 1000;
        display: none;
        border: 10px solid rgba(220, 53, 69, 0.1);
        padding: 20px 50px;
        border-radius: 20px;
    }

    .form-locked input, 
    .form-locked select,
    .form-locked textarea,
    .form-locked #btnAddRow,
    .form-locked .removeRow,
    .form-locked .select2-container,
    .form-locked .btn:not(#editBtn):not(#editBtnLocked):not(#previewPrintBtn):not(#newBtn):not(#listBtn) {
        pointer-events: none !important;
        opacity: 0.8 !important;
        background-color: #f8f9fa !important;
    }

    .ajax-valid-error { 
        color: #dc3545; 
        font-size: 0.75rem; 
        font-weight: 700; 
        margin-bottom: 2px;
        display: block;
    }
</style>

<div class="container-fluid py-4">
    <div class="main-container bg-white border shadow-sm mx-auto p-4 rounded-3 position-relative" style="max-width: 98%;">
        
        <div id="alertBox" class="alert d-none mb-3" role="alert"></div>

        @if(session('success'))
        <div class="alert alert-success shadow-sm mb-4 d-flex align-items-center" role="alert">
            <i class="fa fa-check-circle-o fs-4 me-2"></i>
            <div>
                <strong>Success!</strong> {{ session('success') }}
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger shadow-sm mb-4 d-flex align-items-center" role="alert">
            <i class="fa fa-exclamation-triangle fs-4 me-2"></i>
            <div>
                <strong>Error!</strong> {{ session('error') }}
            </div>
        </div>
        @endif

        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4 bg-light p-3 rounded shadow-sm border">
            <div class="d-flex align-items-center gap-3">
                <h5 class="page-title mb-0 fw-bold text-danger"><i class="fa fa-money me-2"></i>Expense Voucher</h5>
                <span id="statusBadge" class="badge {{ $receipt->status == 'posted' ? 'bg-success' : 'bg-warning text-dark' }} px-3 py-2 rounded-pill shadow-sm">
                    <i class="fa {{ $receipt->status == 'posted' ? 'fa-check-circle' : 'fa-pencil' }} me-1"></i> 
                    {{ strtoupper($receipt->status ?: 'DRAFT') }}
                </span>
                <span class="badge bg-primary px-3 py-2 rounded-pill shadow-sm">
                    <i class="fa fa-tag me-1"></i> <span id="evidBadgeText">{{ $receipt->evid ?: $nextRvid }}</span>
                </span>
            </div>

            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('all-expense-vochers') }}" id="listBtn" class="btn btn-sm btn-outline-secondary rounded-pill px-3 shadow-sm">
                    <i class="fa fa-list me-1"></i> View All
                </a>
            </div>
        </div>

        <form id="expenseForm" autocomplete="off" class="{{ ($receipt->id && $receipt->status == 'posted') ? 'form-locked' : '' }}">
            @csrf
            <input type="hidden" name="id" id="receipt_id" value="{{ $receipt->id }}">

            <!-- Upper Row: Metadata -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 bg-light p-2 shadow-sm h-100">
                        <label class="form-label text-muted small fw-bold mb-1">Entry Date</label>
                        <input type="date" name="entry_date" id="entry_date" class="form-control form-control-sm" value="{{ $receipt->entry_date ?: date('Y-m-d') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-light p-2 shadow-sm h-100">
                        <label class="form-label text-muted small fw-bold mb-1">Party Type <span class="text-danger">*</span></label>
                        <select name="vendor_type" id="vendor_type" class="form-select form-select-sm">
                            <option value="">Select Type...</option>
                            @foreach($AccountHeads as $head)
                                <option value="{{ $head->id }}" {{ $receipt->type == $head->id ? 'selected' : '' }}>{{ $head->name }}</option>
                            @endforeach
                            <option value="vendor" {{ $receipt->type == 'vendor' ? 'selected' : '' }}>Vendor</option>
                            <option value="customer" {{ $receipt->type == 'customer' ? 'selected' : '' }}>Customer</option>
                            <option value="walkin" {{ $receipt->type == 'walkin' ? 'selected' : '' }}>Walkin Customer</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 bg-light p-2 shadow-sm h-100">
                        <div class="row g-1">
                            <div class="col-4 text-center">
                                <label class="form-label text-muted small fw-bold mb-1">Code / ID <span class="text-danger">*</span></label>
                                <input type="text" id="party_code_input" class="form-control form-control-sm border-danger fw-bold text-danger text-center" placeholder="Code/ID" value="{{ $receipt->party_id }}">
                            </div>
                            <div class="col-8">
                                <label class="form-label text-muted small fw-bold mb-1">Party <span class="text-danger">*</span></label>
                                <select name="vendor_id" id="vendor_id" class="form-select form-select-sm" data-selected-id="{{ $receipt->party_id }}">
                                    <option value="">Select Party...</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lower Row: Details -->
            <div class="row g-3 mb-4">
                <div class="col-md-12">
                    <div class="card border-0 bg-light p-2 shadow-sm h-100">
                        <label class="form-label text-muted small fw-bold mb-1">Remarks</label>
                        <input type="text" name="remarks" id="remarks" class="form-control form-control-sm" placeholder="Enter optional remarks..." value="{{ $receipt->remarks }}">
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="card border shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fa fa-list-ul me-2"></i>Voucher Details</h6>
                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" id="btnAddRow">
                        <i class="fa fa-plus me-1"></i> Add Row
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered mb-0" id="voucherTable">
                            <thead>
                                <tr>
                                    <th width="25%">Narration</th>
                                    <th width="20%">Account Head</th>
                                    <th width="10%">Code</th>
                                    <th width="25%">Account</th>
                                    <th width="15%">Amount</th>
                                    <th width="5%">Act</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $narrs = json_decode($receipt->narration_id, true) ?? [''];
                                    $refs = json_decode($receipt->reference_no, true) ?? [''];
                                    $rowHeads = json_decode($receipt->row_account_head, true) ?? [''];
                                    $rowAccs = json_decode($receipt->row_account_id, true) ?? [''];
                                    $amounts = json_decode($receipt->amount, true) ?? [''];
                                @endphp

                                @foreach($narrs as $idx => $nId)
                                <tr>
                                    <td>
                                        <select name="narration_id[]" class="form-select select2-tags narrationSelect">
                                            <option value="">Select Narration...</option>
                                            @foreach($narrationsList as $lid => $lname)
                                                <option value="{{ $lid }}" {{ ($nId == $lid) ? 'selected' : '' }}>{{ $lname }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select name="row_account_head[]" class="form-select form-select-sm rowAccountHead">
                                            <option value="">Select Head...</option>
                                            @foreach($AccountHeads as $head)
                                                <option value="{{ $head->id }}" {{ ($rowHeads[$idx] ?? '') == $head->id ? 'selected' : '' }}>{{ $head->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm account-id-lookup" placeholder="Code" value="{{ $rowAccs[$idx] ?? '' }}">
                                    </td>
                                    <td>
                                        <select name="row_account_id[]" class="form-select form-select-sm rowAccountSub" data-selected="{{ $rowAccs[$idx] ?? '' }}">
                                            <option value="">Select Account...</option>
                                        </select>
                                    </td>
                                    <td><input type="number" step="0.01" name="amount[]" class="form-control form-control-sm text-end row-amount" value="{{ $amounts[$idx] ?? '' }}"></td>
                                    <td class="text-center"><button type="button" class="btn text-danger btn-xs removeRow"><i class="fa fa-trash"></i></button></td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr class="fw-bold">
                                    <td colspan="4" class="text-end py-3">GRAND TOTAL:</td>
                                    <td class="text-end py-3 p-2 bg-primary bg-opacity-10">
                                        <input type="text" name="total_amount" id="totalAmount" class="form-control form-control-sm text-end border-0 bg-transparent fw-bold fs-6 text-primary" readonly value="{{ $receipt->total_amount }}">
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex gap-2 mt-4 pt-4 border-top justify-content-end">
                @if($receipt->status != 'posted')
                <button type="button" id="saveDraftBtn" class="btn btn-sm btn-warning text-dark fw-bold rounded-pill px-4 shadow-sm">
                    <i class="fa fa-floppy-o me-1"></i> Save Draft
                    <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>
                </button>
                <button type="button" id="postBtn" class="btn btn-sm btn-primary text-dark fw-bold rounded-pill px-4 shadow-sm">
                    <i class="fa fa-send me-1"></i> Save Post
                    <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+&#8629;</kbd>
                </button>

                <button type="button" id="editBtn" class="btn btn-sm btn-warning text-dark fw-bold rounded-pill px-4 shadow-sm" style="{{ ($receipt->id && $receipt->status != 'posted') ? 'display:block' : 'display:none' }};">
                    <i class="fa fa-pencil me-1"></i> Edit
                    <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+E</kbd>
                </button>
                @endif
                
                @if($receipt->status == 'posted')
                    <button type="button" id="unpostBtn" class="btn btn-sm btn-outline-danger rounded-pill px-4 shadow-sm">
                        <i class="fa fa-undo me-1"></i> Unpost
                    </button>
                    <button type="button" id="editBtnLocked" class="btn btn-sm btn-warning text-dark fw-bold rounded-pill px-4 shadow-sm">
                        <i class="fa fa-pencil me-1"></i> Edit
                        <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+E</kbd>
                    </button>
                @endif

                <a href="{{ $receipt->id ? route('ExpenseVoucher.print', $receipt->id) : 'javascript:void(0)' }}" 
                   id="previewPrintBtn" 
                   target="_blank" 
                   class="btn btn-sm btn-outline-dark rounded-pill px-4 shadow-sm {{ !$receipt->id ? 'disabled' : '' }}">
                    <i class="fa fa-print me-1"></i> Print Preview
                    <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+P</kbd>
                </a>

                <a href="{{ route('expense-vochers') }}" id="newBtn" class="btn btn-sm btn-info text-dark fw-bold rounded-pill px-4 shadow-sm">
                    <i class="fa fa-plus me-1"></i> New
                    <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+M</kbd>
                </a>
                
                <button type="button" id="cancelBtn" onclick="handleCancel()" class="btn btn-sm btn-danger text-dark fw-bold rounded-pill px-4 shadow-sm">
                    <i class="fa fa-times me-1"></i> Cancel
                    <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Esc</kbd>
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
    // Initialize Select2
    function initSelect2() {
        $('#vendor_id').select2({ placeholder: "Select Party...", allowClear: true, width: '100%' });
        $('.narrationSelect').select2({ placeholder: "Narration...", tags: true, width: '100%' });
    }
    initSelect2();

    // Party ID -> Select2 Sync
    function syncPartyIdToSelect2() {
        let val = $('#party_code_input').val();
        if (val) {
            // Find by primary ID
            let optById = $('#vendor_id option').filter(function() { return $(this).val() == val; });
            if (optById.length > 0) {
                $('#vendor_id').val(val).trigger('change');
                return;
            }
            // Find by Account Code
            let optByCode = $('#vendor_id option').filter(function() { return $(this).attr('data-code') == val; });
            if (optByCode.length > 0) {
                $('#vendor_id').val(optByCode.val()).trigger('change');
            } else {
                showAlert('Code / ID not found!', 'danger');
            }
        }
    }
    $('#party_code_input').on('keydown', function(e) {
        if (e.key === 'Enter' || e.keyCode === 13 || e.key === 'Tab') { 
            if(e.key === 'Enter') e.preventDefault();
            syncPartyIdToSelect2(); 
        }
    }).on('blur', syncPartyIdToSelect2);

    $('#vendor_id').on('change', function() {
        let code = $(this).find('option:selected').attr('data-code');
        $('#party_code_input').val(code || $(this).val() || '');
    });

    // Load Parties based on Type
    $('#vendor_type').change(function() {
        let type = $(this).val();
        let $partySelect = $('#vendor_id');
        let selectedId = $partySelect.data('selected-id');

        // 🔄 Reset Code and Select
        $('#party_code_input').val('');
        $partySelect.html('<option value="">Loading...</option>');
        
        if (!type) { $partySelect.html('<option value="">Select Type...</option>'); return; }

        let url = (type === 'vendor' || type === 'customer' || type === 'walkin') 
            ? '{{ route("party.list") }}?type=' + type 
            : '{{ url("get-accounts-by-head") }}/' + type;

        $.get(url, function(data) {
            $partySelect.html('<option value="">Select Party...</option>');
            data.forEach(item => {
                let code = item.account_code || '';
                let sel = (item.id == selectedId) ? 'selected' : '';
                $partySelect.append(`<option value="${item.id}" data-code="${code}" ${sel}>${item.text || item.title} ${code ? '('+code+')' : ''}</option>`);
            });
            $partySelect.trigger('change');
            if (selectedId) {
                let code = $partySelect.find('option:selected').attr('data-code');
                $('#party_code_input').val(code || selectedId);
            }
        });
    }).trigger('change');

    $('#vendor_id').change(function() {
        let selected = $(this).find(':selected');
        // Any specific source info logic if needed
    });

    // Row Logic
    $(document).on('change', '.rowAccountHead', function() {
        let $row = $(this).closest('tr');
        let headId = $(this).val();
        let $subSelect = $row.find('.rowAccountSub');
        let selected = $subSelect.data('selected');

        // 🔄 Reset Code and Select
        $row.find('.account-id-lookup').val('');
        $subSelect.html('<option value="">Loading...</option>');

        if (headId) {
            $.get('{{ url("get-accounts-by-head") }}/' + headId, function(res) {
                $subSelect.html('<option value="">Select Account</option>');
                res.forEach(acc => {
                    let sel = (acc.id == selected) ? 'selected' : '';
                    $subSelect.append(`<option value="${acc.id}" data-code="${acc.account_code}" ${sel}>${acc.title} (${acc.account_code})</option>`);
                });
                
                if(selected) {
                    let code = $subSelect.find('option:selected').attr('data-code');
                    $row.find('.account-id-lookup').val(code || selected);
                }
            });
        }
    }).each(function() { if ($(this).val()) $(this).trigger('change'); });

    $(document).on('blur keydown', '.account-id-lookup', function(e) {
        if(e.type === 'keydown' && e.which != 13 && e.which != 9) return;
        let idVal = $(this).val();
        let $row = $(this).closest('tr');
        let $select = $row.find('.rowAccountSub');
        if(idVal) {
            // Priority 1: Match by raw ID
            let optById = $select.find('option').filter(function() { return $(this).val() == idVal; });
            if(optById.length > 0) { $select.val(idVal).trigger('change'); return; }
            // Priority 2: Match by Code
            let optByCode = $select.find('option').filter(function() { return $(this).attr('data-code') == idVal; });
            if(optByCode.length > 0) { $select.val(optByCode.val()).trigger('change'); }
            else { showAlert('Code / ID not found!', 'danger'); }
        }
    });

    $(document).on('change', '.rowAccountSub', function() { 
        let code = $(this).find('option:selected').attr('data-code');
        $(this).closest('tr').find('.account-id-lookup').val(code || $(this).val() || ''); 
    });

    // Math
    function calculateTotals() {
        let total = 0;
        $('.row-amount').each(function() { total += parseFloat($(this).val()) || 0; });
        $('#totalAmount').val(total.toFixed(2));
    }
    $(document).on('input', '.row-amount', calculateTotals);

    $('#btnAddRow').click(function() {
        let newRow = `<tr>
            <td>
                <select name="narration_id[]" class="form-select narrationSelect">
                    <option value="">Select Narration...</option>
                    @foreach($narrationsList as $lid => $lname)
                        <option value="{{ $lid }}">{{ $lname }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <select name="row_account_head[]" class="form-select form-select-sm rowAccountHead">
                    <option value="">Select Head...</option>
                    @foreach($AccountHeads as $head)
                        <option value="{{ $head->id }}">{{ $head->name }}</option>
                    @endforeach
                </select>
            </td>
            <td><input type="text" class="form-control form-control-sm account-id-lookup" placeholder="Code/ID"></td>
            <td>
                <select name="row_account_id[]" class="form-select form-select-sm rowAccountSub">
                    <option value="">Select Account...</option>
                </select>
            </td>
            <td><input type="number" step="0.01" name="amount[]" class="form-control form-control-sm text-end row-amount"></td>
            <td class="text-center"><button type="button" class="btn text-danger btn-xs removeRow"><i class="fa fa-trash"></i></button></td>
        </tr>`;
        $('#voucherTable tbody').append(newRow);
        $('.narrationSelect').last().select2({ placeholder: "Narration...", tags: true, width: '100%' });
    });

    $(document).on('click', '.removeRow', function() { if ($('#voucherTable tbody tr').length > 1) { $(this).closest('tr').remove(); calculateTotals(); } });

    // AJAX Save
    function showAlert(msg, type='info') {
        $('#alertBox').removeClass('d-none alert-success alert-danger alert-info').addClass('alert-' + type).text(msg);
        setTimeout(() => $('#alertBox').addClass('d-none'), 5000);
    }

    $('#saveDraftBtn').click(function() {
        $('.ajax-valid-error').remove();
        $.post('{{ route("Expense.vochers.ajax-save") }}', $('#expenseForm').serialize(), function(res) {
            if(res.success) {
                $('#receipt_id').val(res.id); $('#evidInput').val(res.evid); $('#evidBadgeText').text(res.evid);
                showAlert('Draft saved successfully!', 'success');
                $('#expenseForm').addClass('form-locked');
                $('#editBtn').show();
                $('#previewPrintBtn').attr('href', '{{ route("ExpenseVoucher.print", ":id") }}'.replace(':id', res.id)).removeClass('disabled');
            } else { showAlert('Save error: ' + res.message, 'danger'); }
        }).fail(xhr => {
             if (xhr.status === 422) {
                 let errors = xhr.responseJSON.errors;
                 $.each(errors, function(key, val) {
                    let fieldHtml = `<div class="ajax-valid-error"><i class="fa fa-exclamation-circle me-1"></i>${val[0]}</div>`;
                    
                    // Logic for array fields like "amount.0"
                    if (key.includes('.')) {
                        let parts = key.split('.');
                        let fieldName = parts[0] + '[]';
                        let index = parts[1];
                        let $field = $(`[name="${fieldName}"]`).eq(index);
                        
                        if ($field.hasClass('select2-hidden-accessible')) {
                            $field.next('.select2-container').before(fieldHtml);
                        } else {
                            $field.before(fieldHtml);
                        }
                    } else {
                        // Regular fields
                        let $field = $(`[name="${key}"]`);
                        if ($field.length) {
                            if ($field.hasClass('select2-hidden-accessible')) {
                                $field.next('.select2-container').before(fieldHtml);
                            } else {
                                $field.before(fieldHtml);
                            }
                        }
                    }
                 });
                 showAlert('Validation failed. Please check required fields above.', 'danger');
             } else {
                 showAlert('Server error while saving.', 'danger');
             }
        });
    });

    $('#postBtn').click(function() {
        if (!confirm('Save & Post this voucher?')) return;
        $('.ajax-valid-error').remove();
        $.post('{{ route("Expense.vochers.ajax-save") }}', $('#expenseForm').serialize(), function(res) {
            if(res.success) {
                let id = $('#receipt_id').val() || res.id;
                let form = $('<form>', {action: '{{ route("Expense.vochers.post", ":id") }}'.replace(':id', id), method: 'POST'})
                    .append($('<input>', {type: 'hidden', name: '_token', value: '{{ csrf_token() }}'}));
                $('body').append(form); form.submit();
            }
        }).fail(xhr => {
             if (xhr.status === 422) {
                 let errors = xhr.responseJSON.errors;
                 $.each(errors, function(key, val) {
                    let fieldHtml = `<div class="ajax-valid-error"><i class="fa fa-exclamation-circle me-1"></i>${val[0]}</div>`;
                    if (key.includes('.')) {
                        let parts = key.split('.'); let fieldName = parts[0] + '[]'; let index = parts[1];
                        let $field = $(`[name="${fieldName}"]`).eq(index);
                        if ($field.hasClass('select2-hidden-accessible')) { $field.next('.select2-container').before(fieldHtml); }
                        else { $field.before(fieldHtml); }
                    } else {
                        let $field = $(`[name="${key}"]`);
                        if ($field.length) {
                            if ($field.hasClass('select2-hidden-accessible')) { $field.next('.select2-container').before(fieldHtml); }
                            else { $field.before(fieldHtml); }
                        }
                    }
                 });
                 showAlert('Validation failed. Please check the required fields above.', 'danger');
             } else {
                 showAlert('Server error during post.', 'danger');
             }
        });
    });

    $('#unpostBtn').click(function() {
        if(!confirm('Unpost this voucher?')) return;
        let id = $('#receipt_id').val();
        let form = $('<form>', {action: '{{ route("Expense.vochers.unpost", ":id") }}'.replace(':id', id), method: 'POST'})
            .append($('<input>', {type: 'hidden', name: '_token', value: '{{ csrf_token() }}'}));
        $('body').append(form); form.submit();
    });

    $('#editBtn, #editBtnLocked').click(function() {
        $('#expenseForm').removeClass('form-locked');
        $(this).hide();
        showAlert('Form unlocked for editing.', 'info');
    });

    // Keyboard Shortcuts
    $(document).on('keydown', function(e) {
        if (e.ctrlKey && e.key === 's') { e.preventDefault(); $('#saveDraftBtn').click(); }
        if (e.ctrlKey && e.key === 'Enter') { e.preventDefault(); $('#postBtn').click(); }
        if (e.ctrlKey && e.key === 'e') { e.preventDefault(); $('#editBtn, #editBtnLocked').click(); }
        if (e.ctrlKey && e.key === 'p') { e.preventDefault(); $('#previewPrintBtn')[0].click(); }
        if (e.ctrlKey && e.key === 'm') { e.preventDefault(); window.location.href = $('#newBtn').attr('href'); }
        if (e.key === 'Escape') { e.preventDefault(); handleCancel(); }
    });

    calculateTotals();
});

function handleCancel() {
    let id = $('#receipt_id').val();
    if (!id) { window.location.href = "{{ route('all-expense-vochers') }}"; }
    else {
        if (confirm('Delete this draft?')) {
            let form = $('<form>', {action: '{{ route("Expense.vochers.cancel", ":id") }}'.replace(':id', id), method: 'POST'})
                .append($('<input>', {type: 'hidden', name: '_token', value: '{{ csrf_token() }}'}))
                .append($('<input>', {type: 'hidden', name: '_method', value: 'DELETE'}));
            $('body').append(form); form.submit();
        }
    }
}
</script>
@endsection