@extends('admin_panel.layout.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container .select2-selection--single { height: 31px !important; border: 1px solid #ced4da; }
    .select2-container .select2-selection--single .select2-selection__rendered { line-height: 31px !important; padding-left: 8px; }
    .select2-container .select2-selection--single .select2-selection__arrow { height: 31px !important; }
    .input-sm { height: 31px; padding: 2px 8px; font-size: 14px; }
    .table td, .table th { vertical-align: middle !important; padding: 4px !important; }
    
    .form-locked { position: relative; opacity: 0.8; }
    .form-locked .card-body { pointer-events: none !important; }
    .form-locked input, .form-locked .select2-container--default .select2-selection--single, .form-locked select, .form-locked textarea { 
        background-color: #e9ecef !important; cursor: not-allowed !important; 
    }
    .form-locked .remove-row, .form-locked #addItemBtn, .form-locked #btr_search_btn { display: none !important; }
    
    .posted-watermark {
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg);
        font-size: 100px; color: rgba(0, 128, 0, 0.1); font-weight: bold; pointer-events: none; z-index: 1000;
        text-transform: uppercase; border: 10px solid rgba(0, 128, 0, 0.1); padding: 20px; border-radius: 20px; display: none;
    }
    .posted-watermark.show { display: block; }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-2">
            
            {{-- TOP BAR --}}


            {{-- TABS --}}
            <ul class="nav nav-pills mb-2 justify-content-center bg-white p-1 rounded shadow-sm" id="claimTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold px-4 py-1" id="receipt-tab" data-bs-toggle="pill" data-bs-target="#receipt-pane" type="button" role="tab">
                        <i class="fa fa-file-text-o me-2"></i> Item Receipt
                    </button>
                </li>
                <li class="nav-item mx-2" role="presentation">
                    <button class="nav-link fw-bold px-4 py-1" id="credit-tab" data-bs-toggle="pill" data-bs-target="#credit-pane" type="button" role="tab">
                        <i class="fa fa-money me-2"></i> Credit Note
                    </button>
                </li>
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

                    <form action="{{ route('claim-item-receipt.ajax-save') }}" method="POST" id="receiptForm" class="position-relative {{ (isset($voucher) && $voucher->status == 'Posted') ? 'form-locked' : '' }}">
                        @csrf
                        <input type="hidden" name="action" id="receiptFormAction" value="save">
                        <input type="hidden" name="id" value="{{ $voucher->id ?? '' }}">
                        <div class="posted-watermark {{ (isset($voucher) && $voucher->status == 'Posted') ? 'show' : '' }}" id="receiptPostedWatermark">Posted</div>

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
                                    
                                    <div class="col-md-6">
                                        <div class="card border-primary border-opacity-25 bg-primary bg-opacity-10 p-1 px-3 rounded-pill h-100 shadow-sm">
                                            <div class="row g-2 align-items-center">
                                                <div class="col-auto"><i class="fa fa-barcode text-primary fs-4"></i></div>
                                                <div class="col">
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" id="receipt_btr_search_input" class="form-control border-primary" placeholder="Enter BTR# to fetch items (e.g. 22225)...">
                                                        <button type="button" id="receipt_btr_search_btn" class="btn btn-primary px-3">
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
                            <div class="card-footer bg-white py-3">
                                <div class="d-flex gap-2 justify-content-end">
                                    <button type="button" id="receiptSaveDraftBtn" class="btn btn-sm btn-warning rounded-pill px-4 shadow-sm" style="{{ (isset($voucher) && $voucher->status == 'Posted') ? 'display:none;' : '' }}">
                                        <i class="fa fa-floppy-o me-1"></i> Save Draft <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>
                                    </button>

                                    <button type="button" id="receiptPreviewPrintBtn" class="btn btn-sm btn-outline-dark rounded-pill px-4" style="{{ isset($voucher) ? '' : 'display:none;' }}">
                                        <i class="fa fa-print me-1"></i> Print Preview <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+P</kbd>
                                    </button>

                                    <button type="button" id="receiptPostBtn" class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm" style="{{ (isset($voucher) && $voucher->status == 'Posted') ? 'display:none;' : '' }}">
                                        <i class="fa fa-send me-1"></i> Save & Post <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+&#8629;</kbd>
                                    </button>

                                    <button type="button" id="receiptEditBtn" class="btn btn-sm btn-warning rounded-pill px-4 shadow-sm" style="{{ (isset($voucher) && $voucher->status == 'Posted') ? '' : 'display:none;' }}">
                                        <i class="fa fa-pencil me-1"></i> Edit <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+E</kbd>
                                    </button>

                                    <a href="{{ route('claim-item-receipt.create') }}" id="receiptNewBtn" class="btn btn-sm btn-info rounded-pill px-4 shadow-sm text-white" style="{{ isset($voucher) ? '' : 'display:none;' }}">
                                        <i class="fa fa-plus me-1"></i> New <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+M</kbd>
                                    </a>

                                    <a href="{{ route('claim-item-receipt.index') }}" id="receiptCancelBtn" class="btn btn-sm btn-danger rounded-pill px-4 shadow-sm text-white">
                                        <i class="fa fa-times me-1"></i> Cancel <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Esc</kbd>
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

                        <div class="card shadow-sm mt-3 border-0 bg-transparent">
                            <div class="card-footer bg-white py-3 border rounded">
                                <div class="d-flex gap-2 justify-content-end">
                                    <button type="button" id="creditSaveDraftBtn" class="btn btn-sm btn-warning rounded-pill px-4 shadow-sm">
                                        <i class="fa fa-floppy-o me-1"></i> Save Draft <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>
                                    </button>

                                    <button type="button" id="creditPreviewPrintBtn" class="btn btn-sm btn-outline-dark rounded-pill px-4" style="display:none;">
                                        <i class="fa fa-print me-1"></i> Print Preview <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+P</kbd>
                                    </button>

                                    <button type="button" id="creditPostBtn" class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm">
                                        <i class="fa fa-send me-1"></i> Save & Post <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+&#8629;</kbd>
                                    </button>

                                    <button type="button" id="creditEditBtn" class="btn btn-sm btn-warning rounded-pill px-4 shadow-sm" style="display:none;">
                                        <i class="fa fa-pencil me-1"></i> Edit <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+E</kbd>
                                    </button>

                                    <a href="{{ route('claim-item-receipt.create') }}?tab=credit" id="creditNewBtn" class="btn btn-sm btn-info rounded-pill px-4 shadow-sm text-white" style="display:none;">
                                        <i class="fa fa-plus me-1"></i> New <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+M</kbd>
                                    </a>

                                    <a href="{{ route('claim-credit-note.index') }}" id="creditCancelBtn" class="btn btn-sm btn-danger rounded-pill px-4 shadow-sm text-white">
                                        <i class="fa fa-times me-1"></i> Cancel <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Esc</kbd>
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
    var _savedCreditId = "";

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
        $('#receiptFormAction').val(act);
        if($('#receiptItemRows tr').length === 0) { showToast('Add items first', 'error'); return; }
        var $form = $('#receiptForm');
        if(!$form[0].checkValidity()) { $form[0].reportValidity(); return; }
        var btn = act === 'post' ? '#receiptPostBtn' : '#receiptSaveDraftBtn';
        $(btn).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
        $.ajax({
            url: $form.attr('action'), type: 'POST', data: $form.serialize(),
            success: function(res) {
                if(res.success) {
                    _savedReceiptId = res.id;
                    $('[name="id"]', '#receiptForm').val(res.id);
                    $('#receiptForm').addClass('form-locked');
                    $('#receiptSaveDraftBtn').hide();
                    $('#receiptPreviewPrintBtn, #receiptEditBtn, #receiptNewBtn').show();
                    $('#receiptIdBadge').text('ID: ' + res.id).show();
                    if(res.status === 'Posted') {
                        $('#receiptPostBtn').hide();
                        $('#receiptStatusBadge').removeClass('bg-warning').addClass('bg-success text-white').html('<i class="fa fa-check"></i> Posted');
                        $('#receiptPostedWatermark').addClass('show');
                        showToast('Receipt Posted Successfully!');
                    } else {
                        $('#receiptPostBtn').show();
                        $('#receiptStatusBadge').removeClass('bg-warning').addClass('bg-info text-white').html('<i class="fa fa-pencil"></i> Draft');
                        showToast('Receipt Draft Saved');
                    }
                } else { showToast(res.message, 'error'); }
            },
            error: () => showToast('Server Error', 'error'),
            complete: () => $(btn).prop('disabled', false).html(act==='post'?'<i class="fa fa-send me-1"></i> Save & Post':'<i class="fa fa-floppy-o me-1"></i> Save Draft')
        });
    }
    $('#receiptSaveDraftBtn').click(() => saveReceipt('save'));
    $('#receiptPostBtn').click(() => saveReceipt('post'));
    $('#receiptPreviewPrintBtn').click(() => {
        if(!_savedReceiptId) return showToast('Save first', 'error');
        window.open("/claim-item-receipt/print/" + _savedReceiptId, "_blank");
    });
    $('#receiptEditBtn').click(function() {
        $('#receiptForm').removeClass('form-locked');
        $('#receiptSaveDraftBtn, #receiptPostBtn').show();
        $(this).hide();
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
        $('#creditFormAction').val(act);
        if($('#creditItemRows tr').length === 0) { showToast('Add items first', 'error'); return; }
        var $form = $('#creditForm');
        if(!$form[0].checkValidity()) { $form[0].reportValidity(); return; }
        var btn = act === 'post' ? '#creditPostBtn' : '#creditSaveDraftBtn';
        $(btn).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
        $.ajax({
            url: $form.attr('action'), type: 'POST', data: $form.serialize(),
            success: function(res) {
                if(res.success) {
                    _savedCreditId = res.id;
                    $('[name="id"]', '#creditForm').val(res.id);
                    $('#creditForm').addClass('form-locked');
                    $('#creditSaveDraftBtn').hide();
                    $('#creditPreviewPrintBtn, #creditEditBtn, #creditNewBtn').show();
                    $('#creditIdBadge').text('ID: ' + res.id).show();
                    if(res.status === 'Posted') {
                        $('#creditPostBtn').hide();
                        $('#creditStatusBadge').removeClass('bg-warning').addClass('bg-success text-white').html('<i class="fa fa-check"></i> Posted');
                        $('#creditPostedWatermark').addClass('show');
                        showToast('Credit Note Posted Successfully!');
                    } else {
                        $('#creditPostBtn').show();
                        $('#creditStatusBadge').removeClass('bg-warning').addClass('bg-info text-white').html('<i class="fa fa-pencil"></i> Draft');
                        showToast('Credit Note Draft Saved');
                    }
                } else { showToast(res.message, 'error'); }
            },
            error: () => showToast('Server Error', 'error'),
            complete: () => $(btn).prop('disabled', false).html(act==='post'?'<i class="fa fa-send me-1"></i> Save & Post':'<i class="fa fa-floppy-o me-1"></i> Save Draft')
        });
    }
    $('#creditSaveDraftBtn').click(() => saveCredit('save'));
    $('#creditPostBtn').click(() => saveCredit('post'));
    $('#creditPreviewPrintBtn').click(() => {
        if(!_savedCreditId) return showToast('Save first', 'error');
        window.open("/claim-credit-note/print/" + _savedCreditId, "_blank");
    });
    $('#creditEditBtn').click(function() {
        $('#creditForm').removeClass('form-locked');
        $('#creditSaveDraftBtn, #creditPostBtn').show();
        $(this).hide();
    });

    // --- KEYBOARD SHORTCUTS ---
    $(document).on('keydown', function(e) {
        var activeTab = $('.nav-link.active').attr('id');
        
        // Ctrl+S: Save Draft
        if(e.ctrlKey && (e.key === 's' || e.key === 'S')) { 
            e.preventDefault(); 
            if(activeTab === 'receipt-tab') $('#receiptSaveDraftBtn:visible').click();
            else $('#creditSaveDraftBtn:visible').click();
        }
        
        // Ctrl+Enter: Post
        if(e.ctrlKey && e.key === 'Enter') { 
            e.preventDefault(); 
            if(activeTab === 'receipt-tab') $('#receiptPostBtn:visible').click();
            else $('#creditPostBtn:visible').click();
        }

        // Ctrl+P: Print
        if(e.ctrlKey && (e.key === 'p' || e.key === 'P')) {
            e.preventDefault();
            if(activeTab === 'receipt-tab') $('#receiptPreviewPrintBtn:visible').click();
            else $('#creditPreviewPrintBtn:visible').click();
        }

        // Ctrl+E: Edit
        if(e.ctrlKey && (e.key === 'e' || e.key === 'E')) {
            e.preventDefault();
            if(activeTab === 'receipt-tab') $('#receiptEditBtn:visible').click();
            else $('#creditEditBtn:visible').click();
        }

        // Ctrl+M: New
        if(e.ctrlKey && (e.key === 'm' || e.key === 'M')) {
            e.preventDefault();
            if(activeTab === 'receipt-tab') window.location.href = "{{ route('claim-item-receipt.create') }}";
            else window.location.href = "{{ route('claim-item-receipt.create') }}?tab=credit";
        }

        // Ctrl+L: List
        if(e.ctrlKey && (e.key === 'l' || e.key === 'L')) {
            e.preventDefault();
            if(activeTab === 'receipt-tab') window.location.href = "{{ route('claim-item-receipt.index') }}";
            else window.location.href = "{{ route('claim-credit-note.index') }}";
        }

        // Esc: Cancel
        if(e.key === 'Escape') {
            if(activeTab === 'receipt-tab') window.location.href = "{{ route('claim-item-receipt.index') }}";
            else window.location.href = "{{ route('claim-credit-note.index') }}";
        }
    });

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
