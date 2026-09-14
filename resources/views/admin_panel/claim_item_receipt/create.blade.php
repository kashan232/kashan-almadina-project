@extends('admin_panel.layout.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .stock-hold-page.container-fluid { padding: .5rem .75rem !important; }
    .stock-hold-page .main-content-inner { padding: 0 !important; }
    .stock-hold-page .card { margin-bottom: .6rem !important; }
    .stock-hold-page .card-body { padding: .75rem .85rem !important; }
    .stock-hold-page .card-footer { padding: .65rem .85rem !important; }
    .stock-hold-page .row.g-2 { --bs-gutter-x: .6rem; --bs-gutter-y: .4rem; }
    .stock-hold-page .form-label { margin-bottom: .25rem !important; font-size: .82rem !important; font-weight: 600; }
    .stock-hold-page .input-sm,
    .stock-hold-page .form-control,
    .stock-hold-page .form-select { height: 32px !important; min-height: 32px !important; padding: .25rem .5rem !important; font-size: .85rem !important; }
    .stock-hold-page .select2-container .select2-selection--single { height: 32px !important; border: 1px solid #ced4da; }
    .stock-hold-page .select2-container .select2-selection--single .select2-selection__rendered { line-height: 30px !important; padding-left: 8px !important; font-size: .85rem !important; }
    .stock-hold-page .select2-container .select2-selection--single .select2-selection__arrow { height: 30px !important; }
    .stock-hold-page .table td, .stock-hold-page .table th { vertical-align: middle !important; padding: 6px 8px !important; font-size: .85rem !important; }
    .stock-hold-page .table .form-control { height: 30px !important; min-height: 30px !important; padding: 2px 6px !important; font-size: .82rem !important; }
    .stock-hold-page .bottom-bar-btns { gap: .5rem !important; }
    .stock-hold-page .bottom-bar-btns .btn { padding: .35rem .85rem !important; font-size: .85rem !important; }
    .stock-hold-page .badge { font-size: 12px !important; padding: .3rem .65rem !important; }

    .form-locked { position: relative; opacity: 0.8; }
    .form-locked .card-body { pointer-events: none !important; }
    .form-locked input, .form-locked .select2-container--default .select2-selection--single, .form-locked select, .form-locked textarea {
        background-color: #e9ecef !important; cursor: not-allowed !important;
    }
    .form-locked .remove-row, .form-locked #btr_search_btn { display: none !important; }
    .form-locked .save-btn { display: none !important; }
    .form-locked .action-btn { pointer-events: auto !important; opacity: 1 !important; }

    .posted-watermark {
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg);
        font-size: 100px; color: rgba(0, 128, 0, 0.1); font-weight: bold; pointer-events: none; z-index: 1000;
        text-transform: uppercase; border: 10px solid rgba(0, 128, 0, 0.1); padding: 20px; border-radius: 20px; display: none;
    }
    .posted-watermark.show { display: block; }
    .credit-only-field { display: none; }
</style>

@php
    $isCreditEdit = isset($creditVoucher);
    $activeVoucher = $creditVoucher ?? ($voucher ?? null);
    $isViewMode = isset($viewMode) && $viewMode;
    $isPosted = isset($activeVoucher) && $activeVoucher->status === 'Posted';
    
    $formClass = 'position-relative';
    if ($isViewMode || $isPosted) {
        $formClass .= ' form-locked';
    }
    if ($isViewMode) {
        $formClass .= ' view-mode';
    }
    $initialClaimType = $isCreditEdit ? 'credit' : 'receipt';
@endphp

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid stock-hold-page">
            
            {{-- TOP BAR --}}
            <div class="d-flex justify-content-between align-items-center bg-white p-2 mb-2 rounded shadow-sm">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <label class="form-label mb-0 fw-bold text-primary" style="font-size:0.9rem;">
                            <i class="fa fa-list-alt me-1"></i> Claim Type:
                        </label>
                        <select id="claim_type" class="form-select form-select-sm fw-bold border-primary" style="width:180px;" {{ $isViewMode ? 'disabled' : '' }}>
                            <option value="receipt" {{ $initialClaimType === 'receipt' ? 'selected' : '' }}>Item Receipt</option>
                            <option value="credit" {{ $initialClaimType === 'credit' ? 'selected' : '' }}>Credit Note</option>
                        </select>
                    </div>
                    <span id="docTypeBadge" class="badge bg-dark text-white px-3 py-2 rounded-pill shadow-sm" style="font-size:12px; font-weight:bold;">
                        <i class="fa fa-file-text-o me-1"></i> TYPE: {{ $initialClaimType === 'credit' ? 'CREDIT NOTE' : 'ITEM RECEIPT' }}
                    </span>
                    <span id="statusBadge" class="badge {{ $isPosted ? 'bg-success text-white' : (isset($activeVoucher) ? 'bg-info text-white' : 'bg-warning text-dark') }} px-3 py-2 rounded-pill shadow-sm" style="font-size:12px;">
                        <i class="fa {{ $isPosted ? 'fa-check' : 'fa-pencil' }} me-1"></i> {{ isset($activeVoucher) ? $activeVoucher->status : 'New Claim' }}
                    </span>
                    <span id="idBadge" class="badge bg-primary px-3 py-2 rounded-pill shadow-sm" style="{{ isset($activeVoucher) ? '' : 'display:none;' }} font-size:12px;">
                        <i class="fa fa-tag me-1"></i> ID: {{ isset($activeVoucher) ? $activeVoucher->id : 'NEW' }}
                    </span>
                </div>
                <div>
                    <a href="{{ route('claim-item-receipt.index') }}" id="listBtn" class="btn btn-sm btn-outline-secondary rounded-pill px-4 py-1 fw-bold">
                        <i class="fa fa-list me-1"></i> List <kbd style="font-size:9px;opacity:.7;margin-left:4px;">Ctrl+L</kbd>
                    </a>
                </div>
            </div>

            <form action="{{ $initialClaimType === 'credit' ? route('claim-credit-note.ajax-save') : route('claim-item-receipt.ajax-save') }}" method="POST" id="mainClaimForm" class="{{ $formClass }}">
                @csrf
                <input type="hidden" name="action" id="formAction" value="save">
                <input type="hidden" name="id" id="voucher_id" value="{{ $activeVoucher->id ?? '' }}">
                <div class="posted-watermark {{ ($isViewMode && $isPosted) || $isPosted ? 'show' : '' }}" id="postedWatermark">Posted</div>

                {{-- Header Details --}}
                <div class="card shadow-sm mb-2">
                    <div class="card-header py-1 bg-light fw-bold text-primary border-bottom" style="font-size:0.82rem;">
                        <i class="fa fa-info-circle me-1"></i> Section 1: Header & Party Info
                    </div>
                    <div class="card-body">
                        <!-- Row 1: Header Info -->
                        <div class="row g-2 align-items-end mb-2">
                            <div class="col-md-2" style="max-width: 140px;">
                                <label class="form-label small fw-bold text-muted mb-1">Date</label>
                                <input type="date" name="date" class="form-control input-sm" value="{{ $activeVoucher->date ?? date('Y-m-d') }}" required>
                                <input type="hidden" name="entry_date" value="{{ $activeVoucher->entry_date ?? date('Y-m-d') }}">
                                <input type="hidden" name="entry_time" value="{{ $activeVoucher->entry_time ?? date('H:i') }}">
                            </div>
                            <div class="col-md-1" style="max-width: 120px;">
                                <label class="form-label small fw-bold text-muted mb-1">Voucher No</label>
                                <input type="text" class="form-control input-sm fw-bold text-primary bg-light" value="{{ isset($activeVoucher) ? $activeVoucher->voucher_no : 'Auto-Generated' }}" readonly style="font-size: 0.8rem;">
                            </div>
                            <div class="col-md-2" style="max-width: 150px;">
                                <label class="form-label small fw-bold text-danger mb-1"><i class="fa fa-minus-circle"></i> Deduct From (-) Cr</label>
                                <select name="from_warehouse_id" id="from_warehouse_id" class="form-select input-sm" required>
                                    <option value="">Select Stock Source...</option>
                                    @foreach($companyWarehouses as $wh)
                                        <option value="{{ $wh->id }}" {{ (isset($activeVoucher) && $activeVoucher->from_warehouse_id == $wh->id) ? 'selected' : '' }}>{{ $wh->warehouse_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2" id="to_warehouse_col" style="max-width: 150px;">
                                <label class="form-label small fw-bold text-success mb-1"><i class="fa fa-plus-circle"></i> Add To (+) Dr</label>
                                <select name="to_warehouse_id" id="to_warehouse_id" class="form-select input-sm">
                                    <option value="">Select Receipt Wh...</option>
                                    @if(auth()->user()->canAccessShop())
                                        <option value="0" {{ (isset($activeVoucher) && $activeVoucher->to_warehouse_id == 0) ? 'selected' : '' }}>Shop Stock</option>
                                    @endif
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}" {{ (isset($activeVoucher) && $activeVoucher->to_warehouse_id == $wh->id) ? 'selected' : '' }}>{{ $wh->warehouse_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2" style="max-width: 130px;">
                                <label class="form-label small fw-bold text-primary mb-1">Party Type <span class="text-danger">*</span></label>
                                <select name="party_type" id="party_type" class="form-select input-sm" required>
                                    <option value="">Select Type...</option>
                                    <option value="vendor" {{ (isset($activeVoucher) && $activeVoucher->party_type == 'vendor') ? 'selected' : '' }}>Vendor</option>
                                    <option value="customer" {{ (isset($activeVoucher) && $activeVoucher->party_type == 'customer') ? 'selected' : '' }}>Customer</option>
                                    <option value="walking" {{ (isset($activeVoucher) && $activeVoucher->party_type == 'walking') ? 'selected' : '' }}>Walking Customer</option>
                                </select>
                            </div>
                            <div class="col-md">
                                <label class="form-label small fw-bold text-primary mb-1">Supplier / Party Name <span class="text-danger">*</span></label>
                                <select name="party_id" id="party_id" class="form-select select2" required>
                                    <option value="">Select Party...</option>
                                    @if(isset($activeVoucher))
                                        <option value="{{ $activeVoucher->party_id }}" selected>
                                            @if($activeVoucher->party_type == 'vendor')
                                                {{ $activeVoucher->vendor->name ?? 'N/A' }}
                                            @else
                                                {{ $activeVoucher->customer->customer_name ?? 'N/A' }}
                                            @endif
                                        </option>
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-2 credit-only-field">
                                <label class="form-label small fw-bold text-muted mb-1">Remarks</label>
                                <input type="text" name="remarks" class="form-control input-sm" value="{{ $activeVoucher->remarks ?? '' }}" placeholder="Optional notes...">
                            </div>
                        </div>

                        <!-- Row 2: BTR Search & Manual Product Search -->
                        <div class="row g-2 align-items-center">
                            <div class="col-md-5">
                                <div class="card border-primary border-opacity-25 bg-primary bg-opacity-10 p-1 px-2 rounded-3 shadow-sm">
                                    <div class="row g-1 align-items-center">
                                        <div class="col-auto"><i class="fa fa-barcode text-primary fs-5 ms-1"></i></div>
                                        <div class="col">
                                            <div class="input-group input-group-sm">
                                                <input type="text" id="btr_search_input" class="form-control border-primary" placeholder="Enter BTR# (e.g. 22225)...">
                                                <button type="button" id="btr_search_btn" class="btn btn-primary px-2">
                                                    <i class="fa fa-search me-1"></i> BTR#
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if(!$isViewMode)
                            <div class="col-md-7">
                                <div class="card border-success border-opacity-25 bg-success bg-opacity-10 p-1 px-2 rounded-3 shadow-sm">
                                    <div class="row g-1 align-items-center">
                                        <div class="col-auto"><i class="fa fa-plus-circle text-success fs-5 ms-1"></i></div>
                                        <div class="col">
                                            <div class="input-group input-group-sm">
                                                <select id="manual_product_search" class="form-select select2">
                                                    <option value="">Manual Product Search...</option>
                                                    @if(isset($products))
                                                        @foreach($products as $p)
                                                            <option value="{{ $p->id }}" data-name="{{ $p->name }}">{{ $p->id }} - {{ $p->name }}</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                                <button type="button" id="add_manual_item_btn" class="btn btn-success px-2">
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
                <div class="card shadow-sm mb-2">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped mb-0" id="claimItemsTable">
                                <thead class="bg-light text-center">
                                    <tr>
                                        <th style="width:120px;">BTR#</th>
                                        <th style="width:80px;">Item ID</th>
                                        <th>Product Description</th>
                                        <th class="credit-only-field" style="width:100px;">Price</th>
                                        <th class="credit-only-field" style="width:100px;">Retail</th>
                                        <th class="credit-only-field" style="width:140px;">Disc (%) | Amt</th>
                                        <th style="width:100px;">Qty</th>
                                        <th class="credit-only-field" style="width:100px;">Amount</th>
                                        <th class="credit-only-field" style="width:100px;">Total</th>
                                        <th style="width:50px;">Act</th>
                                    </tr>
                                </thead>
                                <tbody id="claimItemRows">
                                    @if($initialClaimType === 'receipt' && isset($voucher))
                                        @foreach($voucher->items as $item)
                                            @php
                                                $priceVal = $item->product->latestPrice->sale_net_amount ?? 0;
                                                $retailVal = $item->product->latestPrice->sale_retail_price ?? $priceVal;
                                            @endphp
                                            <tr>
                                                <td class="text-center"><input type="text" name="btr_no[]" class="form-control input-sm text-center bg-light" value="{{ $item->btr_no }}" readonly></td>
                                                <td class="text-center fw-bold text-primary">{{ $item->product_id }} <input type="hidden" name="product_id[]" value="{{ $item->product_id }}"></td>
                                                <td>{{ $item->product->name ?? 'N/A' }}</td>
                                                <td class="credit-only-field"><input type="number" name="price[]" class="form-control input-sm text-center line-input price" value="{{ number_format($priceVal, 2, '.', '') }}" step="any"></td>
                                                <td class="credit-only-field"><input type="number" name="retail_price[]" class="form-control input-sm text-center line-input retail_price" value="{{ number_format($retailVal, 2, '.', '') }}" step="any"></td>
                                                <td class="credit-only-field">
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" name="discount_percent[]" class="form-control text-center line-input discount_percent" value="0" step="any" placeholder="%">
                                                        <span class="input-group-text px-1" style="font-size: 0.7rem;">%</span>
                                                        <input type="text" name="discount_amount[]" class="form-control text-center bg-light discount_amount" value="0.00" readonly>
                                                    </div>
                                                </td>
                                                <td class="text-center"><input type="number" name="quantity[]" class="form-control input-sm text-center border-success line-input quantity" value="{{ $item->quantity }}" step="any" min="0"></td>
                                                <td class="credit-only-field"><input type="text" name="line_amount[]" class="form-control input-sm text-end bg-light row-rate" value="0.00" readonly></td>
                                                <td class="credit-only-field"><input type="text" name="line_total[]" class="form-control input-sm text-end fw-bold bg-light row-total" value="0.00" readonly></td>
                                                <td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger remove-row p-0"><i class="fa fa-trash fs-5"></i></button></td>
                                            </tr>
                                        @endforeach
                                    @elseif($initialClaimType === 'credit' && isset($cVoucher))
                                        @foreach($cVoucher->items as $cItem)
                                            <tr>
                                                <td class="text-center"><input type="text" name="btr_no[]" class="form-control input-sm text-center bg-light" value="{{ $cItem->btr_no }}" readonly></td>
                                                <td class="text-center fw-bold text-primary">{{ $cItem->product_id }} <input type="hidden" name="product_id[]" value="{{ $cItem->product_id }}"></td>
                                                <td>{{ $cItem->product->name ?? 'N/A' }}</td>
                                                <td class="credit-only-field"><input type="number" name="price[]" class="form-control input-sm text-center line-input price" value="{{ number_format($cItem->price, 2, '.', '') }}" step="any"></td>
                                                <td class="credit-only-field"><input type="number" name="retail_price[]" class="form-control input-sm text-center line-input retail_price" value="{{ number_format($cItem->retail_price, 2, '.', '') }}" step="any"></td>
                                                <td class="credit-only-field">
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" name="discount_percent[]" class="form-control text-center line-input discount_percent" value="{{ $cItem->discount_percent }}" step="any" placeholder="%">
                                                        <span class="input-group-text px-1" style="font-size: 0.7rem;">%</span>
                                                        <input type="text" name="discount_amount[]" class="form-control text-center bg-light discount_amount" value="{{ number_format($cItem->discount_amount, 2, '.', '') }}" readonly>
                                                    </div>
                                                </td>
                                                <td class="text-center"><input type="number" name="qty[]" class="form-control input-sm text-center border-success line-input quantity" value="{{ $cItem->quantity }}" step="any" min="0"></td>
                                                <td class="credit-only-field"><input type="text" name="line_amount[]" class="form-control input-sm text-end bg-light row-rate" value="{{ number_format($cItem->amount, 2, '.', '') }}" readonly></td>
                                                <td class="credit-only-field"><input type="text" name="line_total[]" class="form-control input-sm text-end fw-bold bg-light row-total" value="{{ number_format($cItem->line_total, 2, '.', '') }}" readonly></td>
                                                <td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger remove-row p-0"><i class="fa fa-trash fs-5"></i></button></td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                                <tfoot>
                                    <tr id="receipt_tfoot_row">
                                        <th colspan="3" class="text-end py-2">Grand Total Qty:</th>
                                        <th class="text-center py-2"><span id="total_qty_badge" class="badge bg-secondary">0</span></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Summary Section (Credit Note Only) --}}
                <div class="row credit-only-field" id="creditSummaryCard">
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
                                                @if(strtoupper($head->name) === 'EXPENSE')
                                                    <option value="{{ $head->id }}" {{ (isset($activeVoucher->whtAccount) && $activeVoucher->whtAccount->account_head_id == $head->id) ? 'selected' : '' }}>{{ $head->name }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        <select name="wht_account_id" id="credit_wht_account_id" data-selected="{{ $activeVoucher->wht_account_id ?? '' }}" class="form-select form-select-sm py-0" style="flex-grow:1;">
                                            <option value="">Account</option>
                                            @if(isset($activeVoucher->whtAccount))
                                                <option value="{{ $activeVoucher->whtAccount->id }}" selected>{{ $activeVoucher->whtAccount->title }}</option>
                                            @endif
                                        </select>
                                    </div>
                                    <div class="d-flex align-items-center gap-1">
                                        <input type="number" step="0.01" name="wht_percent" id="credit_wht_percent" class="form-control form-control-sm text-end py-0" placeholder="Val" value="{{ $activeVoucher->wht_percent ?? 0 }}" style="width:60px">
                                        <select id="credit_wht_type" name="wht_type" class="form-select form-select-sm py-0" style="width:60px;">
                                            <option value="percent" {{ (isset($activeVoucher) && ($activeVoucher->wht_type ?? 'percent') == 'percent') ? 'selected' : '' }}>%</option>
                                            <option value="amount" {{ (isset($activeVoucher) && ($activeVoucher->wht_type ?? '') == 'amount') ? 'selected' : '' }}>PKR</option>
                                        </select>
                                    </div>
                                    <input type="text" name="wht_amount" id="credit_wht_amount" class="form-control form-control-sm text-end bg-light" value="{{ number_format($activeVoucher->wht_amount ?? 0, 2, '.', '') }}" readonly style="width:80px;">
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

                {{-- Action Buttons --}}
                <div class="card shadow-sm mt-2 border-0 bg-transparent">
                    <div class="card-footer bg-white border rounded">
                        <div class="d-flex flex-wrap justify-content-center w-100 bottom-bar-btns">
                            <button type="button" id="saveDraftBtn" class="btn btn-primary px-3 fw-bold shadow-sm save-btn action-btn" {{ ($isViewMode || $isPosted) ? 'disabled' : '' }}>
                                <u>S</u>ave <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>
                            </button>
                            <button type="button" id="editBtn" class="btn btn-warning px-3 fw-bold text-dark shadow-sm action-btn" {{ ($isViewMode && !$isPosted) ? '' : 'disabled' }}>
                                <u>E</u>dit <kbd style="font-size:10px;opacity:.8;margin-left:4px;color:#fff;">Ctrl+E</kbd>
                            </button>
                            <button type="button" id="postBtn" class="btn btn-success px-3 fw-bold shadow-sm action-btn" {{ ($isViewMode || $isPosted) ? 'disabled' : '' }}>
                                <u>P</u>ost <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+&crarr;</kbd>
                            </button>
                            <button type="button" id="deleteBtn" class="btn btn-danger px-3 fw-bold shadow-sm action-btn" disabled title="Delete not available">
                                <u>D</u>elete <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+D</kbd>
                            </button>
                            <a href="{{ isset($activeVoucher) ? ($initialClaimType === 'credit' ? url('claim-credit-note/print/'.$activeVoucher->id) : url('claim-item-receipt/print/'.$activeVoucher->id)) : 'javascript:void(0)' }}" id="realPrintBtn" target="_blank" class="btn btn-info px-3 fw-bold text-dark shadow-sm action-btn {{ !isset($activeVoucher) ? 'pe-none opacity-50' : '' }}">
                                <u>P</u>rint <kbd style="font-size:10px;opacity:.8;margin-left:4px;color:#fff;">Ctrl+P</kbd>
                            </a>
                            <a href="{{ route('claim-item-receipt.index') }}" id="exitBtn" class="btn btn-secondary px-3 fw-bold shadow-sm text-white action-btn">
                                E<u>x</u>it <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Esc</kbd>
                            </a>
                            <a href="{{ route('claim-item-receipt.create') }}" id="newBtn" class="btn btn-dark px-3 fw-bold shadow-sm text-white action-btn">
                                <u>N</u>ew <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+M</kbd>
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2').select2({ width: '100%' });
    var _savedId = "{{ $activeVoucher->id ?? '' }}";
    var isViewMode = {{ $isViewMode ? 'true' : 'false' }};
    var isPosted = {{ $isPosted ? 'true' : 'false' }};
    var _saveInFlight = false;
    var _postInFlight = false;
    var saveBtnHtml = '<u>S</u>ave <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>';
    var postBtnHtml = '<u>P</u>ost <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+&crarr;</kbd>';

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

    // --- DYNAMIC CLAIM TYPE TOGGLING ---
    function applyClaimTypeLayout(type) {
        if(type === 'credit') {
            $('#docTypeBadge').html('<i class="fa fa-money me-1"></i> TYPE: CREDIT NOTE');
            $('.credit-only-field').show().css('display', '');
            $('#to_warehouse_col').addClass('d-none');
            $('#to_warehouse_id').prop('required', false);
            $('#mainClaimForm').attr('action', "{{ route('claim-credit-note.ajax-save') }}");
            $('#receipt_tfoot_row').hide();
        } else {
            $('#docTypeBadge').html('<i class="fa fa-file-text-o me-1"></i> TYPE: ITEM RECEIPT');
            $('.credit-only-field').hide();
            $('#to_warehouse_col').removeClass('d-none');
            $('#to_warehouse_id').prop('required', true);
            $('#mainClaimForm').attr('action', "{{ route('claim-item-receipt.ajax-save') }}");
            $('#receipt_tfoot_row').show();
        }
        recalculateTotals();
    }

    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.get('tab') === 'credit') {
        $('#claim_type').val('credit');
    }

    applyClaimTypeLayout($('#claim_type').val());

    $('#claim_type').on('change', function() {
        applyClaimTypeLayout($(this).val());
    });

    // --- PARTY LIST LOADING ---
    function loadParties(type) {
        if(!type) { $('#party_id').empty().append('<option value="">Select Party...</option>'); return; }
        $.get("{{ url('stock-holds/party/list') }}", { type: type }, function(res) {
            var $p = $('#party_id').empty().append('<option value="">Select Party...</option>');
            res.forEach(item => $p.append(`<option value="${item.id}">${item.text}</option>`));
            $p.trigger('change');
        });
    }
    $('#party_type').on('change', function() { loadParties($(this).val()); });

    // --- MANUAL & BTR PRODUCT SEARCH ---
    $('#manual_product_search').select2({ placeholder: 'Manual Product Search...', allowClear: true });

    $('#add_manual_item_btn').on('click', function() {
        let select = $('#manual_product_search');
        let pId = select.val();
        if(!pId) return showToast('Select a product first', 'error');

        let opt = select.find('option:selected');
        let pName = opt.data('name') || '';

        $.get("{{ url('/get-stock') }}/" + pId, function(res) {
            let priceVal = res && res.sales_price ? parseFloat(res.sales_price) : 0;
            let retailVal = res && res.retail_price ? parseFloat(res.retail_price) : priceVal;
            addRow('MANUAL', pId, pName, 1, priceVal, retailVal);
            select.val('').trigger('change');
            showToast('Manual product added.');
        }).fail(function() {
            addRow('MANUAL', pId, pName, 1, 0, 0);
            select.val('').trigger('change');
            showToast('Manual product added.');
        });
    });

    $('#btr_search_btn').on('click', function() {
        var btr = $('#btr_search_input').val();
        if(!btr) return showToast('Please enter a BTR#', 'error');
        $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

        let type = $('#claim_type').val();
        let routeUrl = type === 'credit' ? "{{ route('claim-credit-note.fetch-btr') }}" : "{{ route('claim-item-receipt.fetch-btr') }}";

        $.get(routeUrl, { btr: btr }, function(res) {
            if(res.success) {
                res.data.forEach(item => addRow(item.btr_no, item.product_id, item.product_name, item.quantity, item.price || 0, item.retail_price || 0));
                showToast(res.data.length + ' item(s) attached.');
                $('#btr_search_input').val('');
            } else { showToast(res.message, 'error'); }
        }).always(() => $('#btr_search_btn').prop('disabled', false).html('<i class="fa fa-search me-1"></i> BTR#'));
    });

    function addRow(btr, pid, name, qty, price, retail) {
        var isCredit = $('#claim_type').val() === 'credit';
        var row = `<tr>
            <td class="text-center"><input type="text" name="btr_no[]" class="form-control input-sm text-center bg-light" value="${btr}" readonly></td>
            <td class="text-center fw-bold text-primary">${pid} <input type="hidden" name="product_id[]" value="${pid}"></td>
            <td>${name}</td>
            <td class="credit-only-field"><input type="number" name="price[]" class="form-control input-sm text-center line-input price" value="${parseFloat(price).toFixed(2)}" step="any"></td>
            <td class="credit-only-field"><input type="number" name="retail_price[]" class="form-control input-sm text-center line-input retail_price" value="${parseFloat(retail).toFixed(2)}" step="any"></td>
            <td class="credit-only-field">
                <div class="input-group input-group-sm">
                    <input type="number" name="discount_percent[]" class="form-control text-center line-input discount_percent" value="0" step="any" placeholder="%">
                    <span class="input-group-text px-1" style="font-size: 0.7rem;">%</span>
                    <input type="text" name="discount_amount[]" class="form-control text-center bg-light discount_amount" value="0.00" readonly>
                </div>
            </td>
            <td class="text-center"><input type="number" name="${isCredit ? 'qty[]' : 'quantity[]'}" class="form-control input-sm text-center border-success line-input quantity" value="${qty}" step="any" min="0"></td>
            <td class="credit-only-field"><input type="text" name="line_amount[]" class="form-control input-sm text-end bg-light row-rate" value="0.00" readonly></td>
            <td class="credit-only-field"><input type="text" name="line_total[]" class="form-control input-sm text-end fw-bold bg-light row-total" value="0.00" readonly></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger remove-row p-0"><i class="fa fa-trash fs-5"></i></button></td>
        </tr>`;
        $('#claimItemRows').append(row);
        recalculateTotals();
    }

    $(document).on('click', '.remove-row', function() { $(this).closest('tr').remove(); recalculateTotals(); });
    $(document).on('input', '.line-input, #credit_wht_percent', recalculateTotals);
    $(document).on('change', '#credit_wht_type', recalculateTotals);

    function recalculateTotals() {
        let isCredit = $('#claim_type').val() === 'credit';
        let totalQty = 0;
        let subtotal = 0;
        let totalDisc = 0;

        $('#claimItemRows tr').each(function() {
            let row = $(this);
            let qty = parseFloat(row.find('.quantity').val()) || 0;
            totalQty += qty;

            if (isCredit) {
                let price = parseFloat(row.find('.price').val()) || 0;
                let retailPrice = parseFloat(row.find('.retail_price').val()) || 0;
                let discPct = parseFloat(row.find('.discount_percent').val()) || 0;
                let unitDiscAmt = (retailPrice * discPct) / 100.0;
                row.find('.discount_amount').val(unitDiscAmt.toFixed(2));

                let rate = price - unitDiscAmt;
                row.find('.row-rate').val(rate.toFixed(2));

                let net_line_total = Math.max(0, rate * qty);
                row.find('.row-total').val(net_line_total.toFixed(2));

                subtotal += net_line_total;
                totalDisc += (unitDiscAmt * qty);
            }
        });

        $('#total_qty_badge').text(totalQty.toFixed(2));

        if (isCredit) {
            let whtPctVal = parseFloat($('#credit_wht_percent').val()) || 0;
            let whtType = $('#credit_wht_type').val() || 'percent';
            let netBeforeWHT = subtotal;
            let whtAmt = (whtType === 'percent') ? ((netBeforeWHT * whtPctVal) / 100.0) : whtPctVal;
            let finalNet = netBeforeWHT + whtAmt;

            $('#txtCreditSubtotal').text(subtotal.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}));
            $('#credit_wht_amount').val(whtAmt.toFixed(2));
            $('#txtCreditNetTotal').text(finalNet.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}));

            $('#credit_subtotal').val(subtotal.toFixed(2));
            $('#credit_total_discount').val(totalDisc.toFixed(2));
            $('#credit_net_total').val(finalNet.toFixed(2));
        }
    }

    $(document).on('change', '#credit_wht_head_id', function() {
        var headId = $(this).val();
        var $accSelect = $('#credit_wht_account_id');
        if (!headId) { $accSelect.html('<option value="">Account</option>'); return; }

        $.ajax({
            url: "{{ url('/get-accounts-by-head') }}/" + headId,
            type: "GET",
            success: function(res) {
                var html = '<option value="">Account</option>';
                if (res && res.length) {
                    res.forEach(function(acc) { html += '<option value="' + acc.id + '">' + acc.title + '</option>'; });
                } else { html = '<option value="">No Accounts</option>'; }
                $accSelect.html(html);
            }
        });
    });

    // --- FORM SUBMIT (SAVE & POST) ---
    function submitClaim(act) {
        if (_saveInFlight || _postInFlight) return;
        $('#formAction').val(act);
        if($('#claimItemRows tr').length === 0) { showToast('Add items first', 'error'); return; }

        var $form = $('#mainClaimForm');
        if(!$form[0].checkValidity()) { $form[0].reportValidity(); return; }

        var btn = act === 'post' ? '#postBtn' : '#saveDraftBtn';
        if (act === 'post') _postInFlight = true; else _saveInFlight = true;
        $(btn).prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i>');

        let isCredit = $('#claim_type').val() === 'credit';
        let printBaseUrl = isCredit ? '/claim-credit-note/print/' : '/claim-item-receipt/print/';

        $.ajax({
            url: $form.attr('action'), type: 'POST', data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                if(res.success) {
                    _savedId = res.id;
                    $('#voucher_id').val(res.id);
                    $('#realPrintBtn').attr('href', printBaseUrl + res.id).removeClass('pe-none opacity-50');
                    $('#idBadge').text('ID: ' + res.id).show();

                    if(res.status === 'Posted') {
                        $('#statusBadge').removeClass('bg-warning bg-info').addClass('bg-success text-white').html('<i class="fa fa-check"></i> Posted');
                        $('#postedWatermark').addClass('show');
                        $('#mainClaimForm').addClass('form-locked');
                        $('#editBtn, #postBtn, #saveDraftBtn').prop('disabled', true);
                        showToast('Claim Posted Successfully!');
                    } else {
                        $('#statusBadge').removeClass('bg-warning').addClass('bg-info text-white').html('<i class="fa fa-pencil"></i> Draft');
                        $('#mainClaimForm').addClass('form-locked');
                        $('#editBtn, #postBtn').prop('disabled', false);
                        showToast('Draft Saved — Ctrl+E to edit');
                    }
                } else { showToast(res.message, 'error'); }
            },
            error: () => showToast('Server Error', 'error'),
            complete: function() {
                if (act === 'post') _postInFlight = false; else _saveInFlight = false;
                if (!$('#mainClaimForm').hasClass('form-locked') || act === 'post') {
                    $(btn).prop('disabled', false).html(act === 'post' ? postBtnHtml : saveBtnHtml);
                }
            }
        });
    }

    function doDirectPost() {
        if (_postInFlight || !_savedId) return;
        _postInFlight = true;
        $('#postBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i>');
        let isCredit = $('#claim_type').val() === 'credit';
        let postUrl = isCredit ? "{{ url('claim-credit-note/post') }}/" + _savedId : "{{ url('claim-item-receipt/post') }}/" + _savedId;

        $.ajax({
            url: postUrl,
            type: 'POST',
            data: { _token: $('input[name="_token"]', '#mainClaimForm').val() },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function() {
                $('#statusBadge').removeClass('bg-info').addClass('bg-success text-white').html('<i class="fa fa-check"></i> Posted');
                $('#postedWatermark').addClass('show');
                $('#mainClaimForm').addClass('form-locked');
                $('#editBtn, #postBtn, #saveDraftBtn').prop('disabled', true);
                showToast('Claim Posted Successfully!');
            },
            error: function() {
                showToast('Post failed', 'error');
                _postInFlight = false;
                $('#postBtn').prop('disabled', false).html(postBtnHtml);
            }
        });
    }

    $('#saveDraftBtn').on('click', function(e) { e.preventDefault(); if (!$(this).prop('disabled')) submitClaim('save'); });
    $('#postBtn').on('click', function(e) {
        e.preventDefault();
        if ($(this).prop('disabled')) return;
        if ($('#mainClaimForm').hasClass('form-locked') && _savedId) doDirectPost();
        else submitClaim('post');
    });

    $('#editBtn').on('click', function() {
        if ($(this).prop('disabled')) return;
        if (isViewMode && !isPosted) {
            let isCredit = $('#claim_type').val() === 'credit';
            let editRoute = isCredit ? "{{ isset($activeVoucher) ? route('claim-credit-note.edit', $activeVoucher->id) : '#' }}" : "{{ isset($activeVoucher) ? route('claim-item-receipt.edit', $activeVoucher->id) : '#' }}";
            window.location.href = editRoute;
            return;
        }
        $('#mainClaimForm').removeClass('form-locked');
        $(this).prop('disabled', true);
        $('#postBtn').prop('disabled', true);
        $('#saveDraftBtn').prop('disabled', false).show().html(saveBtnHtml);
    });

    $('#realPrintBtn').on('click', function(e) {
        var href = $(this).attr('href');
        if (!href || href === 'javascript:void(0)') {
            e.preventDefault();
            showToast('Save first', 'error');
        }
    });

    // KEYBOARD SHORTCUTS
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && (e.key === 's' || e.key === 'S')) {
            e.preventDefault(); e.stopImmediatePropagation();
            if(!$('#saveDraftBtn').prop('disabled')) $('#saveDraftBtn').click();
        }
        if (e.ctrlKey && e.key === 'Enter') {
            e.preventDefault(); e.stopImmediatePropagation();
            if(!$('#postBtn').prop('disabled')) $('#postBtn').click();
        }
        if (e.ctrlKey && (e.key === 'p' || e.key === 'P')) {
            e.preventDefault(); $('#realPrintBtn').click();
        }
        if (e.ctrlKey && (e.key === 'e' || e.key === 'E')) {
            e.preventDefault();
            if(!$('#editBtn').prop('disabled')) $('#editBtn').click();
        }
        if (e.ctrlKey && (e.key === 'm' || e.key === 'M')) {
            e.preventDefault(); window.location.href = $('#newBtn').attr('href');
        }
        if (e.ctrlKey && (e.key === 'l' || e.key === 'L')) {
            e.preventDefault(); window.location.href = $('#listBtn').attr('href');
        }
        if (e.key === 'Escape') {
            e.preventDefault(); window.location.href = $('#exitBtn').attr('href');
        }
    }, true);
});
</script>
@endsection
