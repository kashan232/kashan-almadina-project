@extends('admin_panel.layout.app')

@section('content')
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
    .locked-bg {
        background-color: #fcfcfc !important;
    }
    .form-locked input, .form-locked select, .form-locked textarea { pointer-events: none; opacity: 0.8; }
    .form-locked .remove-row, .form-locked .removeAccountRow, .form-locked #addRow, .form-locked #addAccountRow, .form-locked #saveDraftBtn { display: none !important; }
    
</style>
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

                        <div class="row gy-3 ">
                            <div class="col-lg-12 col-md-12 mb-30 m-auto">

<style>
  .main-container {
    font-size: .85rem;
    max-width: 1400px;
  }

  .header-text {
    font-size: 1.1rem;
  }

  .form-control,
  .form-select,
  .btn {
    font-size: .8rem;
    padding: .25rem .5rem;
    height: auto;
  }

  .input-readonly {
    background: #f9fbff !important;
  }

  .section-title {
    font-weight: 700;
    color: #6c757d;
    letter-spacing: .3px;
  }

  .form-locked input,
  .form-locked select,
  .form-locked textarea,
  .form-locked label,
  .form-locked .btn-group .btn,
  .form-locked .select2-container,
  .form-locked .remove-row,
  .form-locked .removeAccountRow,
  .form-locked #addRow,
  .form-locked #addAccountRow {
    pointer-events: none !important;
    opacity: 0.65 !important;
    cursor: not-allowed !important;
  }
  
  .form-locked input:not([type="hidden"]), 
  .form-locked select, 
  .form-locked textarea,
  .form-locked .select2-selection {
    background-color: #e9ecef !important;
  }

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
  .posted-watermark.show { display: block; }

  .table {
    --bs-table-padding-y: .35rem;
    --bs-table-padding-x: .5rem;
    font-size: .85rem;
  }

  .table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #f8f9fa !important;
    text-align: center;
    font-size: 0.75rem;
    padding: 4px !important;
  }
  
  .table-sm td {
    padding: 2px !important;
    vertical-align: middle;
  }

  .table-sm .form-control, 
  .table-sm .form-select {
    padding: 2px 4px !important;
    font-size: 0.8rem !important;
    height: 26px !important;
    min-height: 26px !important;
  }

  /* Compact Select2 */
  .select2-container--default .select2-selection--single {
    height: 26px !important;
    font-size: 0.8rem !important;
    border-color: #dee2e6 !important;
  }
  .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 24px !important;
    padding-left: 6px !important;
  }
  .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 24px !important;
  }

  .table-responsive {
    max-height: 400px;
    overflow: auto;
    border: 1px solid #eee;
    border-radius: .5rem;
  }

  .totals-card {
    background: #fcfcfe;
    border: 1px solid #eee;
    border-radius: .5rem;
  }

  .totals-card .row+.row {
    border-top: 1px dashed #e5e7eb;
  }

  .loading-indicator {
    background-color: #fff9c4 !important;
    border-color: #fdd835 !important;
    transition: background-color 0.3s ease;
  }

  .btn-xs {
    padding: 1px 4px;
    font-size: 0.7rem;
    line-height: 1.2;
  }
</style>

<div class="container-fluid py-2 main-content bg-white">
  <div class="main-container bg-white border shadow-sm mx-auto p-2 rounded-3" style="max-width: 98%;">

    <div id="alertBox" class="alert d-none mb-2" role="alert"></div>

    {{-- TOP BAR --}}
    <div class="d-flex justify-content-between align-items-center mb-2 bg-light p-1 rounded shadow-sm px-3">
      <div class="d-flex align-items-center gap-2">
          <span id="statusBadge" class="badge {{ isset($purchase) && $purchase->status == 'Posted' ? 'bg-success' : 'bg-warning text-dark' }} px-2 py-1 rounded shadow-sm" style="font-size:11px;">
              <i class="fa {{ isset($purchase) && $purchase->status == 'Posted' ? 'fa-check' : 'fa-pencil' }} me-1"></i> {{ isset($purchase) ? ucfirst($purchase->status) : 'New Purchase' }}
          </span>
      </div>

      <div class="d-flex align-items-center gap-2">
          @if(isset($purchase) && $purchase->status != 'Posted')
              <form action="{{ route('purchase.post', $purchase->id) }}" method="POST" class="d-inline">
                  @csrf
                  <button type="submit" class="btn btn-sm btn-primary py-0 px-3 rounded-pill shadow-sm">
                      <i class="fa fa-send me-1"></i> Post
                  </button>
              </form>
          @endif
          <a href="{{ route('Purchase.home') }}" id="listBtn" class="btn btn-sm btn-outline-secondary py-0 px-3 rounded-pill">
              <i class="fa fa-list me-1"></i> List <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+L</kbd>
          </a>
      </div>
    </div>

    <form id="purchaseForm" class="{{ isset($purchase) && $purchase->status == 'Posted' ? 'form-locked' : '' }}" autocomplete="off" action="{{ isset($purchase) ? route('purchase.update', $purchase->id) : route('store.Purchase') }}" method="POST">
      @csrf
      @if(isset($purchase))
          @method('PUT')
      @endif

      <div class="posted-watermark {{ isset($purchase) && $purchase->status == 'Posted' ? 'show' : '' }}" id="postedWatermark">Posted</div>

      <div class="d-flex gap-2 align-items-stretch border-bottom py-2">
        {{-- LEFT: Header & Vendor --}}
        <div class="bg-light border rounded-3 p-2 shadow-sm" style="min-width: 280px; max-width: 280px; font-size: 0.8rem;">
          <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
            <h6 class="mb-0 fw-bold text-primary">
              <i class="fa fa-info-circle me-1"></i>Purchase Details
            </h6>
          </div>

          {{-- Entry Date & Time --}}
          <div class="row g-1 mb-2">
            <div class="col-6">
              <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">Entry Date</label>
              <input type="date" class="form-control form-control-sm py-0" name="entry_date" value="{{ old('entry_date', isset($purchase) ? ($purchase->entry_date ?? \Carbon\Carbon::parse($purchase->current_date)->format('Y-m-d')) : date('Y-m-d')) }}" required>
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">Entry Time</label>
              <input type="time" class="form-control form-control-sm py-0" name="entry_time" value="{{ old('entry_time', isset($purchase) ? ($purchase->entry_time ?? \Carbon\Carbon::parse($purchase->created_at)->format('H:i')) : date('H:i')) }}" required>
            </div>
          </div>

          {{-- Invoice Number --}}
          <div class="mb-2">
            <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">Inv Number</label>
            <input type="text" class="form-control form-control-sm py-0 fw-bold text-primary bg-white" value="{{ $nextInvoice }}" readonly>
          </div>

          {{-- DC Date & Bilty --}}
          <div class="row g-1 mb-2">
            <div class="col-6">
              <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">DC Date</label>
              <input type="date" class="form-control form-control-sm py-0" name="dc_date" value="{{ old('dc_date', isset($purchase) ? \Carbon\Carbon::parse($purchase->dc_date)->format('Y-m-d') : date('Y-m-d')) }}">
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">Bilty No</label>
              <input type="text" class="form-control form-control-sm py-0" name="bilty_no" value="{{ old('bilty_no', $purchase->bilty_no ?? '') }}" placeholder="Bilty #">
            </div>
          </div>

          {{-- DC# & Warehouse --}}
          <div class="row g-1 mb-2">
            <div class="col-4">
              <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">DC No.</label>
              <input type="text" class="form-control form-control-sm py-0 fw-bold text-primary" name="dc" value="{{ old('dc', $purchase->dc ?? '') }}" placeholder="DC #">
            </div>
            <div class="col-8">
              <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">Warehouse</label>
              <select name="warehouse_id" class="form-select form-select-sm py-0" required>
                  @if(auth()->user()->canAccessShop())
                      <option value="0" {{ (string)old('warehouse_id', $purchase->warehouse_id ?? '') === '0' ? 'selected' : '' }}>🏠 Shop</option>
                  @endif
                  @foreach ($Warehouse as $ware)
                  <option value="{{ $ware->id }}" {{ (string)old('warehouse_id', $purchase->warehouse_id ?? '') === (string)$ware->id ? 'selected' : '' }}>📦 {{ $ware->warehouse_name }}</option>
                  @endforeach
              </select>
            </div>
          </div>

          {{-- Party Type Toggle --}}
          <div class="mb-2">
            <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">Party Type</label>
            @php 
              $vType = old('vendor_type');
              if (!$vType && isset($purchase)) {
                  $baseType = strtolower(class_basename($purchase->purchasable_type));
                  if ($baseType == 'customer' && $purchase->purchasable) {
                      $ctype = strtolower($purchase->purchasable->customer_type ?? '');
                      $vType = (strpos($ctype, 'walk') !== false) ? 'walkin' : 'customer';
                  } else {
                      $vType = $baseType;
                  }
              }
              $vType = $vType ?: 'vendor';
            @endphp
            <div class="btn-group w-100" role="group">
              <input type="radio" class="btn-check vendor-type-radio" name="vendor_type" id="typeVendor" value="vendor" {{ $vType == 'vendor' ? 'checked' : '' }}>
              <label class="btn btn-outline-primary btn-sm py-0" for="typeVendor" style="font-size: 0.75rem;">Vendor</label>

              <input type="radio" class="btn-check vendor-type-radio" name="vendor_type" id="typeCustomer" value="customer" {{ $vType == 'customer' ? 'checked' : '' }}>
              <label class="btn btn-outline-primary btn-sm py-0" for="typeCustomer" style="font-size: 0.75rem;">Customer</label>

              <input type="radio" class="btn-check vendor-type-radio" name="vendor_type" id="typeWalkin" value="walkin" {{ $vType == 'walkin' ? 'checked' : '' }}>
              <label class="btn btn-outline-primary btn-sm py-0" for="typeWalkin" style="font-size: 0.75rem;">Walk-in</label>
            </div>
          </div>

          {{-- Select Party --}}
          <div class="mb-2">
            <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">Select Party</label>
            <select name="vendor_id" id="vendor_id_select" class="form-select form-select-sm py-0 select2" required>
                <option value="" disabled selected>Select Party</option>
                @if(isset($purchase))
                    <option value="{{ $purchase->purchasable_id }}" selected>
                        {{ $purchase->purchasable->name ?? ($purchase->purchasable->customer_name ?? 'Unknown') }}
                    </option>
                @endif
            </select>
          </div>

          {{-- Remarks --}}
          <div class="mb-1">
            <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">Remarks</label>
            <textarea class="form-control form-control-sm py-1" name="remarks" rows="2" placeholder="Purchase notes..." style="font-size: 0.75rem;">{{ old('remarks', $purchase->note ?? '') }}</textarea>
          </div>
        </div>

        {{-- RIGHT: Items --}}
        <div class="flex-grow-1 d-flex flex-column">
          <div class="d-flex justify-content-between align-items-center mb-2 px-2">
            <div class="section-title mb-0">Items</div>
            <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" id="addRow">
                <i class="fa fa-plus me-1"></i> Add Row
            </button>
          </div>

          <div class="table-responsive flex-grow-1 d-flex flex-column" style="min-height: 420px; overflow-y: auto;">
            <table class="table table-bordered table-sm mb-0" style="width: 100%; font-size: 0.9rem; table-layout: fixed;">
              <colgroup>
                  <col style="width:6%"> <!-- Item ID -->
                  <col style="width:14%"> <!-- Product -->
                  <col style="width:10%"> <!-- Brand -->
                  <col style="width:10%"> <!-- Price -->
                  <col style="width:10%"> <!-- Retail Price -->
                  <col style="width:15%"> <!-- Disc -->
                  <col style="width:6%">  <!-- Qty -->
                  <col style="width:12%"> <!-- Rate -->
                  <col style="width:14%"> <!-- Total -->
                  <col style="width:3%">  <!-- Action -->
              </colgroup>
              <thead class="table-light">
                <tr>
                  <th>Item ID</th>
                  <th>Product</th>
                  <th>Brand</th>
                  <th class="text-end">Price</th>
                  <th class="text-end">Retail Price</th>
                  <th>Disc</th>
                  <th class="text-center">Qty</th>
                  <th class="text-end">Rate</th>
                  <th class="text-end">Total</th>
                  <th class="text-center">—</th>
                </tr>
              </thead>
              <tbody id="purchaseItems">
                  {{-- Rows will be populated by JS or Blade if editing --}}
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
                              <td>
                                  <input type="text" class="form-control form-control-sm item-id-input text-center" placeholder="ID" value="{{ $item->product_id }}">
                              </td>
                              <td>
                                  <select name="product_id[]" class="form-control form-control-sm product-select" style="width: 100%;">
                                      <option value="{{ $item->product_id }}" selected>{{ $product?->name ?? 'Unknown' }}</option>
                                  </select>
                                  <input type="hidden" name="product_name[]" class="product_name_hidden" value="{{ $product?->name }}">
                              </td>
                              <td>
                                  <input type="text" name="brand[]" class="form-control form-control-sm brand-name input-readonly" readonly value="{{ $product?->brandRelation?->name ?? '' }}">
                              </td>
                              <td>
                                  <input type="number" step="0.01" name="price[]" class="form-control form-control-sm price text-end" value="{{ $item->price }}">
                              </td>
                              <td>
                                  <input type="number" step="0.01" name="retail_price_show[]" class="form-control form-control-sm retail_price_show text-end" value="{{ $retail }}">
                              </td>
                              <td>
                                  <div class="input-group input-group-sm">
                                      <input type="number" step="0.01" min="0" name="item_disc[]" class="form-control form-control-sm item_disc text-end" placeholder="%" value="{{ $item->item_discount }}">
                                      <span class="input-group-text px-1" style="font-size: 0.7rem;">%</span>
                                      <input type="text" name="item_disc_amount[]" class="form-control form-control-sm disc_amount text-end input-readonly" readonly value="{{ number_format($disc_amt, 2, '.', '') }}">
                                  </div>
                                  <input type="hidden" name="purchase_retail_price[]" class="purchase_retail_price" value="{{ $retail }}">
                                  <input type="hidden" name="purchase_net_amount[]" class="purchase_net_amount" value="{{ $net }}">
                              </td>
                              <td>
                                  <input type="number" name="qty[]" class="form-control form-control-sm quantity text-center" value="{{ $item->qty }}" min="1">
                              </td>
                              <td>
                                  <input type="text" name="amount[]" class="form-control form-control-sm row-amount text-end input-readonly" readonly value="{{ number_format($item->price, 2, '.', '') }}">
                              </td>
                              <td>
                                  <input type="text" name="total[]" class="form-control form-control-sm row-total text-end fw-bold input-readonly" readonly value="{{ number_format($item->line_total, 2, '.', '') }}">
                              </td>
                              <td class="text-center">
                                  <button type="button" class="btn btn-xs btn-outline-danger remove-row">&times;</button>
                              </td>
                          </tr>
                      @endforeach
                  @endif
              </tbody>
            </table>
            
            <table class="table table-bordered table-sm mb-0 mt-auto" style="width: 100%; font-size: 0.9rem; table-layout: fixed;">
              <colgroup>
                  <col style="width:6%"> <!-- Item ID -->
                  <col style="width:14%"> <!-- Product -->
                  <col style="width:10%"> <!-- Brand -->
                  <col style="width:10%"> <!-- Price -->
                  <col style="width:10%"> <!-- Retail Price -->
                  <col style="width:15%"> <!-- Disc -->
                  <col style="width:6%">  <!-- Qty -->
                  <col style="width:12%"> <!-- Rate -->
                  <col style="width:14%"> <!-- Total -->
                  <col style="width:3%">  <!-- Action -->
              </colgroup>
              <tfoot class="bg-light border-top shadow-sm">
                <tr class="align-middle">
                  <td colspan="6" class="text-end fw-bold text-muted text-uppercase pe-3" style="font-size: 0.75rem; letter-spacing: 0.5px;">Totals:</td>
                  <td class="text-center fw-bold text-primary" style="font-size: 0.95rem;"><span id="tQty">0</span></td>
                  <td></td>
                  <td class="text-end fw-bold text-success" style="font-size: 1rem;"><span id="tSubTotal">0.00</span></td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>

      {{-- Accounts Allocation & Totals --}}
      <div class="row g-3 mt-1">
        {{-- Accounts Allocation --}}
        <div class="col-lg-7">
          <div class="bg-light border rounded-3 p-2 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
              <h6 class="mb-0 fw-bold text-success">
                <i class="fa fa-share-alt me-2"></i>Accounts Allocation
              </h6>
              <button type="button" class="btn btn-success btn-sm rounded-pill px-3" id="addAccountRow">
                <i class="fa fa-plus me-1"></i>Add Account
              </button>
            </div>
            
            <div class="table-responsive" style="max-height: 250px;">
              <table class="table table-bordered table-sm mb-0" id="accountsTable">
                <thead class="table-light">
                  <tr>
                    <th>Account Head</th>
                    <th>Sub Account</th>
                    <th style="width:120px;">Amount</th>
                    <th style="width:40px;" class="text-center">—</th>
                  </tr>
                </thead>
                <tbody id="accountsTableBody">
                    @if(isset($purchase) && $purchase->accountAllocations->count() > 0 && !old('account_head_id'))
                        @foreach($purchase->accountAllocations as $index => $acc)
                            <tr>
                                <td>
                                    <select name="account_head_id[]" class="form-select form-select-sm accountHead">
                                        <option value="" disabled>Select Head</option>
                                        @foreach ($AccountHeads as $head)
                                            <option value="{{ $head->id }}" {{ $head->id == $acc->account_head_id ? 'selected' : '' }}>{{ $head->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select name="account_id[]" class="form-select form-select-sm accountSub">
                                        <option value="" disabled>Select Account</option>
                                        @php
                                            $headAccounts = \App\Models\Account::where('head_id', $acc->account_head_id)->where('status', 1)->get();
                                        @endphp
                                        @foreach($headAccounts as $hAcc)
                                            <option value="{{ $hAcc->id }}" {{ $hAcc->id == $acc->account_id ? 'selected' : '' }}>{{ $hAcc->title }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="number" step="0.01" name="account_amount[]" class="form-control form-control-sm accountAmount text-end" value="{{ $acc->amount }}">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-xs btn-outline-danger removeAccountRow">&times;</button>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
              </table>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mt-2 p-2 bg-success bg-opacity-10 rounded-3">
              <span class="text-success fw-bold">Allocation Total:</span>
              <input type="text" id="accountsTotal" class="form-control form-control-sm text-end fw-bold text-success border-0 bg-transparent py-0" value="0.00" readonly style="width: 150px; font-size: 1.1rem;">
            </div>
          </div>
        </div>

        {{-- Totals --}}
        <div class="col-lg-5">
          <div class="bg-light border rounded-3 p-2 shadow-sm h-100">
            <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
              <h6 class="mb-0 fw-bold text-info">
                <i class="fa fa-calculator me-2"></i>Purchase Totals
              </h6>
            </div>

            <div class="totals-card p-2">
              <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <span class="text-muted small">Sub-Total</span>
                <input type="text" id="subtotal" name="subtotal" class="form-control form-control-sm text-end fw-bold input-readonly border-0 bg-transparent" value="{{ old('subtotal', $purchase->subtotal ?? 0) }}" readonly style="width:150px">
              </div>

              <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <span class="text-muted small">Total Discount</span>
                <input type="number" step="0.01" id="overallDiscount" name="discount" class="form-control form-control-sm text-end input-readonly border-0 bg-transparent" value="{{ old('discount', $purchase->discount ?? 0) }}" readonly style="width:150px">
              </div>

              <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <span class="text-muted small">WHT (Tax)</span>
                <div class="d-flex align-items-center gap-2">
                  <div class="d-flex gap-1" style="width:190px;">
                    <select id="wht_head_id" class="form-select form-select-sm" style="width:80px;">
                      <option value="">Head</option>
                      @foreach($AccountHeads as $head)
                          <option value="{{ $head->id }}" {{ (isset($purchase) && $purchase->whtAccount && $purchase->whtAccount->head_id == $head->id) ? 'selected' : '' }}>
                              {{ $head->name }}
                          </option>
                      @endforeach
                    </select>
                    <select name="wht_account_id" id="wht_account_id" class="form-select form-select-sm" style="flex-grow:1;">
                      <option value="">Account</option>
                      @if(isset($purchase) && $purchase->whtAccount)
                          <option value="{{ $purchase->wht_account_id }}" selected>{{ $purchase->whtAccount->title }}</option>
                      @endif
                    </select>
                  </div>
                  <div class="d-flex align-items-center gap-1">
                    <input type="number" step="0.01" id="whtPercent" name="wht_percent" class="form-control form-control-sm text-end" placeholder="Val" value="{{ old('wht_percent', $purchase->wht_percent ?? '') }}" style="width:60px">
                    <select id="whtType" name="wht_type" class="form-select form-select-sm" style="width:60px;">
                        @php $wType = old('wht_type', $purchase->wht_type ?? 'percent'); @endphp
                        <option value="percent" {{ $wType == 'percent' ? 'selected' : '' }}>%</option>
                        <option value="amount" {{ $wType == 'amount' ? 'selected' : '' }}>PKR</option>
                    </select>
                  </div>
                  <input type="text" id="whtAmount" name="wht_amount" class="form-control form-control-sm text-end input-readonly border-0 bg-transparent fw-bold" value="{{ old('wht_amount', 0) }}" readonly style="width:80px">
                </div>
                <input type="hidden" id="whtValue" name="wht" value="{{ old('wht', $purchase->wht ?? 0) }}">
              </div>

              <div class="d-flex justify-content-between align-items-center py-3">
                <span class="fw-bold text-dark">Net Amount</span>
                <input type="text" id="netAmount" name="net_amount" class="form-control form-control-lg text-end fw-bold text-primary border-0 bg-transparent" value="{{ old('net_amount', $purchase->net_amount ?? 0) }}" readonly style="width:180px; font-size: 1.5rem;">
              </div>
            </div>

            {{-- BOTTOM BUTTONS --}}
            <div class="d-flex gap-2 mt-3 justify-content-end">
                <button type="button" id="saveDraftBtn" class="btn btn-sm btn-warning rounded-pill px-4 shadow-sm">
                    <i class="fa fa-floppy-o me-1"></i> Save Draft <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>
                </button>

                @if(isset($purchase))
                    <a href="{{ route('purchase.invoice', $purchase->id) }}" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill px-4">
                        <i class="fa fa-print me-1"></i> Print <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+P</kbd>
                    </a>
                @else
                    <button type="button" id="previewPrintBtn" class="btn btn-sm btn-outline-dark rounded-pill px-4">
                        <i class="fa fa-print me-1"></i> Preview <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+P</kbd>
                    </button>
                @endif

                <button type="button" id="postBtn" class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm" style="{{ (isset($purchase) && $purchase->status == 'Posted') ? 'display: none;' : '' }}">
                    <i class="fa fa-send me-1"></i> {{ isset($purchase) ? 'Update & Post' : 'Save & Post' }} <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+&#8629;</kbd>
                </button>

                <button type="button" id="editInvoiceBtn" 
                    class="btn btn-sm btn-warning rounded-pill px-4 shadow-sm text-dark" 
                    style="{{ (isset($purchase) && $purchase->status == 'Posted') ? '' : 'display: none;' }}">
                    <i class="fa fa-edit me-1"></i> Edit 
                    <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+E</kbd>
                </button>

                <a href="{{ route('add_purchase') }}" id="newInvoiceBtn" 
                    class="btn btn-sm btn-info rounded-pill px-4 shadow-sm text-white" 
                    style="{{ isset($purchase) ? '' : 'display: none;' }}">
                    <i class="fa fa-plus me-1"></i> New 
                    <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+M</kbd>
                </a>

                <a href="{{ route('Purchase.home') }}" class="btn btn-sm btn-danger rounded-pill px-4 shadow-sm text-white">
                    <i class="fa fa-times me-1"></i> Cancel <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Esc</kbd>
                </a>
            </div>
          </div>
        </div>
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
                    <td>
                        <input type="text" class="form-control form-control-sm item-id-input text-center" placeholder="ID">
                    </td>
                    <td>
                        <select name="product_id[]" class="form-control form-control-sm product-select" style="width: 100%;">
                            <option value="" disabled selected>Select Product</option>
                        </select>
                        <input type="hidden" name="product_name[]" class="product_name_hidden">
                    </td>
                    <td>
                        <input type="text" name="brand[]" class="form-control form-control-sm brand-name input-readonly" readonly>
                    </td>
                    <td>
                        <input type="number" step="0.01" name="price[]" class="form-control form-control-sm price text-end">
                    </td>
                    <td>
                        <input type="text" name="retail_price_show[]" class="form-control form-control-sm retail_price_show text-end input-readonly" readonly>
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.01" min="0" name="item_disc[]" class="form-control form-control-sm item_disc text-end" placeholder="%">
                            <span class="input-group-text px-1" style="font-size: 0.7rem;">%</span>
                            <input type="text" name="item_disc_amount[]" class="form-control form-control-sm disc_amount text-end input-readonly" readonly placeholder="Amt">
                        </div>
                        <input type="hidden" name="purchase_retail_price[]" class="purchase_retail_price">
                        <input type="hidden" name="purchase_net_amount[]" class="purchase_net_amount">
                    </td>
                    <td>
                        <input type="number" name="qty[]" class="form-control form-control-sm quantity text-center" value="1" min="1">
                    </td>
                    <td>
                        <input type="text" name="amount[]" class="form-control form-control-sm row-amount text-end input-readonly" readonly>
                    </td>
                    <td>
                        <input type="text" name="total[]" class="form-control form-control-sm row-total text-end fw-bold input-readonly" readonly>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-xs btn-outline-danger remove-row" title="Delete (Ctrl+X)">&times;</button>
                    </td>
                </tr>`;
            
            const $row = $(newRowHtml);
            $('#purchaseItems').append($row);
            if (window.initProductSelect) window.initProductSelect($row);
            
            if (focus) {
                setTimeout(() => { $row.find('.item-id-input').focus(); }, 50);
            }
        };

        // Global helper for initializing Select2 on a row
        window.initProductSelect = function($row) {
            const $select = $row.find('.product-select');
            
            if ($select.hasClass('select2-hidden-accessible')) {
                return; // Already initialized
            }

            $select.select2({
                placeholder: "Select Product",
                allowClear: true,
                width: '100%',
                ajax: {
                    url: "{{ route('search-products') }}",
                    dataType: 'json',
                    delay: 100, 
                    data: function (params) {
                        return {
                            q: params.term // search term
                        };
                    },
                    processResults: function (data, params) {
                        const term = (params.term || '').toLowerCase();
                        const results = data.map(function(item) {
                            return {
                                id: item.id,
                                text: item.name,
                                // Pass custom data
                                brand: item.brand,
                                price_net: item.purchase_net_amount,
                                price_retail: item.purchase_retail_price
                            };
                        });

                        // Prioritize exact matches (ID or Name) at the top of the list
                        results.sort((a, b) => {
                            if (String(a.id) === term || a.text.toLowerCase() === term) return -1;
                            if (String(b.id) === term || b.text.toLowerCase() === term) return 1;
                            return 0;
                        });

                        return { results };
                    },
                    cache: true
                },
                minimumInputLength: 1
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
                const $input = $(this);
                const id = $input.val().trim();
                if (!id) {
                    $select.val(null).trigger('change');
                    return;
                }
                
                
                $.getJSON("{{ route('search-products') }}", { 
                    q: id,
                    warehouse_id: $('select[name="warehouse_id"]').val() 
                }, function(data) {

                    
                    // Precise matching prioritize: Exact ID -> Exact Name (Case Insensitive) -> First Result if only 1
                    let product = data.find(p => String(p.id) === String(id)) 
                               || data.find(p => p.name.toLowerCase() === id.toLowerCase());
                    
                    if (!product && data.length === 1) {
                         product = data[0];
                    }

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
                        $input.val('');
                    }
                }).fail(function() {

                    showToast('❌ Server error!', 'error');
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
                const price_val = parseFloat(net) || 0;
                $currentRow.find('.row-amount').val(price_val.toFixed(2)); // Show single qty amount
                $currentRow.find('.row-total').val(price_val.toFixed(2));

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

        // Initialize any existing rows immediately if they exist
        $(function() {
            $('#purchaseItems tr').each(function() {
                if (window.initProductSelect) window.initProductSelect($(this));
            });
        });
    </script>

{{-- Item Row Autocomplete + Add/Remove --}}
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
            const getError = (field) => {
                if (index !== null && errors[field + '.' + index]) {
                     return `<div class="alert alert-danger p-1 mt-1" style="font-size: 10px; margin-bottom:0;">${errors[field + '.' + index][0]}</div>`;
                }
                return '';
            };

            let optionHtml = '<option value="" disabled selected>Select Product</option>';
            if(data.product_id) {
                const pName = data.product_name || 'Product ' + data.product_id;
                optionHtml = `<option value="${data.product_id}" selected>${pName}</option>`;
            }

            return `
                <tr>
                    <td>
                        <input type="text" class="form-control form-control-sm item-id-input text-center" placeholder="ID" value="${data.product_id || ''}">
                    </td>
                    <td>
                        <select name="product_id[]" class="form-control form-control-sm product-select" style="width: 100%;">
                            ${optionHtml}
                        </select>
                        <input type="hidden" name="product_name[]" class="product_name_hidden" value="${(data.product_name || '')}">
                        ${getError('product_id')}
                    </td>
                    <td>
                        <input type="text" name="brand[]" class="form-control form-control-sm brand-name input-readonly" readonly value="${data.brand || ''}">
                    </td>
                    <td>
                        <input type="number" step="0.01" name="price[]" class="form-control form-control-sm price text-end" value="${data.price || ''}">
                        ${getError('price')}
                    </td>
                    <td>
                        <input type="number" step="0.01" name="retail_price_show[]" class="form-control form-control-sm retail_price_show text-end" value="${data.retail_show || ''}">
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.01" min="0" name="item_disc[]" class="form-control form-control-sm item_disc text-end" placeholder="%" value="${data.item_disc || ''}">
                            <span class="input-group-text px-1" style="font-size: 0.7rem;">%</span>
                            <input type="text" name="item_disc_amount[]" class="form-control form-control-sm disc_amount text-end input-readonly" readonly placeholder="Amt" value="${data.disc_amount || ''}">
                        </div>
                        ${getError('item_disc')}
                        <input type="hidden" name="purchase_retail_price[]" class="purchase_retail_price" value="${data.purchase_retail || ''}">
                        <input type="hidden" name="purchase_net_amount[]" class="purchase_net_amount" value="${data.purchase_net || ''}">
                    </td>
                    <td>
                        <input type="number" name="qty[]" class="form-control form-control-sm quantity text-center" value="${data.qty || 1}" min="1">
                        ${getError('qty')}
                    </td>
                    <td>
                        <input type="text" name="amount[]" class="form-control form-control-sm row-amount text-end input-readonly" readonly value="${data.row_amount || ''}">
                    </td>
                    <td>
                        <input type="text" name="total[]" class="form-control form-control-sm row-total text-end fw-bold input-readonly" readonly value="${data.row_total || ''}">
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-xs btn-outline-danger remove-row" title="Delete">&times;</button>
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

            if (!isOldData) {
                const $container = $('#purchaseItems');
                if ($container.find('tr').length === 0) {
                    if(typeof window.appendBlankRow === 'function') window.appendBlankRow(true);
                } else {
                    $container.find('tr').each(function() {
                        if(typeof window.initProductSelect === 'function') window.initProductSelect($(this));
                    });
                }
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

            if (!isOldData) return;

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
                            <input type="number" step="0.01" name="account_amount[]" class="form-control form-control-sm accountAmount" value="${amt}" disabled>
                            ${getError('account_amount')}
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-danger removeAccountRow">X</button>
                        </td>
                    </tr>
                `;
                $('#accountsTable tbody').append(row);
                
                if(head) {
                     const $lastRow = $('#accountsTable tbody tr:last');
                     const headId = head;
                     const $accountSelect = $lastRow.find('.accountSub');
                     const $amountInput = $lastRow.find('.accountAmount');
                     
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
                            $accountSelect.trigger('change');
                            if (typeof recalcAccountsTotal === 'function') recalcAccountsTotal();
                        }
                    });
                }
            });

            if (typeof recalcAccountsTotal === 'function') recalcAccountsTotal();
        }

        $(function() {
            try {
                restoreProducts();
                restoreAccounts();
            } catch (e) {
                console.error('restore error', e);
            }
        });


    $(function() {
        $('#purchaseItems tr').each(function() {
            if (typeof window.initProductSelect === 'function') window.initProductSelect($(this));
        });
        
        setTimeout(() => {
            const $itemRows = $('#purchaseItems tr');
            if ($itemRows.length === 0) {
                if (typeof window.appendBlankRow === 'function') window.appendBlankRow();
            } else {
                $itemRows.each(function() {
                    if (typeof window.recalcRow === 'function') window.recalcRow($(this));
                });
            }
            
            if (typeof window.recalcAccountsTotal === 'function') window.recalcAccountsTotal();
            if (typeof window.recalcSummary === 'function') window.recalcSummary();
        }, 300);
    });

    $('#purchaseForm').on('submit', function(e) {
        $('#purchaseItems tr').each(function() {
            const pid = $(this).find('.product-select').val() || '';
            if (!pid.toString().trim()) $(this).remove();
        });

        if ($('#purchaseItems tr').length === 0) {
            if (typeof window.appendBlankRow === 'function') window.appendBlankRow(); 
        }

        if ($('#purchaseItems .product-select').filter(function() { return $(this).val(); }).length === 0) {
            e.preventDefault();
            showToast('⚠️ Please add at least one valid item before saving.', 'error');
            return false;
        }

        recalcSummary();
        return true;
    });


    $(document).ready(function() {
        if ($.fn.select2) {
            $('#vendor_id_select').select2({ placeholder: 'Select Party', width: '100%', allowClear: true });
        }
        $('.vendor-type-radio').on('change', function() {
            // Handled in the footer script to ensure loadParties is defined
        });
        $('#addRow').on('click', function() {
            window.appendBlankRow(true);
        });
        setTimeout(function() {
            $('#purchaseItems tr:first .item-id-input').focus();
        }, 500);

        // Ensure at least one account row exists
        if ($('#accountsTableBody tr').length === 0) {
            if (typeof window.appendAccountRow === 'function') window.appendAccountRow();
        }
    });

    
    // --- Print Preview Functions ---
    window.showPreviewModal = function() {
        try {
            // Gather Basic Info
            const date = $('input[name="entry_date"]').val() || '-';
            const vendorType = $('.vendor-type-radio:checked').next('label').text() || 'N/A';
            const vendorName = $('#vendor_id_select option:selected').text() || '-';
            const dc = $('input[name="dc"]').val() || '-';
            const warehouse = $('select[name="warehouse_id"] option:selected').text() || '-';
            const bilty = $('input[name="bilty_no"]').val() || '-';
            const remarks = $('textarea[name="remarks"]').val() || '-';
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

            // Show Modal
            const $modal = $('#printPreviewModal');
            if ($modal.length) {
                if (typeof $modal.modal === 'function') {
                    $modal.modal('show');
                } else {
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
    let isInitializing = true;
    let isFormDirty = false;
    let isFormSaved = false; // set true once AJAX draft save succeeds

    // Detect changes in any input/select/textarea within the form
    $(document).on('change input', '#purchaseForm :input', function() {
        if (isInitializing) return;
        isFormDirty = true;
        isFormSaved = false; // re-dirty if user edits after saving
    });

    // Mark initialization complete after a short delay
    setTimeout(() => { isInitializing = false; }, 1500);

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

        var grossAmount = price * qty;
        var netAmount   = grossAmount - discAmt;
        var netRate     = (qty > 0) ? (netAmount / qty) : price;

        $row.find('.disc_amount').val(discAmt.toFixed(2));
        $row.find('.row-amount').val(netRate.toFixed(2));
        $row.find('.row-total').val(netAmount.toFixed(2));
    }

    // Recalculate bottom summary
    function _recalcSummary() {
        var subTotalNetItems = 0;
        var totalQty = 0;

        // Sum all row net totals (since item discount is 'upr hi dia bs khatam')
        $('#purchaseItems tr').each(function() {
            totalQty += _n($(this).find('.quantity').val());
            subTotalNetItems += _n($(this).find('.row-total').val());
        });

        $('#tQty').text(totalQty.toLocaleString('en-US', {maximumFractionDigits:0}));
        $('#tSubTotal').text(subTotalNetItems.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));

        // Sum accounts allocation total
        var accTotal = _n($('#accountsTotal').val());
        var totalDiscount = accTotal; // ONLY account allocations in bottom discount

        $('#subtotal').val(subTotalNetItems.toFixed(2));
        $('#overallDiscount').val(totalDiscount.toFixed(2));

        var whtVal = _n($('#whtPercent').val());
        var whtType = $('#whtType').val() || 'percent';
        var whtAmt = 0;
        
        if (whtType === 'percent') {
            whtAmt = Math.max(0, subTotalNetItems - totalDiscount) * whtVal / 100;
        } else {
            whtAmt = whtVal;
        }

        $('#whtValue').val(whtAmt.toFixed(2));
        $('#whtAmount').val(whtAmt.toFixed(2));
        var netTotal = subTotalNetItems - totalDiscount + whtAmt;
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

    window.loadParties = function(type, selectedId = null) {
        var list = [];
        if (type === 'vendor') {
            list = vendors;
        } else if (type === 'customer') {
            // Filter out walk-in customers for the standard customer list
            list = customers.filter(function(c) {
                var ctype = (c.type || '').toLowerCase();
                return ctype.indexOf('walking') === -1 && ctype.indexOf('walkin') === -1;
            });
        } else if (type === 'walkin') {
            // Filter only walk-in customers
            list = customers.filter(function(c) {
                var ctype = (c.type || '').toLowerCase();
                return ctype.indexOf('walking') !== -1 || ctype.indexOf('walkin') !== -1;
            });
        }

        var $drop = $('#vendor_id_select');
        var html  = '<option value="" disabled ' + (!selectedId ? 'selected' : '') + '>-- Select Party --</option>';
        list.forEach(function(item) {
            var selected = (selectedId && String(item.id) === String(selectedId)) ? 'selected' : '';
            html += '<option value="' + item.id + '" ' + selected + '>' + item.name + '</option>';
        });
        $drop.html(html);
        if ($.fn.select2) {
             $drop.trigger('change');
        }
    };

    // When Type changes -> fill Vendor dropdown
    $('.vendor-type-radio').on('change', function() {
        var type = $(this).val();
        if (type) window.loadParties(type);
    });

    // Initial load for edit mode or old input
    var initialType = $('.vendor-type-radio:checked').val();
    var initialId = "{{ old('vendor_id', isset($purchase) ? $purchase->purchasable_id : '') }}";
    if(initialType) {
        window.loadParties(initialType, initialId);
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
        $('.ajax-valid-error').remove();
        
        // ✨ Remove empty product rows before saving
        $('#purchaseItems tr').each(function() {
            var pid = $(this).find('.product-select').val();
            if (!pid && $('#purchaseItems tr').length > 1) {
                $(this).remove();
            }
        });
        if (typeof window.recalcSummary === 'function') window.recalcSummary();
        
        // Check WHT Account if WHT is entered
        var whtVal = parseFloat($('#whtValue').val()) || 0;
        var whtAcc = $('#wht_account_id').val();
        if (whtVal > 0 && !whtAcc) {
            showToast('⚠️ Please select a WHT Account for the Withholding Tax.', 'error');
            $('#wht_account_id').addClass('border-danger shadow-sm').focus();
            $('#saveDraftBtn').prop('disabled', false).html('<i class="fa fa-floppy-o me-1"></i> Save Draft <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>');
            return;
        } else {
            $('#wht_account_id').removeClass('border-danger shadow-sm');
        }

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
                    
                    // Show New & Edit buttons
                    $('#newInvoiceBtn').show();
                    $('#editInvoiceBtn').show();
                    
                    // Lock the entire form visually from taking new input
                    $('#purchaseForm').addClass('form-locked');
                    
                } else {
                    showToast('❌ ' + (res.message || 'Error saving draft.'), 'error');
                }
            },
            error: function(xhr) {
                $('.ajax-valid-error').remove();
                var msg = 'Save failed.';
                try { 
                    var resp = JSON.parse(xhr.responseText);
                    msg = resp.message || msg;
                    if(resp.errors) {
                        $.each(resp.errors, function(key, val) {
                            var fieldHtml = '<div class="text-danger fw-bold ajax-valid-error mb-1" style="font-size:11px;"><i class="fa fa-exclamation-triangle"></i> ' + val[0] + '</div>';
                            if(key.indexOf('.') !== -1) {
                                var parts = key.split('.');
                                var fieldName = parts[0] + '[]';
                                var index = parseInt(parts[1]);
                                var $target = $('[name="' + fieldName + '"]').eq(index);
                                if($target.length) {
                                    if ($target.is('select')) {
                                        $target.closest('td, div').prepend(fieldHtml);
                                    } else {
                                        $target.before(fieldHtml);
                                    }
                                }
                            } else {
                                var $target = $('[name="' + key + '"]');
                                if($target.closest('.input-group').length && !$target.closest('.input-group').parent().is('td')) {
                                    $target.closest('.input-group').before(fieldHtml);
                                } else if($target.hasClass('select2-hidden-accessible')) {
                                    $target.next('.select2-container').before(fieldHtml);
                                } else if($target.length) {
                                    $target.before(fieldHtml);
                                }
                            }
                        });
                    }
                } catch(e){}
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

    // Edit logic
    $('#editInvoiceBtn').on('click', function() {
        $('#purchaseForm').removeClass('form-locked');
        $(this).hide();
    });

    // Keyboard Shortcuts Capture
    document.addEventListener('keydown', function(e) {
        // Ctrl+L → List page
        if (e.ctrlKey && (e.key === 'l' || e.key === 'L')) {
            e.preventDefault();
            window.location.href = $('#listBtn').attr('href');
        }
        
        // Ctrl+M → New Invoice
        if (e.ctrlKey && (e.key === 'm' || e.key === 'M')) {
            e.preventDefault();
            if ($('#newInvoiceBtn').is(':visible')) {
                window.location.href = $('#newInvoiceBtn').attr('href');
            } else {
                window.location.href = "{{ url('add/Purchase') }}";
            }
        }
        
        // Ctrl+E → Edit Invoice
        if (e.ctrlKey && (e.key === 'e' || e.key === 'E')) {
            if ($('#editInvoiceBtn').is(':visible')) {
                e.preventDefault();
                $('#editInvoiceBtn').trigger('click');
            }
        }
        
        // Esc → Cancel (Redirect to list)
        if (e.key === 'Escape') {
            e.preventDefault();
            window.location.href = $('#cancelBtn').attr('href');
        }
    }, true);

    // Removed redundant definition from ready block
    // Ctrl+X shortcut to remove the current row (REMOVED because it conflicts with Cut text)
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
                
                // When head changes, disable amount until new account is selected
                var $amt = $row.find('.accountAmount');
                $amt.prop('disabled', true).attr('disabled', 'disabled');
            },
            error: function(err) {
                console.error('AJAX Error:', err.statusText);
            }
        });
    });

    $(document).on('change', '#wht_head_id', function() {
        var headId = $(this).val();
        var $accSelect = $('#wht_account_id');

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
                        html += '<option value="' + acc.id + '">' + acc.title + '</option>';
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

    $(document).on('change', '.accountSub', function() {
        var $row = $(this).closest('tr');
        var headVal = $row.find('.accountHead').val();
        var subVal = $(this).val();
        var $amt = $row.find('.accountAmount');
        
        if (headVal && subVal) {
            $amt.prop('disabled', false).removeAttr('disabled');
        } else {
            $amt.prop('disabled', true).attr('disabled', 'disabled');
        }
    });

    // Sweep all rows on load to ensure proper enablement state
    $(document).ready(function() {
        setTimeout(function() {
            $('.accountSub').each(function() {
                var $row = $(this).closest('tr');
                var headVal = $row.find('.accountHead').val();
                var subVal = $(this).val();
                var $amt = $row.find('.accountAmount');
                
                if (headVal && subVal) {
                    $amt.prop('disabled', false).removeAttr('disabled');
                } else {
                    $amt.prop('disabled', true).attr('disabled', 'disabled');
                }
            });
        }, 500); // Wait for restore logic if any
    });

    // 2. Add Account Row
    window.appendAccountRow = function() {
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
                <input type="number" step="0.01" name="account_amount[]" class="form-control form-control-sm accountAmount" value="0" disabled>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger removeAccountRow">X</button>
            </td>
        </tr>`;
        $('#accountsTable tbody').append(newRow);
    };

    $('#addAccountRow').on('click', function() {
        window.appendAccountRow();
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
        // Ctrl + M -> New
        if (e.ctrlKey && (e.key === 'm' || e.key === 'M')) {
            e.preventDefault();
            const newUrl = $('#newInvoiceBtn').attr('href');
            if(newUrl) window.location.href = newUrl;
        }
    });

    // Sync Retail Price Display -> Hidden Field
    $(document).on('input', '.retail_price_show', function() {
        $(this).closest('tr').find('.purchase_retail_price').val($(this).val());
    });

    // Full Grid Navigation (Arrows Up/Down/Left/Right)
    $(document).on('keydown', '#purchaseItems input, #accountsTable input', function(e) {
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

});
</script>
@endsection