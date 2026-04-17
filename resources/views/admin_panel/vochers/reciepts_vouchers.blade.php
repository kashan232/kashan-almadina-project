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

    .section-title {
        font-weight: 700;
        color: #6c757d;
        letter-spacing: .3px;
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
    .form-locked .removeRow {
        pointer-events: none !important;
        opacity: 0.75 !important;
    }

    .form-locked input, .form-locked select, .form-locked textarea, .form-locked .select2-selection {
        background-color: #f8f9fa !important;
        pointer-events: none !important;
    }

    .loading-indicator {
        background-color: #fff9c4 !important;
        border-color: #fdd835 !important;
        transition: background-color 0.3s ease;
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
                <h5 class="page-title mb-0 fw-bold text-primary"><i class="fa fa-file-text-o me-2"></i>Receipts Voucher</h5>
                <span id="statusBadge" class="badge {{ $receipt->status == 'posted' ? 'bg-success' : 'bg-warning text-dark' }} px-3 py-2 rounded-pill shadow-sm">
                    <i class="fa {{ $receipt->status == 'posted' ? 'fa-check-circle' : 'fa-pencil' }} me-1"></i> 
                    {{ ucfirst($receipt->status) }}
                </span>
                <span class="badge bg-primary px-3 py-2 rounded-pill shadow-sm">
                    <i class="fa fa-tag me-1"></i> <span id="rvidBadgeText">{{ $receipt->rvid }}</span>
                </span>
            </div>

            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('all-recepit-vochers') }}" id="listBtn" class="btn btn-sm btn-outline-secondary rounded-pill px-3 shadow-sm">
                    <i class="fa fa-list me-1"></i> View All
                </a>
            </div>
        </div>

        <form id="receiptForm" autocomplete="off" class="{{ ($receipt->id || $receipt->status == 'posted') ? 'form-locked' : '' }}">
            @csrf
            <input type="hidden" name="id" id="receipt_id" value="{{ $receipt->id }}">

            <!-- Upper Row: Metadata -->
            <div class="row g-3 mb-4">
                <div class="col-md-2">
                    <div class="card border-0 bg-light p-2 shadow-sm h-100">
                        <label class="form-label text-muted small fw-bold mb-1">RVID</label>
                        <input type="text" id="rvidInput" class="form-control form-control-sm border-0 fw-bold text-primary input-readonly" value="{{ $receipt->rvid }}" readonly>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card border-0 bg-light p-2 shadow-sm h-100">
                        <label class="form-label text-muted small fw-bold mb-1">Receipt Date</label>
                        <input type="date" name="receipt_date" id="receipt_date" class="form-control form-control-sm" value="{{ $receipt->receipt_date ?? now()->toDateString() }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card border-0 bg-light p-2 shadow-sm h-100">
                        <label class="form-label text-muted small fw-bold mb-1">Entry Date</label>
                        <input type="date" name="entry_date" id="entry_date" class="form-control form-control-sm" value="{{ $receipt->entry_date ?? now()->toDateString() }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-light p-2 shadow-sm h-100">
                        <label class="form-label text-muted small fw-bold mb-1">Party Type <span class="text-danger">*</span></label>
                        <select name="vendor_type" id="vendor_type" class="form-select form-select-sm">
                            <option value="">Select Type...</option>
                            <option value="vendor" {{ $receipt->type == 'vendor' ? 'selected' : '' }}>Vendor</option>
                            <option value="customer" {{ $receipt->type == 'customer' ? 'selected' : '' }}>Customer</option>
                            <option value="walkin" {{ $receipt->type == 'walkin' ? 'selected' : '' }}>Walkin Customer</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
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
                <div class="col-md-3">
                    <div class="card border-0 bg-light p-2 shadow-sm h-100">
                        <label class="form-label text-muted small fw-bold mb-1">Tel / Account Code</label>
                        <input type="text" name="tel" id="tel" class="form-control form-control-sm input-readonly" value="{{ $receipt->tel }}" readonly>
                    </div>
                </div>
                <div class="col-md-9">
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
                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" id="btnAddRow" {{ $receipt->status == 'posted' ? 'disabled' : '' }}>
                        <i class="fa fa-plus me-1"></i> Add Row
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered mb-0" id="voucherTable">
                            <thead>
                                <tr>
                                    <th style="width: 20%;">Narration</th>
                                    <th style="width: 12%;">Reference#</th>
                                    <th style="width: 15%;">Account Head</th>
                                    <th style="width: 8%;">Code</th>
                                    <th style="width: 15%;">Account</th>
                                    <th style="width: 8%;">Discount</th>
                                    <th style="width: 8%;">KG</th>
                                    <th style="width: 8%;">Rate</th>
                                    <th style="width: 12%;">Amount</th>
                                    <th style="width: 2%;">Act</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $narrations_json = json_decode($receipt->narration_id, true) ?? [];
                                    $references = json_decode($receipt->reference_no, true) ?? [];
                                    $rowHeads = json_decode($receipt->row_account_head, true) ?? [];
                                    $rowAccounts = json_decode($receipt->row_account_id, true) ?? [];
                                    $discounts = json_decode($receipt->discount_value, true) ?? [];
                                    $kgs = json_decode($receipt->kg, true) ?? [];
                                    $rates = json_decode($receipt->rate, true) ?? [];
                                    $amounts = json_decode($receipt->amount, true) ?? [];
                                    
                                    // If empty, add at least one empty row
                                    if(empty($narrations_json)) $narrations_json = [null];
                                @endphp

                                @foreach($narrations_json as $index => $nId)
                                <tr>
                                    <td>
                                        <select name="narration_id[]" class="form-select narrationSelect">
                                            <option value="">Search or type narration...</option>
                                            @foreach($narrations as $id => $name)
                                            <option value="{{ $id }}" {{ $nId == $id ? 'selected' : '' }}>{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input name="reference_no[]" type="text" class="form-control form-control-sm" value="{{ $references[$index] ?? '' }}"></td>
                                    <td>
                                        <select name="row_account_head[]" class="form-select form-select-sm rowAccountHead">
                                            <option value="">Select Head</option>
                                            @foreach($AccountHeads as $head)
                                            <option value="{{ $head->id }}" {{ ($rowHeads[$index] ?? '') == $head->id ? 'selected' : '' }}>{{ $head->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm text-center rowAccountCode" placeholder="Code" value=""></td>
                                    <td>
                                        <select name="row_account_id[]" class="form-select form-select-sm rowAccountSub" data-selected="{{ $rowAccounts[$index] ?? '' }}">
                                            <option value="">Select Account</option>
                                        </select>
                                    </td>
                                    <td><input name="discount_value[]" type="number" step="any" class="form-control form-control-sm text-end discountValue" value="{{ $discounts[$index] ?? 0 }}"></td>
                                    <td><input name="kg[]" type="number" step="any" class="form-control form-control-sm text-center kg" value="{{ $kgs[$index] ?? '' }}"></td>
                                    <td><input name="rate[]" type="number" step="any" class="form-control form-control-sm text-end rate" value="{{ $rates[$index] ?? '' }}"></td>
                                    <td><input name="amount[]" type="text" class="form-control form-control-sm text-end amount fw-bold" value="{{ $amounts[$index] ?? '' }}"></td>
                                    <td class="text-center">
                                        <button type="button" class="btn text-danger btn-xs removeRow"><i class="fa fa-trash"></i></button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr class="fw-bold">
                                    <td colspan="7" class="text-end py-3">GRAND TOTAL:</td>
                                    <td class="text-end py-3 p-2 bg-primary bg-opacity-10">
                                        <input type="text" name="total_amount" class="form-control form-control-sm text-end border-0 bg-transparent fw-bold fs-6 text-primary" id="totalAmount" value="{{ $receipt->total_amount ?? '0.00' }}" readonly>
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
                @if($receipt->status == 'draft')
                <button type="button" id="saveDraftBtn" class="btn btn-sm btn-warning text-dark fw-bold rounded-pill px-4 shadow-sm">
                    <i class="fa fa-floppy-o me-1"></i> Save Draft
                    <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>
                </button>
                <button type="button" id="postBtn" class="btn btn-sm btn-primary text-dark fw-bold rounded-pill px-4 shadow-sm">
                    <i class="fa fa-send me-1"></i> Save Post
                    <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+&#8629;</kbd>
                </button>

                <button type="button" id="editBtn" class="btn btn-sm btn-warning text-dark fw-bold rounded-pill px-4 shadow-sm" style="{{ ($receipt->id && $receipt->status == 'draft') ? 'display:block' : 'display:none' }};">
                    <i class="fa fa-pencil me-1"></i> Edit
                    <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+E</kbd>
                </button>
                @endif
                
                <a href="{{ $receipt->id ? route('receiptVoucher.print', $receipt->id) : 'javascript:void(0)' }}" 
                   id="previewPrintBtn" 
                   target="_blank" 
                   class="btn btn-sm btn-outline-dark rounded-pill px-4 shadow-sm {{ !$receipt->id ? 'disabled' : '' }}">
                    <i class="fa fa-print me-1"></i> Print Preview
                    <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+P</kbd>
                </a>

                <a href="{{ route('recepit-vochers') }}" id="newBtn" class="btn btn-sm btn-info text-dark fw-bold rounded-pill px-4 shadow-sm">
                    <i class="fa fa-plus me-1"></i> New
                    <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+M</kbd>
                </a>
                
                <button type="button" id="cancelBtn" onclick="handleCancel()" class="btn btn-sm btn-danger text-dark fw-bold rounded-pill px-4 shadow-sm">
                    <i class="fa fa-times me-1"></i> Cancel
                    <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Esc</kbd>
                </button>
            </div>
        </form>

        </form>

        <div class="posted-watermark" id="postedWatermark" style="{{ $receipt->status == 'posted' ? 'display: block;' : '' }}">Posted</div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // 🛠️ Select2 Initialization
    function initSelect2() {
        $('#vendor_id').select2({
            placeholder: "Select Party...",
            allowClear: true,
            width: '100%'
        });

        $('.narrationSelect').select2({
            placeholder: "Narration...",
            tags: true,
            width: '100%'
        });
    }

    initSelect2();

    // 🛠️ Party ID -> Select2 Sync
    function syncPartyIdToSelect2() {
        let val = $('#party_code_input').val();
        if (val) {
            // Priority 1: Check match by primary ID
            let optionById = $('#vendor_id option').filter(function() {
                return $(this).val() == val;
            });

            if (optionById.length > 0) {
                $('#vendor_id').val(val).trigger('change');
                return;
            }

            // Priority 2: Check match by Account Code (data-code attribute)
            let optionByCode = $('#vendor_id option').filter(function() {
                return $(this).attr('data-code') == val;
            });

            if (optionByCode.length > 0) {
                $('#vendor_id').val(optionByCode.val()).trigger('change');
            } else {
                showAlert('Code / ID not found in current list!', 'danger');
            }
        }
    }

    $('#party_code_input').on('keydown', function(e) {
        if (e.key === 'Enter' || e.keyCode === 13 || e.key === 'Tab' || e.keyCode === 9) {
            // Only prevent default on Enter, let Tab move focus
            if (e.key === 'Enter' || e.keyCode === 13) e.preventDefault();
            syncPartyIdToSelect2();
        }
    }).on('blur', function() {
        syncPartyIdToSelect2();
    });

    // 🛠️ Select2 -> Party ID Sync
    $('#vendor_id').on('select2:select', function(e) {
        let val = e.params.data.id;
        $('#party_code_input').val(val);
    });

    $('#vendor_id').on('select2:clear', function() {
        $('#party_code_input').val('');
    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('input[name="_token"]').val()
        }
    });

    // 🧩 Party Handling
    $('#receiptForm').on('submit', function(e) {
        e.preventDefault();
    });

    $('#vendor_type').change(function() {
        let type = $(this).val();
        let $partySelect = $('#vendor_id');
        let selectedId = $partySelect.data('selected-id');

        // 🔄 Reset Code / ID and Tel when type changes
        $('#party_code_input').val('');
        $('#tel').val('');
        
        $partySelect.html('<option value="">Loading...</option>');
        
        if (!type) {
            $partySelect.html('<option value="">Select Party...</option>');
            return;
        }

        if (type === 'vendor' || type === 'customer' || type === 'walkin') {
            $.get('{{ route("party.list") }}?type=' + type, function(data) {
                $partySelect.html('<option value="">Select Party...</option>');
                data.forEach(item => {
                    let sel = (item.id == selectedId) ? 'selected' : '';
                    $partySelect.append(`<option value="${item.id}" ${sel}>${item.text}</option>`);
                });
                $partySelect.trigger('change');
                
                // Set initial Party ID if editing
                if (selectedId) $('#party_code_input').val(selectedId);
            });
        } else {
            $.get('{{ url("get-accounts-by-head") }}/' + type, function(data) {
                $partySelect.html('<option value="">Select Account...</option>');
                data.forEach(acc => {
                    let sel = (acc.id == selectedId) ? 'selected' : '';
                    $partySelect.append(`<option value="${acc.id}" data-code="${acc.account_code}" ${sel}>${acc.title}</option>`);
                });
                $partySelect.trigger('change');
            });
        }
    }).trigger('change');

    $('#vendor_id').change(function() {
        let $opt = $(this).find(':selected');
        let type = $('#vendor_type').val();
        
        if (type && !isNaN(type)) { // Account Head mode
            $('#tel').val($opt.data('code') || '');
        } else {
            let id = $opt.val();
            if (id) {
                $.get('{{ route("customers.show", ["id" => "__ID__"]) }}'.replace('__ID__', id) + '?type=' + type, function(d) {
                    $('#tel').val(d.mobile || '');
                    if(!$('#remarks').val()) $('#remarks').val(d.remarks || '');
                });
            }
        }
    });

    // 🧩 Table Row Management
    $('#btnAddRow').click(function() {
        let newRow = `<tr>
            <td>
                <select name="narration_id[]" class="form-select narrationSelect">
                    <option value="">Search or type narration...</option>
                    @foreach($narrations as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </td>
            <td><input name="reference_no[]" type="text" class="form-control form-control-sm"></td>
            <td>
                <select name="row_account_head[]" class="form-select form-select-sm rowAccountHead">
                    <option value="">Select Head</option>
                    @foreach($AccountHeads as $head)
                    <option value="{{ $head->id }}">{{ $head->name }}</option>
                    @endforeach
                </select>
            </td>
            <td><input type="text" class="form-control form-control-sm text-center rowAccountCode" placeholder="Code"></td>
            <td>
                <select name="row_account_id[]" class="form-select form-select-sm rowAccountSub">
                    <option value="">Select Account</option>
                </select>
            </td>
            <td><input name="discount_value[]" type="number" step="any" class="form-control form-control-sm text-end discountValue" value="0"></td>
            <td><input name="kg[]" type="number" step="any" class="form-control form-control-sm text-center kg"></td>
            <td><input name="rate[]" type="number" step="any" class="form-control form-control-sm text-end rate"></td>
            <td><input name="amount[]" type="text" class="form-control form-control-sm text-end amount fw-bold"></td>
            <td class="text-center">
                <button type="button" class="btn text-danger btn-xs removeRow"><i class="fa fa-trash"></i></button>
            </td>
        </tr>`;
        $('#voucherTable tbody').append(newRow);
        $('.narrationSelect').last().select2({
            placeholder: "Narration...",
            tags: true,
            width: '100%'
        });
    });

    $(document).on('click', '.removeRow', function() {
        if ($('#voucherTable tbody tr').length > 1) {
            $(this).closest('tr').remove();
            calculateTotals();
        }
    });

    $(document).on('change', '.rowAccountHead', function() {
        let $row = $(this).closest('tr');
        let headId = $(this).val();
        let $subSelect = $row.find('.rowAccountSub');
        let $codeField = $row.find('.rowAccountCode');
        let selected = $subSelect.data('selected');

        // 🔄 Reset Code and Account Sub when Head changes
        $codeField.val('');
        $subSelect.html('<option value="">Loading...</option>');

        if (headId) {
            $.get('{{ url("get-accounts-by-head") }}/' + headId, function(res) {
                $subSelect.html('<option value="">Select Account</option>');
                res.forEach(acc => {
                    let sel = (acc.id == selected) ? 'selected' : '';
                    $subSelect.append(`<option value="${acc.id}" data-code="${acc.account_code}" ${sel}>${acc.title}</option>`);
                });
                
                // If there's an already selected ID, maybe update the code input too
                if (selected) {
                   let code = $subSelect.find('option:selected').data('code');
                   $row.find('.rowAccountCode').val(code || '');
                }
            });
        }
    }).each(function() {
        if ($(this).val()) $(this).trigger('change');
    });

    $(document).on('change', '.rowAccountSub', function() {
        let code = $(this).find('option:selected').data('code');
        $(this).closest('tr').find('.rowAccountCode').val(code || '');
    });

    $(document).on('keydown', '.rowAccountCode', function(e) {
        if (e.key === 'Tab' || e.key === 'Enter') {
            let code = $(this).val();
            if (code) {
                let $row = $(this).closest('tr');
                let $subSelect = $row.find('.rowAccountSub');
                let $opt = $subSelect.find('option').filter(function() {
                    return $(this).attr('data-code') == code;
                });

                if ($opt.length > 0) {
                    $subSelect.val($opt.val()).trigger('change');
                }
            }
        }
    });

    $(document).on('blur', '.rowAccountCode', function() {
        let code = $(this).val();
        if (code) {
            let $row = $(this).closest('tr');
            let $subSelect = $row.find('.rowAccountSub');
            let $opt = $subSelect.find('option').filter(function() {
                return $(this).attr('data-code') == code;
            });

            if ($opt.length > 0) {
                $subSelect.val($opt.val()).trigger('change');
            }
        }
    });

    // Removed old narration show/hide logic (Select2 handles this via tags:true)


    // 🧩 Calculations
    function calculateTotals() {
        let grandTotal = 0;
        $('.amount').each(function() {
            grandTotal += parseFloat($(this).val()) || 0;
        });
        $('#totalAmount').val(grandTotal.toFixed(2));
    }

    $(document).on('input', '.kg, .rate, .discountValue', function() {
        let $tr = $(this).closest('tr');
        let kg = parseFloat($tr.find('.kg').val()) || 0;
        let rate = parseFloat($tr.find('.rate').val()) || 0;
        let disc = parseFloat($tr.find('.discountValue').val()) || 0;
        
        let amount = (kg > 0) ? (kg * rate) : rate;
        amount = amount - disc;
        if(amount < 0) amount = 0;
        
        $tr.find('.amount').val(amount.toFixed(2));
        calculateTotals();
    });

    $(document).on('input', '.amount', function() {
        calculateTotals();
    });

    // 🧩 AJAX Saving
    function showAlert(msg, type='info') {
        $('#alertBox').removeClass('d-none alert-success alert-danger alert-info')
                    .addClass('alert-' + type).text(msg);
        setTimeout(() => $('#alertBox').addClass('d-none'), 5000);
    }

    $('#saveDraftBtn').click(function() {
        $(this).addClass('loading-indicator');
        $.ajax({
            url: '{{ route("recepit.vochers.ajax-save") }}',
            method: 'POST',
            data: $('#receiptForm').serialize(),
            success: function(res) {
                $('#saveDraftBtn').removeClass('loading-indicator');
                if(res.success) {
                    // Update ID and RVID if it's the first save
                    if (!$('#receipt_id').val()) {
                        $('#receipt_id').val(res.id);
                        $('#rvidInput').val(res.rvid);
                        $('#rvidBadgeText').text(res.rvid);
                    }
                    showAlert(res.message, 'success');
                    $('#receiptForm').addClass('form-locked');
                    $('#editBtn').show();
                    
                    // Enable print button and update its href
                    let printUrl = '{{ route("receiptVoucher.print", ":id") }}'.replace(':id', res.id);
                    $('#previewPrintBtn').attr('href', printUrl).removeClass('disabled');
                } else {
                    showAlert('Save error: ' + res.message, 'danger');
                }
            },
            error: function(xhr) {
                $('#saveDraftBtn').removeClass('loading-indicator');
                $('.ajax-valid-error').remove(); // Clear previous errors

                let errorMsg = 'Server error while saving.';
                if (xhr.status === 422) {
                    // Validation error
                    let errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, val) {
                        let fieldHtml = '<div class="text-danger fw-bold ajax-valid-error mb-1" style="font-size:11px;"><i class="fa fa-exclamation-triangle"></i> ' + val[0] + '</div>';
                        
                        // Handle array fields or normal fields
                        let $target = $('[name="' + key + '"]');
                        if (!$target.length && key.includes('.')) {
                             let parts = key.split('.');
                             $target = $('[name="' + parts[0] + '[]"]').eq(parts[1]);
                        }

                        if ($target.length) {
                            if ($target.hasClass('select2-hidden-accessible')) {
                                $target.next('.select2-container').before(fieldHtml);
                            } else {
                                $target.before(fieldHtml);
                            }
                        }
                    });
                    errorMsg = 'Please fix the required fields.';
                } else {
                    if (xhr.status === 419) errorMsg = 'Session expired. Please refresh the page.';
                    if (xhr.responseJSON && xhr.responseJSON.message) errorMsg = xhr.responseJSON.message;
                }
                
                showAlert(errorMsg, 'danger');
                console.error(xhr.responseText);
            }
        });
    });

    $('#postBtn').click(function() {

        // Auto save draft first
        $.post('{{ route("recepit.vochers.ajax-save") }}', $('#receiptForm').serialize(), function(res) {
            if(res.success) {
                // Now post using the saved ID
                let postId = $('#receipt_id').val() || res.id;
                let postUrl = '{{ route("recepit.vochers.post", ":id") }}'.replace(':id', postId);
                
                let form = $('<form>', {
                    action: postUrl,
                    method: 'POST'
                }).append($('<input>', {type: 'hidden', name: '_token', value: '{{ csrf_token() }}'}));
                $('body').append(form);
                form.submit();
            } else {
                showAlert('Failed to save draft before posting: ' + res.message, 'danger');
            }
        });
    });

    // 🧩 Keyboard Shortcuts
    $(document).on('keydown', function(e) {
        if (e.ctrlKey && (e.key === 's' || e.key === 'S')) {
            e.preventDefault();
            $('#saveDraftBtn').click();
        }
        if (e.ctrlKey && e.key === 'Enter') {
            e.preventDefault();
            $('#postBtn').click();
        }
        if (e.ctrlKey && (e.key === 'p' || e.key === 'P')) {
            e.preventDefault();
            $('#previewPrintBtn')[0].click(); // target link
        }
        if (e.ctrlKey && (e.key === 'm' || e.key === 'M')) {
            e.preventDefault();
            window.location.href = $('#newBtn').attr('href');
        }
        if (e.key === 'Escape') {
            e.preventDefault();
            handleCancel();
        }
        if (e.ctrlKey && (e.key === 'e' || e.key === 'E')) {
            e.preventDefault();
            $('#editBtn').click();
        }
    });

    $('#editBtn').click(function() {
        $('#receiptForm').removeClass('form-locked');
        $(this).hide();
        showAlert('Form unlocked for editing.', 'info');
    });

    // Ctrl+L overwrite
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && (e.key === 'l' || e.key === 'L')) {
            e.preventDefault();
            window.location.href = '{{ route("all-recepit-vochers") }}';
        }
    }, true);

    calculateTotals();
});

function handleCancel() {
    let id = $('#receipt_id').val();
    if (!id) {
        window.location.href = "{{ route('all-recepit-vochers') }}";
    } else {
        if (confirm('Are you sure you want to cancel and DELETE this draft?')) {
            let cancelUrl = '{{ route("recepit.vochers.cancel", ":id") }}'.replace(':id', id);
            let form = $('<form>', {
                action: cancelUrl,
                method: 'POST'
            }).append($('<input>', {type: 'hidden', name: '_token', value: '{{ csrf_token() }}'}))
              .append($('<input>', {type: 'hidden', name: '_method', value: 'DELETE'}));
            $('body').append(form);
            form.submit();
        }
    }
}
</script>
@endsection