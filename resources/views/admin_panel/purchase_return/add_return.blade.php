@extends('admin_panel.layout.app')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .manual-only { display: none; }
    .input-readonly { background: #f9fbff !important; }

    .form-locked input:not(.no-lock),
    .form-locked select,
    .form-locked textarea,
    .form-locked .btn-group,
    .form-locked .select2-container,
    .form-locked .select2-selection,
    .form-locked .select2-selection__rendered {
        pointer-events: none !important;
        background-color: #f8f9fa !important;
        opacity: 0.75 !important;
        cursor: not-allowed !important;
    }
    .form-locked select {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
    }
    .form-locked .remove-row,
    .form-locked #saveDraftBtn {
        display: none !important;
    }
    .form-locked #editInvoiceBtn,
    .form-locked #newInvoiceBtn,
    .form-locked #realPrintBtn,
    .form-locked #postBtn,
    .form-locked #exitBtn,
    .form-locked #deleteBtn {
        pointer-events: auto !important;
        opacity: 1 !important;
    }

    .purchase-page .main-container { padding: .35rem !important; border-radius: .35rem !important; max-width: 99%; }
    .purchase-page.container-fluid,
    .purchase-page-inner.container-fluid { padding: .2rem .35rem !important; }
    .purchase-page.main-content,
    .purchase-page .main-content-inner { padding: 0 !important; }
    .purchase-page .header-panel { padding: .4rem !important; min-width: 255px !important; max-width: 255px !important; font-size: .78rem; }
    .purchase-page .section-block { padding: .4rem !important; }
    .purchase-page .items-panel { padding-left: .35rem !important; padding-right: 0 !important; }
    .purchase-page .items-table-wrap { min-height: 280px !important; border-radius: .35rem !important; overflow: auto; }
    .purchase-page .main-row { gap: .35rem !important; padding: .25rem 0 !important; }
    .purchase-page .field-gap { margin-bottom: .3rem !important; }
    .purchase-page .panel-head { margin-bottom: .35rem !important; padding-bottom: .2rem !important; }
    .purchase-page .panel-head h6 { font-size: .8rem !important; }
    .purchase-page .form-label { margin-bottom: 0 !important; line-height: 1.1; font-size: .7rem !important; }
    .purchase-page .form-control-sm,
    .purchase-page .form-select-sm { padding: .1rem .35rem !important; font-size: .75rem !important; height: 24px !important; min-height: 24px !important; }
    .purchase-page textarea.form-control-sm { height: auto !important; min-height: 38px !important; }
    .purchase-page .table thead th { padding: 2px !important; font-size: .7rem !important; background: #f8f9fa !important; position: sticky; top: 0; z-index: 2; }
    .purchase-page .table-sm td { padding: 1px !important; vertical-align: middle; }
    .purchase-page .table-sm .form-control,
    .purchase-page .table-sm .form-select { height: 24px !important; min-height: 24px !important; font-size: .75rem !important; padding: 2px 4px !important; }
    .purchase-page .select2-container--default .select2-selection--single { height: 24px !important; font-size: .75rem !important; }
    .purchase-page .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 22px !important; padding-left: 6px !important; }
    .purchase-page .select2-container--default .select2-selection--single .select2-selection__arrow { height: 22px !important; }
    .purchase-page .bottom-section { --bs-gutter-x: .5rem; --bs-gutter-y: .35rem; margin-top: .25rem !important; }
    .purchase-page .totals-card .py-1 { padding-top: .15rem !important; padding-bottom: .15rem !important; }
    .purchase-page #netAmount { font-size: 1rem !important; width: 140px !important; }
    .purchase-page .bottom-bar { margin-top: .4rem !important; padding: .75rem !important; }
    .purchase-page .mode-toggle .btn { font-size: .7rem !important; padding: .1rem .4rem !important; }
    .purchase-page .btn-xs { padding: 1px 4px; font-size: .7rem; line-height: 1.2; }
</style>

@section('content')
@php
    $isPosted = isset($returnData) && $returnData->status == 'Posted';
    $isDraft = isset($returnData) && $returnData->status != 'Posted';
    $isNew = !isset($returnData);
    $formLocked = $isPosted || (isset($returnData) && !$isNew);
@endphp

<div class="main-content bg-white purchase-page">
    <div class="main-content-inner">
        <div class="container-fluid purchase-page-inner">
            <div class="main-container bg-white border shadow-sm mx-auto rounded-3">

                <form id="returnForm" class="{{ $formLocked && !$isPosted ? 'form-locked' : ($isPosted ? 'form-locked' : '') }}" action="{{ isset($returnData) ? route('purchase.return.update', $returnData->id) : route('purchase.return.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="purchase_id" id="purchase_id" value="{{ $returnData->purchase_id ?? '' }}">
                    <input type="hidden" name="current_date" id="current_date_hidden" value="{{ $returnData->current_date ?? date('Y-m-d') }}">

                    <div class="d-flex align-items-stretch border-bottom main-row">
                        {{-- LEFT: Return Details --}}
                        <div class="bg-light border rounded-3 header-panel shadow-sm">
                            <div class="d-flex align-items-center justify-content-between panel-head border-bottom">
                                <h6 class="mb-0 fw-bold text-danger d-flex align-items-center gap-1">
                                    <i class="fa fa-undo"></i> Purchase Return
                                    @if($isPosted)
                                        <span class="badge bg-success px-1 py-0 rounded" style="font-size:9px;">Posted</span>
                                    @endif
                                </h6>
                                <a href="{{ route('purchase.return.home') }}" id="listBtn" class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-0" style="font-size:.7rem;">
                                    <i class="fa fa-list"></i> List
                                </a>
                            </div>

                            <div class="field-gap">
                                <span class="badge bg-danger w-100 text-start py-1" style="font-size:.72rem;" id="invoiceNoDisplay">Return No: {{ isset($returnData) ? $nextInvoice : 'Auto-Generated' }}</span>
                            </div>

                            <div class="field-gap mode-toggle">
                                <label class="form-label text-muted small">Return Mode</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="return_mode" id="mode_invoice" value="invoice" {{ !isset($returnData) || (isset($returnData) && $returnData->purchase_id) ? 'checked' : '' }} autocomplete="off">
                                    <label class="btn btn-outline-primary btn-sm py-0" for="mode_invoice">Invoice</label>
                                    <input type="radio" class="btn-check" name="return_mode" id="mode_manual" value="manual" {{ isset($returnData) && !$returnData->purchase_id ? 'checked' : '' }} autocomplete="off">
                                    <label class="btn btn-outline-primary btn-sm py-0" for="mode_manual">Manual</label>
                                </div>
                            </div>

                            <div class="row g-1 field-gap">
                                <div class="col-6">
                                    <label class="form-label text-muted small">Entry Date</label>
                                    <input name="entry_date" value="{{ $returnData->entry_date ?? date('Y-m-d') }}" type="date" class="form-control form-control-sm py-0" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-muted small">Entry Time</label>
                                    <input name="entry_time" value="{{ $returnData->entry_time ?? date('H:i') }}" type="time" class="form-control form-control-sm py-0" required>
                                </div>
                            </div>

                            <div class="field-gap" id="vendor_type_col">
                                <label class="form-label text-muted small">Party Type</label>
                                <select name="vendor_type" id="vendor_type_select" class="form-select form-select-sm py-0">
                                    <option value="" disabled selected>Select</option>
                                    <option value="vendor" {{ isset($returnData) && class_basename($returnData->purchasable_type) == 'Vendor' ? 'selected' : '' }}>Vendor</option>
                                    <option value="customer" {{ isset($returnData) && class_basename($returnData->purchasable_type) == 'Customer' ? 'selected' : '' }}>Customer</option>
                                    <option value="walkin">Walking Customer</option>
                                </select>
                            </div>

                            <div class="field-gap" id="party_col">
                                <label class="form-label text-muted small">Select Party</label>
                                <select name="party_id" id="party_select" class="form-select form-select-sm py-0 select2">
                                    <option value="">Select Party</option>
                                    @if(isset($returnData))
                                        <option value="{{ $returnData->purchasable_id }}" selected>{{ $returnData->purchasable->name ?? $returnData->purchasable->customer_name }}</option>
                                    @endif
                                </select>
                            </div>

                            <div class="field-gap invoice-only" id="invoice_col" style="{{ isset($returnData) && !$returnData->purchase_id ? 'display:none;' : '' }}">
                                <label class="form-label text-muted small">Purchase Invoice</label>
                                <select id="purchase_invoice_select" class="form-select form-select-sm py-0 select2">
                                    <option value="">Select Invoice</option>
                                    @if(isset($returnData) && $returnData->purchase)
                                        <option value="{{ $returnData->purchase->invoice_no }}" selected>{{ $returnData->purchase->invoice_no }}</option>
                                    @endif
                                </select>
                            </div>

                            <div class="field-gap">
                                <label class="form-label text-muted small">Warehouse</label>
                                <select name="warehouse_id" id="warehouse_select" class="form-select form-select-sm py-0 select2" required>
                                    <option value="">Select Warehouse</option>
                                    <option value="0" {{ isset($returnData) && $returnData->warehouse_id === 0 ? 'selected' : '' }}>Shop</option>
                                    @foreach($warehouses as $w)
                                        <option value="{{ $w->id }}" {{ isset($returnData) && $returnData->warehouse_id == $w->id ? 'selected' : '' }}>{{ $w->warehouse_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="field-gap invoice-only" id="display_col" style="display:none;">
                                <label class="form-label text-muted small">Loaded Party</label>
                                <input id="party_name_display" type="text" class="form-control form-control-sm py-0 bg-white" readonly placeholder="Auto-fill" value="{{ isset($returnData) ? ($returnData->purchasable->name ?? $returnData->purchasable->customer_name) : '' }}">
                            </div>

                            <div class="mb-0">
                                <label class="form-label text-muted small">Remarks</label>
                                <textarea name="remarks" class="form-control form-control-sm py-0" rows="1" placeholder="Reason for return...">{{ $returnData->note ?? '' }}</textarea>
                            </div>
                        </div>

                        {{-- RIGHT: Items --}}
                        <div class="flex-grow-1 d-flex flex-column items-panel">
                            <div class="table-responsive flex-grow-1 border shadow-sm items-table-wrap">
                                <table class="table table-bordered table-sm mb-0 text-center align-middle" style="table-layout: fixed; width: 100%; font-size: .82rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:6%;">Item ID</th>
                                            <th style="width:14%;">Product</th>
                                            <th style="width:10%;">Brand</th>
                                            <th style="width:9%;" class="text-end">Price</th>
                                            <th style="width:9%;" class="text-end">Retail</th>
                                            <th style="width:14%;">Disc</th>
                                            <th class="invoice-only text-center" style="width:6%;">Orig</th>
                                            <th style="width:6%;" class="text-center">Qty</th>
                                            <th style="width:9%;" class="text-end">Rate</th>
                                            <th style="width:10%;" class="text-end">Total</th>
                                            <th style="width:3%;" class="text-center"><span style="font-size:9px;font-weight:normal;color:#888;"><kbd style="font-size:8px;padding:0 2px;">Ctrl+I</kbd></span></th>
                                        </tr>
                                    </thead>
                                    <tbody id="purchaseItems">
                                        @if(isset($returnData))
                                            @foreach($returnData->items as $item)
                                                @php
                                                    $discAmt = $item->qty > 0 ? ($item->item_discount / $item->qty) : 0;
                                                    $rate = $item->price - $discAmt;
                                                @endphp
                                                <tr>
                                                    <td><input type="text" class="form-control form-control-sm item-id-input text-center" value="{{ $item->product_id }}"></td>
                                                    <td>
                                                        <select name="product_id[]" class="form-control form-control-sm product-select" style="width:100%;">
                                                            <option value="{{ $item->product_id }}" selected>{{ $item->product->name }}</option>
                                                        </select>
                                                    </td>
                                                    <td><input type="text" name="brand[]" class="form-control form-control-sm brand-name input-readonly" readonly value="{{ $item->product->brand ?? '' }}"></td>
                                                    <td><input type="number" step="0.01" name="price[]" class="form-control form-control-sm price text-end" value="{{ $item->price }}"></td>
                                                    <td><input type="number" step="0.01" name="retail_price[]" class="form-control form-control-sm retail_price text-end" value="{{ $item->retail_price }}"></td>
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            <input type="number" step="0.01" min="0" name="discount_percent[]" class="form-control form-control-sm discount_percent text-end" value="{{ $item->discount_percent }}">
                                                            <span class="input-group-text px-1" style="font-size:.65rem;">%</span>
                                                            <input type="text" name="item_disc_amount[]" class="form-control form-control-sm disc_amount text-end input-readonly" value="{{ number_format($discAmt, 2, '.', '') }}" readonly>
                                                        </div>
                                                    </td>
                                                    <td class="invoice-only"><input type="text" class="form-control form-control-sm text-center input-readonly" value="{{ $item->qty }}" readonly></td>
                                                    <td><input type="number" name="qty[]" class="form-control form-control-sm quantity text-center" value="{{ $item->qty }}" min="0"></td>
                                                    <td><input type="number" step="0.01" name="rate[]" class="form-control form-control-sm rate text-end input-readonly" value="{{ number_format($rate, 2, '.', '') }}" readonly></td>
                                                    <td><input type="text" name="line_total[]" class="form-control form-control-sm row-total text-end fw-bold input-readonly" readonly value="{{ $item->line_total }}"></td>
                                                    <td class="text-center"><button type="button" class="btn btn-xs btn-outline-danger remove-row" title="Delete">&times;</button></td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr><td colspan="11" class="text-center text-muted py-3">No invoice selected yet.</td></tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Totals --}}
                    <div class="row bottom-section">
                        <div class="col-lg-5 ms-auto">
                            <div class="bg-light border rounded-3 section-block shadow-sm">
                                <div class="panel-head border-bottom">
                                    <h6 class="mb-0 fw-bold text-info"><i class="fa fa-calculator me-1"></i>Return Totals</h6>
                                </div>
                                <div class="totals-card">
                                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom px-1">
                                        <span class="text-dark small fw-bold">Subtotal</span>
                                        <input type="text" id="subtotal" name="subtotal" class="form-control form-control-sm text-end bg-transparent border-0 fw-bold py-0" readonly value="{{ $returnData->subtotal ?? 0 }}" style="width:120px">
                                    </div>
                                    <input type="hidden" id="overallDiscount" name="discount" value="0">

                                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom px-1">
                                        <span class="text-dark small fw-bold">WHT (Tax)</span>
                                        <div class="d-flex align-items-center gap-1 flex-wrap justify-content-end">
                                            @php
                                                $selectedHeadId = null;
                                                $selectedWhtAcc = null;
                                                if (isset($returnData)) {
                                                    if ($returnData->whtAccount) {
                                                        $selectedHeadId = $returnData->whtAccount->head_id;
                                                        $selectedWhtAcc = $returnData->whtAccount;
                                                    } elseif ($returnData->purchase && $returnData->purchase->whtAccount) {
                                                        $selectedHeadId = $returnData->purchase->whtAccount->head_id;
                                                        $selectedWhtAcc = $returnData->purchase->whtAccount;
                                                    }
                                                }
                                            @endphp
                                            <select id="wht_head_id" class="form-select form-select-sm py-0" style="width:70px;">
                                                <option value="">Head</option>
                                                @if(isset($AccountHeads))
                                                    @foreach($AccountHeads as $head)
                                                        <option value="{{ $head->id }}" {{ $selectedHeadId == $head->id ? 'selected' : '' }}>{{ $head->name }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            <select name="wht_account_id" id="wht_account_id" class="form-select form-select-sm py-0" style="width:100px;">
                                                <option value="">Account</option>
                                                @if($selectedWhtAcc)
                                                    <option value="{{ $selectedWhtAcc->id }}" selected>{{ $selectedWhtAcc->title }}</option>
                                                @endif
                                            </select>
                                            <input type="number" step="0.01" id="whtPercent" name="wht_percent" class="form-control form-control-sm text-end py-0" placeholder="Val" value="{{ $returnData->wht_percent ?? 0 }}" style="width:55px">
                                            <select id="whtType" name="wht_type" class="form-select form-select-sm py-0" style="width:55px;">
                                                <option value="percent" {{ !isset($returnData) || (isset($returnData) && $returnData->wht_type != 'amount') ? 'selected' : '' }}>%</option>
                                                <option value="amount" {{ isset($returnData) && $returnData->wht_type == 'amount' ? 'selected' : '' }}>PKR</option>
                                            </select>
                                            <input type="text" id="whtAmount" class="form-control form-control-sm text-end input-readonly border-0 bg-transparent fw-bold py-0" readonly value="{{ $returnData->wht ?? 0 }}" style="width:70px">
                                            <input type="hidden" id="whtValue" name="wht" value="{{ $returnData->wht ?? 0 }}">
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center py-1 bg-light px-2 rounded mt-1">
                                        <span class="fw-bold text-dark">Net Return</span>
                                        <input type="text" id="netAmount" name="net_amount" class="form-control form-control-sm fw-bold text-end text-danger border-0 bg-transparent py-0" readonly value="{{ $returnData->net_amount ?? 0 }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- BOTTOM BUTTONS (same as Purchase) --}}
                    <div class="d-flex flex-wrap gap-2 justify-content-center bg-light bottom-bar rounded-2 border shadow-sm w-100">

                        <button type="button" id="saveDraftBtn" class="btn btn-primary px-3 fw-bold shadow-sm" {{ $isPosted || $isDraft ? 'disabled' : '' }}>
                            <u>S</u>ave <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>
                        </button>

                        <button type="button" id="editInvoiceBtn" class="btn btn-warning px-3 fw-bold text-dark shadow-sm" {{ $isNew || $isPosted ? 'disabled' : '' }}>
                            <u>E</u>dit <kbd style="font-size:10px;opacity:.8;margin-left:4px;color:#fff;">Ctrl+E</kbd>
                        </button>

                        <button type="button" id="postBtn" class="btn btn-success px-3 fw-bold shadow-sm" {{ $isNew || $isPosted ? 'disabled' : '' }}>
                            <u>P</u>ost <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+&crarr;</kbd>
                        </button>

                        <button type="button" id="deleteBtn" class="btn btn-danger px-3 fw-bold shadow-sm" {{ $isNew || $isPosted ? 'disabled' : '' }}>
                            <u>D</u>elete <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+D</kbd>
                        </button>

                        @if(isset($returnData))
                            <a href="{{ route('purchase.return.print', $returnData->id) }}" target="_blank" id="realPrintBtn" class="btn btn-info px-3 fw-bold text-dark shadow-sm">
                                <u>P</u>rint <kbd style="font-size:10px;opacity:.8;margin-left:4px;color:#fff;">Ctrl+P</kbd>
                            </a>
                        @else
                            <a href="javascript:void(0)" id="realPrintBtn" class="btn btn-info px-3 fw-bold text-dark shadow-sm" onclick="showPreviewModal(); return false;">
                                <u>P</u>rint <kbd style="font-size:10px;opacity:.8;margin-left:4px;color:#fff;">Ctrl+P</kbd>
                            </a>
                        @endif

                        <a href="{{ route('purchase.return.home') }}" id="exitBtn" class="btn btn-secondary px-3 fw-bold shadow-sm text-white">
                            E<u>x</u>it <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Esc</kbd>
                        </a>

                        <a href="{{ route('purchase.return.add') }}" id="newInvoiceBtn" class="btn btn-dark px-3 fw-bold shadow-sm text-white">
                            <u>N</u>ew <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+M</kbd>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Print Preview Modal -->
<div class="modal fade" id="printPreviewModal" tabindex="-1" aria-labelledby="printPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="printPreviewModalLabel">Purchase Return Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="printArea">
                <!-- Preview Content Injected Here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" onclick="printDiv('printArea')">Print</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@if(session('error'))
<script>
    Swal.fire({ icon: 'error', title: 'Error', text: @json(session('error')), confirmButtonColor: '#d33' });
</script>
@endif

@if (session('success'))
<script>
    Swal.fire({ icon: 'success', title: 'Success', text: @json(session('success')), confirmButtonColor: '#3085d6' });
</script>
@endif
<script>
$(document).ready(function() {
    $('.select2').select2({ width: '100%' });

    const vendors = @json($vendors);
    const customers = @json($customers);

    const allPurchases = @json($purchases);
    
    // =============================================
    //  SAVED PURCHASE STATE (after AJAX save)
    // =============================================
    var _savedReturnId = @json(isset($returnData) ? $returnData->id : null);
    var _saveInFlight = false;
    var _postInFlight = false;

    function setFormLocked(isLocked) {
        if (isLocked) {
            $('#returnForm').addClass('form-locked');
            $('#returnForm input, #returnForm select, #returnForm textarea, #returnForm button')
                .not('#editInvoiceBtn, #exitBtn, #realPrintBtn, #newInvoiceBtn, #postBtn, #deleteBtn')
                .attr('tabindex', '-1');
            $('#returnForm .select2').prop('disabled', true);
        } else {
            $('#returnForm').removeClass('form-locked');
            $('#returnForm input, #returnForm select, #returnForm textarea, #returnForm button').removeAttr('tabindex');
            $('#returnForm .select2').prop('disabled', false);
        }
    }

    if (_savedReturnId) {
        setFormLocked(true);
        @if(isset($returnData) && $returnData->status == 'Posted')
             $('#editInvoiceBtn').prop('disabled', true);
             $('#postBtn').prop('disabled', true);
             $('#deleteBtn').prop('disabled', true);
        @endif
        
        $('#purchaseItems tr').each(function() {
            recalcRow($(this));
        });
        recalcSummary();
    }

    function showToast(msg, type) {
        type = type || 'success';
        var icon = type === 'success' ? 'fa-check-circle' : 'fa-times-circle';
        var color = type === 'success' ? '#28a745' : '#dc3545';
        var $toast = $('<div>').css({
            position: 'fixed', top: '20px', right: '20px', zIndex: 9999,
            background: color, color: '#fff',
            padding: '12px 20px', borderRadius: '8px',
            boxShadow: '0 4px 15px rgba(0,0,0,.2)',
            fontSize: '14px', fontWeight: '500',
            display: 'flex', alignItems: 'center', gap: '8px',
            minWidth: '280px'
        }).html('<i class="fa ' + icon + '"></i> ' + msg);
        $('body').append($toast);
        setTimeout(function() { $toast.fadeOut(400, function(){ $(this).remove(); }); }, 3500);
    }
    
    function ajaxSaveDraft() {
        if (_saveInFlight) return;
        if(!$('#party_select').val()) {
            showToast('⚠️ Please select a party', 'error');
            return;
        }
        if($('#purchaseItems tr').length === 0 || $('#purchaseItems .text-muted').length > 0) {
            showToast('⚠️ Please select an invoice or add products first', 'error');
            return;
        }

        var $form = $('#returnForm');
        _saveInFlight = true;
        $('#saveDraftBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Saving...');

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                if(res.success) {
                    _savedReturnId = res.id;
                    showToast('✅ Draft Saved — ' + res.message, 'success');
                    if (res.invoice_no) {
                        $('#invoiceNoDisplay').text('Return No: ' + res.invoice_no);
                    }

                    $('#saveDraftBtn').prop('disabled', true).html('<u>S</u>ave');
                    $('#editInvoiceBtn').prop('disabled', false);
                    $('#postBtn').prop('disabled', false);
                    $('#deleteBtn').prop('disabled', false);

                    setFormLocked(true);

                    $form.attr('action', '/purchase-returns/' + res.id + '/update');

                    $('#realPrintBtn')
                        .attr('href', '/purchase-returns/print/' + res.id)
                        .attr('target', '_blank')
                        .removeAttr('onclick');
                } else {
                    showToast('❌ ' + (res.message || 'Error saving draft.'), 'error');
                    $('#saveDraftBtn').prop('disabled', false).html('<u>S</u>ave <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>');
                }
            },
            error: function(xhr) {
                var msg = 'Save failed.';
                try { msg = JSON.parse(xhr.responseText).message || msg; } catch(e){}
                showToast('❌ ' + msg, 'error');
                $('#saveDraftBtn').prop('disabled', false).html('<u>S</u>ave <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>');
            },
            complete: function() {
                _saveInFlight = false;
            }
        });
    }

    function doPost() {
        if (_postInFlight) return;
        if(!_savedReturnId) {
            showToast('⚠️ Please save draft first before posting.', 'error');
            return;
        }
        _postInFlight = true;
        $('#postBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Posting...');

        $.ajax({
            url: '/purchase-returns/post/' + _savedReturnId,
            type: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': $('input[name="_token"]').val()
            },
            success: function(res) {
                showToast('✅ Return posted successfully! Redirecting...', 'success');
                setTimeout(function() {
                    window.location.href = "{{ route('purchase.return.add') }}";
                }, 2000);
            },
            error: function(xhr) {
                var msg = 'Post failed.';
                try {
                    var r = JSON.parse(xhr.responseText);
                    msg = r.message || r.error || msg;
                } catch(e) {}
                showToast('❌ ' + msg, 'error');
                _postInFlight = false;
                $('#postBtn').prop('disabled', false)
                    .html('<u>P</u>ost <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+&crarr;</kbd>');
            }
        });
    }

    $('#saveDraftBtn').on('click', function(e) { e.preventDefault(); ajaxSaveDraft(); });
    $('#postBtn').on('click', function(e) { e.preventDefault(); doPost(); });
    
    $('#editInvoiceBtn').on('click', function() {
        setFormLocked(false);
        $('#saveDraftBtn').prop('disabled', false).html('<u>S</u>ave <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>');
        $(this).prop('disabled', true);
        $('#postBtn').prop('disabled', true);
        $('#deleteBtn').prop('disabled', true);
    });

    $('#deleteBtn').on('click', function() {
        if (!_savedReturnId) return;
        if (confirm('Are you sure you want to delete this draft?')) {
            $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>...');
            $.ajax({
                url: '/purchase-returns/' + _savedReturnId + '/destroy',
                type: 'DELETE',
                data: { _token: $('input[name="_token"]').val() },
                success: function() {
                    showToast('✅ Return deleted successfully!', 'success');
                    setTimeout(function() { window.location.href = "{{ route('purchase.return.add') }}"; }, 1500);
                },
                error: function() {
                    showToast('❌ Failed to delete.', 'error');
                    $('#deleteBtn').prop('disabled', false).html('<u>D</u>elete <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+D</kbd>');
                }
            });
        }
    });

    // Block Enter in inputs; keyboard shortcuts (single handler — prevents double save/post)
    $(document).on('keydown', '#returnForm input, #returnForm select, #purchaseItems input', function(e) {
        if (e.key === 'Enter' && !$(e.target).is('textarea') && !e.ctrlKey) {
            e.preventDefault();
            return false;
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.ctrlKey) {
            var t = e.target;
            if (t && t.tagName !== 'TEXTAREA' && $(t).closest('#returnForm').length) {
                e.preventDefault();
                return;
            }
        }

        if (e.ctrlKey && (e.key === 's' || e.key === 'S')) {
            e.preventDefault();
            e.stopImmediatePropagation();
            if (!_saveInFlight && !$('#saveDraftBtn').prop('disabled')) {
                $('#saveDraftBtn').click();
            }
        }
        if (e.ctrlKey && (e.key === 'e' || e.key === 'E')) {
            e.preventDefault();
            if (!$('#editInvoiceBtn').prop('disabled')) $('#editInvoiceBtn').click();
        }
        if (e.ctrlKey && e.key === 'Enter') {
            e.preventDefault();
            e.stopImmediatePropagation();
            if (!_postInFlight && !$('#postBtn').prop('disabled')) {
                $('#postBtn').click();
            }
        }
        if (e.ctrlKey && (e.key === 'd' || e.key === 'D')) {
            e.preventDefault();
            if (!$('#deleteBtn').prop('disabled')) $('#deleteBtn').click();
        }
        if (e.key === 'Escape') {
            e.preventDefault();
            window.location.href = $('#exitBtn').attr('href');
        }
        if (e.ctrlKey && (e.key === 'm' || e.key === 'M')) {
            e.preventDefault();
            window.location.href = $('#newInvoiceBtn').attr('href');
        }
        if (e.ctrlKey && (e.key === 'l' || e.key === 'L')) {
            e.preventDefault();
            window.location.href = $('#listBtn').attr('href');
        }
        if (e.ctrlKey && (e.key === 'i' || e.key === 'I')) {
            e.preventDefault();
            if ($('input[name="return_mode"]:checked').val() === 'manual' && typeof appendBlankRow === 'function') {
                appendBlankRow(true);
            }
        }
        if (e.ctrlKey && (e.key === 'p' || e.key === 'P')) {
            e.preventDefault();
            if ($('#realPrintBtn').length && !$('#realPrintBtn').hasClass('disabled')) {
                var href = $('#realPrintBtn').attr('href');
                if (href && href !== 'javascript:void(0)') {
                    window.open(href, '_blank');
                } else {
                    showPreviewModal();
                }
            }
        }
    }, true);

    // Mode Switching
    $('input[name="return_mode"]').on('change', function() {
        let mode = $(this).val();
        if (mode === 'manual') {
            $('.manual-only').show();
            $('.invoice-only').hide();
            $('#purchase_id').val('');
            $('#purchaseItems').empty();
            appendBlankRow(true);
            $('#saveDraftBtn').attr('disabled', false);
        } else {
            $('.manual-only').hide();
            $('.invoice-only').show();
            $('#invoice_col').show();
            $('#purchaseItems').html('<tr><td colspan="11" class="text-center text-muted py-4">No invoice selected yet.</td></tr>');
        }
        recalcSummary();
    });

    $('#vendor_type_select').on('change', function() {
        updatePartyList();
        filterInvoices();
    });

    $('#party_select').on('change', function() {
        if ($('input[name="return_mode"]:checked').val() === 'invoice') {
            filterInvoices();
        }
    });

    function updatePartyList() {
        let type = $('#vendor_type_select').val();
        let list = [];
        
        if (type === 'vendor') {
            list = vendors;
        } else if (type === 'customer') {
            list = customers; 
        } else if (type === 'walkin') {
            list = customers.filter(c => (c.customer_type || '').toLowerCase().includes('walking'));
        }

        let html = '<option value="">Select Party</option>';
        list.forEach(item => {
            html += `<option value="${item.id}">${item.name || item.customer_name}</option>`;
        });
        $('#party_select').html(html).trigger('change.select2');
    }

    function filterInvoices() {
        let type = $('#vendor_type_select').val();
        let partyId = $('#party_select').val();

        if (!type || !partyId) {
            $('#purchase_invoice_select').html('<option value="">Select Invoice</option>').trigger('change.select2');
            return;
        }

        let targetTypeClass = '';
        if (type === 'vendor') targetTypeClass = 'Vendor';
        else if (type === 'customer' || type === 'walkin') targetTypeClass = 'Customer'; 

        let filtered = allPurchases.filter(p => {
            if (!p.purchasable_type) return false;
            return p.purchasable_type.endsWith(targetTypeClass) && p.purchasable_id == partyId;
        });

        let html = '<option value="">Select Invoice</option>';
        if (filtered.length === 0) {
            html += '<option value="" disabled>No invoices found</option>';
        } else {
            filtered.forEach(p => {
                html += `<option value="${p.invoice_no}">${p.invoice_no}</option>`;
            });
        }
        $('#purchase_invoice_select').html(html).trigger('change.select2');
    }

    // --- MANUAL MODE ROW MANAGEMENT (Matching Purchase Form) ---

    window.initProductSelect = function($row) {
        const $select = $row.find('.product-select');
        
        $select.select2({
            placeholder: "Select Product",
            allowClear: true,
            width: '100%',
            ajax: {
                url: "{{ route('search-products') }}",
                dataType: 'json',
                delay: 100,
                data: params => ({ q: params.term }),
                processResults: data => ({
                    results: data.map(item => ({
                        id: item.id,
                        text: item.name,
                        price: item.purchase_net_amount,
                        retail: item.purchase_retail_price,
                        brand: item.brand || (item.brand_relation ? item.brand_relation.name : '') || ''
                    }))
                }),
                cache: true
            },
            minimumInputLength: 1
        });

        // Tab/Enter on Item ID -> Auto-Append Row if last
        $row.find('.item-id-input').on('keydown', function(e) {
            if (e.key === 'Enter' || e.key === 'Tab') {
                const $currentRow = $(this).closest('tr');
                if ($currentRow.is(':last-child')) {
                    appendBlankRow(true, false);
                }
                if (!$(this).val()) {
                    e.preventDefault();
                    $select.select2('open');
                }
            }
        });

        // Sync ID input -> Select2
        $row.find('.item-id-input').on('change', function() {
            const id = $(this).val();
            if (!id) {
                $select.val(null).trigger('change');
                return;
            }
            
            $.getJSON("{{ route('search-products') }}", { q: id }, function(data) {
                // Precise matching prioritize: Exact ID -> Exact Name (Case Insensitive) -> First Result if only 1
                let product = data.find(p => String(p.id) === String(id))
                            || data.find(p => p.name.toLowerCase() === id.toLowerCase());

                if (!product && data.length === 1) {
                    product = data[0];
                }

                if (product) {
                    $select.empty().append(new Option(product.name, product.id, true, true));
                    $select.trigger({
                        type: 'select2:select',
                        params: {
                            data: {
                                id: product.id,
                                text: product.name,
                                price: product.purchase_net_amount,
                                retail: product.purchase_retail_price,
                                brand: product.brand
                            }
                        }
                    });
                } else {
                    $select.val(null).trigger('change');
                    showToast('❌ Product ID not found!', 'error');
                    $row.find('.item-id-input').val('');
                }
            });
        });

        // Handle selection
        $select.on('select2:select', function (e) {
            const data = e.params.data;
            const $currentRow = $(this).closest('tr');

            $currentRow.find('.item-id-input').val(data.id);
            $currentRow.find('.brand-name').val(data.brand || '');
            $currentRow.find('.price').val(data.price).trigger('input');
            $currentRow.find('.retail_price').val(data.retail);
            $currentRow.find('.quantity').val(1).trigger('input');
            $currentRow.find('.discount_percent').val(0);

            setTimeout(() => { $currentRow.find('.quantity').focus().select(); }, 50);
        });
    };

    window.appendBlankRow = function(force = false, focus = true) {
        const lastRow = $('#purchaseItems tr:last');
        if (!force && lastRow.length > 0) {
            const pid = lastRow.find('.product-select').val();
            if(!pid) {
                lastRow.find('.item-id-input').focus();
                return;
            }
        }

        const newRowHtml = `
            <tr>
                <td><input type="text" class="form-control form-control-sm item-id-input text-center" placeholder="ID"></td>
                <td>
                    <select name="product_id[]" class="form-control form-control-sm product-select" style="width: 100%;">
                        <option value="" disabled selected>Select Product</option>
                    </select>
                </td>
                <td><input type="text" name="brand[]" class="form-control form-control-sm brand-name input-readonly" readonly></td>
                <td><input type="number" step="0.01" name="price[]" class="form-control form-control-sm price text-end"></td>
                <td><input type="number" step="0.01" name="retail_price[]" class="form-control form-control-sm retail_price text-end"></td>
                <td>
                    <div class="input-group input-group-sm">
                        <input type="number" step="0.01" min="0" name="discount_percent[]" class="form-control form-control-sm discount_percent text-end" placeholder="%">
                        <span class="input-group-text px-1" style="font-size: 0.7rem;">%</span>
                        <input type="text" name="item_disc_amount[]" class="form-control form-control-sm disc_amount text-end input-readonly" readonly placeholder="Amt">
                    </div>
                </td>
                <td class="invoice-only"><input type="text" class="form-control form-control-sm text-center input-readonly" value="-" readonly></td>
                <td><input type="number" name="qty[]" class="form-control form-control-sm quantity text-center" value="1" min="0"></td>
                <td><input type="number" step="0.01" name="rate[]" class="form-control form-control-sm rate text-end input-readonly" readonly></td>
                <td><input type="text" name="line_total[]" class="form-control form-control-sm row-total text-end fw-bold input-readonly" readonly value="0"></td>
                <td class="text-center"><button type="button" class="btn btn-xs btn-outline-danger remove-row" title="Delete">&times;</button></td>
            </tr>`;
        
        const $row = $(newRowHtml);
        if ($('input[name="return_mode"]:checked').val() === 'manual') {
            $row.find('.invoice-only').hide();
            $row.find('.manual-only').show();
        } else {
            $row.find('.invoice-only').show();
            $row.find('.manual-only').hide();
        }
        $('#purchaseItems').append($row);
        initProductSelect($row);
        
        if (focus) {
            setTimeout(() => { $row.find('.item-id-input').focus(); }, 50);
        }
    };


    // Ctrl+I / manual mode add row handled in global shortcut above

    // Invoice Selection
    $('#purchase_invoice_select').on('change', function() {
        let inv = $(this).val();
        if (!inv) return;

        $.get("{{ url('/purchase-returns/get-purchase') }}/" + inv, function(res) {
            $('#purchaseItems').empty();
            $('#purchase_id').val(res.purchase.id);
            $('#party_name_display').val(res.party_name);
            $('#warehouse_select').val(res.warehouse_id).trigger('change');

            if (res.wht_type === 'percent') {
                 $('#whtType').val('percent');
                 $('#whtPercent').val(res.wht_percent > 0 ? res.wht_percent : (res.wht > 0 ? res.wht : 0));
            } else {
                 $('#whtType').val('amount');
                 $('#whtPercent').val(res.wht);
            }

            if (res.wht_head_id) {
                $('#wht_head_id').val(res.wht_head_id);
                $.ajax({
                    url: "{{ url('/get-accounts-by-head') }}/" + res.wht_head_id,
                    type: "GET",
                    success: function(accs) {
                        var html = '<option value="">Select Account</option>';
                        if (accs && accs.length) {
                            accs.forEach(function(acc) {
                                html += '<option value="' + acc.id + '" ' + (acc.id == res.wht_account_id ? 'selected' : '') + '>' + acc.title + '</option>';
                            });
                        }
                        $('#wht_account_id').html(html);
                    }
                });
            } else {
                $('#wht_head_id').val('');
                $('#wht_account_id').html('<option value="">Account</option>');
            }

            if (res.items.length === 0) {
                $('#purchaseItems').html('<tr><td colspan="11" class="text-danger p-3">This purchase has no items!</td></tr>');
            } else {
                res.items.forEach(item => {
                    appendInvoiceRow(item);
                });
            }
            recalcSummary();
        });
    });

    function appendInvoiceRow(item) {
        let discBase = item.retail_price > 0 ? item.retail_price : item.price;
        let perUnitDisc = (discBase * item.discount_percent) / 100;
        let discAmt = perUnitDisc.toFixed(2);
        let html = `
        <tr>
            <td>
                <input type="text" class="form-control form-control-sm text-center input-readonly" value="${item.product_id}" readonly>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm bg-white" value="${item.product_name}" readonly title="${item.product_name}">
                <input type="hidden" name="product_id[]" value="${item.product_id}">
            </td>
            <td><input type="text" name="brand[]" class="form-control form-control-sm brand-name input-readonly" readonly value="${item.brand || ''}"></td>
            <td><input type="number" step="0.01" name="price[]" class="form-control form-control-sm price text-end input-readonly" readonly value="${item.price}"></td>
            <td><input type="number" step="0.01" name="retail_price[]" class="form-control form-control-sm retail_price text-end input-readonly" value="${item.retail_price}" readonly></td>
            <td>
                <div class="input-group input-group-sm">
                    <input type="number" step="0.01" min="0" name="discount_percent[]" class="form-control form-control-sm discount_percent text-end" value="${item.discount_percent}">
                    <span class="input-group-text px-1" style="font-size: 0.7rem;">%</span>
                    <input type="text" name="item_disc_amount[]" class="form-control form-control-sm disc_amount text-end input-readonly" value="${discAmt}" readonly>
                </div>
            </td>
            <td class="invoice-only"><input type="text" class="form-control form-control-sm text-center input-readonly" value="${item.qty}" readonly></td>
            <td><input type="number" name="qty[]" class="form-control form-control-sm quantity text-center" value="${item.qty}" max="${item.qty}" min="0"></td>
            <td><input type="number" step="0.01" name="rate[]" class="form-control form-control-sm rate text-end input-readonly" readonly></td>
            <td><input type="text" name="line_total[]" class="form-control form-control-sm row-total text-end fw-bold input-readonly" readonly value="0"></td>
            <td class="text-center"><button type="button" class="btn btn-xs btn-outline-danger remove-row" title="Delete">&times;</button></td>
        </tr>`;
        const $row = $(html);
        if ($('input[name="return_mode"]:checked').val() === 'manual') {
            $row.find('.invoice-only').hide();
            $row.find('.manual-only').show();
        } else {
            $row.find('.invoice-only').show();
            $row.find('.manual-only').hide();
        }
        $('#purchaseItems').append($row);
        recalcRow($('#purchaseItems tr:last'));
    }

    $(document).on('click', '.remove-row', function() {
        $(this).closest('tr').remove();
        recalcSummary();
    });

    $(document).on('input change', '.quantity, .price, .discount_percent, #whtPercent, #whtType', function() {
        let row = $(this).closest('tr');
        if (row.length) recalcRow(row);
        recalcSummary();
    });

    function recalcRow($row) {
        let qty = parseFloat($row.find('.quantity').val()) || 0;
        let price = parseFloat($row.find('.price').val()) || 0;
        let retail = parseFloat($row.find('.retail_price').val()) || 0;
        let discPercent = parseFloat($row.find('.discount_percent').val()) || 0;

        let discBase = (retail > 0) ? retail : price;
        let perUnitDisc = (discBase * discPercent) / 100;
        
        let rate = price - perUnitDisc;
        let netAmount = rate * qty;

        $row.find('.rate').val(rate.toFixed(2));
        $row.find('.row-total').val(netAmount.toFixed(2));
        $row.find('.disc_amount').val(perUnitDisc.toFixed(2));
    }

    function recalcSummary() {
        let subtotal = 0;

        $('#purchaseItems tr').each(function() {
            let rowTotal = parseFloat($(this).find('.row-total').val()) || 0;
            subtotal += rowTotal;
        });

        let whtVal = parseFloat($('#whtPercent').val()) || 0;
        let whtType = $('#whtType').val() || 'percent';
        let whtAmt = 0;

        if (whtType === 'percent') {
            whtAmt = Math.max(0, subtotal) * whtVal / 100;
        } else {
            whtAmt = whtVal;
        }

        $('#whtAmount').val(whtAmt.toFixed(2));
        $('#whtValue').val(whtAmt.toFixed(2));
        let net = subtotal + whtAmt;

        $('#subtotal').val(subtotal.toFixed(2));
        $('#overallDiscount').val(0);
        $('#netAmount').val(net.toFixed(2));
    }

    // Ensure correct column visibility based on current mode
    if ($('input[name="return_mode"]:checked').val() === 'manual') {
        $('.invoice-only').hide();
        $('.manual-only').show();
    } else {
        $('.invoice-only').show();
        $('.manual-only').hide();
    }

    // Initialize existing rows
    $('#purchaseItems tr').each(function() {
        if ($(this).find('.product-select').length) {
            initProductSelect($(this));
        }
        recalcRow($(this));
    });
});

// --- Print Preview Functions ---
window.showPreviewModal = function() {
    try {
        const date = $('input[name="current_date"]').val();
        const vendorType = $('#vendor_type_select option:selected').text();
        const vendorName = $('#party_select option:selected').text() || '-';
        const invoiceNo = "{{ $nextInvoice ?? 'RET-XXX' }}"; 

        if(!vendorName || vendorName === 'Select Party') {
            alert('Please select a party first.');
            return;
        }

        let itemsHtml = '';
        $('#purchaseItems tr').each(function(index) {
            const productName = $(this).find('input[type="text"]').first().val();
            const qty = $(this).find('.quantity').val();
            const price = $(this).find('.price').val();
            const total = $(this).find('.row-total').val();

            if(productName && qty) {
                 itemsHtml += `
                    <tr>
                        <td style="padding: 4px; border: 1px solid #ddd; text-align: center;">${index + 1}</td>
                        <td style="padding: 4px; border: 1px solid #ddd;">${productName}</td>
                        <td style="padding: 4px; border: 1px solid #ddd; text-align: center;">${qty}</td>
                        <td style="padding: 4px; border: 1px solid #ddd; text-align: right;">${price}</td>
                        <td style="padding: 4px; border: 1px solid #ddd; text-align: right;">${total}</td>
                    </tr>
                 `;
            }
        });

        const subtotal = $('#subtotal').val();
        const discount = $('#overallDiscount').val();
        const net = $('#netAmount').val();
        const wht = $('#whtAmount').val();

        const html = `
            <div style="font-family: 'Segoe UI', Arial, sans-serif; color: #000; padding: 20px; border: 1px solid #ccc;">
                <div style="text-align: center; margin-bottom: 25px; border-bottom: 3px double #000; padding-bottom: 15px;">
                    <h1 style="margin: 0; font-weight: 800; text-transform: uppercase; font-size: 28px; letter-spacing: 1px;">AL Madina Traders</h1>
                    <div style="font-size: 16px; margin-top: 5px; font-weight: 500;">Deals in: UPS, Solar, Batteries & Electronics</div>
                    <div style="font-size: 15px; margin-top: 3px;"><strong>Phone:</strong> 0300-1234567, 0321-7654321</div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                    <div>
                        <h3 style="margin: 0; font-weight: bold; text-transform: uppercase; border-bottom: 2px solid #000; display: inline-block; padding-bottom: 2px; margin-bottom: 5px;">Purchase Return Receipt</h3>
                        <div style="font-size: 15px; margin-top: 8px;"><strong>Party:</strong> ${vendorName} (${vendorType})</div>
                    </div>
                    <div style="text-align: right;">
                        <h4 style="margin: 0; color: #000; font-weight: bold; font-size: 18px;">Return #${invoiceNo}</h4>
                        <div style="font-size: 15px; margin-top: 8px;"><strong>Date:</strong> ${date}</div>
                    </div>
                </div>

                <table style="width: 100%; border-collapse: collapse; font-size: 14px; margin-bottom: 20px;">
                    <thead>
                        <tr style="background: #f0f0f0; border-top: 2px solid #000; border-bottom: 2px solid #000;">
                            <th style="padding: 8px; border-right: 1px solid #ccc; width: 40px; text-align: center; font-weight: bold;">#</th>
                            <th style="padding: 8px; border-right: 1px solid #ccc; text-align: left; font-weight: bold;">Item Description</th>
                            <th style="padding: 8px; border-right: 1px solid #ccc; width: 80px; text-align: center; font-weight: bold;">Qty</th>
                            <th style="padding: 8px; border-right: 1px solid #ccc; width: 110px; text-align: right; font-weight: bold;">Rate</th>
                            <th style="padding: 8px; width: 130px; text-align: right; font-weight: bold;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${itemsHtml}
                    </tbody>
                    <tfoot>
                         <tr>
                            <td colspan="3" style="border-top: 2px solid #000; padding-top: 15px;">
                                <small style="color: #555;">Generated by System</small>
                            </td>
                            <td style="text-align: right; border-top: 2px solid #000; padding: 10px 5px; font-weight: bold;">Subtotal:</td>
                            <td style="text-align: right; border-top: 2px solid #000; padding: 10px 5px; font-weight: bold;">${subtotal}</td>
                         </tr>

                           <tr>
                            <td colspan="3" style="border: none;"></td>
                            <td style="text-align: right; padding: 5px;">WHT:</td>
                            <td style="text-align: right; padding: 5px;">${wht}</td>
                         </tr>
                         <tr>
                            <td colspan="3" style="border: none;"></td>
                            <td style="text-align: right; padding: 8px 5px; font-weight: bold; font-size: 18px; border-top: 1px solid #ccc; border-bottom: 3px double #000;">Net Total:</td>
                            <td style="text-align: right; padding: 8px 5px; font-weight: bold; font-size: 18px; border-top: 1px solid #ccc; border-bottom: 3px double #000;">${net}</td>
                         </tr>
                    </tfoot>
                </table>
            </div>
        `;

        $('#printArea').html(html);

        const $modal = $('#printPreviewModal');
        if ($modal.length) {
            if (typeof $modal.modal === 'function') {
                $modal.modal('show');
            } else {
                const myModal = new bootstrap.Modal(document.getElementById('printPreviewModal'));
                myModal.show();
            }
        }
    } catch(e) {
        console.error('Error showing preview:', e);
        alert('Error showing preview: ' + e.message);
    }
};

window.printDiv = function(divId) {
    var printContents = document.getElementById(divId).innerHTML;
    var originalContents = document.body.innerHTML;
    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
    window.location.reload(); 
};
    $(document).on('change', '#wht_head_id', function() {
        var headId = $(this).val();
        var $accSelect = $('#wht_account_id');
        var prevSelected = $accSelect.data('selected') || $accSelect.val();

        if (!headId) {
            $accSelect.html('<option value="">Select Account</option>');
            return;
        }

        $.ajax({
            url: "{{ url('/get-accounts-by-head') }}/" + headId,
            type: "GET",
            success: function(res) {
                var html = '<option value="">Select Account</option>';
                if (res && res.length) {
                    res.forEach(function(acc) {
                        var selected = (acc.id == prevSelected) ? 'selected' : '';
                        html += '<option value="' + acc.id + '" ' + selected + '>' + acc.title + '</option>';
                    });
                } else {
                    html = '<option value="">No Accounts Found</option>';
                }
                $accSelect.html(html);
            },
            error: function(err) {
                console.error('AJAX Error:', err.statusText);
            }
        });
    });

    if ($('#wht_head_id').val()) {
        $('#wht_account_id').data('selected', $('#wht_account_id').val());
        $('#wht_head_id').trigger('change');
    }
</script>
@endsection
