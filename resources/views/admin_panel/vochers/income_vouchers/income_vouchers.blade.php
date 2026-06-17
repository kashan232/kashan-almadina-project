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
    .form-locked {
        background-color: #f8f9fa !important;
        position: relative;
    }
    .form-locked input, 
    .form-locked .select2-container--default .select2-selection--single,
    .form-locked .select2-container, 
    .form-locked select, 
    .form-locked textarea { 
        pointer-events: none !important; 
        opacity: 0.85 !important; 
        background-color: #f1f3f5 !important;
        cursor: not-allowed !important;
    }
    .form-locked .removeRow, .form-locked #btnAddRow, .form-locked #saveDraftBtn { 
        display: none !important; 
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
                                <h6 class="mb-0 fw-bold text-dark"><i class="fa fa-line-chart me-2 text-success"></i>Income Voucher</h6>
                            </div>
                            <span id="statusBadge" class="badge {{ $receipt->status == 'posted' ? 'bg-success' : 'bg-warning text-dark' }} rounded-pill px-3 py-1" style="font-size: 10px;">
                                {{ strtoupper($receipt->status ?: 'DRAFT') }}
                            </span>
                            <span class="badge bg-light text-primary border rounded-pill px-3 py-1" style="font-size: 10px;">
                                <i class="fa fa-hashtag me-1"></i> <span id="ividBadgeText">{{ $receipt->ivid ?: $nextIvid }}</span>
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

            <form id="incomeForm" autocomplete="off" class="{{ ($receipt->id && $receipt->status == 'posted') ? 'form-locked' : '' }}">
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
                        <button type="button" class="btn btn-primary btn-xs px-3 rounded-pill" id="btnAddRow" {{ $receipt->status == 'posted' ? 'disabled' : '' }}>
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
                <div class="row g-2 align-items-end mb-4">
                    <div class="col-md-7">
                        <div class="card form-card mb-0">
                            <div class="card-body p-2">
                                <label class="form-label">General Remarks / Note</label>
                                <input type="text" name="remarks" class="form-control form-control-sm" placeholder="Type additional voucher notes here..." value="{{ $receipt->remarks }}">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="d-flex gap-1 justify-content-end mb-1">
                            @if($receipt->status != 'posted')
                                <button type="button" id="saveDraftBtn" class="btn btn-sm btn-warning rounded-pill px-4 shadow-sm">
                                    <i class="fa fa-floppy-o me-1"></i> Save Draft
                                    <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>
                                </button>
                                <button type="button" id="postBtn" class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm">
                                    <i class="fa fa-send me-1"></i> Save & Post
                                    <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+&#8629;</kbd>
                                </button>
                            @endif
                            
                            @if($receipt->status == 'posted')
                                <button type="button" id="unpostBtn" class="btn btn-sm btn-outline-danger rounded-pill px-4 shadow-sm">
                                    <i class="fa fa-undo me-1"></i> Unpost
                                </button>
                            @endif

                            <button type="button" id="editBtn" class="btn btn-sm btn-warning rounded-pill px-4 shadow-sm" style="{{ ($receipt->id && $receipt->status != 'posted') ? 'display:block' : 'display:none' }};">
                                <i class="fa fa-pencil me-1"></i> Edit
                                <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+E</kbd>
                            </button>

                            <a href="{{ $receipt->id ? route('incomeVoucher.print', $receipt->id) : 'javascript:void(0)' }}" id="previewPrintBtn" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill px-4 shadow-sm {{ !$receipt->id ? 'disabled' : '' }}">
                                <i class="fa fa-print me-1"></i> Print Preview
                                <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+P</kbd>
                            </a>
                            
                            <a href="{{ route('income-vochers') }}" id="newInvoiceBtn" class="btn btn-sm btn-info rounded-pill px-4 shadow-sm text-white">
                                <i class="fa fa-plus me-1"></i> New
                                <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+M</kbd>
                            </a>
                            
                            <button type="button" id="cancelBtn" onclick="handleCancel()" class="btn btn-sm btn-danger rounded-pill px-4 shadow-sm text-white">
                                <i class="fa fa-times me-1"></i> Cancel
                                <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Esc</kbd>
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
            $.get('{{ url("get-accounts-by-head") }}/' + id, function(res) {
                $sub.html('<option value="">Select Account...</option>');
                res.forEach(a => $sub.append(`<option value="${a.id}" data-code="${a.account_code}" ${a.id == selected ? 'selected' : ''}>${a.title}</option>`));
                if(selected) {
                    let code = $sub.find('option:selected').attr('data-code');
                    $('#account_code_input').val(code || selected);
                }
            });
        }
    }).trigger('change');

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
        $('.ajax-valid-error').remove();
        $.post('{{ route("income.vochers.ajax-save") }}', $('#incomeForm').serialize())
            .done(res => {
                if(res.success) {
                    $('#receipt_id').val(res.id); $('#ividBadgeText').text(res.ivid);
                    $('#incomeForm').addClass('form-locked'); $('#editBtn').show();
                    $('#previewPrintBtn').attr('href', '{{ route("incomeVoucher.print", ":id") }}'.replace(':id', res.id)).removeClass('disabled');
                    showAlert('Draft saved and form locked.');
                }
            })
            .fail(xhr => {
                if(xhr.status == 422) {
                    let errs = xhr.responseJSON.errors;
                    Object.keys(errs).forEach(k => {
                        let $el = $(`[name="${k}"], #${k}`);
                        if(k.includes('.')) { let p = k.split('.'); $el = $(`[name="${p[0]}[]"]`).eq(p[1]); }
                        $el.after(`<div class="ajax-valid-error"><i class="fa fa-exclamation-circle"></i> ${errs[k][0]}</div>`);
                    });
                    showAlert('Please fix errors.', 'danger');
                }
            });
    });

    $('#editBtn').click(function() { $('#incomeForm').removeClass('form-locked'); $(this).hide(); });

    $('#postBtn').click(function() {
        $('#saveDraftBtn').click();
        setTimeout(() => {
            let id = $('#receipt_id').val();
            if(id) {
                let f = $('<form>', {action: '{{ route("income.vochers.post", ":id") }}'.replace(':id', id), method: 'POST'});
                f.append($('<input>', {type: 'hidden', name: '_token', value: '{{ csrf_token() }}'}));
                $('body').append(f); f.submit();
            }
        }, 1000);
    });

    $(window).on('keydown', function(e) {
        // Ctrl+S (Save)
        if ((e.ctrlKey || e.metaKey) && (e.which === 83 || e.keyCode === 83)) {
            e.preventDefault(); $('#saveDraftBtn').click(); return false;
        }
        // Ctrl+Enter (Post)
        if ((e.ctrlKey || e.metaKey) && (e.which === 13 || e.keyCode === 13)) {
            e.preventDefault(); $('#postBtn').click(); return false;
        }
        // Ctrl+P (Print)
        if ((e.ctrlKey || e.metaKey) && (e.which === 80 || e.keyCode === 80)) {
            e.preventDefault();
            if ($('#previewPrintBtn').length > 0 && !$('#previewPrintBtn').hasClass('disabled')) {
                window.open($('#previewPrintBtn').attr('href'), '_blank');
            }
            return false;
        }
        // Ctrl+L (List)
        if ((e.ctrlKey || e.metaKey) && (e.which === 76 || e.keyCode === 76)) {
            e.preventDefault();
            if ($('#listBtn').length > 0) { window.location.href = $('#listBtn').attr('href'); }
            return false;
        }
        // Ctrl+E (Edit)
        if ((e.ctrlKey || e.metaKey) && (e.which === 69 || e.keyCode === 69)) {
            e.preventDefault();
            if ($('#editBtn').is(':visible')) { $('#editBtn').click(); }
            return false;
        }
        // Ctrl+M (New)
        if ((e.ctrlKey || e.metaKey) && (e.which === 77 || e.keyCode === 77)) {
            e.preventDefault();
            window.location.href = "{{ route('income-vochers') }}";
            return false;
        }
        // Escape
        if (e.which === 27 || e.keyCode === 27) {
            if ($('.modal.show').length) {
                $('.modal.show').modal('hide');
            } else {
               handleCancel();
            }
        }
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


});
</script>
@endsection
