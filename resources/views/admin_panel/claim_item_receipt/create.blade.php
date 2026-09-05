@extends('admin_panel.layout.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .stock-hold-page.container-fluid { padding: .25rem .4rem !important; }
    .stock-hold-page .main-content-inner { padding: 0 !important; }
    .stock-hold-page .nav-pills { margin-bottom: .35rem !important; padding: .25rem !important; }
    .stock-hold-page .nav-pills .nav-link { padding: .25rem .65rem !important; font-size: .78rem !important; }
    .stock-hold-page .card { margin-bottom: .35rem !important; }
    .stock-hold-page .card-body { padding: .45rem .55rem !important; }
    .stock-hold-page .card-footer { padding: .45rem .55rem !important; }
    .stock-hold-page .row.g-2 { --bs-gutter-x: .4rem; --bs-gutter-y: .25rem; }
    .stock-hold-page .form-label { margin-bottom: .1rem !important; font-size: .72rem !important; }
    .stock-hold-page .input-sm,
    .stock-hold-page .form-control,
    .stock-hold-page .form-select { height: 26px !important; min-height: 26px !important; padding: .1rem .4rem !important; font-size: .78rem !important; }
    .stock-hold-page .select2-container .select2-selection--single { height: 26px !important; border: 1px solid #ced4da; }
    .stock-hold-page .select2-container .select2-selection--single .select2-selection__rendered { line-height: 24px !important; padding-left: 6px !important; font-size: .78rem !important; }
    .stock-hold-page .select2-container .select2-selection--single .select2-selection__arrow { height: 24px !important; }
    .stock-hold-page .table td, .stock-hold-page .table th { vertical-align: middle !important; padding: 2px 4px !important; font-size: .78rem !important; }
    .stock-hold-page .table .form-control { height: 24px !important; min-height: 24px !important; padding: 1px 4px !important; font-size: .75rem !important; }
    .stock-hold-page .bottom-bar-btns { gap: .35rem !important; }
    .stock-hold-page .bottom-bar-btns .btn { padding: .25rem .65rem !important; font-size: .78rem !important; }
    .stock-hold-page .badge { font-size: 11px !important; padding: .2rem .55rem !important; }

    .form-locked { position: relative; opacity: 0.8; }
    .form-locked .card-body { pointer-events: none !important; }
    .form-locked input, .form-locked .select2-container--default .select2-selection--single, .form-locked select, .form-locked textarea {
        background-color: #e9ecef !important; cursor: not-allowed !important;
    }
    .form-locked .remove-row, .form-locked .remove-receipt-row, .form-locked .remove-credit-row,
    .form-locked #receipt_btr_search_btn, .form-locked #credit_btr_search_btn { display: none !important; }
    .form-locked .receipt-save-btn, .form-locked .credit-save-btn { display: none !important; }
    .form-locked .receipt-action-btn, .form-locked .credit-action-btn { pointer-events: auto !important; opacity: 1 !important; }

    .posted-watermark {
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg);
        font-size: 100px; color: rgba(0, 128, 0, 0.1); font-weight: bold; pointer-events: none; z-index: 1000;
        text-transform: uppercase; border: 10px solid rgba(0, 128, 0, 0.1); padding: 20px; border-radius: 20px; display: none;
    }
    .posted-watermark.show { display: block; }
</style>

@php
    $isViewMode = isset($viewMode) && $viewMode;
    $isReceiptPosted = isset($voucher) && $voucher->status === 'Posted';
    $receiptFormClass = 'position-relative';
    if ($isViewMode || $isReceiptPosted) {
        $receiptFormClass .= ' form-locked';
    }
    if ($isViewMode) {
        $receiptFormClass .= ' view-mode';
    }
@endphp

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid stock-hold-page">
            
            {{-- TOP BAR --}}


            {{-- TABS --}}
            <ul class="nav nav-pills mb-2 justify-content-center bg-white p-1 rounded shadow-sm" id="claimTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold px-4 py-1" id="receipt-tab" data-bs-toggle="pill" data-bs-target="#receipt-pane" type="button" role="tab">
                        <i class="fa fa-file-text-o me-2"></i> Item Receipt
                        @if($isViewMode)
                            <span class="badge bg-info ms-1" style="font-size:9px;"><i class="fa fa-eye"></i> View</span>
                        @endif
                    </button>
                </li>
                @if(!$isViewMode)
                <li class="nav-item mx-2" role="presentation">
                    <button class="nav-link fw-bold px-4 py-1" id="credit-tab" data-bs-toggle="pill" data-bs-target="#credit-pane" type="button" role="tab">
                        <i class="fa fa-money me-2"></i> Credit Note
                    </button>
                </li>
                @endif
                <li class="nav-item" role="presentation">
                    <a href="{{ route('claim-item-receipt.index') }}" id="listBtn" class="btn btn-sm btn-outline-secondary rounded-pill px-4 py-1 fw-bold">
                        <i class="fa fa-list me-1"></i> List <kbd style="font-size:9px;opacity:.7;margin-left:4px;">Ctrl+L</kbd>
                    </a>
                </li>
            </ul>

            <div class="tab-content" id="claimTabsContent">
                {{-- ITEM RECEIPT PANE --}}
                <div class="tab-pane fade show active" id="receipt-pane" role="tabpanel">
                    <div class="d-flex align-items-center gap-2 mb-2 justify-content-center">
                        <span id="receiptStatusBadge" class="badge bg-warning text-dark px-3 py-2 rounded-pill shadow-sm" style="font-size:12px;">
                            <i class="fa fa-pencil me-1"></i> {{ isset($voucher) ? $voucher->status : 'New Receipt' }}
                        </span>
                        <span id="receiptIdBadge" class="badge bg-primary px-3 py-2 rounded-pill shadow-sm" style="{{ isset($voucher) ? '' : 'display:none;' }} font-size:12px;">
                            <i class="fa fa-tag me-1"></i> ID: {{ isset($voucher) ? $voucher->id : 'NEW' }}
                        </span>
                    </div>

                    <form action="{{ $isViewMode ? '#' : route('claim-item-receipt.ajax-save') }}" method="POST" id="receiptForm" class="{{ $receiptFormClass }}">
                        @csrf
                        <input type="hidden" name="action" id="receiptFormAction" value="save">
                        <input type="hidden" name="id" value="{{ $voucher->id ?? '' }}">
                        <div class="posted-watermark {{ ($isViewMode && $isReceiptPosted) || $isReceiptPosted ? 'show' : '' }}" id="receiptPostedWatermark">Posted</div>

                        {{-- Header Details --}}
                        <div class="card shadow-sm mb-2">
                            <div class="card-body">
                                <div class="row g-2 mb-3 align-items-end">
                                    <div class="col-md-2">
                                        <label class="form-label small fw-bold text-muted mb-1">Entry Date</label>
                                        <input type="date" name="entry_date" class="form-control input-sm" value="{{ $voucher->entry_date ?? date('Y-m-d') }}" required>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label small fw-bold text-muted mb-1">Entry Time</label>
                                        <input type="time" name="entry_time" class="form-control input-sm" value="{{ $voucher->entry_time ?? date('H:i') }}" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small fw-bold text-muted mb-1">Receipt Date</label>
                                        <input type="date" name="date" class="form-control input-sm" value="{{ $voucher->date ?? date('Y-m-d') }}" required>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label small fw-bold text-muted mb-1">Receipt No</label>
                                        <input type="text" class="form-control input-sm fw-bold text-primary bg-light" value="{{ isset($voucher) ? $voucher->voucher_no : 'Auto-Generated' }}" readonly style="font-size: 0.8rem;">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small fw-bold text-danger mb-1"><i class="fa fa-minus-circle"></i> Deduct From (-) Cr</label>
                                        <select name="from_warehouse_id" id="receipt_from_warehouse_id" class="form-select input-sm" required>
                                            <option value="">Select Stock Source...</option>
                                            @foreach($companyWarehouses as $wh)
                                                <option value="{{ $wh->id }}" {{ (isset($voucher) && $voucher->from_warehouse_id == $wh->id) ? 'selected' : '' }}>{{ $wh->warehouse_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small fw-bold text-success mb-1"><i class="fa fa-plus-circle"></i> Add To (+) Dr</label>
                                        <select name="to_warehouse_id" id="receipt_to_warehouse_id" class="form-select input-sm" required>
                                            <option value="">Select Receipt Wh...</option>
                                            @if(auth()->user()->canAccessShop())
                                                <option value="0" {{ (isset($voucher) && $voucher->to_warehouse_id == 0) ? 'selected' : '' }}>Shop Stock</option>
                                            @endif
                                            @foreach($warehouses as $wh)
                                                <option value="{{ $wh->id }}" {{ (isset($voucher) && $voucher->to_warehouse_id == $wh->id) ? 'selected' : '' }}>{{ $wh->warehouse_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small fw-bold text-muted mb-1">Remarks</label>
                                        <input type="text" name="remarks" class="form-control input-sm" value="{{ $voucher->remarks ?? '' }}" placeholder="Optional notes...">
                                    </div>
                                </div>

                                <div class="row g-2 align-items-end">
                                    <div class="col-md-2">
                                        <label class="form-label small fw-bold text-primary mb-1">Party Type <span class="text-danger">*</span></label>
                                        <select name="party_type" id="receipt_party_type" class="form-select input-sm" required>
                                            <option value="">Select Type...</option>
                                            <option value="vendor" {{ (isset($voucher) && $voucher->party_type == 'vendor') ? 'selected' : '' }}>Vendor</option>
                                            <option value="customer" {{ (isset($voucher) && $voucher->party_type == 'customer') ? 'selected' : '' }}>Customer</option>
                                            <option value="walking" {{ (isset($voucher) && $voucher->party_type == 'walking') ? 'selected' : '' }}>Walking Customer</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold text-primary mb-1">Supplier / Party Name <span class="text-danger">*</span></label>
                                        <select name="party_id" id="receipt_party_id" class="form-select select2" required>
                                            <option value="">Select Party...</option>
                                            @if(isset($voucher))
                                                <option value="{{ $voucher->party_id }}" selected>
                                                    @if($voucher->party_type == 'vendor')
                                                        {{ $voucher->vendor->name ?? 'N/A' }}
                                                    @else
                                                        {{ $voucher->customer->customer_name ?? 'N/A' }}
                                                    @endif
                                                </option>
                                            @endif
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <div class="card border-primary border-opacity-25 bg-primary bg-opacity-10 p-1 px-2 rounded-3 h-100 shadow-sm">
                                            <div class="row g-1 align-items-center">
                                                <div class="col-auto"><i class="fa fa-barcode text-primary fs-5 ms-1"></i></div>
                                                <div class="col">
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" id="receipt_btr_search_input" class="form-control border-primary" placeholder="Enter BTR# (e.g. 22225)...">
                                                        <button type="button" id="receipt_btr_search_btn" class="btn btn-primary px-2">
                                                            <i class="fa fa-search me-1"></i> BTR#
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    @if(!$isViewMode)
                                    <div class="col-md-4">
                                        <div class="card border-success border-opacity-25 bg-success bg-opacity-10 p-1 px-2 rounded-3 h-100 shadow-sm">
                                            <div class="row g-1 align-items-center">
                                                <div class="col-auto"><i class="fa fa-plus-circle text-success fs-5 ms-1"></i></div>
                                                <div class="col">
                                                    <div class="input-group input-group-sm">
                                                        <select id="receipt_manual_product_search" class="form-select select2">
                                                            <option value="">Manual Product Search...</option>
                                                            @if(isset($products))
                                                                @foreach($products as $p)
                                                                    <option value="{{ $p->id }}" data-name="{{ $p->name }}">{{ $p->id }} - {{ $p->name }}</option>
                                                                @endforeach
                                                            @endif
                                                        </select>
                                                        <button type="button" id="receipt_add_manual_item_btn" class="btn btn-success px-2">
                                                            <i class="fa fa-plus me-1"></i> Add Manual
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Table for Items --}}
                        <div class="card shadow-sm">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped mb-0" id="receiptItemsTable">
                                        <thead class="bg-light text-center">
                                            <tr>
                                                <th style="width:150px;">BTR#</th>
                                                <th style="width:100px;">Item ID</th>
                                                <th>Product Description</th>
                                                <th style="width:120px;">Qty Receipt</th>
                                                <th style="width:50px;">Act</th>
                                            </tr>
                                        </thead>
                                        <tbody id="receiptItemRows">
                                            @if(isset($voucher))
                                                @foreach($voucher->items as $item)
                                                    <tr>
                                                        <td class="text-center"><input type="text" name="btr_no[]" class="form-control input-sm text-center bg-light" value="{{ $item->btr_no }}" readonly></td>
                                                        <td class="text-center fw-bold text-primary">{{ $item->product_id }} <input type="hidden" name="product_id[]" value="{{ $item->product_id }}"></td>
                                                        <td>{{ $item->product->name ?? 'N/A' }}</td>
                                                        <td class="text-center"><input type="number" name="quantity[]" class="form-control input-sm text-center border-success" value="{{ $item->quantity }}" step="any" min="0"></td>
                                                        <td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger remove-row p-0"><i class="fa fa-trash fs-5"></i></button></td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="3" class="text-end py-2">Grand Total Qty:</th>
                                                <th class="text-center py-2"><span id="receipt_total_qty_badge" class="badge bg-secondary">0</span></th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer bg-white">
                                <div class="d-flex flex-wrap justify-content-center w-100 bottom-bar-btns">
                                    <button type="button" id="receiptSaveDraftBtn" class="btn btn-primary px-3 fw-bold shadow-sm receipt-save-btn receipt-action-btn" {{ ($isViewMode || $isReceiptPosted) ? 'disabled' : '' }}>
                                        <u>S</u>ave <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>
                                    </button>
                                    <button type="button" id="receiptEditBtn" class="btn btn-warning px-3 fw-bold text-dark shadow-sm receipt-action-btn" {{ ($isViewMode && !$isReceiptPosted) ? '' : 'disabled' }}>
                                        <u>E</u>dit <kbd style="font-size:10px;opacity:.8;margin-left:4px;color:#fff;">Ctrl+E</kbd>
                                    </button>
                                    <button type="button" id="receiptPostBtn" class="btn btn-success px-3 fw-bold shadow-sm receipt-action-btn" {{ ($isViewMode || $isReceiptPosted) ? 'disabled' : '' }}>
                                        <u>P</u>ost <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+&crarr;</kbd>
                                    </button>
                                    <button type="button" id="receiptDeleteBtn" class="btn btn-danger px-3 fw-bold shadow-sm receipt-action-btn" disabled title="Delete not available">
                                        <u>D</u>elete <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+D</kbd>
                                    </button>
                                    <a href="{{ isset($voucher) ? url('claim-item-receipt/print/'.$voucher->id) : 'javascript:void(0)' }}" id="receiptRealPrintBtn" target="_blank" class="btn btn-info px-3 fw-bold text-dark shadow-sm receipt-action-btn {{ !isset($voucher) ? 'pe-none opacity-50' : '' }}">
                                        <u>P</u>rint <kbd style="font-size:10px;opacity:.8;margin-left:4px;color:#fff;">Ctrl+P</kbd>
                                    </a>
                                    <a href="{{ route('claim-item-receipt.index') }}" id="receiptExitBtn" class="btn btn-secondary px-3 fw-bold shadow-sm text-white receipt-action-btn">
                                        E<u>x</u>it <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Esc</kbd>
                                    </a>
                                    <a href="{{ route('claim-item-receipt.create') }}" id="receiptNewBtn" class="btn btn-dark px-3 fw-bold shadow-sm text-white receipt-action-btn">
                                        <u>N</u>ew <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+M</kbd>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- CREDIT NOTE PANE --}}
                <div class="tab-pane fade" id="credit-pane" role="tabpanel">
                    <div class="d-flex align-items-center gap-2 mb-2 justify-content-center">
                        <span id="creditStatusBadge" class="badge bg-warning text-dark px-3 py-2 rounded-pill shadow-sm" style="font-size:12px;">
                            <i class="fa fa-pencil me-1"></i> New Credit Note
                        </span>
                        <span id="creditIdBadge" class="badge bg-primary px-3 py-2 rounded-pill shadow-sm" style="display:none; font-size:12px;">
                            <i class="fa fa-tag me-1"></i> ID: NEW
                        </span>
                    </div>

                    <form action="{{ route('claim-credit-note.ajax-save') }}" method="POST" id="creditForm" class="position-relative">
                        @csrf
                        <input type="hidden" name="action" id="creditFormAction" value="save">
                        <input type="hidden" name="id" value="">
                        <div class="posted-watermark" id="creditPostedWatermark">Posted</div>

                        {{-- Header Details --}}
                        <div class="card shadow-sm mb-2">
                            <div class="card-body">
                                <div class="row g-2 mb-3 align-items-end">
                                    <div class="col-md-2">
                                        <label class="form-label small fw-bold text-muted mb-1">Entry Date</label>
                                        <input type="date" name="entry_date" class="form-control input-sm" value="{{ date('Y-m-d') }}" required>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label small fw-bold text-muted mb-1">Entry Time</label>
                                        <input type="time" name="entry_time" class="form-control input-sm" value="{{ date('H:i') }}" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small fw-bold text-muted mb-1">Date</label>
                                        <input type="date" name="date" class="form-control input-sm" value="{{ date('Y-m-d') }}" required>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label small fw-bold text-muted mb-1">Voucher No</label>
                                        <input type="text" class="form-control input-sm fw-bold text-primary bg-light" value="{{ isset($voucher) ? $voucher->voucher_no : 'Auto-Generated' }}" readonly style="font-size: 0.8rem;">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small fw-bold text-danger mb-1"><i class="fa fa-minus-circle"></i> Deduct From (-) Cr</label>
                                        <select name="from_warehouse_id" id="credit_from_warehouse_id" class="form-select input-sm" required>
                                            <option value="">Select Stock Source...</option>
                                            @foreach($companyWarehouses as $wh)
                                                <option value="{{ $wh->id }}">{{ $wh->warehouse_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2 d-none">
                                        <label class="form-label small fw-bold text-success mb-1"><i class="fa fa-plus-circle"></i> Add To (+) Dr</label>
                                        <select name="to_warehouse_id" id="credit_to_warehouse_id" class="form-select input-sm">
                                            <option value="">Select Target...</option>
                                            @if(auth()->user()->canAccessShop())
                                                <option value="0">Shop Stock</option>
                                            @endif
                                            @foreach($warehouses as $wh)
                                                <option value="{{ $wh->id }}">{{ $wh->warehouse_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small fw-bold text-muted mb-1">Remarks</label>
                                        <input type="text" name="remarks" class="form-control input-sm" placeholder="Optional notes...">
                                    </div>
                                </div>

                                <div class="row g-2 align-items-end">
                                    <div class="col-md-2">
                                        <label class="form-label small fw-bold text-primary mb-1">Party Type <span class="text-danger">*</span></label>
                                        <select name="party_type" id="credit_party_type" class="form-select input-sm" required>
                                            <option value="">Select Type...</option>
                                            <option value="vendor">Vendor</option>
                                            <option value="customer">Customer</option>
                                            <option value="walking">Walking Customer</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold text-primary mb-1">Supplier / Party Name <span class="text-danger">*</span></label>
                                        <select name="party_id" id="credit_party_id" class="form-select select2" required>
                                            <option value="">Select Party...</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="card border-primary border-opacity-25 bg-primary bg-opacity-10 p-1 px-3 rounded-pill h-100 shadow-sm">
                                            <div class="row g-2 align-items-center">
                                                <div class="col-auto"><i class="fa fa-barcode text-primary fs-4"></i></div>
                                                <div class="col">
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" id="credit_btr_search_input" class="form-control border-primary" placeholder="Enter BTR# to fetch claim items...">
                                                        <button type="button" id="credit_btr_search_btn" class="btn btn-primary px-3">
                                                            <i class="fa fa-search me-1"></i> Find BTR#
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Table for Items --}}
                        <div class="card shadow-sm mb-2">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped mb-0" id="creditItemsTable">
                                        <thead class="bg-light text-center" style="font-size:11px;">
                                            <tr>
                                                <th style="width:100px;">BTR#</th>
                                                <th style="width:70px;">Item ID</th>
                                                <th>Product Description</th>
                                                <th style="width:100px;">Price</th>
                                                <th style="width:100px;">Retail</th>
                                                <th style="width:140px;">Disc (%) | Amt</th>
                                                <th style="width:70px;">Qty</th>
                                                <th style="width:100px;">Amount</th>
                                                <th style="width:100px;">Total</th>
                                                <th style="width:40px;">Act</th>
                                            </tr>
                                        </thead>
                                        <tbody id="creditItemRows"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- Summary Section --}}
                        <div class="row">
                            <div class="col-md-7"></div>
                            <div class="col-md-5">
                                <div class="card p-3 shadow-sm" style="background:#f8f9fa;">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted fw-bold">Subtotal:</span>
                                        <span id="txtCreditSubtotal" class="fw-bold">0.00</span>
                                        <input type="hidden" name="subtotal" id="credit_subtotal">
                                    </div>
                                    <input type="hidden" name="total_discount" id="credit_total_discount">
                                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom px-1 mb-2">
                                        <span class="text-muted fw-bold">WHT (Tax):</span>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="d-flex gap-1" style="width:190px;">
                                                <select id="credit_wht_head_id" class="form-select form-select-sm py-0" style="width:80px;">
                                                    <option value="">Head</option>
                                                    @foreach($AccountHeads as $head)
                                                        <option value="{{ $head->id }}">{{ $head->name }}</option>
                                                    @endforeach
                                                </select>
                                                <select name="wht_account_id" id="credit_wht_account_id" class="form-select form-select-sm py-0" style="flex-grow:1;">
                                                    <option value="">Account</option>
                                                </select>
                                            </div>
                                            <div class="d-flex align-items-center gap-1">
                                                <input type="number" step="0.01" name="wht_percent" id="credit_wht_percent" class="form-control form-control-sm text-end py-0" placeholder="Val" value="0" style="width:60px">
                                                <select id="credit_wht_type" name="wht_type" class="form-select form-select-sm py-0" style="width:60px;">
                                                    <option value="percent">%</option>
                                                    <option value="amount">PKR</option>
                                                </select>
                                            </div>
                                            <input type="text" name="wht_amount" id="credit_wht_amount" class="form-control form-control-sm text-end bg-light" value="0.00" readonly style="width:80px;">
                                        </div>
                                    </div>
                                    <hr class="my-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-dark fw-bolder fs-5">Net Total:</span>
                                        <span id="txtCreditNetTotal" class="fs-4 fw-bold text-primary">0.00</span>
                                        <input type="hidden" name="net_total" id="credit_net_total">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card shadow-sm mt-2 border-0 bg-transparent">
                            <div class="card-footer bg-white border rounded">
                                <div class="d-flex flex-wrap justify-content-center w-100 bottom-bar-btns">
                                    <button type="button" id="creditSaveDraftBtn" class="btn btn-primary px-3 fw-bold shadow-sm credit-save-btn credit-action-btn">
                                        <u>S</u>ave <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>
                                    </button>
                                    <button type="button" id="creditEditBtn" class="btn btn-warning px-3 fw-bold text-dark shadow-sm credit-action-btn" disabled>
                                        <u>E</u>dit <kbd style="font-size:10px;opacity:.8;margin-left:4px;color:#fff;">Ctrl+E</kbd>
                                    </button>
                                    <button type="button" id="creditPostBtn" class="btn btn-success px-3 fw-bold shadow-sm credit-action-btn">
                                        <u>P</u>ost <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+&crarr;</kbd>
                                    </button>
                                    <button type="button" id="creditDeleteBtn" class="btn btn-danger px-3 fw-bold shadow-sm credit-action-btn" disabled title="Delete not available">
                                        <u>D</u>elete <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+D</kbd>
                                    </button>
                                    <a href="javascript:void(0)" id="creditRealPrintBtn" class="btn btn-info px-3 fw-bold text-dark shadow-sm credit-action-btn">
                                        <u>P</u>rint <kbd style="font-size:10px;opacity:.8;margin-left:4px;color:#fff;">Ctrl+P</kbd>
                                    </a>
                                    <a href="{{ route('claim-credit-note.index') }}" id="creditExitBtn" class="btn btn-secondary px-3 fw-bold shadow-sm text-white credit-action-btn">
                                        E<u>x</u>it <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Esc</kbd>
                                    </a>
                                    <a href="{{ route('claim-item-receipt.create') }}?tab=credit" id="creditNewBtn" class="btn btn-dark px-3 fw-bold shadow-sm text-white credit-action-btn">
                                        <u>N</u>ew <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+M</kbd>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2').select2({ width: '100%' });
    var _savedReceiptId = "{{ $voucher->id ?? '' }}";
    var isReceiptViewMode = {{ $isViewMode ? 'true' : 'false' }};
    var isReceiptPosted = {{ $isReceiptPosted ? 'true' : 'false' }};
    var _savedCreditId = "";
    var _receiptSaveInFlight = false;
    var _receiptPostInFlight = false;
    var _creditSaveInFlight = false;
    var _creditPostInFlight = false;
    var receiptSaveBtnHtml = '<u>S</u>ave <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>';
    var receiptPostBtnHtml = '<u>P</u>ost <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+&crarr;</kbd>';
    var creditSaveBtnHtml = '<u>S</u>ave <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>';
    var creditPostBtnHtml = '<u>P</u>ost <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+&crarr;</kbd>';

    function showToast(msg, type = 'success') {
        var icon = type === 'success' ? 'fa-check-circle' : 'fa-times-circle';
        var color = type === 'success' ? '#28a745' : '#dc3545';
        var $toast = $('<div>').css({
            position: 'fixed', top: '20px', right: '20px', zIndex: 9999,
            background: color, color: '#fff', padding: '12px 20px', borderRadius: '8px',
            boxShadow: '0 4px 15px rgba(0,0,0,.2)', display: 'flex', alignItems: 'center', gap: '8px'
        }).html('<i class="fa ' + icon + '"></i> ' + msg);
        $('body').append($toast);
        setTimeout(function() { $toast.fadeOut(400, function(){ $(this).remove(); }); }, 3000);
    }

    // --- TAB PARAM HANDLING ---
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.get('tab') === 'credit') {
        $('#credit-tab').click();
    }

    // --- SHARED PARTY LIST LOADING ---
    function loadParties(type, targetSelectId) {
        if(!type) { $(targetSelectId).empty().append('<option value="">Select Party...</option>'); return; }
        $.get("{{ url('stock-holds/party/list') }}", { type: type }, function(res) {
            var $p = $(targetSelectId).empty().append('<option value="">Select Party...</option>');
            res.forEach(item => $p.append(`<option value="${item.id}">${item.text}</option>`));
            $p.trigger('change');
        });
    }
    $('#receipt_party_type').on('change', function() { loadParties($(this).val(), '#receipt_party_id'); });
    $('#credit_party_type').on('change', function() { loadParties($(this).val(), '#credit_party_id'); });

    // --- ITEM RECEIPT LOGIC ---
    $('#receipt_manual_product_search').select2({
        placeholder: 'Manual Product Search...',
        allowClear: true
    });

    $('#receipt_add_manual_item_btn').on('click', function() {
        var $opt = $('#receipt_manual_product_search').find(':selected');
        var id = $opt.val();
        var name = $opt.data('name');
        
        if (!id) {
            showToast('Please select a product first', 'error');
            return;
        }
        
        addReceiptRow('MANUAL', id, name, 1);
        $('#receipt_manual_product_search').val('').trigger('change');
    });

    $('#receipt_btr_search_btn').on('click', function() {
        var btr = $('#receipt_btr_search_input').val();
        if(!btr) return showToast('Please enter a BTR#', 'error');
        $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
        $.get("{{ route('claim-item-receipt.fetch-btr') }}", { btr: btr }, function(res) {
            if(res.success) {
                res.data.forEach(item => addReceiptRow(item.btr_no, item.product_id, item.product_name, item.quantity));
                showToast(res.data.length + ' item(s) attached.');
                $('#receipt_btr_search_input').val('');
            } else { showToast(res.message, 'error'); }
        }).always(() => $('#receipt_btr_search_btn').prop('disabled', false).html('<i class="fa fa-search me-1"></i> Find BTR#'));
    });

    function addReceiptRow(btr, pid, name, qty) {
        var row = `<tr>
            <td class="text-center"><input type="text" name="btr_no[]" class="form-control input-sm text-center bg-light" value="${btr}" readonly></td>
            <td class="text-center fw-bold text-primary">${pid} <input type="hidden" name="product_id[]" value="${pid}"></td>
            <td>${name}</td>
            <td class="text-center"><input type="number" name="quantity[]" class="form-control input-sm text-center border-success" value="${qty}" step="any" min="0"></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger remove-receipt-row p-0"><i class="fa fa-trash fs-5"></i></button></td>
        </tr>`;
        $('#receiptItemRows').append(row);
        updateReceiptTotal();
    }
    $(document).on('click', '.remove-receipt-row', function() { $(this).closest('tr').remove(); updateReceiptTotal(); });
    $(document).on('input', '#receiptItemRows input[name="quantity[]"]', updateReceiptTotal);
    function updateReceiptTotal() {
        var total = 0;
        $('#receiptItemRows input[name="quantity[]"]').each(function() { total += parseFloat($(this).val()) || 0; });
        $('#receipt_total_qty_badge').text(total.toFixed(2));
    }
    updateReceiptTotal();

    function saveReceipt(act) {
        if (_receiptSaveInFlight || _receiptPostInFlight) return;
        $('#receiptFormAction').val(act);
        if($('#receiptItemRows tr').length === 0) { showToast('Add items first', 'error'); return; }
        var $form = $('#receiptForm');
        if(!$form[0].checkValidity()) { $form[0].reportValidity(); return; }
        var btn = act === 'post' ? '#receiptPostBtn' : '#receiptSaveDraftBtn';
        if (act === 'post') _receiptPostInFlight = true; else _receiptSaveInFlight = true;
        $(btn).prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i>');
        $.ajax({
            url: $form.attr('action'), type: 'POST', data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                if(res.success) {
                    _savedReceiptId = res.id;
                    $('[name="id"]', '#receiptForm').val(res.id);
                    $('#receiptRealPrintBtn').attr('href', '/claim-item-receipt/print/' + res.id).removeClass('pe-none opacity-50');
                    $('#receiptIdBadge').text('ID: ' + res.id).show();
                    if(res.status === 'Posted') {
                        $('#receiptStatusBadge').removeClass('bg-warning').addClass('bg-success text-white').html('<i class="fa fa-check"></i> Posted');
                        $('#receiptPostedWatermark').addClass('show');
                        $('#receiptForm').addClass('form-locked');
                        $('#receiptEditBtn, #receiptPostBtn, #receiptSaveDraftBtn').prop('disabled', true);
                        showToast('Receipt Posted Successfully!');
                    } else {
                        $('#receiptStatusBadge').removeClass('bg-warning').addClass('bg-info text-white').html('<i class="fa fa-pencil"></i> Draft');
                        $('#receiptForm').addClass('form-locked');
                        $('#receiptEditBtn, #receiptPostBtn').prop('disabled', false);
                        showToast('Draft Saved — Ctrl+E to edit');
                    }
                } else { showToast(res.message, 'error'); }
            },
            error: () => showToast('Server Error', 'error'),
            complete: function() {
                if (act === 'post') _receiptPostInFlight = false; else _receiptSaveInFlight = false;
                if (!$('#receiptForm').hasClass('form-locked') || act === 'post') {
                    $(btn).prop('disabled', false).html(act === 'post' ? receiptPostBtnHtml : receiptSaveBtnHtml);
                }
            }
        });
    }

    function doReceiptPost() {
        if (_receiptPostInFlight || !_savedReceiptId) return;
        _receiptPostInFlight = true;
        $('#receiptPostBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i>');
        $.ajax({
            url: "{{ url('claim-item-receipt/post') }}/" + _savedReceiptId,
            type: 'POST',
            data: { _token: $('input[name="_token"]', '#receiptForm').val() },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function() {
                $('#receiptStatusBadge').removeClass('bg-info').addClass('bg-success text-white').html('<i class="fa fa-check"></i> Posted');
                $('#receiptPostedWatermark').addClass('show');
                $('#receiptForm').addClass('form-locked');
                $('#receiptEditBtn, #receiptPostBtn, #receiptSaveDraftBtn').prop('disabled', true);
                showToast('Receipt Posted Successfully!');
            },
            error: function() {
                showToast('Post failed', 'error');
                _receiptPostInFlight = false;
                $('#receiptPostBtn').prop('disabled', false).html(receiptPostBtnHtml);
            }
        });
    }

    $('#receiptSaveDraftBtn').on('click', function(e) { e.preventDefault(); if (!$(this).prop('disabled')) saveReceipt('save'); });
    $('#receiptPostBtn').on('click', function(e) {
        e.preventDefault();
        if ($(this).prop('disabled')) return;
        if ($('#receiptForm').hasClass('form-locked') && _savedReceiptId) doReceiptPost();
        else saveReceipt('post');
    });
    $('#receiptEditBtn').on('click', function() {
        if ($(this).prop('disabled')) return;
        if (isReceiptViewMode && !isReceiptPosted) {
            window.location.href = "{{ isset($voucher) ? route('claim-item-receipt.edit', $voucher->id) : '#' }}";
            return;
        }
        $('#receiptForm').removeClass('form-locked');
        $(this).prop('disabled', true);
        $('#receiptPostBtn').prop('disabled', true);
        $('#receiptSaveDraftBtn').prop('disabled', false).show().html(receiptSaveBtnHtml);
    });
    $('#receiptRealPrintBtn').on('click', function(e) {
        var href = $(this).attr('href');
        if (!href || href === 'javascript:void(0)') {
            e.preventDefault();
            showToast('Save first', 'error');
        }
    });

    // --- CREDIT NOTE LOGIC ---
    $('#credit_btr_search_btn').on('click', function() {
        var btr = $('#credit_btr_search_input').val();
        if(!btr) return showToast('Please enter a BTR#', 'error');
        $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
        $.get("{{ route('claim-credit-note.fetch-btr') }}", { btr: btr }, function(res) {
            if(res.success) {
                res.data.forEach(item => addCreditRow(item));
                showToast(res.data.length + ' item(s) attached.');
                $('#credit_btr_search_input').val('');
            } else { showToast(res.message, 'error'); }
        }).always(() => $('#credit_btr_search_btn').prop('disabled', false).html('<i class="fa fa-search me-1"></i> Find BTR#'));
    });

    function addCreditRow(item) {
        var row = `<tr>
            <td class="text-center"><input type="text" name="btr_no[]" class="form-control form-control-sm text-center bg-light" value="${item.btr_no}" readonly></td>
            <td class="text-center fw-bold text-primary">${item.product_id} <input type="hidden" name="product_id[]" value="${item.product_id}"></td>
            <td>${item.product_name} <small class="text-muted d-block">${item.brand_name}</small></td>
            <td><input type="number" name="price[]" class="form-control form-control-sm text-center credit-line-input price" value="${parseFloat(item.price).toFixed(2)}" step="any"></td>
            <td><input type="number" name="retail_price[]" class="form-control form-control-sm text-center credit-line-input retail_price" value="${parseFloat(item.retail_price).toFixed(2)}" step="any"></td>
            <td>
                <div class="input-group input-group-sm">
                    <input type="number" name="discount_percent[]" class="form-control text-center credit-line-input discount_percent" value="0" step="any" placeholder="%">
                    <span class="input-group-text px-1" style="font-size: 0.7rem;">%</span>
                    <input type="text" name="discount_amount[]" class="form-control text-center bg-light discount_amount" value="0.00" readonly>
                </div>
            </td>
            <td><input type="number" name="qty[]" class="form-control form-control-sm text-center credit-line-input quantity" value="${item.quantity}" step="any"></td>
            <td><input type="text" name="line_amount[]" class="form-control form-control-sm text-end bg-light row-rate" value="0.00" readonly></td>
            <td><input type="text" name="line_total[]" class="form-control form-control-sm text-end fw-bold bg-light row-total" value="0.00" readonly></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger remove-credit-row p-0"><i class="fa fa-trash"></i></button></td>
        </tr>`;
        $('#creditItemRows').append(row);
        calculateCredit();
    }
    $(document).on('click', '.remove-credit-row', function() { $(this).closest('tr').remove(); calculateCredit(); });
    $(document).on('input', '.credit-line-input, #credit_wht_percent', calculateCredit);

    function calculateCredit() {
        let subtotal = 0;
        let totalDisc = 0;
        $('#creditItemRows tr').each(function() {
            let row = $(this);
            let price = parseFloat(row.find('.price').val()) || 0;
            let retailPrice = parseFloat(row.find('.retail_price').val()) || 0;
            let qty = parseFloat(row.find('.quantity').val()) || 0;
            
            // Discount calculated on Unit Retail Price (qty 1)
            let discPct = parseFloat(row.find('.discount_percent').val()) || 0;
            let unitDiscAmt = (retailPrice * discPct) / 100.0;
            row.find('.discount_amount').val(unitDiscAmt.toFixed(2));

            // Rate (Amount column) = Price - Unit Discount
            let rate = price - unitDiscAmt;
            row.find('.row-rate').val(rate.toFixed(2));

            // Total (Line Total) = Rate * Qty
            let net_line_total = Math.max(0, rate * qty);
            row.find('.row-total').val(net_line_total.toFixed(2));
            
            subtotal += net_line_total;
            totalDisc += (unitDiscAmt * qty);
        });
        
        let whtPctVal = parseFloat($('#credit_wht_percent').val()) || 0;
        let whtType = $('#credit_wht_type').val() || 'percent';
        let netBeforeWHT = subtotal; // subtotal already has discounts subtracted
        
        let whtAmt = 0;
        if(whtType === 'percent') {
            whtAmt = (netBeforeWHT * whtPctVal) / 100.0;
        } else {
            whtAmt = whtPctVal;
        }
        
        let finalNet = netBeforeWHT + whtAmt;
        
        $('#txtCreditSubtotal').text(subtotal.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}));
        $('#credit_wht_amount').val(whtAmt.toFixed(2));
        $('#txtCreditNetTotal').text(finalNet.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}));
        
        $('#credit_subtotal').val(subtotal.toFixed(2));
        $('#credit_total_discount').val(totalDisc.toFixed(2));
        $('#credit_net_total').val(finalNet.toFixed(2));
    }
    
    $(document).on('input', '#credit_wht_percent', calculateCredit);
    $(document).on('change', '#credit_wht_type', calculateCredit);

    $(document).on('change', '#credit_wht_head_id', function() {
        var headId = $(this).val();
        var $accSelect = $('#credit_wht_account_id');

        if (!headId) {
            $accSelect.html('<option value="">Account</option>');
            return;
        }

        $.ajax({
            url: "{{ url('/get-accounts-by-head') }}/" + headId,
            type: "GET",
            success: function(res) {
                var html = '<option value="">Account</option>';
                if (res && res.length) {
                    res.forEach(function(acc) {
                        html += '<option value="' + acc.id + '">' + acc.title + '</option>';
                    });
                } else {
                    html = '<option value="">No Accounts</option>';
                }
                $accSelect.html(html);
            },
            error: function(err) {
                console.error('AJAX Error:', err.statusText);
            }
        });
    });

    function saveCredit(act) {
        if (_creditSaveInFlight || _creditPostInFlight) return;
        $('#creditFormAction').val(act);
        if($('#creditItemRows tr').length === 0) { showToast('Add items first', 'error'); return; }
        var $form = $('#creditForm');
        if(!$form[0].checkValidity()) { $form[0].reportValidity(); return; }
        var btn = act === 'post' ? '#creditPostBtn' : '#creditSaveDraftBtn';
        if (act === 'post') _creditPostInFlight = true; else _creditSaveInFlight = true;
        $(btn).prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i>');
        $.ajax({
            url: $form.attr('action'), type: 'POST', data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                if(res.success) {
                    _savedCreditId = res.id;
                    $('[name="id"]', '#creditForm').val(res.id);
                    $('#creditRealPrintBtn').attr('href', '/claim-credit-note/print/' + res.id);
                    $('#creditIdBadge').text('ID: ' + res.id).show();
                    if(res.status === 'Posted') {
                        $('#creditStatusBadge').removeClass('bg-warning').addClass('bg-success text-white').html('<i class="fa fa-check"></i> Posted');
                        $('#creditPostedWatermark').addClass('show');
                        $('#creditForm').addClass('form-locked');
                        $('#creditEditBtn, #creditPostBtn, #creditSaveDraftBtn').prop('disabled', true);
                        showToast('Credit Note Posted Successfully!');
                    } else {
                        $('#creditStatusBadge').removeClass('bg-warning').addClass('bg-info text-white').html('<i class="fa fa-pencil"></i> Draft');
                        $('#creditForm').addClass('form-locked');
                        $('#creditEditBtn, #creditPostBtn').prop('disabled', false);
                        showToast('Draft Saved — Ctrl+E to edit');
                    }
                } else { showToast(res.message, 'error'); }
            },
            error: () => showToast('Server Error', 'error'),
            complete: function() {
                if (act === 'post') _creditPostInFlight = false; else _creditSaveInFlight = false;
                if (!$('#creditForm').hasClass('form-locked') || act === 'post') {
                    $(btn).prop('disabled', false).html(act === 'post' ? creditPostBtnHtml : creditSaveBtnHtml);
                }
            }
        });
    }
    $('#creditSaveDraftBtn').on('click', function(e) { e.preventDefault(); if (!$(this).prop('disabled')) saveCredit('save'); });
    $('#creditPostBtn').on('click', function(e) { e.preventDefault(); if (!$(this).prop('disabled')) saveCredit('post'); });
    $('#creditRealPrintBtn').on('click', function(e) {
        var href = $(this).attr('href');
        if (!href || href === 'javascript:void(0)') {
            e.preventDefault();
            showToast('Save first', 'error');
        }
    });
    $('#creditEditBtn').on('click', function() {
        if ($(this).prop('disabled')) return;
        $('#creditForm').removeClass('form-locked');
        $(this).prop('disabled', true);
        $('#creditPostBtn').prop('disabled', true);
        $('#creditSaveDraftBtn').prop('disabled', false).show().html(creditSaveBtnHtml);
    });

    @if($isViewMode)
    $('#receiptForm').addClass('form-locked view-mode');
    $('#receiptSaveDraftBtn, #receiptPostBtn').prop('disabled', true);
    $('#receiptEditBtn').prop('disabled', isReceiptPosted);
    @elseif(isset($voucher) && $voucher->status == 'Posted')
    $('#receiptEditBtn, #receiptPostBtn, #receiptSaveDraftBtn').prop('disabled', true);
    @endif

    // --- KEYBOARD SHORTCUTS ---
    document.addEventListener('keydown', function(e) {
        var activeTab = $('.nav-link.active').attr('id');

        if (e.ctrlKey && (e.key === 's' || e.key === 'S')) {
            e.preventDefault(); e.stopImmediatePropagation();
            if(activeTab === 'receipt-tab' && !$('#receiptSaveDraftBtn').prop('disabled')) $('#receiptSaveDraftBtn').click();
            else if(activeTab === 'credit-tab' && !$('#creditSaveDraftBtn').prop('disabled')) $('#creditSaveDraftBtn').click();
        }
        if (e.ctrlKey && e.key === 'Enter') {
            e.preventDefault(); e.stopImmediatePropagation();
            if(activeTab === 'receipt-tab' && !$('#receiptPostBtn').prop('disabled')) $('#receiptPostBtn').click();
            else if(activeTab === 'credit-tab' && !$('#creditPostBtn').prop('disabled')) $('#creditPostBtn').click();
        }
        if (e.ctrlKey && (e.key === 'p' || e.key === 'P')) {
            e.preventDefault();
            if(activeTab === 'receipt-tab') $('#receiptRealPrintBtn').click();
            else $('#creditRealPrintBtn').click();
        }
        if (e.ctrlKey && (e.key === 'e' || e.key === 'E')) {
            e.preventDefault();
            if(activeTab === 'receipt-tab' && !$('#receiptEditBtn').prop('disabled')) $('#receiptEditBtn').click();
            else if(activeTab === 'credit-tab' && !$('#creditEditBtn').prop('disabled')) $('#creditEditBtn').click();
        }
        if (e.ctrlKey && (e.key === 'm' || e.key === 'M')) {
            e.preventDefault();
            if(activeTab === 'receipt-tab') window.location.href = $('#receiptNewBtn').attr('href');
            else window.location.href = $('#creditNewBtn').attr('href');
        }
        if (e.ctrlKey && (e.key === 'l' || e.key === 'L')) {
            e.preventDefault();
            window.location.href = $('#listBtn').attr('href');
        }
        if (e.key === 'Escape') {
            e.preventDefault();
            if(activeTab === 'receipt-tab') window.location.href = $('#receiptExitBtn').attr('href');
            else window.location.href = $('#creditExitBtn').attr('href');
        }
    }, true);

    // Top List Button Behavior
    $('#listBtn').on('click', function(e) {
        e.preventDefault();
        var activeTab = $('.nav-link.active').attr('id');
        if(activeTab === 'receipt-tab') window.location.href = "{{ route('claim-item-receipt.index') }}";
        else window.location.href = "{{ route('claim-credit-note.index') }}";
    });
});
</script>
@endsection
