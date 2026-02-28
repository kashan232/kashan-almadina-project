@extends('admin_panel.layout.app')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
                @media print {
                    body * {
                        visibility: hidden;
                    }
                    /* Modal content wrapper */
                    .modal-content, .modal-content * {
                        visibility: visible;
                    }
                    /* Position fixed for print */
                    .modal-content {
                        position: absolute;
                        left: 0;
                        top: 0;
                        width: 100%;
                        border: none !important;
                        box-shadow: none !important;
                    }
                    /* Hide modal header/footer buttons if desired, or keep them hidden via class */
                    .modal-header, .modal-footer {
                        display: none !important; 
                    }
                     .btn, .badge, .page-title, .d-flex.justify-content-between, .alert {
                        display: none !important;
                    }
                }
    /* Select2 customizations to match theme */
    .select2-container .select2-selection--single {
        height: 31px !important;
        border: 1px solid #ced4da;
    }
    .select2-container .select2-selection--single .select2-selection__rendered {
        line-height: 31px !important;
        padding-left: 8px;
    }
    .select2-container .select2-selection--single .select2-selection__arrow {
        height: 31px !important;
    }
    th {
        font-weight: 500 !important;
    }
    .posted-watermark {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-30deg);
        font-size: 100px;
        color: rgba(255, 0, 0, 0.1);
        font-weight: bold;
        pointer-events: none;
        z-index: 1000;
        text-transform: uppercase;
        border: 10px solid rgba(255, 0, 0, 0.1);
        padding: 20px;
        border-radius: 20px;
    }
    .posted-bg {
        background-color: #fcfcfc !important;
    }
    {{ isset($purchase) && $purchase->status == 'Posted' ? 'input, select, textarea, button[type="submit"] { pointer-events: none; opacity: 0.8; } .remove-row, .removeAccountRow, #addRow, #addAccountRow { display: none !important; }' : '' }}
</style>
@section('content')
<div class="main-content bg-white">
    <div class="main-content-inner">
        <div class="row">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"
                rel="stylesheet">

            <style>
                .table-scroll tbody {
                    display: block;
                    max-height: calc(60px * 5);
                    overflow-y: auto;
                }

                .table-scroll thead,
                .table-scroll tbody tr {
                    display: table;
                    width: 100%;
                    table-layout: fixed;
                }

                .table-scroll thead {
                    width: calc(100% - 1em);
                }

                .table-scroll .icon-col {
                    width: 51px;
                    min-width: 51px;
                    max-width: 40px;
                }

                .table-scroll {
                    max-height: none !important;
                    overflow-y: visible !important;
                }

                .disabled-row input {
                    background-color: #f8f9fa;
                    pointer-events: none;
                }
            </style>

            <body>
                <div class="body-wrapper">
                    <div class="bodywrapper__inner">
                        <div class="d-flex justify-content-between align-items-center mb-3 bg-light p-2 rounded shadow-sm">

                            {{-- LEFT: Post button --}}
                            <div class="d-flex align-items-center" style="min-width:80px;">
                                @if(isset($purchase) && $purchase->status != 'Posted')
                                    <form id="topPostForm" action="{{ route('purchase.post', $purchase->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm">
                                            <i class="bi bi-send-fill me-1"></i> Post
                                        </button>
                                    </form>
                                @else
                                    {{-- placeholder so layout doesn't shift on create page --}}
                                    <span></span>
                                @endif
                            </div>

                            {{-- CENTER: Title + Draft badge + Invoice --}}
                            <div class="d-flex align-items-center gap-2 justify-content-center flex-grow-1">
                                <h6 class="page-title mb-0 fw-bold">{{ isset($purchase) ? 'Edit Purchase' : 'Create Purchase' }}</h6>
                                <span class="badge bg-primary px-3 py-2 rounded-pill shadow-sm" style="font-size:12px;">
                                    <i class="bi bi-receipt me-1"></i> {{ $nextInvoice }}
                                </span>
                            </div>

                            {{-- RIGHT: List button --}}
                            <div class="d-flex align-items-center justify-content-end" style="min-width:80px;">
                                <a href="{{ route('Purchase.home') }}" id="listBtn" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                                    <i class="bi bi-list me-1"></i> List
                                    <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+L</kbd>
                                </a>
                            </div>

                        </div>

                        <div class="row gy-3 ">
                            <div class="col-lg-12 col-md-12 mb-30 m-auto">
                                <div class="card position-relative {{ isset($purchase) && $purchase->status == 'Posted' ? 'posted-bg' : '' }}">
                                    @if(isset($purchase) && $purchase->status == 'Posted')
                                        <div class="posted-watermark">Posted</div>
                                    @endif
                                    <div class="card-body  ml-2">

                                        @if (session('success'))
                                        <div class="alert alert-success alert-dismissible fade show"
                                            role="alert">
                                            <strong>Success!</strong> {{ session('success') }}
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"
                                                aria-label="Close"></button>
                                        </div>
                                        @endif

                                        <form id="purchaseForm" action="{{ isset($purchase) ? route('purchase.update', $purchase->id) : route('store.Purchase') }}" method="POST">
                                            @csrf
                                            @if(isset($purchase))
                                                @method('PUT')
                                            @endif
                                            <table class="table table-bordered table-sm text-center align-middle">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Current Date</th>
                                                        <th>DC Date</th>
                                                        <th>Type</th>
                                                        <th>Vendor</th>
                                                        <th>DC #</th>
                                                        <th>Warehouse</th>
                                                        <th>Bilty No</th>
                                                        <th>Remarks</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>
                                                            <input name="current_date" value="{{ old('current_date', isset($purchase) ? \Carbon\Carbon::parse($purchase->current_date)->format('Y-m-d') : date('Y-m-d')) }}"
                                                                 type="date" class="form-control form-control-sm" required>
                                                             @error('current_date')
                                                                 <div class="alert alert-danger p-1 mt-1" style="font-size: 12px;">{{ $message }}</div>
                                                             @enderror
                                                         </td>
                                                        <td><input name="dc_date" value="{{ old('dc_date', isset($purchase) ? \Carbon\Carbon::parse($purchase->dc_date)->format('Y-m-d') : date('Y-m-d')) }}"
                                                                 type="date" class="form-control form-control-sm">
                                                             @error('dc_date')
                                                                 <div class="alert alert-danger p-1 mt-1" style="font-size: 12px;">{{ $message }}</div>
                                                             @enderror
                                                         </td>
                                                        <td>
                                                            @php
                                                                $vType = old('vendor_type', isset($purchase) ? strtolower(class_basename($purchase->purchasable_type)) : '');
                                                            @endphp
                                                            <select name="vendor_type" class="form-control form-control-sm" id="vendor_type_select">
                                                                <option value="" {{ $vType ? '' : 'selected' }} disabled>Select</option>
                                                                <option value="vendor" {{ $vType == 'vendor' ? 'selected' : '' }}>Vendor</option>
                                                                <option value="customer" {{ $vType == 'customer' ? 'selected' : '' }}>Customer</option>
                                                                <option value="walkin" {{ $vType == 'walkin' ? 'selected' : '' }}>Walkin Customer</option>
                                                            </select>
                                                            @error('vendor_type')
                                                                <div class="alert alert-danger p-1 mt-1" style="font-size: 12px;">{{ $message }}</div>
                                                            @enderror
                                                        </td>

                                                        <td>
                                                            <select name="vendor_id" id="vendor_id_select" class="form-control form-control-sm" style="width:100%; min-width: 150px;">
                                                                <option value="" disabled selected>Select Party</option>
                                                                @if(isset($purchase))
                                                                    <option value="{{ $purchase->purchasable_id }}" selected>
                                                                        {{ $purchase->purchasable->name ?? ($purchase->purchasable->customer_name ?? 'Unknown') }}
                                                                    </option>
                                                                @endif
                                                            </select>
                                                            @error('vendor_id')
                                                                <div class="alert alert-danger p-1 mt-1" style="font-size: 12px;">{{ $message }}</div>
                                                            @enderror
                                                        </td>
                                                        <td><input name="dc" type="text" value="{{ old('dc', $purchase->dc ?? '') }}"
                                                                class="form-control form-control-sm" style="width:90px;">
                                                            @error('dc')
                                                                <div class="alert alert-danger p-1 mt-1" style="font-size: 12px;">{{ $message }}</div>
                                                            @enderror
                                                        </td>
                                                        <td>
                                                            <select name="warehouse_id" class="form-control form-control-sm">
                                                                <option value="" disabled {{ old('warehouse_id', $purchase->warehouse_id ?? '') ? '' : 'selected' }}>Select</option>
                                                                @foreach ($Warehouse as $ware)
                                                                <option value="{{ $ware->id }}" {{ (string)old('warehouse_id', $purchase->warehouse_id ?? '') === (string)$ware->id ? 'selected' : '' }}>
                                                                    {{ $ware->warehouse_name }}
                                                                </option>
                                                                @endforeach
                                                            </select>
                                                            @error('warehouse_id')
                                                                <div class="alert alert-danger p-1 mt-1" style="font-size: 12px;">{{ $message }}</div>
                                                            @enderror
                                                        </td>
                                                        <td>
                                                            <input name="bilty_no" type="text" value="{{ old('bilty_no', $purchase->bilty_no ?? '') }}"
                                                                class="form-control form-control-sm" style="width:90px;">
                                                            @error('bilty_no')
                                                                <div class="alert alert-danger p-1 mt-1" style="font-size: 12px;">{{ $message }}</div>
                                                            @enderror
                                                        </td>
                                                        <td><input name="remarks" type="text" value="{{ old('remarks', $purchase->note ?? '') }}" class="form-control form-control-sm">
                                                            @error('remarks')
                                                                <div class="alert alert-danger p-1 mt-1" style="font-size: 12px;">{{ $message }}</div>
                                                            @enderror
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                             <table
                                                 class="table table-bordered table-sm text-center align-middle mt-2">
                                                 <thead class="table-light">
                                                     <tr>
                                                         <th>Item ID</th>
                                                        <th>Product</th>
                                                        <th>Brand</th>
                                                        <th>Price</th>
                                                        <th>Retail Price</th> <!-- ✅ New column -->
                                                        <th>Disc</th>
                                                        <th>Qty</th>
                                                        <th>Amount</th>
                                                        <th>Total</th>
                                                        <th class="text-center">
                                                            Act <kbd style="font-size: 8px; opacity: 0.7;">Ctrl+X</kbd>
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody id="purchaseItems">
                                                    @if(isset($purchase) && $purchase->items->count() > 0 && !old('product_id'))
                                                        @foreach($purchase->items as $index => $item)
                                                            @php
                                                                $product = $item->product;
                                                                $retail = $product?->latestPrice?->purchase_retail_price ?? 0;
                                                                $net = $product?->latestPrice?->purchase_net_amount ?? 0;
                                                                $gross = ($item->price ?? 0) * ($item->qty ?? 0);
                                                                $disc_amt = $gross * (($item->item_discount ?? 0) / 100);
                                                            @endphp
                                                            <tr>
                                                                <td style="width: 100px;">
                                                                    <input type="text" class="form-control form-control-sm item-id-input" placeholder="ID" value="{{ $item->product_id }}">
                                                                </td>
                                                                <td style="width: 250px;">
                                                                    <select name="product_id[]" class="form-control form-control-sm product-select" style="width: 100%;">
                                                                        <option value="{{ $item->product_id }}" selected>{{ $product?->name ?? 'Unknown' }}</option>
                                                                    </select>
                                                                    <input type="hidden" name="product_name[]" class="product_name_hidden" value="{{ $product?->name }}">
                                                                </td>
                                                                <td class="uom border">
                                                                    <input type="text" name="brand[]" class="form-control form-control-sm brand-name" readonly value="{{ $product?->brandRelation?->name ?? '' }}">
                                                                </td>
                                                                <td>
                                                                    <input type="number" step="0.01" name="price[]" class="form-control form-control-sm price" value="{{ $item->price }}">
                                                                </td>
                                                                <td>
                                                                    <input type="text" name="retail_price_show[]" class="form-control form-control-sm retail_price_show" readonly value="{{ $retail }}">
                                                                </td>
                                                                <td>
                                                                    <div class="input-group">
                                                                        <input type="number" step="0.01" min="0" name="item_disc[]" class="form-control form-control-sm item_disc" placeholder="%" value="{{ $item->item_discount }}">
                                                                        <input type="text" name="item_disc_amount[]" class="form-control form-control-sm disc_amount" readonly placeholder="Disc Amt" value="{{ number_format($disc_amt, 2, '.', '') }}">
                                                                    </div>
                                                                    <input type="hidden" name="purchase_retail_price[]" class="purchase_retail_price" value="{{ $retail }}">
                                                                    <input type="hidden" name="purchase_net_amount[]" class="purchase_net_amount" value="{{ $net }}">
                                                                </td>
                                                                <td>
                                                                    <input type="number" name="qty[]" class="form-control form-control-sm quantity" value="{{ $item->qty }}" min="1">
                                                                </td>
                                                                <td>
                                                                    <input type="text" name="amount[]" class="form-control form-control-sm row-amount" readonly value="{{ number_format($gross, 2, '.', '') }}">
                                                                </td>
                                                                <td>
                                                                    <input type="text" name="total[]" class="form-control form-control-sm row-total" readonly value="{{ number_format($item->line_total, 2, '.', '') }}">
                                                                </td>
                                                                <td>
                                                                    <button type="button" class="btn btn-sm btn-danger remove-row">X</button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    @elseif(!old('product_id'))
                                                        {{-- Default blank row for new purchase --}}
                                                        <tr>
                                                            <td style="width:100px;">
                                                                <input type="text" class="form-control form-control-sm item-id-input" placeholder="ID">
                                                            </td>
                                                            <td style="width: 250px;">
                                                                <select name="product_id[]" class="form-control form-control-sm product-select" style="width: 100%;">
                                                                    <option value="" disabled selected>Select Product</option>
                                                                </select>
                                                                <input type="hidden" name="product_name[]" class="product_name_hidden">
                                                            </td>
                                                            <td class="uom border">
                                                                <input type="text" name="brand[]" class="form-control form-control-sm brand-name" readonly>
                                                            </td>
                                                            <td><input type="number" step="0.01" name="price[]" class="form-control form-control-sm price"></td>
                                                            <td>
                                                                <input type="text" name="retail_price_show[]" class="form-control form-control-sm retail_price_show" readonly>
                                                            </td>
                                                            <td>
                                                                <div class="input-group">
                                                                    <input type="number" step="0.01" min="0" name="item_disc[]" class="form-control form-control-sm item_disc" placeholder="%">
                                                                    <input type="text" name="item_disc_amount[]" class="form-control form-control-sm disc_amount" readonly placeholder="Disc Amt">
                                                                </div>
                                                                <input type="hidden" name="purchase_retail_price[]" class="purchase_retail_price">
                                                                <input type="hidden" name="purchase_net_amount[]" class="purchase_net_amount">
                                                            </td>
                                                            <td>
                                                                <input type="number" name="qty[]" class="form-control form-control-sm quantity" value="" min="1">
                                                            </td>
                                                            <td>
                                                                <input type="text" name="amount[]" class="form-control form-control-sm row-amount" readonly>
                                                            </td>
                                                            <td>
                                                                <input type="text" name="total[]" class="form-control form-control-sm row-total" readonly>
                                                            </td>
                                                            <td><button type="button" class="btn btn-sm btn-danger remove-row">X</button></td>
                                                        </tr>
                                                    @endif
                                                </tbody>
                                            </table>
                                            <div class="row mt-2">
                                                <!-- Accounts Allocation -->
                                                <div class="col-md-6">
                                                    <div class="card h-100">

                                                        <div class="card-header p-2 bg-light fw-bold d-flex justify-content-between align-items-center">
                                                            <span>Accounts Allocation</span>
                                                            <button type="button" id="addAccountRow" class="btn btn-sm btn-primary">
                                                                + Add
                                                            </button>
                                                        </div>

                                                        <div class="card-body p-2">
                                                            <table class="table table-bordered table-sm text-center align-middle" id="accountsTable">
                                                                <thead class="table-light">
                                                                    <tr>
                                                                        <th>Account Head</th>
                                                                        <th>Account</th>
                                                                        <th>Amount</th>
                                                                        <th>X</th>
                                                                    </tr>
                                                                </thead>
                                                                    <tbody id="accountsTableBody">
                                                                        @if(isset($purchase) && $purchase->accountAllocations->count() > 0 && !old('account_head_id'))
                                                                            @foreach($purchase->accountAllocations as $index => $acc)
                                                                                <tr>
                                                                                    <td>
                                                                                        <select name="account_head_id[]" class="form-control form-control-sm accountHead">
                                                                                            <option value="" disabled>Select Head</option>
                                                                                            @foreach ($AccountHeads as $head)
                                                                                                <option value="{{ $head->id }}" {{ $head->id == $acc->account_head_id ? 'selected' : '' }}>{{ $head->name }}</option>
                                                                                            @endforeach
                                                                                        </select>
                                                                                    </td>
                                                                                    <td>
                                                                                        <select name="account_id[]" class="form-control form-control-sm accountSub">
                                                                                            <option value="{{ $acc->account_id }}" selected>{{ $acc->account->title ?? 'Unknown Account' }}</option>
                                                                                        </select>
                                                                                    </td>
                                                                                    <td>
                                                                                        <input type="number" step="0.01" name="account_amount[]" class="form-control form-control-sm accountAmount" value="{{ $acc->amount }}">
                                                                                    </td>
                                                                                    <td>
                                                                                        <button type="button" class="btn btn-sm btn-danger removeAccountRow">X</button>
                                                                                    </td>
                                                                                </tr>
                                                                            @endforeach
                                                                        @elseif(!old('account_head_id'))
                                                                            {{-- Default blank row --}}
                                                                            <tr>
                                                                                <td>
                                                                                    <select name="account_head_id[]" class="form-control form-control-sm accountHead">
                                                                                        <option value="" disabled selected>Select Head</option>
                                                                                        @foreach ($AccountHeads as $head)
                                                                                            <option value="{{ $head->id }}">{{ $head->name }}</option>
                                                                                        @endforeach
                                                                                    </select>
                                                                                </td>
                                                                                <td>
                                                                                    <select name="account_id[]" class="form-control form-control-sm accountSub">
                                                                                        <option value="" disabled selected>Select Account</option>
                                                                                    </select>
                                                                                </td>
                                                                                <td>
                                                                                    <input type="number" step="0.01" name="account_amount[]" class="form-control form-control-sm accountAmount" value="0">
                                                                                </td>
                                                                                <td>
                                                                                    <button type="button" class="btn btn-sm btn-danger removeAccountRow">X</button>
                                                                                </td>
                                                                            </tr>
                                                                        @endif
                                                                    </tbody>
                                                            </table>

                                                            <div class="mt-2 text-end">
                                                                <label class="fw-bold">Accounts Total:</label>
                                                                <input type="text" id="accountsTotal" class="form-control form-control-sm d-inline-block w-auto fw-bold" value="0" readonly>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Totals -->
                                                <div class="col-md-6">
                                                    <div class="card h-100">
                                                        <div class="card-header p-2 bg-light fw-bold">Totals</div>
                                                        <div class="card-body p-2">
                                                            <table class="table table-bordered table-sm text-center align-middle mb-0">
                                                                <tr>
                                                                    <th>Subtotal</th>
                                                                    <td><input type="text" id="subtotal" name="subtotal" class="form-control form-control-sm" value="{{ old('subtotal', $purchase->subtotal ?? 0) }}" readonly></td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Discount</th>
                                                                    <td><input type="number" step="0.01" id="overallDiscount" name="discount" class="form-control form-control-sm" value="{{ old('discount', $purchase->discount ?? 0) }}" readonly>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <th>WHT</th>
                                                                    <td>
                                                                        <div class="input-group">
                                                                            <input type="number" step="0.01" id="whtPercent" name="wht_percent" class="form-control form-control-sm" placeholder="%" value="{{ old('wht_percent', $purchase->wht_percent ?? '') }}">
                                                                            <input type="hidden" id="whtValue" name="wht" value="{{ old('wht', $purchase->wht ?? 0) }}">
                                                                            <select id="whtType" name="wht_type" class="form-select form-select-sm" style="max-width:90px;">
                                                                                @php $wType = old('wht_type', $purchase->wht_type ?? 'percent'); @endphp
                                                                                <option value="percent" {{ $wType == 'percent' ? 'selected' : '' }}>%</option>
                                                                                <option value="amount" {{ $wType == 'amount' ? 'selected' : '' }}>PKR</option>
                                                                            </select>
                                                                        </div>
                                                                    </td>
                                                                </tr>

                                                                <tr>
                                                                    <th>WHT Amount</th>
                                                                    <td>
                                                                        <input type="text" id="whtAmount" name="wht_amount" class="form-control form-control-sm" value="{{ old('wht_amount', 0) }}" readonly>
                                                                    </td>
                                                                </tr>


                                                                <tr>
                                                                    <th>Net</th>
                                                                    <td><input type="text" id="netAmount" name="net_amount" class="form-control form-control-sm fw-bold" value="{{ old('net_amount', $purchase->net_amount ?? 0) }}" readonly></td>
                                                                </tr>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            {{-- BOTTOM BUTTONS --}}
                                            <div class="d-flex gap-2 mt-3 justify-content-end">

                                                {{-- Save Draft --}}
                                                <button type="button" id="saveDraftBtn"
                                                    class="btn btn-sm btn-warning rounded-pill px-4 shadow-sm">
                                                    <i class="fa fa-floppy-o me-1"></i> Save Draft
                                                    <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>
                                                </button>

                                                {{-- Print Preview --}}
                                                @if(isset($purchase))
                                                    <a href="{{ route('purchase.invoice', $purchase->id) }}" target="_blank"
                                                        class="btn btn-sm btn-outline-dark rounded-pill px-4">
                                                        <i class="fa fa-print me-1"></i> Print
                                                        <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+P</kbd>
                                                    </a>
                                                @else
                                                    <button type="button" id="previewPrintBtn"
                                                        class="btn btn-sm btn-outline-dark rounded-pill px-4">
                                                        <i class="fa fa-print me-1"></i> Print Preview
                                                        <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+P</kbd>
                                                    </button>
                                                @endif

                                                {{-- Post --}}
                                                @if(isset($purchase) && $purchase->status != 'Posted')
                                                    <button type="button" id="postBtn" data-action="post"
                                                        class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm">
                                                        <i class="fa fa-send me-1"></i> Post
                                                        <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+&#8629;</kbd>
                                                    </button>
                                                @elseif(!isset($purchase))
                                                    <button type="button" id="postBtn" data-action="post"
                                                        class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm">
                                                        <i class="fa fa-send me-1"></i> Save &amp; Post
                                                        <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+&#8629;</kbd>
                                                    </button>
                                                @endif

                                            </div>

                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div><!-- bodywrapper__inner end -->
                </div><!-- body-wrapper end -->
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>



@if(session('error'))
<script>
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: @json(session('error')),
        confirmButtonColor: '#d33',
    });
</script>
@endif

@if (session('success'))
<script>
    Swal.fire({
        icon: 'success',
        title: 'Success',
        text: @json(session('success')),
        confirmButtonColor: '#3085d6',
    });
</script>
@endif






    <script>
        // Global helper for initializing Select2 on a row
        window.initProductSelect = function($row) {
            const $select = $row.find('.product-select');
            
            $select.select2({
                placeholder: "Select Product",
                allowClear: true,
                width: '100%',
                ajax: {
                    url: "{{ route('search-products') }}",
                    dataType: 'json',
                    delay: 250, 
                    data: function (params) {
                        return {
                            q: params.term // search term
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data.map(function(item) {
                                return {
                                    id: item.id,
                                    text: item.name,
                                    // Pass custom data
                                    brand: item.brand,
                                    price_net: item.purchase_net_amount,
                                    price_retail: item.purchase_retail_price
                                };
                            })
                        };
                    },
                    cache: true
                },
                minimumInputLength: 0
            });

            // Tab/Enter on Item ID -> Auto-Append Row if last
            $row.find('.item-id-input').on('keydown', function(e) {
                if (e.key === 'Enter' || e.key === 'Tab') {
                    const $currentRow = $(this).closest('tr');
                    // Always append a new row at the bottom if we are on the last row
                    if ($currentRow.is(':last-child')) {
                        // focus = false so the focus doesn't jump to the new row yet
                        window.appendBlankRow(true, false);
                    }

                    // If empty ID, open the product selector
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
                    const product = data.find(p => p.id == id);
                    if (product) {
                        const newOption = new Option(product.name, product.id, true, true);
                        $select.empty().append(newOption).trigger('change');
                        
                        // Populate and trigger row calcs
                        $select.trigger({
                            type: 'select2:select',
                            params: {
                                data: {
                                    id: product.id,
                                    text: product.name,
                                    brand: product.brand,
                                    price_net: product.purchase_net_amount,
                                    price_retail: product.purchase_retail_price
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

                // Update ID input
                $currentRow.find('.item-id-input').val(data.id);

                // Populate fields
                $currentRow.find('.product_name_hidden').val(data.text);
                $currentRow.find('.brand-name').val(data.brand || '');
                
                const net = parseFloat(data.price_net || 0).toFixed(2);
                const retail = parseFloat(data.price_retail || 0).toFixed(2);
                
                // Set prices
                $currentRow.find('.price').val(net).trigger('input');
                $currentRow.find('.retail_price_show').val(retail);
                $currentRow.find('.purchase_net_amount').val(net);
                $currentRow.find('.purchase_retail_price').val(retail);
                
                // Default Quantity to 1
                $currentRow.find('.quantity').val(1);
                $currentRow.find('.item_disc').val(0);
                $currentRow.find('.disc_amount').val('0.00');

                // Immediate calculation for better responsiveness
                const gross = (parseFloat(net) || 0) * 1;
                $currentRow.find('.row-amount').val(gross.toFixed(2));
                $currentRow.find('.row-total').val(gross.toFixed(2));

                // Trigger formal calculation and summary
                if(typeof window.recalcRow === 'function') {
                    window.recalcRow($currentRow);
                }
                if(typeof window.recalcSummary === 'function') {
                    window.recalcSummary();
                }

                // Focus Price instead of next row for manual adjustment
                setTimeout(() => {
                    $currentRow.find('.price').focus().select();
                }, 50);
            });
            
            $select.on('select2:clear', function (e) {
                const $currentRow = $(this).closest('tr');
                $currentRow.find('input').not(this).val('');
                $currentRow.find('.quantity').val(1);
                if(typeof window.recalcRow === 'function') window.recalcRow($currentRow);
                if(typeof window.recalcSummary === 'function') window.recalcSummary();
            });
        };

    document.addEventListener('DOMContentLoaded', function() {

        // Initialize any existing rows
        $('#purchaseItems tr').each(function() {
             window.initProductSelect($(this));
        });

        // AUTO-ADD ROW on last product selection
        // REMOVED: Handled inside select2:select for better control
        /*
        $(document).on('select2:select', '.product-select', function() {
            if ($(this).closest('tr').is(':last-child')) {
                if(typeof window.appendBlankRow === 'function') window.appendBlankRow();
            }
        });
        */
    });
    </script>

{{-- Item Row Autocomplete + Add/Remove --}}
<!-- Make sure jQuery and Bootstrap Typeahead are included -->

<script>
    (function() {
        // restore old arrays from server (Blade -> JS)
        const oldProducts = @json(old('product_id', []));
        const oldPrices = @json(old('price', []));
        const oldQtys = @json(old('qty', []));
        const oldItemDiscs = @json(old('item_disc', []));
        const oldDiscAmounts = @json(old('item_disc_amount', []));
        const oldRetailPrices = @json(old('purchase_retail_price', []));
        const oldPurchaseNet = @json(old('purchase_net_amount', []));
        const oldRowAmounts = @json(old('total', []));
        const oldProductNames = @json(old('product_name', []));
        const oldBrands = @json(old('brand', []));

        // account allocations
        const oldAccHeads = @json(old('account_head_id', []));
        const oldAccIds = @json(old('account_id', []));
        const oldAccAmounts = @json(old('account_amount', []));

        @if(isset($purchase))
            @php
                $pItems = $purchase->items->map(function($item) {
                    $product = $item->product;
                    $retail = $product?->latestPrice?->purchase_retail_price ?? 0;
                    $net = $product?->latestPrice?->purchase_net_amount ?? 0;
                    
                    $price = (float)($item->price ?? 0);
                    $qty = (float)($item->qty ?? 0);
                    $disc_percent = (float)($item->item_discount ?? 0);
                    
                    $gross = $price * $qty;
                    $disc_amt = $gross * ($disc_percent / 100);
                    
                    return [
                        'product_id' => $item->product_id,
                        'product_name' => $product?->name ?? 'Unknown',
                        'brand' => $product?->brandRelation?->name ?? '',
                        'price' => $price,
                        'retail_show' => $retail,
                        'item_disc' => $disc_percent,
                        'disc_amount' => number_format($disc_amt, 2, '.', ''),
                        'purchase_retail' => $retail,
                        'purchase_net' => $net,
                        'qty' => $qty,
                        'row_amount' => number_format($gross, 2, '.', ''),
                        'row_total' => number_format($item->line_total ?? $gross - $disc_amt, 2, '.', ''),
                    ];
                });
                $pAccs = $purchase->accountAllocations->map(function($acc) {
                    return [
                        'head_id' => $acc->account_head_id,
                        'account_id' => $acc->account_id,
                        'account_name' => $acc->account->title ?? 'Unknown Account',
                        'amount' => $acc->amount
                    ];
                });
            @endphp
            const purchaseItems = {!! $pItems->toJson() !!};
            const purchaseAccounts = {!! $pAccs->toJson() !!};
        @else
            const purchaseItems = [];
            const purchaseAccounts = [];
        @endif

        const errors = @json($errors->toArray());
        @php
            $ahList = $AccountHeads->map(function($head) {
                return ['id' => $head->id, 'name' => $head->name];
            });
        @endphp
        const accountHeadsList = {!! $ahList->toJson() !!};

        // helper: create a product row HTML (same structure as appendBlankRow)
        window.makeRowHtml = function(data, index = null) {
            // Error handling helper
            const getError = (field) => {
                if (index !== null && errors[field + '.' + index]) {
                     return `<div class="alert alert-danger p-1 mt-1" style="font-size: 12px; margin-bottom:0;">${errors[field + '.' + index][0]}</div>`;
                }
                return '';
            };

            // Pre-select option if data exists
            let optionHtml = '<option value="" disabled selected>Select Product</option>';
            if(data.product_id) {
                const pName = data.product_name || 'Product ' + data.product_id;
                optionHtml = `<option value="${data.product_id}" selected>${pName}</option>`;
            }

            return `
      <tr>
        <td style="width: 100px;">
          <input type="text" class="form-control form-control-sm item-id-input" placeholder="ID" value="${data.product_id || ''}">
        </td>
        <td style="width: 250px;">
          <select name="product_id[]" class="form-control form-control-sm product-select" style="width: 100%;">
            ${optionHtml}
          </select>
          <input type="hidden" name="product_name[]" class="product_name_hidden" value="${(data.product_name || '')}">
          ${getError('product_id')}
        </td>
        <td class="uom border">
          <input type="text" name="brand[]" class="form-control form-control-sm brand-name" readonly value="${data.brand || ''}">
        </td>
        <td>
          <input type="number" step="0.01" name="price[]" class="form-control form-control-sm price" value="${data.price || ''}">
          ${getError('price')}
        </td>
        <td>
          <input type="text" name="retail_price_show[]" class="form-control form-control-sm retail_price_show" readonly value="${data.retail_show || ''}">
        </td>
        <td>
          <div class="input-group">
            <input type="number" step="0.01" min="0" name="item_disc[]" class="form-control form-control-sm item_disc" placeholder="%" value="${data.item_disc || ''}">
            <input type="text" name="item_disc_amount[]" class="form-control form-control-sm disc_amount" readonly placeholder="Disc Amt" value="${data.disc_amount || ''}">
          </div>
          ${getError('item_disc')}
          <input type="hidden" name="purchase_retail_price[]" class="purchase_retail_price" value="${data.purchase_retail || ''}">
          <input type="hidden" name="purchase_net_amount[]" class="purchase_net_amount" value="${data.purchase_net || ''}">
        </td>
        <td>
          <input type="number" name="qty[]" class="form-control form-control-sm quantity" value="${data.qty || 1}" min="1">
          ${getError('qty')}
        </td>
        <td>
          <input type="text" name="amount[]" class="form-control form-control-sm row-amount" readonly value="${data.row_amount || ''}">
        </td>
        <td>
          <input type="text" name="total[]" class="form-control form-control-sm row-total" readonly value="${data.row_total || ''}">
        </td>
        <td>
          <button type="button" class="btn btn-sm btn-danger remove-row" title="Delete Row (Ctrl+X)">
            X <span style="font-size: 8px; opacity: 0.8; margin-left: 2px;">Ctrl+X</span>
          </button>
        </td>
      </tr>
    `;
        }

        function restoreProducts() {
            let dataToRestore = [];
            let isOldData = false;
            
            if (oldProducts && oldProducts.length > 0) {
                isOldData = true;
                const max = oldProducts.length;
                for (let i = 0; i < max; i++) {
                    dataToRestore.push({
                        product_id: oldProducts[i] ?? '',
                        product_name: (oldProductNames[i] ?? ''),
                        brand: (oldBrands[i] ?? ''),
                        price: oldPrices[i] ?? '',
                        retail_show: oldRetailPrices[i] ?? '',
                        item_disc: oldItemDiscs[i] ?? '',
                        disc_amount: oldDiscAmounts[i] ?? '',
                        purchase_retail: oldRetailPrices[i] ?? '',
                        purchase_net: oldPurchaseNet[i] ?? '',
                        qty: oldQtys[i] ?? 1,
                        row_amount: '',
                        row_total: oldRowAmounts[i] ?? ''
                    });
                }
            } 

            // ONLY empty and restore if we have 'old' data from a failed submission
            // Otherwise, we keep what's already in the table (either Blade rows or blank row)
            if (!isOldData) {
                // Just ensure initial rows are initialized (though domestic ready also does this)
                $('#purchaseItems tr').each(function() {
                    if(typeof window.initProductSelect === 'function') window.initProductSelect($(this));
                });
                return;
            }

            const $container = $('#purchaseItems');
            $container.empty();

            dataToRestore.forEach((rowData, i) => {
                const html = window.makeRowHtml(rowData, i);
                const $newRow = $(html);
                $container.append($newRow);
                if(typeof window.initProductSelect === 'function') {
                    window.initProductSelect($newRow);
                }
            });

            // Recalculate everything
            setTimeout(() => {
                $container.find('tr').each(function() {
                    if (typeof window.recalcRow === 'function') window.recalcRow($(this));
                });
                if (typeof window.recalcSummary === 'function') window.recalcSummary();
            }, 50);
        }

        function restoreAccounts() {
            let accountsToRestore = [];
            let isOldData = false;

            if (oldAccHeads && oldAccHeads.length > 0) {
                isOldData = true;
                const max = oldAccHeads.length;
                for (let i = 0; i < max; i++) {
                    accountsToRestore.push({
                        head_id: oldAccHeads[i] ?? '',
                        account_id: oldAccIds[i] ?? '',
                        amount: oldAccAmounts[i] ?? 0
                    });
                }
            } 

            if (!isOldData) {
                // If not old data, just trigger internal logic if needed
                // But generally Blade rows are already there.
                // We might need to trigger account loading but Blade already puts the selected option there.
                return;
            }

            // clear table body
            $('#accountsTableBody').empty();

            accountsToRestore.forEach((data, i) => {
                const head = data.head_id ?? '';
                const acc = data.account_id ?? '';
                const amt = data.amount ?? '';
                const accName = data.account_name || acc;

                const getError = (field) => {
                    if (errors[field + '.' + i]) {
                        return `<div class="alert alert-danger p-0 m-0 mt-1" style="font-size: 10px;">${errors[field + '.' + i][0]}</div>`;
                    }
                    return '';
                };

                const headsOptions = accountHeadsList.map(h => `<option value="${h.id}" ${h.id == head ? 'selected' : ''}>${h.name}</option>`).join('');

                const row = `
        <tr>
          <td>
            <select name="account_head_id[]" class="form-control form-control-sm accountHead">
              <option value="" disabled>Select Head</option>
              ${headsOptions}
            </select>
            ${getError('account_head_id')}
          </td>
          <td>
            <select name="account_id[]" class="form-control form-control-sm accountSub">
              <option value="${acc}" selected>${accName}</option>
            </select>
            ${getError('account_id')}
          </td>
          <td>
            <input type="number" step="0.01" name="account_amount[]" class="form-control form-control-sm accountAmount" value="${amt}">
            ${getError('account_amount')}
          </td>
          <td>
            <button type="button" class="btn btn-sm btn-danger removeAccountRow">X</button>
          </td>
        </tr>
      `;
                $('#accountsTable tbody').append(row);
                
                // Trigger change to load accounts if head is selected
                if(head) {
                     const $lastRow = $('#accountsTable tbody tr:last');
                     // Manually call the load logic effectively to ensure value is preserved
                     const headId = head;
                     const $accountSelect = $lastRow.find('.accountSub');
                     const $amountInput = $lastRow.find('.accountAmount');
                     
                     // Enable inputs
                     $accountSelect.prop('disabled', false).prop('required', true);
                     $amountInput.prop('disabled', false).prop('required', true).attr('min', '0.01');

                     $.ajax({
                        url: "{{ url('/get-accounts-by-head') }}/" + headId,
                        type: "GET",
                        dataType: 'json',
                        success: function(res) {
                            const currentAcc = acc;
                            let html = '<option value="" disabled>Select Account</option>';
                            if (Array.isArray(res) && res.length) {
                                res.forEach(a => {
                                    const selected = String(a.id) === String(currentAcc) ? ' selected' : '';
                                    html += `<option value="${a.id}"${selected}>${a.title}</option>`;
                                });
                            }
                            $accountSelect.html(html);
                            if (!currentAcc) {
                                $accountSelect.prepend('<option value="" disabled selected>Select Account</option>');
                                $accountSelect.val('');
                            }
                            if (typeof recalcAccountsTotal === 'function') recalcAccountsTotal();
                        },
                        error: function() {
                            if (typeof recalcAccountsTotal === 'function') recalcAccountsTotal();
                        }
                    });
                }
            });  // <-- FIX: was missing closing ); for forEach callback

            // trigger recalc of allocations
            if (typeof recalcAccountsTotal === 'function') recalcAccountsTotal();
        }

        // Run restore on DOM ready (after your other handlers)
        document.addEventListener('DOMContentLoaded', function() {
            try {
                restoreProducts();
                restoreAccounts();
            } catch (e) {
                console && console.error && console.error('restore error', e);
            }
        });
    })();

    // --- Core Calculation Engine ---
    window.num = function(n) {
        if (n === null || n === undefined) return 0;
        if (typeof n === 'string') n = n.replace(/,/g, '');
        const f = parseFloat(n);
        return isNaN(f) ? 0 : f;
    };

    window.recalcRow = function($row) {
        if (!$row || !$row.length) return;

        // Get fresh values
        const price = window.num($row.find('.price').val());
        const retail = window.num($row.find('.purchase_retail_price').val());
        const discPercent = window.num($row.find('.item_disc').val());
        
        let qRaw = $row.find('.quantity').val();
        let qty = (qRaw === '' || isNaN(parseFloat(qRaw))) ? 1 : parseFloat(qRaw);

        // Calculation
        const discBase = (retail > 0) ? retail : price;
        const perUnitDisc = discBase * (discPercent / 100);
        const totalDisc = perUnitDisc * qty;

        const gross = price * qty;
        const net = gross - totalDisc;

        // Update fields
        $row.find('.disc_amount').val(totalDisc.toFixed(2));
        $row.find('.row-amount').val(gross.toFixed(2));
        $row.find('.row-total').val(net.toFixed(2));

        // Always update summary after row change
        window.recalcSummary();
    };

    window.recalcSummary = function() {
        let sub = 0;
        // Sum all row totals
        $('#purchaseItems .row-total').each(function() {
            sub += window.num($(this).val());
        });
        
        $('#subtotal').val(sub.toFixed(2));

        const oDisc = window.num($('#overallDiscount').val());
        const whtPercent = window.num($('#whtPercent').val());
        const whtType = $('#whtType').val();

        let whtAmount = 0;
        if (whtType === 'percent') {
            const taxable = Math.max(0, sub - oDisc);
            whtAmount = taxable * (whtPercent / 100);
        } else {
            // If PKR mode, whtPercent holds the direct PKR amount
            whtAmount = whtPercent;
        }

        $('#whtAmount').val(whtAmount.toFixed(2));
        // Write calculated amount into whtValue (name=wht) so controller gets PKR amount
        $('#whtValue').val(whtAmount.toFixed(2));

        const netTotal = sub - oDisc - whtAmount;
        $('#netAmount').val(netTotal.toFixed(2));
    };

    // --- Unified Event Delegation ---
    $(document).ready(function() {
        // Handle Item inputs
        $(document).on('input change', '.quantity, .item_disc, .price', function() {
            const $row = $(this).closest('tr');
            if ($row.length) window.recalcRow($row);
        });

        // Handle Summary inputs
        $(document).on('input change', '#overallDiscount, #whtPercent, #whtType', function() {
            window.recalcSummary();
        });

        // Auto-select on focus
        $(document).on('focus', '.quantity, .item_disc, .price', function() {
            $(this).select();
        });

        // Manual Add Row Button
        $(document).on('click', '#addRow', function(e) {
            e.preventDefault();
            if (typeof window.appendBlankRow === 'function') window.appendBlankRow(true);
        });

        // Initial Summary calculation
        window.recalcSummary();
    });



        function initRecalcAllRows() {
            $('#purchaseItems tr').each(function() {
                try {
                    if (typeof window.recalcRow === 'function') window.recalcRow($(this));
                } catch (err) {
                    // ignore individual row errors but log for debugging
                    console && console.error && console.error('recalcRow error', err);
                }
            });
            if (typeof window.recalcSummary === 'function') window.recalcSummary();
        }

        // call it now (inside ready)
        initRecalcAllRows();


        // keyboard Enter on suggestion list
        $(document).on('keydown', '.searchResults .search-result-item', function(e) {
            if (e.key === 'Enter') {
                $(this).trigger('click');
            }
        });








        // Initial entry setup
        $(document).ready(function() {
            // Initialize Select2 on any rows that were rendered by Blade
            $('#purchaseItems tr').each(function() {
                if (typeof window.initProductSelect === 'function') window.initProductSelect($(this));
            });
            
            // If table is still empty after restoration scripts (run on DOMContentLoaded), add a blank row
            // Note: restoreProducts is already triggered on DOMContentLoaded
            setTimeout(() => {
                const $itemRows = $('#purchaseItems tr');
                if ($itemRows.length === 0) {
                    if (typeof window.appendBlankRow === 'function') {
                        window.appendBlankRow();
                    }
                } else {
                    // Recalculate each existing row to be sure
                    $itemRows.each(function() {
                        if (typeof window.recalcRow === 'function') window.recalcRow($(this));
                    });
                }
                
                // Recalculate summary and accounts
                if (typeof window.recalcAccountsTotal === 'function') window.recalcAccountsTotal();
                if (typeof window.recalcSummary === 'function') window.recalcSummary();
            }, 300);
        });

    $('#purchaseForm').on('submit', function(e) {
        // remove any item rows that do not have a product selected
        $('#purchaseItems tr').each(function() {
            // if product id blank, remove row
            const pid = $(this).find('.product-select').val() || '';
            if (!pid.toString().trim()) {
                $(this).remove();
            }
        });

        // Safe guard: if all rows were removed (because empty), add one back
        if ($('#purchaseItems tr').length === 0) {
            if (typeof window.appendBlankRow === 'function') {
                window.appendBlankRow(); 
            } else {
                 // Fallback if function is somehow not reachable (though it should be)
                 const fallbackRow = `<tr><td><input type="hidden" name="product_id[]" class="product_id"><input type="hidden" name="product_name[]" class="product_name_hidden"><input type="text" class="form-control form-control-sm productSearch" placeholder="Search product..."><div class="searchResults"></div></td><td class="uom border"><input type="text" name="brand[]" class="form-control form-control-sm" readonly></td><td><input type="number" step="0.01" name="price[]" class="form-control form-control-sm price"></td><td><input type="text" name="retail_price_show[]" class="form-control form-control-sm retail_price_show" readonly></td><td><div class="input-group"><input type="number" step="0.01" min="0" name="item_disc[]" class="form-control form-control-sm item_disc" placeholder="%"><input type="text" name="item_disc_amount[]" class="form-control form-control-sm disc_amount" readonly placeholder="Disc Amt"></div><input type="hidden" name="purchase_retail_price[]" class="purchase_retail_price"><input type="hidden" name="purchase_net_amount[]" class="purchase_net_amount"></td><td><input type="number" name="qty[]" class="form-control form-control-sm quantity" value="1" min="1"></td><td><input type="text" name="amount[]" class="form-control form-control-sm row-amount" readonly></td><td><input type="text" name="total[]" class="form-control form-control-sm row-total" readonly></td><td><button type="button" class="btn btn-sm btn-danger remove-row">X</button></td></tr>`;
                 $('#purchaseItems').append(fallbackRow);
            }
        }

        // after removal, check if we still have at least one valid row
        if ($('#purchaseItems .product-select').filter(function() {
                return $(this).val();
            }).length === 0) {
            e.preventDefault();
            showToast('⚠️ Please add at least one valid item before saving.', 'error');
            return false;
        }

        // optionally, still re-run client recalc to ensure totals are accurate
        recalcSummary();
        return true; // allow submit
    });

    // --- BLOCK ENTER KEY (prevents accidental form submit on qty, price etc) ---
    $(document).on('keydown', function(e) {
        if (e.key === 'Enter') {
            var $t = $(e.target);
            // Only allow Enter in textarea
            if (!$t.is('textarea')) {
                e.preventDefault();
                return false;
            }
        }

        // --- CTRL+S = Submit form ---
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            $('#purchaseForm').submit();
        }
    });



    // Initialize on DOM Ready
    $(document).ready(function() {
        // Init Select2 on type and party dropdowns
        if ($.fn.select2) {
            $('#vendor_type_select').select2({ placeholder: 'Select Type', width: '100%', allowClear: true });
            $('#vendor_id_select').select2({ placeholder: 'Select Party', width: '100%', allowClear: true });
        }

        // Focus first row Item ID on load
        setTimeout(function() {
            $('#purchaseItems tr:first .item-id-input').focus();
        }, 500);
    });

    
    // --- Print Preview Functions ---
    window.showPreviewModal = function() {
        console.log('showPreviewModal called');
        try {
            // Gather Basic Info
            const date = $('input[name="current_date"]').val();
            const vendorType = $('#vendor_type_select option:selected').text();
            const vendorName = $('select[name="vendor_id"] option:selected').text() || '-';
            const dc = $('input[name="dc"]').val() || '-';
            const warehouse = $('select[name="warehouse_id"] option:selected').text() || '-';
            const bilty = $('input[name="bilty_no"]').val() || '-';
            const remarks = $('input[name="remarks"]').val() || '-';
            const invoiceNo = "{{ $nextInvoice ?? 'PUR-XXX' }}"; 

            // Gather Items
            let itemsHtml = '';
            $('#purchaseItems tr').each(function(index) {
                const productName = $(this).find('.product_name_hidden').val() || $(this).find('.product-select option:selected').text();
                const brand = $(this).find('input[name="brand[]"]').val();
                const qty = $(this).find('.quantity').val();
                const price = $(this).find('.price').val();
                const total = $(this).find('.row-total').val();

                if(productName && productName !== 'Select Product' && qty) {
                     itemsHtml += `
                        <tr>
                            <td style="padding: 4px; border: 1px solid #ddd; text-align: center;">${index + 1}</td>
                            <td style="padding: 4px; border: 1px solid #ddd;">${productName} <br> <small class="text-muted">${brand || ''}</small></td>
                            <td style="padding: 4px; border: 1px solid #ddd; text-align: center;">${qty}</td>
                            <td style="padding: 4px; border: 1px solid #ddd; text-align: right;">${price}</td>
                            <td style="padding: 4px; border: 1px solid #ddd; text-align: right;">${total}</td>
                        </tr>
                     `;
                }
            });

            // Gather Totals
            const subtotal = $('#subtotal').val();
            const discount = $('#overallDiscount').val();
            const net = $('#netAmount').val();
            const wht = $('#whtAmount').val();

            // Build Template
            // Build Template
            const html = `
                <div style="font-family: 'Segoe UI', Arial, sans-serif; color: #000; padding: 20px; border: 1px solid #ccc;">
                    <!-- Header -->
                    <div style="text-align: center; margin-bottom: 25px; border-bottom: 3px double #000; padding-bottom: 15px;">
                        <h1 style="margin: 0; font-weight: 800; text-transform: uppercase; font-size: 28px; letter-spacing: 1px;">AL Madina Traders</h1>
                        <div style="font-size: 16px; margin-top: 5px; font-weight: 500;">Deals in: UPS, Solar, Batteries & Electronics</div>
                        <div style="font-size: 15px; margin-top: 3px;"><strong>Phone:</strong> 0300-1234567, 0321-7654321</div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                        <div>
                            <h3 style="margin: 0; font-weight: bold; text-transform: uppercase; border-bottom: 2px solid #000; display: inline-block; padding-bottom: 2px; margin-bottom: 5px;">Purchase Receipt</h3>
                            <div style="font-size: 15px; margin-top: 8px;"><strong>Supplier:</strong> ${vendorName} (${vendorType})</div>
                            <div style="font-size: 15px;"><strong>Warehouse:</strong> ${warehouse}</div>
                        </div>
                        <div style="text-align: right;">
                            <h4 style="margin: 0; color: #000; font-weight: bold; font-size: 18px;">Inv #${invoiceNo}</h4>
                            <div style="font-size: 15px; margin-top: 8px;"><strong>Date:</strong> ${date}</div>
                            <div style="font-size: 15px;"><strong>DC / Bilty:</strong> ${dc} / ${bilty}</div>
                        </div>
                    </div>

                    <!-- Items Table -->
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
                             <!-- Spacer Row/Footer Details -->
                             <tr>
                                <td colspan="3" style="border-top: 2px solid #000; padding-top: 15px;">
                                    <strong>Remarks:</strong> ${remarks} <br>
                                    <small style="color: #555;">Generated by System</small>
                                </td>
                                <td style="text-align: right; border-top: 2px solid #000; padding: 10px 5px; font-weight: bold;">Subtotal:</td>
                                <td style="text-align: right; border-top: 2px solid #000; padding: 10px 5px; font-weight: bold;">${subtotal}</td>
                             </tr>
                             <tr>
                                <td colspan="3" style="border: none;"></td>
                                <td style="text-align: right; padding: 5px;">Discount:</td>
                                <td style="text-align: right; padding: 5px;">${discount}</td>
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
                    
                    <div style="text-align: center; font-size: 12px; margin-top: 30px; border-top: 1px dashed #ccc; padding-top: 10px;">
                        Thank you for your business!
                    </div>
                </div>
            `;

            // Inject
            $('#printArea').html(html);

            // Show Modal - Attempt both Bootstrap 5 and jQuery fallback
            // Show Modal
            const $modal = $('#printPreviewModal');
            if ($modal.length) {
                // Try jQuery first as it seems to be loaded
                if (typeof $modal.modal === 'function') {
                    $modal.modal('show');
                } else {
                    // Fallback to vanilla JS / Bootstrap 5
                    const myModal = new bootstrap.Modal(document.getElementById('printPreviewModal'));
                    myModal.show();
                }
            } else {
                alert('Error: Modal element not found!');
            }
        
        } catch(e) {
            console.error('Error in showPreviewModal:', e);
            alert('Error showing preview: ' + e.message);
        }
    };

    window.printDiv = function(divId) {
        var printContents = document.getElementById(divId).innerHTML;
        var originalContents = document.body.innerHTML;

        // Simple Print trick
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        
        // Re-bind events or refresh (Simplest is refresh, but let's try to reload page to ensure state)
        window.location.reload(); 
    };
    // --- Navigation Guard (Prevent leaving incomplete form) ---
    let isFormDirty = false;
    let isFormSaved = false; // set true once AJAX draft save succeeds

    // Detect changes in any input/select/textarea within the form
    $(document).on('change input', '#purchaseForm :input', function() {
        isFormDirty = true;
        isFormSaved = false; // re-dirty if user edits after saving
    });

    // If the form is submitted (traditional), clear dirty flag
    $('#purchaseForm').on('submit', function() {
        isFormDirty = false;
        isFormSaved = true;
    });

    // Expose a function so ajaxSaveDraft success can clear the guard
    window.markFormSaved = function() {
        isFormDirty = false;
        isFormSaved = true;
    };

    // Intercept all link clicks
    $(document).on('click', 'a', function(e) {
        // Allow Ctrl/Meta/Shift+Click (new tab, new window) to proceed always
        if (e.ctrlKey || e.metaKey || e.shiftKey) {
            return;
        }

        const intendedUrl = $(this).attr('href');
        // Skip hash links and javascript: links
        if (!intendedUrl || intendedUrl.startsWith('#') || intendedUrl.toLowerCase().startsWith('javascript')) {
            return;
        }

        // Block only if dirty and not yet saved
        if (isFormDirty && !isFormSaved) {
            e.preventDefault();
            showToast('⚠️ Please save the purchase (Save Draft) before leaving.', 'error');
            return;
        }
    });

    // Browser-level guard (reload / close tab)
    window.addEventListener('beforeunload', function (e) {
        if (isFormDirty && !isFormSaved) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
})();
</script>

<!-- Print Preview Modal -->
<div class="modal fade" id="printPreviewModal" tabindex="-1" aria-labelledby="printPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="printPreviewModalLabel">Purchase Receipt Preview</h5>
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

{{-- Select2 JS --}}

{{-- FINAL CALCULATION OVERRIDE - runs last, guarantees qty/total always updates --}}
<script>
$(function() {

    // Simple robust number parser
    function _n(v) {
        v = (v + '').replace(/,/g, '');
        var f = parseFloat(v);
        return isNaN(f) ? 0 : f;
    }

    // Recalculate a single row
    function _recalcRow($row) {
        var price  = _n($row.find('.price').val());
        var qty    = _n($row.find('.quantity').val()) || 1;
        var disc   = _n($row.find('.item_disc').val());
        var retail = _n($row.find('.purchase_retail_price').val());

        var base    = retail > 0 ? retail : price;
        var discAmt = (base * disc / 100) * qty;

        // Amount = Price * Qty (Gross Amount)
        var grossAmount = price * qty;
        // Total = Gross - Discount (Net Amount)
        var netAmount   = grossAmount - discAmt;

        $row.find('.disc_amount').val(discAmt.toFixed(2));
        $row.find('.row-amount').val(grossAmount.toFixed(2));
        $row.find('.row-total').val(netAmount.toFixed(2));
    }

    // Recalculate bottom summary
    function _recalcSummary() {
        var subTotalGross = 0;
        var itemDiscTotal = 0;

        // Sum all row gross amounts and disc amounts
        $('#purchaseItems tr').each(function() {
            subTotalGross += _n($(this).find('.row-amount').val());
            itemDiscTotal += _n($(this).find('.disc_amount').val());
        });

        // Sum accounts allocation total
        var accTotal = _n($('#accountsTotal').val());
        var totalDiscount = itemDiscTotal + accTotal;

        $('#subtotal').val(subTotalGross.toFixed(2));
        $('#overallDiscount').val(totalDiscount.toFixed(2));

        var whtVal = _n($('#whtPercent').val());
        var whtType = $('#whtType').val() || 'percent';
        var whtAmt = 0;
        
        if (whtType === 'percent') {
            whtAmt = Math.max(0, subTotalGross - totalDiscount) * whtVal / 100;
        } else {
            whtAmt = whtVal;
        }

        $('#whtValue').val(whtVal);
        $('#whtAmount').val(whtAmt.toFixed(2));
        var netTotal = subTotalGross - totalDiscount - whtAmt;
        $('#netAmount').val(netTotal.toFixed(2));
    }

    // Override window functions so all other code uses these too
    window.recalcRow = function($row) { _recalcRow($row); _recalcSummary(); };
    window.recalcSummary = _recalcSummary;

    // THE KEY LISTENER - qty / price / disc changes
    $(document).on('input change', '.quantity, .price, .item_disc', function() {
        var $row = $(this).closest('tr');
        if ($row.length) {
            _recalcRow($row);
            _recalcSummary();
        }
    });

    // Summary field changes
    $(document).on('input change', '#overallDiscount, #whtPercent, #whtType', function() {
        _recalcSummary();
    });

    // Run once on load
    _recalcSummary();

});
</script>

{{-- TYPE -> VENDOR DROPDOWN FIX --}}
<script>
$(document).ready(function() {

    var vendors   = @json($Vendor->map(fn($v) => ['id' => $v->id, 'name' => $v->name]));
    var customers = @json($customers->map(fn($c) => ['id' => $c->id, 'name' => $c->customer_name, 'type' => $c->customer_type]));

    function loadParties(type, selectedId = null) {
        var list = [];
        if (type === 'vendor') {
            list = vendors;
        } else if (type === 'customer') {
            list = customers;
        } else if (type === 'walkin') {
            list = customers.filter(function(c) {
                return (c.type || '').toLowerCase().indexOf('walking') !== -1;
            });
        }

        var $drop = $('#vendor_id_select');
        var html  = '<option value="" disabled>-- Select --</option>';
        list.forEach(function(item) {
            var selected = (selectedId && String(item.id) === String(selectedId)) ? 'selected' : '';
            html += '<option value="' + item.id + '" ' + selected + '>' + item.name + '</option>';
        });
        $drop.html(html);
    }

    // When Type changes -> fill Vendor dropdown
    $('#vendor_type_select').on('change', function() {
        var type = $(this).val();
        if (type) loadParties(type);
    });

    // Initial load for edit mode
    var initialType = $('#vendor_type_select').val();
    var initialId = "{{ isset($purchase) ? $purchase->purchasable_id : '' }}";
    if(initialType) {
        loadParties(initialType, initialId);
    }

});
</script>


{{-- AJAX Save, Post, Print, Keyboard Shortcuts -- same as stock wastage --}}
<script>
$(document).ready(function() {

    // =============================================
    //  SAVED PURCHASE STATE (after AJAX save)
    // =============================================
    var _savedPurchaseId = @json(isset($purchase) ? $purchase->id : null);

    // =============================================
    //  SHOW SUCCESS TOAST
    // =============================================
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

    // =============================================
    //  AJAX SAVE DRAFT (no page reload)
    // =============================================
    function ajaxSaveDraft() {
        var $form  = $('#purchaseForm');

        $('#saveDraftBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Saving...');

        $.ajax({
            url:  $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                if (res.success) {
                    _savedPurchaseId = res.id;
                    // Clear navigation guard - form is now saved
                    if (typeof window.markFormSaved === 'function') window.markFormSaved();
                    showToast('✅ Draft Saved — ' + (res.message || 'Purchase saved as unposted.'), 'success');

                    // Show Post button (becomes real post)
                    $('#postBtn')
                        .show()
                        .prop('disabled', false)
                        .removeClass('btn-primary')
                        .addClass('btn-success')
                        .html('<i class="fa fa-send me-1"></i> Post <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+↵</kbd>');

                    // Update print button to real invoice link
                    var printUrl = '/purchase/' + res.id + '/invoice';
                    if ($('#previewPrintBtn').length) {
                        $('#previewPrintBtn').replaceWith(
                            $('<a>').attr({href: printUrl, target:'_blank', id:'realPrintBtn', class:'btn btn-sm btn-outline-dark rounded-pill px-4'})
                            .html('<i class="fa fa-print me-1"></i> Print <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+P</kbd>')
                        );
                    }
                } else {
                    showToast('❌ ' + (res.message || 'Error saving draft.'), 'error');
                }
            },
            error: function(xhr) {
                var msg = 'Save failed.';
                try { msg = JSON.parse(xhr.responseText).message || msg; } catch(e){}
                showToast('❌ ' + msg, 'error');
            },
            complete: function() {
                $('#saveDraftBtn').prop('disabled', false)
                    .html('<i class="fa fa-floppy-o me-1"></i> Save Draft <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>');
            }
        });
    }

    // =============================================
    //  POST (after save) → AJAX → reload create page
    // =============================================
    function doPost() {
        if (!_savedPurchaseId) {
            showToast('⚠️ Please save draft first before posting.', 'error');
            return;
        }
        $('#postBtn').prop('disabled', true)
            .html('<i class="fa fa-spinner fa-spin me-1"></i> Posting...');

        $.ajax({
            url:  '/purchase/' + _savedPurchaseId + '/post',
            type: 'POST',
            data: { _token: $('input[name="_token"]').first().val() },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                showToast('✅ Purchase posted successfully! Redirecting...', 'success');
                setTimeout(function() {
                    window.location.href = '/add/Purchase';
                }, 2000);
            },
            error: function(xhr) {
                var msg = 'Post failed.';
                try {
                    var r = JSON.parse(xhr.responseText);
                    msg = r.message || r.error || msg;
                } catch(e) {}
                showToast('❌ ' + msg, 'error');
                $('#postBtn').prop('disabled', false)
                    .html('<i class="fa fa-send me-1"></i> Post <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+↵</kbd>');
            }
        });
    }

    // =============================================
    //  BUTTON CLICK HANDLERS
    // =============================================
    $('#saveDraftBtn').on('click', function() { ajaxSaveDraft(); });
    $('#postBtn').on('click',      function() { doPost(); });
    $('#previewPrintBtn').on('click', function() {
        if (!_savedPurchaseId) {
            showToast('⚠️ Please save draft first before printing.', 'error');
            return;
        }
        window.open('/purchase/' + _savedPurchaseId + '/invoice', '_blank');
    });

    // NOTE: Global keyboard shortcuts are handled in a single block below to avoid duplicate saves.

    // Ctrl+L → List page (capture phase — overrides browser address bar shortcut)
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && (e.key === 'l' || e.key === 'L')) {
            e.preventDefault();
            window.location.href = $('#listBtn').attr('href');
        }
    }, true);

    // =============================================
    //  FLAGSHIP ITEM ROW MANAGEMENT
    // =============================================
    
    window.appendBlankRow = function(force = false, focus = true) {
        console.log('Global appendBlankRow triggered');
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
                <td style="width:100px;">
                   <input type="text" class="form-control form-control-sm item-id-input" placeholder="ID">
                </td>
                <td style="width: 250px;">
                  <select name="product_id[]" class="form-control form-control-sm product-select" style="width: 100%;">
                    <option value="" disabled selected>Select Product</option>
                  </select>
                  <input type="hidden" name="product_name[]" class="product_name_hidden">
                </td>
                <td class="uom border">
                    <input type="text" name="brand[]" class="form-control form-control-sm brand-name" readonly>
                </td>
                <td>
                    <input type="number" step="0.01" name="price[]" class="form-control form-control-sm price">
                </td>
                <td>
                    <input type="text" name="retail_price_show[]" class="form-control form-control-sm retail_price_show" readonly>
                </td>
                <td>
                  <div class="input-group">
                    <input type="number" step="0.01" min="0" name="item_disc[]" class="form-control form-control-sm item_disc" placeholder="%" value="">
                    <input type="text" name="item_disc_amount[]" class="form-control form-control-sm disc_amount" readonly placeholder="Disc Amt">
                  </div>
                  <input type="hidden" name="purchase_retail_price[]" class="purchase_retail_price">
                  <input type="hidden" name="purchase_net_amount[]" class="purchase_net_amount">
                </td>
                <td class="qty">
                    <input type="number" name="qty[]" class="form-control form-control-sm quantity" value="1" min="1">
                </td>
                <td>
                    <input type="text" name="amount[]" class="form-control form-control-sm row-amount" readonly>
                </td>
                <td class="total border">
                    <input type="text" name="total[]" class="form-control form-control-sm row-total" readonly>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger remove-row" title="Delete Row (Ctrl+X)">
                        X <span style="font-size: 8px; opacity: 0.8; margin-left: 1px;">Ctrl+X</span>
                    </button>
                </td>
            </tr>`;
        
        const $row = $(newRowHtml);
        $('#purchaseItems').append($row);
        if (window.initProductSelect) window.initProductSelect($row);
        
        if (focus) {
            setTimeout(() => { $row.find('.item-id-input').focus(); }, 50);
        }
    };
    // Ctrl+X shortcut to remove the current row
    $(document).on('keydown', 'input, select', function(e) {
        if (e.ctrlKey && (e.key === 'x' || e.key === 'X')) {
            const $row = $(this).closest('tr');
            if ($row.length && $row.find('.remove-row').length) {
                e.preventDefault();
                $row.find('.remove-row').trigger('click');
            }
        }
    });

    $(document).on('click', '.remove-row', function() {
        if ($('#purchaseItems tr').length > 1) {
            $(this).closest('tr').remove();
            if (typeof window.recalcSummary === 'function') window.recalcSummary();
        } else {
            showToast('⚠️ At least one row must remain!', 'error');
        }
    });



    $(document).on('keydown', '.price', function(e) {
        if (e.key === 'Enter') {
            const $row = $(this).closest('tr');
            $row.find('.quantity').focus().select();
            e.preventDefault();
            return false;
        }
    });

    $(document).on('keydown', '.quantity', function(e) {
        if (e.key === 'Enter') {
            const $row = $(this).closest('tr');
            if ($row.is(':last-child')) {
                window.appendBlankRow(true);
            } else {
                $row.next().find('.item-id-input').focus();
            }
            e.preventDefault();
            return false;
        }
    });

    // =============================================
    //  ACCOUNTS ALLOCATION LOGIC (Fixed & Simple)
    // =============================================
    
    $(document).on('change', '.accountHead', function() {
        var headId = $(this).val();
        var $row = $(this).closest('tr');
        var $accSelect = $row.find('.accountSub');

        if (!headId) return;

        $.ajax({
            url: "{{ url('/get-accounts-by-head') }}/" + headId,
            type: "GET",
            success: function(res) {
                var html = '<option value="" disabled selected>Select Account</option>';
                if (res && res.length) {
                    res.forEach(function(acc) {
                        html += '<option value="' + acc.id + '">' + acc.title + '</option>';
                    });
                } else {
                    html = '<option value="" disabled>No Accounts Found</option>';
                }
                $accSelect.html(html);
            },
            error: function(err) {
                console.error('AJAX Error:', err.statusText);
            }
        });
    });

    // 2. Add Account Row
    $('#addAccountRow').on('click', function() {
        var newRow = `<tr>
            <td>
                <select name="account_head_id[]" class="form-control form-control-sm accountHead">
                    <option value="" disabled selected>Select Head</option>
                    @foreach ($AccountHeads as $head)
                        <option value="{{ $head->id }}">{{ $head->name }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <select name="account_id[]" class="form-control form-control-sm accountSub">
                    <option value="" disabled selected>Select Account</option>
                </select>
            </td>
            <td>
                <input type="number" step="0.01" name="account_amount[]" class="form-control form-control-sm accountAmount" value="0">
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger removeAccountRow">X</button>
            </td>
        </tr>`;
        $('#accountsTable tbody').append(newRow);
    });

    // 3. Remove Account Row
    $(document).on('click', '.removeAccountRow', function() {
        $(this).closest('tr').remove();
        window.recalcAccountsTotal();
    });

    // 4. Recount Allocation Totals
    window.recalcAccountsTotal = function() {
        var total = 0;
        $('.accountAmount').each(function() {
            total += parseFloat($(this).val()) || 0;
        });
        $('#accountsTotal').val(total.toFixed(2));
        // Responsibility to update overallDiscount is now in recalcSummary
        if (typeof window.recalcSummary === 'function') window.recalcSummary();
    }

    $(document).on('input', '.accountAmount', function() {
        window.recalcAccountsTotal();
    });

    // 5. Enter on Account Amount -> Add New Row
    $(document).on('keydown', '.accountAmount', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $('#addAccountRow').trigger('click');
            // Focus the first select of the new row
            setTimeout(function() {
                $('#accountsTable tbody tr:last .accountHead').focus();
            }, 60);
        }
    });

    // =============================================
    //  GLOBAL SHORTCUTS
    // =============================================
    $(document).on('keydown', function(e) {
        // Ctrl + S -> Save Draft
        if (e.ctrlKey && (e.key === 's' || e.key === 'S')) {
            e.preventDefault();
            $('#saveDraftBtn').trigger('click');
        }
        // Ctrl + P -> Print
        if (e.ctrlKey && (e.key === 'p' || e.key === 'P')) {
            e.preventDefault();
            if ($('#previewPrintBtn').length) {
                $('#previewPrintBtn').trigger('click');
            } else {
                 const prtLink = $('a.btn-outline-dark[href*="invoice"]').attr('href');
                 if(prtLink) window.open(prtLink, '_blank');
            }
        }
        // Ctrl + Enter -> Post
        if (e.ctrlKey && e.key === 'Enter') {
            e.preventDefault();
            $('#postBtn').trigger('click');
        }
        // Ctrl + L -> List
        if (e.ctrlKey && (e.key === 'l' || e.key === 'L')) {
            e.preventDefault();
            const listUrl = $('#listBtn').attr('href');
            if(listUrl) window.location.href = listUrl;
        }
    });

});
</script>
@endsection