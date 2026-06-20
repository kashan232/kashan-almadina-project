@extends('admin_panel.layout.app')

@section('content')

@php
  $isEdit = isset($booking) || isset($sale);
  $editData = isset($booking) ? $booking : (isset($sale) ? $sale : null);
  $eDate = $editData ? $editData->entry_date : date('Y-m-d');
  $eTime = $editData ? $editData->entry_time : date('H:i');
  $eInvoice = $editData ? $editData->invoice_no : $nextInvoiceNumber;
  $eManual = $editData ? $editData->manual_invoice : '';
  $eIsOrder = $editData ? $editData->is_sale_order : 0;
  $ePartyType = $editData ? $editData->party_type : 'customer';
  $ePartyId = $editData ? $editData->customer_id : '';
  $eAddress = $editData ? $editData->address : '';
  $eTel = $editData ? $editData->tel : '';
  $ePrevBal = $editData ? $editData->previous_balance : '0.00';
  $eRemarks = $editData ? $editData->remarks : '';
  $eOrderDiscValue = $editData ? ($editData->discount_amount > 0 ? $editData->discount_amount : $editData->discount_percent) : 0;
  $eOrderDiscMode = $editData && $editData->discount_amount > 0 ? 'amount' : 'percent';
  
  // Try to find the exact receipt variables
  $receiptAccs = $editData ? json_decode($editData->receipt_accounts ?? '[]', true) : [];
  if (empty($receiptAccs) && $editData) {
      if ($editData->receipt1) $receiptAccs[] = $editData->receipt1;
      if ($editData->receipt2) $receiptAccs[] = $editData->receipt2;
  }
@endphp

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

  .invalid-cell {
    background-color: #fff5f5 !important;
    /* soft red */
    border: 1px solid #e3342f !important;
    /* red border */
  }

  .invalid-select,
  .invalid-input {
    border-color: #e3342f !important;
    box-shadow: none !important;
  }

  .input-readonly {
    background: #f9fbff;
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
  .form-locked .del-row,
  .form-locked #btnAdd,
  .form-locked #btnAddRV,
  .form-locked .btnRemRV,
  .form-locked .discount-value,
  .form-locked .order-disc-btn,
  .form-locked .rv-amount,
  .form-locked .rv-head,
  .form-locked .rv-account,
  .form-locked .rv-narration {
    pointer-events: none !important;
    opacity: 0.65 !important;
    cursor: not-allowed !important;
  }
  
  /* GRAY BACKGROUND FOR LOCKED INPUTS */
  .form-locked input:not([type="hidden"]), 
  .form-locked select, 
  .form-locked textarea,
  .form-locked .select2-selection {
    background-color: #e9ecef !important;
  }

  /* WATERMARK FOR POSTED STATE */
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
    max-height: 360px;
    overflow: auto;
    border: 1px solid #eee;
    border-radius: .5rem;
  }

  .minw-350 {
    min-width: 360px;
  }

  .w-70 {
    width: 70px
  }

  .w-90 {
    width: 90px
  }

  .w-110 {
    width: 110px
  }

  .w-120 {
    width: 120px
  }

  .w-150 {
    width: 150px
  }

  .totals-card {
    background: #fcfcfe;
    border: 1px solid #eee;
    border-radius: .5rem;
  }

  .totals-card .row+.row {
    border-top: 1px dashed #e5e7eb;
  }

  .badge-soft {
    background: #eef2ff;
    color: #3730a3;
  }

  /* Product Search Dropdown */
  .searchResults {
    position: fixed !important; /* Changed from absolute to fixed */
    z-index: 99999 !important; /* Very high z-index */
    width: 400px;
    max-height: 350px;
    overflow-y: auto;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 6px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    margin-top: 2px;
    display: none;
  }

  .searchResults.show {
    display: block !important;
  }

  .search-result-item {
    padding: 12px 15px;
    cursor: pointer;
    border-bottom: 1px solid #eee;
    transition: background 0.2s;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .search-result-item:hover,
  .search-result-item.active {
    background: #007bff;
    color: white;
  }

  .search-result-item:last-child {
    border-bottom: none;
  }

  .product-info-left {
    flex: 1;
  }

  .product-name {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 3px;
  }

  .product-brand {
    font-size: 12px;
    opacity: 0.8;
  }

  .product-price {
    font-weight: 700;
    font-size: 14px;
    color: #28a745;
    white-space: nowrap;
    margin-left: 10px;
  }

  .search-result-item.active .product-price {
    color: #fff;
  }

  .search-loading {
    padding: 15px;
    text-align: center;
    color: #999;
  }

  /* Discount Toggle Buttons */
  .discount-wrapper {
    display: flex;
    align-items: center;
    gap: 5px;
    flex-wrap: nowrap;
  }

  .discount-wrapper .form-control {
    width: 70px;
    flex-shrink: 0;
  }

  .discount-wrapper .btn-group {
    flex-shrink: 0;
  }

  .disc-mode-btn {
    min-width: 35px;
    font-size: 11px;
    padding: 4px 8px;
    font-weight: 600;
  }

  .disc-mode-btn.active {
    background: #0d6efd;
    color: white;
    border-color: #0d6efd;
  }

  .order-disc-btn {
    min-width: 35px;
    font-size: 11px;
    padding: 4px 8px;
    font-weight: 600;
  }

  .order-disc-btn.active {
    background: #0d6efd;
    color: white;
    border-color: #0d6efd;
  }

  /* Prevent Select2 dropdown from overflowing container */
  .select2-container {
    max-width: 100% !important;
  }
  
   .select2-container .select2-selection {
    max-width: 100% !important;
  }
  
  /* Compact Select2 for Narration */
  .receipt-row .select2-container--default .select2-selection--single {
    height: 31px !important;
    padding: 0px 5px !important;
    font-size: 0.8rem;
    border-radius: 0.375rem;
  }
  .receipt-row .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 29px !important;
    padding-left: 0 !important;
  }
  .receipt-row .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 29px !important;
  }

  .discount-wrapper {
    display: flex;
    align-items: center;
    gap: 2px;
  }
  
    .btn-xs {
        padding: 1px 4px;
        font-size: 0.7rem;
        line-height: 1.2;
    }
  .loading-indicator {
    background-color: #fff9c4 !important; /* soft yellow */
    border-color: #fdd835 !important;
    transition: background-color 0.3s ease;
  }
</style>

<div class="container-fluid py-2">
  <div class="main-container bg-white border shadow-sm mx-auto p-2 rounded-3" style="max-width: 98%;">

    <div id="alertBox" class="alert d-none mb-2" role="alert"></div>

    <div class="d-flex justify-content-between align-items-center mb-2 bg-light p-1 rounded shadow-sm px-3">
      <div class="d-flex align-items-center gap-2">
          <span id="statusBadge" class="badge bg-warning text-dark px-2 py-1 rounded shadow-sm" style="font-size:11px;">
              <i class="fa fa-pencil me-1"></i> New Sale
          </span>
          <span id="idBadge" class="badge bg-primary px-2 py-1 rounded shadow-sm" style="display:none;font-size:11px;">
              <i class="fa fa-tag me-1"></i> ID: N/A
          </span>
      </div>

      <div class="d-flex align-items-center gap-2">
          <a href="{{ route('sale.index') }}" id="listBtn" class="btn btn-sm btn-outline-secondary py-0 px-3">
              <i class="fa fa-list me-1"></i> List <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+L</kbd>
          </a>
      </div>
    </div>

    <form id="saleForm" autocomplete="off" action="{{ route('sale.ajax.save') }}" method="POST">
      @csrf
      <input type="hidden" id="booking_id" name="booking_id" value="{{ isset($booking) ? $booking->id : '' }}">
      <input type="hidden" id="sale_id" name="sale_id" value="{{ isset($sale) ? $sale->id : '' }}">


      <div class="d-flex gap-2 align-items-start border-bottom py-2">
        {{-- LEFT: Invoice & Customer --}}
        <div class="bg-light border rounded-3 p-2 shadow-sm" style="min-width: 280px; max-width: 280px; font-size: 0.8rem;">
          <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
            <h6 class="mb-0 fw-bold text-primary">
              <i class="bi bi-receipt me-1"></i>Invoice & Customer
            </h6>
          </div>

          {{-- Entry Date & Time --}}
          <div class="row g-1 mb-2">
            <div class="col-6">
              <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">Entry Date</label>
              <input type="date" class="form-control form-control-sm py-0" name="entry_date" value="{{ old('entry_date', $eDate) }}" style="font-size: 0.8rem;">
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">Entry Time</label>
              <input type="time" class="form-control form-control-sm py-0" name="entry_time" value="{{ old('entry_time', $eTime) }}" style="font-size: 0.8rem;">
            </div>
          </div>

          {{-- Invoice Numbers - Grid Layout --}}
          <div class="row g-1 mb-2">
            <div class="col-6">
              <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">Invoice No.</label>
              <input type="text" class="form-control form-control-sm bg-white border-0 shadow-sm fw-bold text-primary py-0" 
                     name="Invoice_no" value="{{ $eInvoice }}" readonly style="font-size: 0.8rem;">
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">Manual Invoice</label>
              <input type="text" class="form-control form-control-sm py-0" 
                     name="Invoice_main" placeholder="Optional" value="{{ old('Invoice_main', $eManual) }}" style="font-size: 0.8rem;">
            </div>
          </div>

          <div class="mb-2">
            <div class="sale-order-mode p-2 border rounded shadow-sm d-flex align-items-center justify-content-between transition-all" 
                 id="saleOrderContainer" 
                 style="background: #fff; cursor: pointer; border-left: 4px solid #6c757d !important;">
                <div class="d-flex align-items-center">
                    <div class="icon-box me-3 rounded-circle d-flex align-items-center justify-content-center" 
                         style="width: 32px; height: 32px; background: #f8fafc; color: #6c757d;">
                        <i class="fa fa-calendar-check-o"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark small mb-0">Order Mode</div>
                        <div class="text-muted" style="font-size: 10px;">Reserve stock (Alt + R)</div>
                    </div>
                </div>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" name="is_sale_order" id="isSaleOrder" value="1" {{ old('is_sale_order', $eIsOrder) == 1 ? 'checked' : '' }} style="width: 38px; height: 18px; cursor: pointer;">
                </div>
            </div>
          </div>

          <style>
            .sale-order-mode.active-mode {
                background: #fff5f5 !important;
                border-left-color: #dc3545 !important;
                border-color: #feb2b2 !important;
            }
            .sale-order-mode.active-mode .icon-box {
                background: #ffebeb !important;
                color: #dc3545 !important;
            }
            .sale-order-mode.active-mode .fw-bold {
                color: #dc3545 !important;
            }
            .transition-all {
                transition: all 0.3s ease;
            }
          </style>

          {{-- Party Type Toggle --}}
          <div class="mb-2">
            <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">Party Type</label>
            <div class="btn-group w-100" role="group">
              <input type="radio" class="btn-check" name="partyType" id="typeCustomers" value="customer" {{ old('partyType', $ePartyType) == 'customer' ? 'checked' : '' }}>
              <label class="btn btn-outline-primary btn-sm py-0" for="typeCustomers" style="font-size: 0.75rem;">
                Customers
              </label>

              <input type="radio" class="btn-check" name="partyType" id="typeWalkin" value="walking" {{ old('partyType', $ePartyType) == 'walking' ? 'checked' : '' }}>
              <label class="btn btn-outline-primary btn-sm py-0" for="typeWalkin" style="font-size: 0.75rem;">
                Walk-in
              </label>

              <input type="radio" class="btn-check" name="partyType" id="typeVendors" value="vendor" {{ old('partyType', $ePartyType) == 'vendor' ? 'checked' : '' }}>
              <label class="btn btn-outline-primary btn-sm py-0" for="typeVendors" style="font-size: 0.75rem;">
                Vendors
              </label>
            </div>
          </div>

          {{-- Party Identification & Selection --}}
          <div class="row g-1 mb-2">
            <div class="col-4">
              <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">Party ID</label>
              <input type="text" class="form-control form-control-sm py-0 fw-bold text-danger" id="partyIdInput" placeholder="ID" value="{{ $ePartyId }}" style="font-size: 0.8rem;">
            </div>
            <div class="col-8">
              <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">Select Party</label>
              <select class="form-select form-select-sm py-0" name="customer" id="customerSelect" data-old-val="{{ old('customer', $ePartyId) }}" style="font-size: 0.8rem;">
                <option selected disabled>Loading…</option>
              </select>
            </div>
          </div>

          {{-- Address --}}
          <div class="mb-2">
            <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">Address</label>
            <textarea class="form-control form-control-sm py-1" id="address" name="address" rows="1" placeholder="Address" style="font-size: 0.75rem;">{{ old('address', $eAddress) }}</textarea>
          </div>

          {{-- Tel & Balance --}}
          <div class="row g-1 mb-2">
            <div class="col-5">
              <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">Tel#</label>
              <input type="text" class="form-control form-control-sm py-0" id="tel" name="tel" placeholder="Phone" value="{{ old('tel', $eTel) }}" style="font-size: 0.8rem;">
            </div>
            <div class="col-7">
              <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">Current Balance</label>
              <input type="text" class="form-control form-control-sm text-end fw-bold py-0 input-readonly" id="previousBalance" 
                     name="previousBalance" value="{{ old('previousBalance', $ePrevBal) }}" placeholder="0.00" readonly 
                     style="font-size: 1.1rem; color: #d63384; background: #fffcfd;">
            </div>
          </div>

          {{-- Remarks --}}
          <div class="mb-1">
            <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">Remarks</label>
            <textarea class="form-control form-control-sm py-1" id="remarks" name="remarks" rows="1" placeholder="Notes" style="font-size: 0.75rem;">{{ old('remarks', $eRemarks) }}</textarea>
          </div>

          <div class="text-end mt-1">
            <button id="clearCustomerData" type="button" class="btn btn-xs btn-outline-secondary py-0" style="font-size: 0.7rem;">
              Clear Selection
            </button>
          </div>
        </div>

        {{-- RIGHT: Items --}}
        <div class="flex-grow-1">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="section-title mb-0">Items</div>
            <div class="d-flex align-items-center gap-2 wh-bulk-container">
                <label class="form-label text-muted small mb-0 fw-bold">Bulk Warehouse:</label>
                <select class="form-select form-select-sm" id="globalWarehouse" style="width: 150px; font-size: 0.8rem;">
                    @if(auth()->user()->canAccessShop())
                        <option value="0">🏠 Shop Stock</option>
                    @endif
                    @foreach ($warehouses as $wh)
                        <option value="{{ $wh->id }}">📦 {{ $wh->warehouse_name }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-sm btn-primary" id="btnAdd">Add Row</button>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0" style="width: 100%; font-size: 0.9rem;">
              <thead class="table-light">
                <tr>
                  <th style="width:7%">Item ID</th>
                  <th style="width:18%">Product</th>
                  <th style="width:11%" class="wh-col">Warehouse</th>
                  <th style="width:8%" class="text-center">Stock</th>
                  <th style="width:10%" class="text-end">Sales Price</th>
                  <th style="width:7%" class="text-center">Qty</th>
                  <th style="width:10%" class="text-end">Retail Price</th>
                  <th style="width:16%" class="text-center">Discount (% | Amt)</th>
                  <th style="width:10%" class="text-end">Rate</th>
                  <th style="width:10%" class="text-end">Amount</th>
                  <th style="width:3%" class="text-center">—</th>
                </tr>
              </thead>
              <tbody id="salesTableBody">
                @if(old('product_id'))
                  @foreach(old('product_id') as $index => $pid)
                    @php
                      $rowId = 'row-' . $index . '-' . time();
                      $pSearch = old('product_search')[$index] ?? '';
                      $whId = old('warehouse_name')[$index] ?? '';
                      $stock = old('stock')[$index] ?? '';
                      $sPrice = old('sales-price')[$index] ?? 0;
                      $qty = old('sales-qty')[$index] ?? '';
                      $rPrice = old('retail-price')[$index] ?? 0;
                      $dMode = old('discount_mode')[$index] ?? 'percent';
                      $dPct = old('discount-percent')[$index] ?? 0;
                      $dAmt = old('discount-amount')[$index] ?? 0;
                      $sAmount = old('sales-amount')[$index] ?? 0;
                      $displayValue = ($dMode == 'amount') ? $dAmt : $dPct;
                    @endphp
                    <tr data-row-id="{{ $rowId }}">
                      <td style="width: 70px;">
                        <input type="text" class="form-control form-control-sm item-id-input text-center" placeholder="ID" value="{{ $pid }}">
                      </td>
                      <td>
                        <select name="product_id[]" class="form-control form-control-sm product-select" style="width: 100%;">
                          <option value="{{ $pid }}" selected>{{ $pSearch }}</option>
                        </select>
                        <input type="hidden" name="product_search[]" class="product_name_hidden" value="{{ $pSearch }}">
                      </td>
                      <td style="width: 120px;">
                        <select class="form-select form-select-sm warehouse" name="warehouse_name[]">
                            @if(auth()->user()->canAccessShop())
                                <option value="0" {{ (!$whId || $whId == 0) ? 'selected' : '' }}>🏠 Shop Stock</option>
                            @endif
                          @foreach ($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ $whId == $wh->id ? 'selected' : '' }}>📦 {{ $wh->warehouse_name }}</option>
                          @endforeach
                        </select>
                      </td>
                      <td style="width: 80px;"><input type="text" class="form-control form-control-sm stock text-center input-readonly" name="stock[]" value="{{ $stock }}" readonly></td>
                      <td style="width: 100px;"><input type="text" class="form-control form-control-sm text-end sales-price input-readonly" name="sales-price[]" value="{{ $sPrice }}" readonly></td>
                      <td style="width: 70px;"><input type="number" step="any" class="form-control form-control-sm text-center sales-qty" name="sales-qty[]" value="{{ $qty }}"></td>
                      <td style="width: 100px;"><input type="text" class="form-control form-control-sm text-end retail-price input-readonly" name="retail-price[]" value="{{ $rPrice }}" readonly></td>
                      <td style="width:165px;">
                        <div class="input-group input-group-sm">
                          <input type="number" step="0.01" class="form-control text-end discount-value" placeholder="%" value="{{ $dPct }}" style="max-width: 65px;">
                          <span class="input-group-text px-1" style="font-size: 0.7rem;">%</span>
                          <input type="text" class="form-control text-end discount-amount-display input-readonly" value="{{ $dAmt }}" readonly style="background: #f8f9fa;">
                        </div>
                        <input type="hidden" class="discount-mode" name="discount_mode[]" value="percent">
                        <input type="hidden" class="discount-percent" name="discount-percent[]" value="{{ $dPct }}">
                        <input type="hidden" class="discount-amount" name="discount-amount[]" value="{{ $dAmt }}">
                      </td>
                      <td style="width: 100px;"><input type="text" class="form-control form-control-sm text-end sales-rate input-readonly" name="sales-rate[]" value="{{ old('sales-rate')[$index] ?? 0 }}" readonly></td>
                      <td style="width: 100px;"><input type="text" class="form-control form-control-sm text-end sales-amount input-readonly" name="sales-amount[]" value="{{ $sAmount }}" readonly></td>
                      <td class="text-center" style="width: 40px;"><button type="button" class="btn btn-xs btn-outline-danger del-row">&times;</button></td>
                    </tr>
                  @endforeach
                @elseif($isEdit)
                  @foreach($editData->items as $index => $item)
                    @php
                      $rowId = 'row-' . $index . '-' . time();
                      $pid = $item->product_id;
                      $pSearch = $item->product ? $item->product->name : '';
                      $whId = $item->warehouse_id;
                      $stock = $item->stock;
                      $sPrice = $item->sales_price;
                      $qty = $item->sales_qty + 0;
                      $rPrice = $item->retail_price;
                      $dMode = $item->discount_mode ?? 'percent';
                      $dPct = $item->discount_percent;
                      $dAmt = $item->discount_amount;
                      $sAmount = $item->amount;
                      $sRate = $item->sales_rate;
                    @endphp
                    <tr data-row-id="{{ $rowId }}">
                      <td style="width: 70px;">
                        <input type="text" class="form-control form-control-sm item-id-input text-center" placeholder="ID" value="{{ $pid }}">
                      </td>
                      <td>
                        <select name="product_id[]" class="form-control form-control-sm product-select" style="width: 100%;">
                          <option value="{{ $pid }}" selected>{{ $pSearch }}</option>
                        </select>
                        <input type="hidden" name="product_search[]" class="product_name_hidden" value="{{ $pSearch }}">
                      </td>
                      <td style="width: 120px;">
                        <select class="form-select form-select-sm warehouse" name="warehouse_name[]">
                            @if(auth()->user()->canAccessShop())
                                <option value="0" {{ $whId == 0 ? 'selected' : '' }}>🏠 Shop Stock</option>
                            @endif
                          @foreach ($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ $whId == $wh->id ? 'selected' : '' }}>📦 {{ $wh->warehouse_name }}</option>
                          @endforeach
                        </select>
                      </td>
                      <td style="width: 80px;"><input type="text" class="form-control form-control-sm stock text-center input-readonly" name="stock[]" value="{{ $stock }}" readonly></td>
                      <td style="width: 100px;"><input type="text" class="form-control form-control-sm text-end sales-price input-readonly" name="sales-price[]" value="{{ $sPrice }}" readonly></td>
                      <td style="width: 70px;"><input type="number" step="any" class="form-control form-control-sm text-center sales-qty" name="sales-qty[]" value="{{ $qty }}"></td>
                      <td style="width: 100px;"><input type="text" class="form-control form-control-sm text-end retail-price input-readonly" name="retail-price[]" value="{{ $rPrice }}" readonly></td>
                      <td style="width:165px;">
                        <div class="input-group input-group-sm">
                          <input type="number" step="0.01" class="form-control text-end discount-value" placeholder="%" value="{{ $dPct }}" style="max-width: 65px;">
                          <span class="input-group-text px-1" style="font-size: 0.7rem;">%</span>
                          <input type="text" class="form-control text-end discount-amount-display input-readonly" value="{{ $dAmt }}" readonly style="background: #f8f9fa;">
                        </div>
                        <input type="hidden" class="discount-mode" name="discount_mode[]" value="{{ $dMode }}">
                        <input type="hidden" class="discount-percent" name="discount-percent[]" value="{{ $dPct }}">
                        <input type="hidden" class="discount-amount" name="discount-amount[]" value="{{ $dAmt }}">
                      </td>
                      <td style="width: 100px;"><input type="text" class="form-control form-control-sm text-end sales-rate input-readonly" name="sales-rate[]" value="{{ $sRate }}" readonly></td>
                      <td style="width: 100px;"><input type="text" class="form-control form-control-sm text-end sales-amount input-readonly" name="sales-amount[]" value="{{ $sAmount }}" readonly></td>
                      <td class="text-center" style="width: 40px;"><button type="button" class="btn btn-xs btn-outline-danger del-row">&times;</button></td>
                    </tr>
                  @endforeach
                @endif
              </tbody>
              <tfoot class="table-light">
                <tr>
                  <td colspan="5" class="text-end fw-bold">Totals:</td>
                  <td class="text-center fw-bold"><span id="tQty">0</span></td>
                  <td class="text-end fw-bold"><span id="tRetail">0.00</span></td>
                  <td colspan="2"></td>
                  <td class="text-end fw-bold"><span id="totalAmount">0.00</span></td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>

      {{-- Receipt Vouchers + Totals --}}
      <div class="row g-3 mt-3">
        {{-- Receipt Vouchers --}}
        <div class="col-lg-7">
          <div class="bg-light border rounded-3 p-2 shadow-sm">
            <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
              <h6 class="mb-0 fw-bold text-success">
                <i class="bi bi-cash-stack me-2"></i>Receipt Vouchers
              </h6>
              <button type="button" class="btn btn-success btn-sm" id="btnAddRV">
                <i class="bi bi-plus-circle me-1"></i>Add Receipt
              </button>
            </div>
            
            <div id="rvWrapper">
              @php
                $receiptLoop = [];
                if (old('receipt_account_id')) {
                    foreach(old('receipt_account_id') as $i => $accId) {
                        $receiptLoop[] = [
                            'head_id' => old('receipt_head_id')[$i] ?? '',
                            'account_id' => $accId,
                            'amount' => old('receipt_amount')[$i] ?? '',
                            'narration' => old('receipt_narration')[$i] ?? '',
                        ];
                    }
                } else if ($editData && !empty(json_decode($editData->receipt_accounts ?? '[]', true))) {
                    $h = json_decode($editData->receipt_heads ?? '[]', true);
                    $a = json_decode($editData->receipt_accounts ?? '[]', true);
                    $m = json_decode($editData->receipt_amounts_json ?? '[]', true);
                    $n = json_decode($editData->receipt_narrations ?? '[]', true);
                    if(is_array($a)) {
                        foreach($a as $i => $accId) {
                            $receiptLoop[] = [
                                'head_id' => $h[$i] ?? '',
                                'account_id' => $accId,
                                'amount' => $m[$i] ?? '',
                                'narration' => $n[$i] ?? '',
                            ];
                        }
                    }
                }
              @endphp

              @if(count($receiptLoop) > 0)
                  @foreach($receiptLoop as $index => $rv)
                      <div class="receipt-row bg-white border rounded p-1 mb-1 shadow-sm rv-row">
                        <div class="row g-2 align-items-center">
                          <div class="col-md-3">
                            <label class="form-label text-muted small mb-0" style="font-size:0.7rem;">Head</label>
                            <select class="form-select form-select-sm rv-head" name="receipt_head_id[]">
                              <option value="" disabled {{ empty($rv['head_id']) ? 'selected' : '' }}>Select Head</option>
                              @foreach ($accountHeads as $head)
                                <option value="{{ $head->id }}" {{ $rv['head_id'] == $head->id ? 'selected' : '' }}>{{ $head->name }}</option>
                              @endforeach
                            </select>
                          </div>
                          <div class="col-md-3">
                            <label class="form-label text-muted small mb-0" style="font-size:0.7rem;">Account</label>
                            <select class="form-select form-select-sm rv-account" name="receipt_account_id[]" data-selected="{{ $rv['account_id'] }}">
                              <option value="" disabled selected>Select account</option>
                            </select>
                          </div>
                          <div class="col-md-2">
                            <label class="form-label text-muted small mb-0" style="font-size:0.7rem;">Amount</label>
                            <input type="number" step="0.01" class="form-control form-control-sm text-end fw-bold rv-amount" 
                                   name="receipt_amount[]" placeholder="0.00" value="{{ $rv['amount'] }}"
                                   {{ empty($rv['account_id']) ? 'disabled' : '' }}>
                          </div>
                          <div class="col-md-3">
                            <label class="form-label text-muted small mb-0" style="font-size:0.7rem;">Narration</label>
                            <select class="form-select form-select-sm rv-narration" name="receipt_narration[]" 
                                    data-selected="{{ $rv['narration'] }}">
                              <option value="">Select narration...</option>
                            </select>
                          </div>
                          <div class="col-md-1 text-center">
                            @if(!$loop->first)
                            <label class="form-label text-muted small mb-1">&nbsp;</label>
                            <button type="button" class="btn btn-outline-danger btn-sm btnRemRV">
                              <i class="bi bi-trash"></i>
                            </button>
                            @endif
                          </div>
                        </div>
                      </div>
                  @endforeach
              @else
                  <div class="receipt-row bg-white border rounded p-1 mb-1 shadow-sm rv-row">
                    <div class="row g-2 align-items-center">
                      <div class="col-md-3">
                        <label class="form-label text-muted small mb-0" style="font-size:0.7rem;">Head</label>
                        <select class="form-select form-select-sm rv-head" name="receipt_head_id[]">
                          <option value="" disabled selected>Select Head</option>
                          @foreach ($accountHeads as $head)
                            <option value="{{ $head->id }}">{{ $head->name }}</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-md-3">
                        <label class="form-label text-muted small mb-0" style="font-size:0.7rem;">Account</label>
                        <select class="form-select form-select-sm rv-account" name="receipt_account_id[]" disabled>
                          <option value="" disabled selected>Select account</option>
                        </select>
                      </div>
                      <div class="col-md-2">
                        <label class="form-label text-muted small mb-0" style="font-size:0.7rem;">Amount</label>
                        <input type="text" class="form-control form-control-sm text-end fw-bold rv-amount" 
                               name="receipt_amount[]" placeholder="0.00" disabled>
                      </div>
                      <div class="col-md-3">
                        <label class="form-label text-muted small mb-0" style="font-size:0.7rem;">Narration</label>
                        <select class="form-select form-select-sm rv-narration" name="receipt_narration[]">
                          <option value="">Select narration...</option>
                        </select>
                      </div>
                      <div class="col-md-1 text-center"></div>
                    </div>
                  </div>
              @endif
            </div>
            
            <div class="d-flex justify-content-between align-items-center mt-3 p-2 bg-success bg-opacity-10 rounded-3">
              <span class="text-success fw-bold">Receipts Total:</span>
              <span class="fw-bold fs-5 text-success" id="receiptsTotal">0.00</span>
            </div>
          </div>
        </div>

        {{-- Totals --}}
        <div class="col-lg-5">
          <div class="bg-light border rounded-3 p-2 shadow-sm">
            <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
              <h6 class="mb-0 fw-bold text-info">
                <i class="bi bi-calculator me-2"></i>Totals
              </h6>
            </div>

            <div class="totals-card">
              <!-- Qty and Retail moved to table footer -->

              <!-- Sub-Total moved to table footer -->

              <!-- Invoice Total -->
              <div class="d-flex justify-content-between py-2 border-bottom">
                <span class="text-muted small fw-bold">Invoice Total</span>
                <span class="fw-bold fs-6" id="tSub">0.00</span>
              </div>

              <!-- Order Discount (Merged) -->
              <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <span class="text-muted small">Order Discount</span>
                <div class="d-flex align-items-center gap-3">
                  <div class="d-flex align-items-center gap-1">
                    <input type="number" step="0.01" class="form-control form-control-sm text-end" 
                           id="orderDiscountValue" name="order_discount_value" 
                           value="{{ old('order_discount_value', $eOrderDiscValue) }}" style="width:70px">
                    <div class="btn-group btn-group-sm">
                      <button type="button" class="btn btn-outline-primary order-disc-btn {{ old('order_discount_mode', $eOrderDiscMode) == 'percent' ? 'active' : '' }}" data-mode="percent">%</button>
                      <button type="button" class="btn btn-outline-primary order-disc-btn {{ old('order_discount_mode', $eOrderDiscMode) == 'amount' ? 'active' : '' }}" data-mode="amount">₨</button>
                    </div>
                  </div>
                  <span class="fw-semibold text-danger" id="tOrderDisc" style="min-width: 60px; text-align: right;">0.00</span>
                </div>
                <input type="hidden" id="orderDiscountMode" name="order_discount_mode" value="{{ old('order_discount_mode', $eOrderDiscMode) }}">
                <input type="hidden" id="discountPercent" name="discountPercent" value="{{ old('discountPercent', $eOrderDiscMode == 'percent' ? $eOrderDiscValue : '0') }}">
                <input type="hidden" id="discountAmountHidden" value="0">
              </div>

              <!-- Total Receipts -->
              <div class="d-flex justify-content-between py-2 border-bottom">
                <span class="text-success small fw-semibold">Less: Receipts</span>
                <span class="fw-semibold text-success" id="tReceiptsMirror">0.00</span>
              </div>

              <!-- Previous Balance -->
              <div class="d-flex justify-content-between py-2 border-bottom">
                <span class="text-warning small fw-semibold">Previous Balance</span>
                <span class="fw-semibold text-warning" id="tPrev">0.00</span>
              </div>

              <!-- Payable / Total Balance -->
              <div class="d-flex justify-content-between py-2 bg-primary bg-opacity-10 rounded-3 px-2 mt-1">
                <span class="fw-bold text-primary">Payable / Total Balance</span>
                <span class="fw-bold fs-5 text-primary" id="tPayable">0.00</span>
              </div>

              {{-- hidden mirrors for backend --}}
              <input type="hidden" name="subTotal1" id="subTotal1" value="0">
              <input type="hidden" name="subTotal2" id="subTotal2" value="0">
              <input type="hidden" name="discountAmount" id="discountAmount" value="0">
              <input type="hidden" name="totalBalance" id="totalBalance" value="0">
            </div>
          </div>
        </div>
      </div>

      {{-- BOTTOM BUTTONS --}}
      <div class="d-flex gap-2 mt-4 justify-content-end border-top pt-3">
        
        <button type="button" id="saveDraftBtn" class="btn btn-sm btn-warning rounded-pill px-4 shadow-sm">
          <i class="fa fa-floppy-o me-1"></i> Save Draft
          <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>
        </button>

        <button type="button" id="previewPrintBtn" class="btn btn-sm btn-outline-dark rounded-pill px-4">
          <i class="fa fa-print me-1"></i> Print Preview
          <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+P</kbd>
        </button>

        <button type="button" id="postBtn" class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm">
          <i class="fa fa-send me-1"></i> Save & Post
          <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+&#8629;</kbd>
        </button>

        <button type="button" id="editBtn" class="btn btn-sm btn-warning rounded-pill px-4 shadow-sm" style="display:none;">
          <i class="fa fa-pencil me-1"></i> Edit <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+E</kbd>
        </button>

        <a href="{{ route('sale.add') }}" id="newBtn" class="btn btn-sm btn-info rounded-pill px-4 shadow-sm text-white" style="display:none;">
          <i class="fa fa-plus me-1"></i> New <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+M</kbd>
        </a>

        <a href="{{ route('sale.index') }}" id="cancelBtn" class="btn btn-sm btn-danger rounded-pill px-4 shadow-sm text-white">
          <i class="fa fa-times me-1"></i> Cancel <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Esc</kbd>
        </a>

      </div>
    </form>
    <div class="posted-watermark" id="postedWatermark">Posted</div>
  </div>
</div>

<!-- Print Modal -->
<div class="modal fade" id="printModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header py-2 bg-dark text-white">
        <h5 class="modal-title fs-6"><i class="fa fa-print me-2"></i>Print Preview</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0" style="height: 85vh;">
        <iframe id="printFrame" src="" style="width: 100%; height: 100%; border: none;"></iframe>
      </div>
      <div class="modal-footer py-1">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById('printFrame').contentWindow.print()">
            <i class="fa fa-print me-1"></i>Print
        </button>
      </div>
    </div>
  </div>
</div>


@endsection

@section('scripts')
<script>
  /* ---------- helpers ---------- */
  function pad(n) {
    return n < 10 ? '0' + n : n
  }

  function setNowStamp() {
    const d = new Date();
    const dt = `${pad(d.getDate())}-${pad(d.getMonth()+1)}-${String(d.getFullYear()).slice(-2)} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
    const dOnly = `${pad(d.getDate())}-${pad(d.getMonth()+1)}-${String(d.getFullYear()).slice(-2)}`;
    $('#entryDateTime').text('Entry Date_Time: ' + dt);
    $('#entryDate').text('Date: ' + dOnly);
  }
  setNowStamp();
  setInterval(setNowStamp, 60 * 1000);
  $('.js-customer').select2();

  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('input[name="_token"]').val()
    }
  });

  function showAlert(type, msg) {
    const el = $('#alertBox');
    el.removeClass('d-none alert-success alert-danger').addClass('alert-' + type).text(msg);
    setTimeout(() => el.addClass('d-none'), 2500);
  }

  /* ---------- Unified helper to update row with product data ---------- */
  function updateRowWithProductData($row, data) {
    if (!data) return;
    $row.find('.item-id-input').val(data.id);
    $row.find('.product_name_hidden').val(data.text || data.name);
    
    const $select = $row.find('.product-select');
    if ($select.val() !== String(data.id)) {
        const newOption = new Option(data.text || data.name, data.id, true, true);
        $select.empty().append(newOption).trigger('change');
    }
    
    $row.find('.stock').val(data.stock || 0);
    $row.find('.sales-price').val(parseFloat(data.sale_price || 0).toFixed(2));
    $row.find('.retail-price').val(parseFloat(data.retail_price || 0).toFixed(2));
    
    computeRow($row);
    updateGrandTotals();
    refreshPostedState();
  }

  /* ---------- Select2 Product Initialization ---------- */
  function initProductSelect($row) {
    const $select = $row.find('.product-select');
    if ($select.hasClass('select2-hidden-accessible')) {
        return; // Already initialized
    }
    $select.select2({
      placeholder: "Select Product",
      allowClear: true,
      minimumInputLength: 1,
      ajax: {
        url: '{{ route("search-products") }}',
        dataType: 'json',
        delay: 100,
        cache: true,
        data: function(params) {
          return {
            q: params.term,
            warehouse_id: $row.find('.warehouse').val()
          };
        },
        processResults: function(data, params) {
          const term = (params.term || '').toLowerCase();
          const results = data.map(function(item) {
            return {
              id: item.id,
              text: item.name,
              stock: item.stock || 0,
              sale_price: item.sale_price || 0,
              retail_price: item.retail_price || 0
            };
          });

          // Prioritize exact matches (ID or Name) at the top of the list
          results.sort((a, b) => {
             if (String(a.id) === term || a.text.toLowerCase() === term) return -1;
             if (String(b.id) === term || b.text.toLowerCase() === term) return 1;
             return 0;
          });

          return { results };
        }
      }
    });



    $select.on('select2:select', function(e) {
      const data = e.params.data;
      const $currentRow = $(this).closest('tr');
      updateRowWithProductData($currentRow, data);
      
      if ($currentRow.is(':last-child')) {
          addNewRow(false);
      }
      setTimeout(() => $currentRow.find('.sales-qty').focus(), 50);
    });

    $select.on('select2:clear', function(e) {
      const $currentRow = $(this).closest('tr');
      $currentRow.find('input').not('.item-id-input').val('');
      $currentRow.find('.item-id-input').val('');
      $currentRow.find('.stock').val('');
      $currentRow.find('.sales-price').val('0');
      $currentRow.find('.retail-price').val('0');
      computeRow($currentRow);
      updateGrandTotals();
    });
  }

  /* ---------- Add New Row ---------- */
  function addNewRow(focusNewRow = true, force = false) {
    const $last = $('#salesTableBody tr:last-child');
    if ($last.length && !force) {
      const pid = $last.find('.product-select').val();
      if (!pid) {
        if(focusNewRow) $last.find('.item-id-input').focus();
        return;
      }
    }

    const rowId = 'row-' + Date.now();
    const template = `
    <tr data-row-id="${rowId}">
      <td style="width: 70px;">
        <input type="text" class="form-control form-control-sm item-id-input text-center" placeholder="ID">
      </td>
      <td>
        <select name="product_id[]" class="form-control form-control-sm product-select" style="width: 100%;">
          <option value=""></option>
        </select>
        <input type="hidden" name="product_search[]" class="product_name_hidden">
      </td>
      <td style="width: 120px;" class="wh-cell">
        <select class="form-select form-select-sm warehouse" name="warehouse_name[]">
            @if(auth()->user()->canAccessShop())
                <option value="0" selected>🏠 Shop Stock</option>
            @endif
          @foreach ($warehouses as $wh)
            <option value="{{ $wh->id }}">📦 {{ $wh->warehouse_name }}</option>
          @endforeach
        </select>
      </td>
      <td style="width: 80px;"><input type="text" class="form-control form-control-sm stock text-center input-readonly" name="stock[]" readonly></td>
      <td style="width: 100px;"><input type="text" class="form-control form-control-sm text-end sales-price input-readonly" name="sales-price[]" value="0" readonly></td>
      <td style="width: 70px;"><input type="number" step="any" class="form-control form-control-sm text-center sales-qty" name="sales-qty[]" value=""></td>
      <td style="width: 100px;"><input type="text" class="form-control form-control-sm text-end retail-price input-readonly" name="retail-price[]" value="0" readonly></td>
      <td style="width: 165px;">
        <div class="input-group input-group-sm">
          <input type="number" step="0.01" class="form-control text-end discount-value" placeholder="%" value="0" style="max-width: 65px;">
          <span class="input-group-text px-1" style="font-size: 0.7rem;">%</span>
          <input type="text" class="form-control text-end discount-amount-display input-readonly" value="0" readonly style="background: #f8f9fa;">
        </div>
        <input type="hidden" class="discount-mode" name="discount_mode[]" value="percent">
        <input type="hidden" class="discount-percent" name="discount-percent[]" value="0">
        <input type="hidden" class="discount-amount" name="discount-amount[]" value="0">
      </td>
      <td style="width: 100px;"><input type="text" class="form-control form-control-sm text-end sales-rate input-readonly" name="sales-rate[]" value="0" readonly></td>
      <td style="width: 100px;"><input type="text" class="form-control form-control-sm text-end sales-amount input-readonly" name="sales-amount[]" value="0" readonly></td>
      <td class="text-center" style="width: 40px;"><button type="button" class="btn btn-xs btn-outline-danger del-row">&times;</button></td>
    </tr>`;

    const $newRow = $(template);
    
    // Inherit from Global Warehouse
    $newRow.find('.warehouse').val($('#globalWarehouse').val());
    
    $('#salesTableBody').append($newRow);
    
    // Explicitly initialize Select2 BEFORE setting focus
    initProductSelect($newRow);
    
    if (focusNewRow) {
        $newRow.find('.item-id-input').focus();
    }
    refreshPostedState();
  }

  // Load narrations into dropdown
  function loadNarrationsInto($select) {
    if ($select.hasClass('select2-hidden-accessible')) {
        $select.select2('destroy');
    }

    $select.prop('disabled', true).empty().append('<option value="">Loading...</option>');

    const selectedVal = $select.data('selected') || $select.val();

    $.get('{{ route("narrations.receipts") }}', function(data) {
      $select.empty().append('<option value="">Select narration...</option>');
      
      if (data && data.length > 0) {
          data.forEach(function(n) {
            const text = n.narration_text || n.narration || 'Unknown';
            const isSel = (selectedVal && selectedVal == text) ? 'selected' : '';
            $select.append('<option value="' + text + '" '+isSel+'>' + text + '</option>');
          });
      }
      
      // If we had a custom value typed before, it might not be in the list, so we add it
      if (selectedVal && !$select.find('option[value="'+selectedVal+'"]').length) {
          $select.append('<option value="'+selectedVal+'" selected>'+selectedVal+'</option>');
      }

      $select.prop('disabled', false);

      // Initialize Select2 with tags: true
      $select.select2({
        tags: true,
        placeholder: "Select or type narration...",
        width: '100%'
      });

    }).fail(function(xhr, status, error) {
      console.error('Error loading narrations:', status, error);
      $select.empty().append('<option value="">Error loading narrations</option>').prop('disabled', false);
    });
  }

  // Initializing rows and narration - handled by init() at the bottom
  $(function() {
    // Only handle things that need ready() and aren't in init()
  });

  function isRowMeaningful($row) {
    const productId = $row.find('.product-select').val();
    const qty = parseFloat($row.find('.sales-qty').val() || '0');
    return productId && qty > 0;
  }




  function canPost() {
    // 1. Party must be selected
    if (!$('#customerSelect').val()) {
        return false;
    }

    // 2. At least one valid item row
    let ok = false;
    $('#salesTableBody tr').each(function() {
      const pid = $(this).find('.product-select').val();
      const qty = parseFloat($(this).find('.sales-qty').val() || '0');
      if (pid && qty > 0) {
        ok = true;
        return false;
      }
    });
    return ok;
  }

  function refreshPostedState() {
    const state = canPost();
    $('#btnPosted, #btnHeaderPosted').prop('disabled', !state);
  }

  /* ---------- SAVE/POST ---------- */
  function serializeForm() {
    return $('#saleForm').serialize();
  }

  function ensureSaved() {
    return new Promise(function(resolve, reject) {
      const existing = $('#booking_id').val();
      if (existing) return resolve(existing);

      $('#btnSave, #btnHeaderPosted, #btnPosted').prop('disabled', true); // disable while saving

      $.post('{{ route("sale.ajax.save") }}', serializeForm())
        .done(function(res) {
          $('#btnSave, #btnHeaderPosted, #btnPosted').prop('disabled', false);
          if (res?.ok) {
            $('#booking_id').val(res.booking_id);
            showAlert('success', 'Saved (Booking #' + res.booking_id + ')');
            resolve(res.booking_id);
          } else {
            showAlert('danger', res.msg || 'Save failed');
            reject(res);
          }
        })
        .fail(function(xhr) {
          $('#btnSave, #btnHeaderPosted, #btnPosted').prop('disabled', false);
          console.error(xhr.responseText);
          showAlert('danger', 'Save error');
          reject(xhr);
        });
    });
  }

  function postNow() {
    $.post('{{ route("sale.ajax.post") }}', serializeForm())
      .done(function(res) {
        if (res?.ok) {
          window.open(res.invoice_url, '_blank');
          showAlert('success', 'Posted & invoice opened');
        } else {
          showAlert('danger', 'Post failed');
        }
      })
      .fail(function(xhr) {
        console.error(xhr.responseText);
        showAlert('danger', 'Post error');
      });
  }

  /* ---------- Events top buttons ---------- */
  $('#btnAdd').on('click', addNewRow);
  $('#btnEdit').on('click', () => alert('Edit mode activated'));
  $('#btnRevert').on('click', () => location.reload());
  $('#btnDelete').on('click', function() {
    if (!confirm('Reset all fields?')) return;
    $('#saleForm')[0].reset();
    $('#booking_id').val('');
    $('#salesTableBody').html('');
    addNewRow();
    $('#totalAmount').text('0.00');
    updateGrandTotals();
    refreshPostedState();
    showAlert('success', 'Form cleared');
  });
  /* ---------- AJAX Save, Post, Print, Keyboard Shortcuts (Purchase Style) ---------- */
  $(document).ready(function() {
      var _savedBookingId = null;

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

      function ajaxSaveDraft(showMsg = true) {
          // Remove empty rows before save
          $('#salesTableBody tr').each(function() {
              const pid = $(this).find('.product-select').val();
              if (!pid) {
                  $(this).remove();
              }
          });
          updateGrandTotals();

          $('.ajax-valid-error').remove();
          $('#saveDraftBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Saving...');
          
          return $.ajax({
              url: '{{ route("sale.ajax.save") }}',
              type: 'POST',
              data: $('#saleForm').serialize(),
              headers: { 'X-Requested-With': 'XMLHttpRequest' },
              success: function(res) {
                  if (res.ok) {
                      _savedBookingId = res.booking_id;
                      $('#booking_id').val(res.booking_id);
                      $('#idBadge').text('ID: ' + res.booking_id).show();
                      $('#saleForm').addClass('form-locked');
                      $('#saveDraftBtn, #postBtn, #previewPrintBtn, #cancelBtn, #editBtn, #newBtn').show();
                      $('#postBtn').removeClass('btn-primary').addClass('btn-success');
                      $('#statusBadge').removeClass('bg-warning').addClass('bg-info text-white').html('<i class="fa fa-pencil"></i> Unposted');

                      if (showMsg) {
                          showToast('Draft Saved', 'success');
                      }
                  } else {
                      showToast(res.msg || 'Save failed', 'error');
                  }
              },
              error: function(xhr) {
                  $('.ajax-valid-error').remove();
                  var msg = 'Save failed.';
                  try {
                      var resp = JSON.parse(xhr.responseText);
                      msg = resp.msg || msg;
                      if(resp.errors) {
                          $.each(resp.errors, function(key, val) {
                              var fieldHtml = '<div class="text-danger fw-bold ajax-valid-error mb-1" style="font-size:11px;"><i class="fa fa-exclamation-triangle"></i> ' + val[0] + '</div>';
                              if(key.indexOf('.') !== -1) {
                                  var parts = key.split('.');
                                  var fieldName = parts[0] + '[]';
                                  var index = parseInt(parts[1]);
                                  var $target = $('[name="' + fieldName + '"]').eq(index);
                                  if($target.length) {
                                      if ($target.is('select') || $target.is('input')) {
                                          $target.closest('td, div').prepend(fieldHtml);
                                      } else {
                                          $target.before(fieldHtml);
                                      }
                                  }
                              } else {
                                  var $target = $('[name="' + key + '"]');
                                  if($target.length) {
                                      if($target.next('.select2-container').length) {
                                          $target.next('.select2-container').before(fieldHtml);
                                      } else {
                                          $target.before(fieldHtml);
                                      }
                                  }
                              }
                          });
                      }
                  } catch(e) {}
                  showToast(msg, 'error');
              },
              complete: function() {
                  $('#saveDraftBtn').prop('disabled', false).html('<i class="fa fa-floppy-o me-1"></i> Save Draft <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>');
              }
          });
      }

      // AJAX Post
      function doPost() {
          const bookingId = $('#booking_id').val();
          if (!bookingId) {
              Swal.fire({
                  icon: 'warning',
                  title: 'Save First',
                  text: 'Please save draft before posting.'
              });
              return;
          }

          $('#postBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Posting...');

          $.ajax({
              url: '{{ route("sale.ajax.post") }}',
              type: 'POST',
              data: { _token: '{{ csrf_token() }}', booking_id: bookingId },
              success: function(res) {
                  if (res.ok) {
                      Swal.fire({ 
                          icon: 'success', 
                          title: 'Posted!', 
                          text: 'Sale posted successfully. Redirecting to new sale.', 
                          timer: 2000, 
                          showConfirmButton: false 
                      }).then(() => { 
                          window.location.href = '{{ route("sale.add") }}'; 
                      });
                  } else {
                      Swal.fire({
                          icon: 'error',
                          title: 'Error',
                          text: res.error || 'Post failed'
                      });
                  }
              },
              error: function(xhr) {
                  let msg = 'Post failed.';
                  try {
                      msg = JSON.parse(xhr.responseText).message || msg;
                  } catch(e) {}
                  Swal.fire({
                      icon: 'error',
                      title: 'Post Failed',
                      text: msg
                  });
              },
              complete: function() {
                   $('#postBtn').prop('disabled', false).html('<i class="fa fa-send me-1"></i> Post <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+&#8629;</kbd>');
              }
          });
      }

      $('#saveDraftBtn').on('click', function() { ajaxSaveDraft(); });
      $('#postBtn').on('click', function() { doPost(); });
      
      $('#previewPrintBtn').on('click', function() {
          const bookingId = $('#booking_id').val();
          if (!bookingId) {
              Swal.fire('Info', 'Please save the draft first (Ctrl+S).', 'info');
              return;
          }
          // The route should probably be booking.print but controller said bookingPrint
          const printUrl = '{{ url("booking/print") }}/' + bookingId; 
          $('#printFrame').attr('src', printUrl);
          $('#printModal').modal('show');
      });

      $('#editBtn').on('click', function() {
          $('#saleForm').removeClass('form-locked');
          // Update status if needed or just show a message
          Swal.fire({ icon: 'info', title: 'Unlocked', text: 'Form is now editable.', timer: 1000, showConfirmButton: false });
          // Note: User wants and asked for buttons to stay, so we don't hide this button necessarily, 
          // but usually Edit hides when you ARE editing. Let's keep it visible per request or hide it to avoid confusion.
          // The user specifically said "sary show hongey", so I'll keep it but maybe it does nothing when unlocked.
      });

      // KEYBOARD SHORTCUTS
      $(document).on('keydown', function(e) {
          if (e.ctrlKey && (e.key === 's' || e.key === 'S')) {
              e.preventDefault();
              ajaxSaveDraft();
          }
          if (e.ctrlKey && e.key === 'Enter') {
              e.preventDefault();
              doPost();
          }
          if (e.ctrlKey && (e.key === 'p' || e.key === 'P')) {
              e.preventDefault();
              $('#previewPrintBtn').trigger('click');
          }
          if (e.ctrlKey && (e.key === 'e' || e.key === 'E')) {
              e.preventDefault();
              e.stopPropagation();
              $('#editBtn').click();
          }
          if (e.ctrlKey && (e.key === 'm' || e.key === 'M')) {
              e.preventDefault();
              window.location.href = '{{ route("sale.add") }}';
          }
          if (e.key === 'Escape') {
              window.location.href = '{{ route("sale.index") }}';
          }
          if (e.ctrlKey && (e.key === 'x' || e.key === 'X')) {
              const $row = $(document.activeElement).closest('tr');
              if ($row.length) {
                  e.preventDefault();
                  $row.find('.del-row').click();
              }
          }
      });

      // Ctrl+L overwrite
      document.addEventListener('keydown', function(e) {
          if (e.ctrlKey && (e.key === 'l' || e.key === 'L')) {
              e.preventDefault();
              window.location.href = $('#listBtn').attr('href');
          }
      }, true);

      // On load, if booking_id exists, lock form
      if($('#booking_id').val()) {
          $('#saleForm').addClass('form-locked');
          $('#saveDraftBtn, #postBtn, #previewPrintBtn, #cancelBtn, #editBtn, #newBtn').show();
          $('#postBtn').removeClass('btn-primary').addClass('btn-success');
          $('#idBadge').text('ID: ' + $('#booking_id').val()).show();
          $('#statusBadge').removeClass('bg-warning').addClass('bg-info text-white').html('<i class="fa fa-pencil"></i> Unposted');
      }
  });


  /* ---------- Customer type & list ---------- */
  function loadCustomersByType(type) {
    const $sel = $('#customerSelect').prop('disabled', true).empty().append('<option selected disabled>Loading...</option>');
    $.get('{{ route("customers.filter") }}', {
      type
    }, function(list) {
      $sel.empty().append('<option selected disabled value="">Select ' + (type === 'vendor' ? 'vendor' : (type === 'walking' ? 'walk-in customer' : 'customer')) + '</option>');
      list.forEach(r => {
        let opt = $('<option>').val(r.id).text(r.text).attr('data-customer_id', r.customer_id);
        $sel.append(opt);
      });
      $sel.prop('disabled', false);

      // Initialize Select2 if not already initialized, or refresh it
      if ($sel.hasClass('select2-hidden-accessible')) {
          $sel.select2('destroy');
      }
      $sel.select2({
          placeholder: 'Select ' + type,
          allowClear: true,
          width: '100%'
      });

      // Handle old values if any
      const oldVal = $sel.attr('data-old-val');
      if (oldVal) {
          let $matchedOpt = $sel.find('option[value="' + oldVal + '"]');
          if ($matchedOpt.length === 0) {
              $matchedOpt = $sel.find('option[data-customer_id="' + oldVal + '"]');
          }
          if ($matchedOpt.length > 0) {
              $sel.val($matchedOpt.val()).trigger('change');
          }
          $sel.attr('data-old-val', '');
      }
    }).fail(function() {
      $sel.empty().append('<option selected disabled>Error loading</option>').prop('disabled', false);
    });
  }

  let initialType = $('input[name="partyType"]:checked').val() || 'customer';
  loadCustomersByType(initialType);

  $(document).on('change', 'input[name="partyType"]', function() {
    $('#customerSelect').val(null).trigger('change');
    $('#address,#tel,#remarks,#partyIdInput').val('');
    loadCustomersByType(this.value);
  });

  // Sale Order Toggle UI Sync
  function updateSaleOrderUI() {
    const isChecked = $('#isSaleOrder').is(':checked');
    if (isChecked) {
        $('#saleOrderContainer').addClass('active-mode');
        $('.wh-col, .wh-cell').hide();
        $('.wh-bulk-container label, .wh-bulk-container select').hide();
    } else {
        $('#saleOrderContainer').removeClass('active-mode');
        $('.wh-col, .wh-cell').show();
        $('.wh-bulk-container label, .wh-bulk-container select').show();
    }
  }

  $(document).on('change', '#isSaleOrder', function() {
    updateSaleOrderUI();
  });

  // Make the entire container clickable
  $('#saleOrderContainer').on('click', function(e) {
    if (e.target !== document.getElementById('isSaleOrder')) {
        $('#isSaleOrder').prop('checked', !$('#isSaleOrder').is(':checked')).trigger('change');
    }
  });

  // Keyboard Shortcut: Alt + R for Reserve (Sale Order)
  $(document).on('keydown', function(e) {
    if (e.altKey && (e.key === 'r' || e.key === 'R')) {
        e.preventDefault();
        $('#isSaleOrder').prop('checked', !$('#isSaleOrder').is(':checked')).trigger('change');
    }
  });

  // Update addNewRow to respect Sale Order state
  const originalAddNewRow = addNewRow;
  addNewRow = function(focusNewRow = true, force = false) {
    originalAddNewRow(focusNewRow, force);
    if ($('#isSaleOrder').is(':checked')) {
        $('#salesTableBody tr:last-child .wh-cell').hide();
    }
  };

  // Party ID Lookup
  $('#partyIdInput').on('keydown', function(e) {
      if (e.key === 'Tab' || e.key === 'Enter') {
          const pid = $(this).val().trim();
          if (!pid) return;

          let foundId = null;
          // Try to match in the already loaded Select2 options
          $('#customerSelect option').each(function() {
              const customerId = $(this).attr('data-customer_id');
              const val = $(this).val();

              if (customerId == pid || val == pid) {
                  foundId = val;
                  return false;
              }
          });

          if (foundId) {
              if ($('#customerSelect').val() !== foundId) {
                  $('#customerSelect').val(foundId).trigger('change');
              }
              e.preventDefault();
              // Transition to first row's Item ID
              setTimeout(() => $('#salesTableBody tr:first-child .item-id-input').focus(), 100);
          } else {
              // Optional: You could add a small visual indicator that ID was not found
              $(this).addClass('is-invalid');
              setTimeout(() => $(this).removeClass('is-invalid'), 1000);
          }
      }
  });

  $(document).on('change', '#customerSelect', function() {
    let id = $(this).val();
    if (!id) {
        $('#partyIdInput').val('');
        $('#address').val('');
        $('#tel').val('');
        $('#remarks').val('');
        $('#previousBalance').val('0.00');
        updateGrandTotals();
        return;
    }

    // Update Party ID Input field with the display ID (customer_id)
    const customerId = $("#customerSelect option:selected").attr('data-customer_id');
    if (customerId) {
        $('#partyIdInput').val(customerId);
    } else {
        $('#partyIdInput').val(id);
    }

    let type = $('input[name="partyType"]:checked').val();

    $.get('{{ route("customers.show", ["id" => "__ID__"]) }}'.replace('__ID__', id) + '?type=' + type, function(d) {
      $('#address').val(d.address || '');
      $('#tel').val(d.mobile || '');
      $('#remarks').val(d.remarks || '');
      $('#previousBalance').val((+d.previous_balance || 0).toFixed(2));
      updateGrandTotals();
    });
  });

  $('#clearCustomerData').on('click', function() {
    $('#customerSelect').val(null).trigger('change');
    $('#address,#tel,#remarks,#partyIdInput').val('');
    $('#previousBalance').val('0.00');
    updateGrandTotals();
  });




  /* ---------- Item ID Lookup Logic ---------- */
  $(document).on('keydown', '.item-id-input', function(e) {
      if (e.key === 'Enter' || e.key === 'Tab') {
          const $input = $(this);
          const id = $input.val().trim();
          const $row = $input.closest('tr');
          const $select = $row.find('.product-select');
          
          if (!id) {
              if (e.key === 'Enter') e.preventDefault();
              return;
          }

          // If current selection is already same, just move focus
          if ($select.val() === String(id)) {
              if ($row.is(':last-child')) addNewRow(false);
              setTimeout(() => $row.find('.sales-qty').focus(), 50);
              e.preventDefault();
              return;
          }

          e.preventDefault(); // Lock focus until we fetch data
          $input.addClass('loading-indicator'); 

          $.get('{{ route("search-products") }}', { 
              q: id,
              warehouse_id: $row.find('.warehouse').val()
          })
            .done(function(res) {
                $input.removeClass('loading-indicator');
                if (res && res.length > 0) {
                    // Precise matching: for numeric input, prioritize exact ID match. 
                    // Only fallback to first result if the input isn't a simple ID lookup.
                    // Precise matching prioritize: Exact ID -> Exact Name (Case Insensitive) -> First Result
                    let item = res.find(i => String(i.id) === String(id)) 
                              || res.find(i => i.name.toLowerCase() === id.toLowerCase());
                    
                    if (!item && res.length === 1) {
                         item = res[0]; 
                    }
                    
                    if (!item) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Not Found',
                            text: 'Product ID ' + id + ' not found.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        $input.select().focus();
                        return;
                    }

                    // 1. Populate current row first
                    updateRowWithProductData($row, {
                        id: item.id,
                        name: item.name,
                        text: item.name,
                        stock: item.stock,
                        sale_price: item.sale_price,
                        retail_price: item.retail_price
                    });

                    // 2. Append row ONLY after data is loaded (if last)
                    if ($row.is(':last-child')) {
                        addNewRow(false);
                    }
                    
                    // 3. Focus Quantity of current row
                    setTimeout(() => $row.find('.sales-qty').focus(), 50);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Not Found',
                        text: 'Product ID ' + id + ' not found in system.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    $input.select().focus();
                }
            })
            .fail(function() {
                $input.removeClass('loading-indicator');
                Swal.fire({ icon: 'error', title: 'Error', text: 'Server error while fetching product.' });
                $input.select().focus();
            });
      }
  });



  /* ---------- Row compute with Discount Toggle ---------- */
  function toNum(v) {
    return parseFloat(v || 0) || 0;
  }

  function computeRow($row) {
    const sp = toNum($row.find('.sales-price').val());
    const rp = toNum($row.find('.retail-price').val());
    const qty = toNum($row.find('.sales-qty').val());
    const value = toNum($row.find('.discount-value').val());

    // Percentage mode: value % on (Retail Price * Qty)
    let discPct = value;
    let discAmt = ((rp * qty) * value) / 100.0;

    // Update hidden fields
    $row.find('.discount-percent').val(discPct.toFixed(2));
    $row.find('.discount-amount').val(discAmt.toFixed(2));
    
    // Update visible discount amount display
    $row.find('.discount-amount-display').val(discAmt.toFixed(2));

    // Calculate Rate per Unit (Sales Price minus unit discount)
    let rate = 0;
    if (qty > 0) {
        rate = sp - (discAmt / qty);
    } else {
        rate = sp;
    }
    $row.find('.sales-rate').val(rate.toFixed(2));

    // Calculate Net Amount for the row (Sales Price * Qty - Discount)
    const lineGross = sp * qty;
    const netAmount = Math.max(0, lineGross - discAmt);
    $row.find('.sales-amount').val(netAmount.toFixed(2));
  }

  // Discount Toggle Button Click - ELIMINATED (only percent now)

  // Discount Value Input
  $(document).on('input', '.discount-value', function() {
    const $row = $(this).closest('tr');
    computeRow($row);
    updateGrandTotals();
    refreshPostedState();
  });

  // Other inputs (price, qty)
  $(document).on('input change', '.sales-price, .sales-qty, .retail-price, #customerSelect', function() {
    const $row = $(this).closest('tr');
    if ($row.length) computeRow($row);
    updateGrandTotals();
    refreshPostedState();
  });

  // Warehouse change -> re-fetch stock for CURRENT row only
  $(document).on('change', '.warehouse', function() {
      const selectedWH = $(this).val();
      const $r = $(this).closest('tr');
      const productId = $r.find('.product-select').val();
      if (!productId) return;

      const $stock = $r.find('.stock');
      $stock.addClass('loading-indicator');

      $.get('{{ route("search-products") }}', { q: productId, warehouse_id: selectedWH })
          .done(function(res) {
              $stock.removeClass('loading-indicator');
              if (res && res.length > 0) {
                  const item = res.find(i => String(i.id) === String(productId));
                  if (item) {
                      $stock.val(item.stock || 0);
                  }
              }
          })
          .fail(function() {
              $stock.removeClass('loading-indicator');
          });
  });

  // Global Warehouse Change -> Bulk Update ALL rows
  $(document).on('change', '#globalWarehouse', function() {
      const selectedWH = $(this).val();
      $('.warehouse').val(selectedWH).trigger('change');
  });

  // Receipt Voucher Account -> Amount Toggle
  $(document).on('change', '.rv-account', function() {
    const $row = $(this).closest('.row');
    const $amount = $row.find('.rv-amount');
    if ($(this).val()) {
        $amount.prop('disabled', false).focus();
    } else {
        $amount.prop('disabled', true).val('');
        updateGrandTotals();
    }
  });

  // Initialize RV states on load
  $('.rv-account').each(function() {
    const $row = $(this).closest('.row');
    if (!$(this).val()) {
        $row.find('.rv-amount').prop('disabled', true);
    } else {
        $row.find('.rv-amount').prop('disabled', false);
    }
  });

  /* ---------- Delete row ---------- */
  $(document).on('click', '.del-row', function() {
    const $tr = $(this).closest('tr');
    const $tbody = $('#salesTableBody');
    if ($tbody.find('tr').length > 1) {
      $tr.remove();
      updateGrandTotals();
      refreshPostedState();
    }
  });

  /* ---------- Totals ---------- */
  function updateGrandTotals() {
    let tQty = 0, tSub = 0, tRetail = 0;
    $('#salesTableBody tr').each(function() {
      const $r = $(this);
      const qty = toNum($r.find('.sales-qty').val());
      const rowNet = toNum($r.find('.sales-amount').val());
      const rowRetail = toNum($r.find('.retail-price').val()) * qty;

      tQty += qty;
      tSub += rowNet;
      tRetail += rowRetail;
    });

    // Sub-Total for calculation is what user calls "Retail Price"
    // But we show Net Total (tSub) as the main summary Sub-Total normally.
    // However, the order discount will be calculated on tRetail.

    // Update hidden mirrors
    $('#subTotal1').val(tRetail.toFixed(2)); // Store Retail Total in subTotal1
    $('#subTotal2').val(tSub.toFixed(2));    // Store Net Total in subTotal2

    const discountBase = tRetail; // Request: discount on retail price
    const subTotal = tSub;        // Basis for payable balance

    // Order discount with toggle support
    const orderMode = $('#orderDiscountMode').val();
    const orderValue = toNum($('#orderDiscountValue').val());
    let orderDisc = 0;
    let orderPct = 0;

    if (orderMode === 'percent') {
      orderPct = orderValue;
      orderDisc = (discountBase * orderValue) / 100.0;
    } else {
      orderDisc = orderValue;
      orderPct = discountBase > 0 ? (orderValue / discountBase) * 100 : 0;
    }

    // Update hidden fields
    $('#discountPercent').val(orderPct.toFixed(2));
    $('#discountAmountHidden').val(orderDisc.toFixed(2));
    $('#discountAmount').val(orderDisc.toFixed(2)); // mirror to hidden name="discountAmount"

    const prev = toNum($('#previousBalance').val());
    const receipts = toNum($('#receiptsTotal').text());
    const roundOffIntended = toNum($('#roundOff').val());

    let currentInvoice = subTotal - orderDisc;
    if (roundOffIntended > 0) {
        currentInvoice = roundOffIntended;
    }

    const balAfterReceipt = prev - receipts;
    const payable = currentInvoice + balAfterReceipt;

    // UI Updates
    $('#tQty').text(tQty.toFixed(0));
    $('#tRetail').text(tRetail.toFixed(2));
    $('#tSub').text(subTotal.toFixed(2));
    $('#tOrderDisc').text(orderDisc.toFixed(2));
    $('#tCurrentInvoice').text(currentInvoice.toFixed(2));
    $('#tPrev').text(prev.toFixed(2));
    $('#tReceiptsMirror').text(receipts.toFixed(2));
    $('#tBalAfterReceipt').text(balAfterReceipt.toFixed(2));
    $('#tPayable').text(payable.toFixed(2));
    $('#totalAmount').text(subTotal.toFixed(2));

    // backend mirrors
    $('#subTotal1').val(subTotal.toFixed(2));
    $('#subTotal2').val(subTotal.toFixed(2));
    $('#discountAmount').val(orderDisc.toFixed(2));
    $('#totalBalance').val(payable.toFixed(2));
  }
  
  // Order Discount Toggle Button Click
  $(document).on('click', '.order-disc-btn', function() {
    const $btn = $(this);
    const mode = $btn.data('mode');

    // Update UI
    $('.order-disc-btn').removeClass('active');
    $btn.addClass('active');

    // Update mode
    $('#orderDiscountMode').val(mode);

    // Recalculate
    updateGrandTotals();
  });

  // Order Discount Value Input
    $(document).on('input', '#orderDiscountValue, #roundOff', function() {
        updateGrandTotals();
    });
  
  $(document).on('input', '#previousBalance, #discountPercent', updateGrandTotals);

  /* ---------- Row auto-add ---------- */
  $('#salesTableBody').on('input', '.sales-qty', function() {
    const $row = $(this).closest('tr');
    computeRow($row);
    updateGrandTotals();
    refreshPostedState();
  });

  /* ---------- Add new row when user presses Enter in Discount field ---------- */
  $('#salesTableBody').on('keydown', '.discount-value', function(e) {
    if (e.key === 'Enter' || e.keyCode === 13) {
      e.preventDefault(); // prevent accidental form submit
      const $current = $(this).closest('tr');

      // compute current row first
      computeRow($current);
      updateGrandTotals();
      refreshPostedState();

      // Add new row and focus on product search
      addNewRow();
      const $newRow = $('#salesTableBody tr:last-child');
      setTimeout(() => $newRow.find('.item-id-input').focus(), 100);
    }
  });

  /* ---------- Add new row when user presses Enter in Disc % (only on last row) ---------- */
  $('#salesTableBody').on('keydown', '.discount-percent', function(e) {
    if (e.key === 'Enter' || e.keyCode === 13) {
      e.preventDefault(); // prevent accidental form submit
      const $current = $(this).closest('tr');

      // compute current row first (in case user typed value and pressed Enter)
      computeRow($current);
      updateGrandTotals();
      refreshPostedState();

      // only add new row when this is the last row AND discount has some value OR qty > 0 or product selected
      const isLast = $current.is(':last-child');
      const discVal = parseFloat($(this).val() || '0') || 0;
      const qtyVal = parseFloat($current.find('.sales-qty').val() || '0') || 0;
      const prodSelected = !!$current.find('.product').val();

      // require at least one 'meaningful' value so blank Enter doesn't create rows
      if (isLast && (discVal !== 0 || qtyVal > 0 || prodSelected)) {
        addNewRow();
        // focus on new row product for quick entry
        const $newRow = $('#salesTableBody tr:last-child');
        setTimeout(() => $newRow.find('.item-id-input').focus(), 100);
      }
    }
  });


  /* ---------- Receipts (accounts) ---------- */
  $(document).on('change', '.rv-head', function() {
    const headId = $(this).val();
    const $row = $(this).closest('.rv-row');
    const $accSelect = $row.find('.rv-account');
    const $amtInput = $row.find('.rv-amount');

    if (!headId) return;

    loadAccountsByHead(headId, $accSelect);
    $amtInput.prop('disabled', true).val(''); 
    recomputeReceipts();
  });

  function loadAccountsByHead(headId, $select) {
    if (!headId) return;
    $select.prop('disabled', true).empty().append('<option value="">Loading...</option>');

    $.get('{{ url("/get-accounts-by-head") }}/' + headId, function(rows) {
      $select.empty().append('<option value="" disabled selected>Select account</option>');
      (rows || []).forEach(function(a) {
        $select.append('<option value="' + a.id + '">' + a.title + '</option>');
      });
      $select.prop('disabled', false);
      
      // If we have a pre-selected value (from old() or edit)
      const selected = $select.attr('data-selected');
      if (selected) {
        $select.val(selected).trigger('change');
        $select.removeAttr('data-selected');
      }
    }).fail(function() {
      $select.empty().append('<option value="">Error loading</option>').prop('disabled', false);
    });
  }

  function recomputeReceipts() {
    let sum = 0;
    // Calculate the total receipt amount
    $('.rv-amount').each(function() {
      sum += toNum($(this).val()); // Sum up all the receipt amounts
    });
    $('#receiptsTotal').text(sum.toFixed(2)); // Display total in the respective element
    updateGrandTotals(); // Update other totals if needed
  }

  $('#btnAddRV').on('click', function() {
    let headOptions = '<option value="" disabled selected>Select Head</option>';
    @foreach($accountHeads as $head)
      headOptions += `<option value="{{ $head->id }}">{{ $head->name }}</option>`;
    @endforeach

    $('#rvWrapper').append(`
    <div class="receipt-row bg-white border rounded p-1 mb-1 shadow-sm rv-row">
      <div class="row g-2 align-items-center">
        <div class="col-md-3">
          <label class="form-label text-muted small mb-0" style="font-size:0.7rem;">Head</label>
          <select class="form-select form-select-sm rv-head" name="receipt_head_id[]">
            ${headOptions}
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label text-muted small mb-0" style="font-size:0.7rem;">Account</label>
          <select class="form-select form-select-sm rv-account" name="receipt_account_id[]" disabled>
            <option value="" disabled selected>Select account</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label text-muted small mb-0" style="font-size:0.7rem;">Amount</label>
          <input type="text" class="form-control form-control-sm text-end fw-bold rv-amount" 
                 name="receipt_amount[]" placeholder="0.00" disabled>
        </div>
        <div class="col-md-3">
          <label class="form-label text-muted small mb-0" style="font-size:0.7rem;">Narration</label>
          <select class="form-select form-select-sm rv-narration" name="receipt_narration[]">
            <option value="">Select narration...</option>
          </select>
        </div>
        <div class="col-md-1 text-center">
          <label class="form-label text-muted small mb-1">&nbsp;</label>
          <button type="button" class="btn btn-outline-danger btn-sm btnRemRV">
            <i class="bi bi-trash"></i>
          </button>
        </div>
      </div>
    </div>
  `);

    // Load narrations into the newly added row
    loadNarrationsInto($('#rvWrapper .rv-row:last .rv-narration'));
  });
  $(document).on('click', '.btnRemRV', function() {
    $(this).closest('.rv-row').remove();
    recomputeReceipts(); // Recompute total receipts after removal
  });

  // Recompute total receipt amounts when input changes
  $(document).on('input', '.rv-amount', recomputeReceipts);

  /* ---------- init ---------- */
  function init() {
    // 1. Initialize existing rows (from PHP old input or edit)
    $('#salesTableBody tr').each(function() {
      initProductSelect($(this));
    });

    // 2. Add blank row if needed
    if ($('#salesTableBody tr').length === 0) {
      addNewRow();
    } else {
      // Check if last row is empty; if not, you might want to add another blank row or just leave it.
      // For now, let's just make sure the last row is focused if it's empty.
      const $last = $('#salesTableBody tr:last-child');
      if ($last.find('.product-select').val()) {
          addNewRow(false);
      }
    }

    let initialType = $('input[name="partyType"]:checked').val() || 'customer';
    loadCustomersByType(initialType);
    loadNarrationsInto($('.rv-narration'));

    // If there are existing heads (from old() or edit), load their accounts
    $('.rv-head').each(function() {
      const headId = $(this).val();
      if (headId) {
        loadAccountsByHead(headId, $(this).closest('.rv-row').find('.rv-account'));
      }
    });

    updateGrandTotals();
    refreshPostedState();
  }

  $(function() {
    init();
  });

  function markInvalid($el) {
    // add visuals; $el can be input/select/td
    $el.addClass('invalid-input invalid-select');
    // also add class to closest td for table cells
    $el.closest('td').addClass('invalid-cell');
  }

  function clearInvalid($el) {
    $el.removeClass('invalid-input invalid-select');
    $el.closest('td').removeClass('invalid-cell');
  }

  function clearAllInvalids() {
    $('.invalid-input, .invalid-select').removeClass('invalid-input invalid-select');
    $('.invalid-cell').removeClass('invalid-cell');
  }

  $(document).on('input change', 'select, input, textarea', function() {
    clearInvalid($(this));
  });

  function validateRows() {
    let ok = true;
    let firstMessage = null;
    let firstEl = null;

    $('#salesTableBody tr').each(function(rowIndex) {
      const $row = $(this);
      const $wh = $row.find('.warehouse');
      const $prod = $row.find('.product-select'); // Updated for Select2
      const $qty = $row.find('.sales-qty');

      // Warehouse
      if (!$wh.val()) {
        ok = false;
        if (!firstMessage) {
          firstMessage = 'Please select Warehouse for row ' + (rowIndex + 1);
          firstEl = $wh;
        }
        markInvalid($wh);
      }

      // Product / Item
      if (!$prod.val()) {
        ok = false;
        if (!firstMessage) {
          firstMessage = 'Please select Item for row ' + (rowIndex + 1);
          firstEl = $prod;
        }
        markInvalid($prod);
      }

      // Qty > 0
      const qtyVal = parseFloat($qty.val() || '0') || 0;
      if (qtyVal <= 0) {
        ok = false;
        if (!firstMessage) {
          firstMessage = 'Please enter Item qty (> 0) for row ' + (rowIndex + 1);
          firstEl = $qty;
        }
        markInvalid($qty);
      }
    });

    return {
      ok,
      firstMessage,
      firstEl
    };
  }

  /**
   * validateReceipts() -> if any receipt amount > 0 then account must be selected
   * returns { ok, firstMessage, firstEl }
   */
  function validateReceipts() {
    let ok = true,
      firstMessage = null,
      firstEl = null;
    $('#rvWrapper .rv-row').each(function(i) {
      const $row = $(this);
      const $head = $row.find('.rv-head');
      const $acc = $row.find('.rv-account');
      const $amt = $row.find('.rv-amount');
      const amtVal = parseFloat($amt.val() || '0') || 0;

      if (amtVal > 0) {
        if (!$head.val()) {
          ok = false;
          if (!firstMessage) {
            firstMessage = 'Please select Head for receipt row ' + (i + 1);
            firstEl = $head;
          }
          markInvalid($head);
        }
        if (!$acc.val()) {
          ok = false;
          if (!firstMessage) {
            firstMessage = 'Please select Account for receipt row ' + (i + 1);
            firstEl = $acc;
          }
          markInvalid($acc);
        }
      }
    });
    return {
      ok,
      firstMessage,
      firstEl
    };
  }

  /**
   * validateHeader() -> Type & Party mandatory
   */
  function validateHeader() {
    let ok = true,
      firstMessage = null,
      firstEl = null;
    // Type (partyType) - we expect a radio selected
    const partyType = $('input[name="partyType"]:checked').val();
    if (!partyType) {
      ok = false;
      firstMessage = 'Please select Type';
      firstEl = $('input[name="partyType"]').first();
      // mark buttons visually
      $('#partyTypeGroup').addClass('invalid-cell');
    } else {
      $('#partyTypeGroup').removeClass('invalid-cell');
    }

    // Party / Customer
    const cust = $('#customerSelect').val();
    if (!cust) {
      ok = false;
      if (!firstMessage) {
        firstMessage = 'Please select Party (Customer / Vendor)';
        firstEl = $('#customerSelect');
      }
      markInvalid($('#customerSelect'));
    }

    return {
      ok,
      firstMessage,
      firstEl
    };
  }

  /**
   * validateFormAll() -> run header, rows, receipts
   * returns { ok, message, el }
   */
  function validateFormAll() {
    clearAllInvalids();

    // header
    const h = validateHeader();
    if (!h.ok) {
      return {
        ok: false,
        message: h.firstMessage,
        el: h.firstEl
      };
    }

    // rows
    const r = validateRows();
    if (!r.ok) {
      return {
        ok: false,
        message: r.firstMessage,
        el: r.firstEl
      };
    }

    // receipts
    const rec = validateReceipts();
    if (!rec.ok) {
      return {
        ok: false,
        message: rec.firstMessage,
        el: rec.firstEl
      };
    }

    // if all ok
    return {
      ok: true
    };
  }

  /* ---------- Hook validation into Save / Post ---------- */

  // override Save button to validate first
  $('#btnSave').off('click').on('click', function() {
    cleanupEmptyRows(); // remove empty rows
    updateGrandTotals(); // recompute totals after cleanup
    refreshPostedState();

    // run the existing validation pipeline
    const v = validateFormAll();
    if (!v.ok) {
      showAlert('danger', v.message);
      if (v.el && v.el.length) {
        v.el.focus();
        if (v.el.hasClass('js-customer')) v.el.select2?.('open');
      }
      return;
    }

    // proceed to save
    ensureSaved();
  });


  // override Post buttons to validate first
  $('#btnHeaderPosted, #btnPosted').off('click').on('click', function() {
    cleanupEmptyRows();
    updateGrandTotals();
    refreshPostedState();

    const v = validateFormAll();
    if (!v.ok) {
      showAlert('danger', v.message);
      if (v.el && v.el.length) {
        v.el.focus();
        if (v.el.hasClass('js-customer')) v.el.select2?.('open');
      }
      return;
    }

    if (!canPost()) {
      showAlert('danger', 'No valid item lines to post');
      return;
    }

    ensureSaved().then(postNow);
  });


  function isRowMeaningful($row) {
    const prod = $row.find('.product-select').val();
    const wh = $row.find('.warehouse').val();
    const qty = parseFloat($row.find('.sales-qty').val() || '0') || 0;
    const discPct = parseFloat($row.find('.discount-percent').val() || '0') || 0;
    const discAmt = parseFloat($row.find('.discount-amount').val() || '0') || 0;

    // consider row meaningful if product selected OR qty > 0 OR discount entered OR warehouse selected
    return !!prod || !!wh || qty > 0 || discPct !== 0 || discAmt !== 0;
  }

  function cleanupEmptyRows() {
    $('#salesTableBody tr').each(function() {
      const $r = $(this);
      const prod = $r.find('.product-select').val();
      const wh = $r.find('.warehouse').val();
      const qty = parseFloat($r.find('.sales-qty').val() || '0') || 0;

      // Remove row when qty is zero or (product empty AND warehouse empty)
      // We want to remove:
      //  - rows where qty <= 0 (user didn't enter qty) because they are meaningless,
      //  - or rows that are fully empty.
      if ((qty <= 0) || ((!prod || prod === '') && (!wh || wh === ''))) {
        // ensure we keep at least one row in UI
        if ($('#salesTableBody tr').length > 1) {
          $r.remove();
        } else {
          // if only one row left, clear its fields instead of removing (keeps UI stable)
          $r.find('select').val('');
          $r.find('input').val('');
          $r.find('.stock').val('');
          $r.find('.sales-amount').val('0');
        }
      }
    });

    // ensure at least one blank row exists
    if ($('#salesTableBody tr').length === 0) addNewRow();
  }

  /* ========== AUTO-SAVE/RESTORE FORM STATE (SIMPLE & GUARANTEED) ========== */
  const FORM_STATE_KEY = 'sales_form_autosave';

  // Save IMMEDIATELY (no delay)
  function saveFormState() {
    try {
      const formData = $('#saleForm').serializeArray();
      localStorage.setItem(FORM_STATE_KEY, JSON.stringify(formData));
    } catch(e) {/* ignore */}
  }

  // Clear saved state
  function clearFormState() {
    try {
      localStorage.removeItem(FORM_STATE_KEY);
    } catch(e) {/* ignore */}
  }

  // Restore on page load - SIMPLE VERSION
  function restoreFormState() {
    try {
      const saved = localStorage.getItem(FORM_STATE_KEY);
      if (!saved) return false;

      const formData = JSON.parse(saved);
      
      // Group by field name
      const grouped = {};
      formData.forEach(function(item) {
        if (!grouped[item.name]) grouped[item.name] = [];
        grouped[item.name].push(item.value);
      });

      // Restore ALL fields
      Object.keys(grouped).forEach(function(name) {
        const values = grouped[name];
        const $field = $('[name="'+name+'"]');
        
        if ($field.length === 0 || name === 'Invoice_no') return;
        
        // Radio buttons
        if ($field.is(':radio')) {
          $field.filter('[value="'+values[0]+'"]').prop('checked', true);
          return;
        }
        
        // Single field
        if ($field.length === 1) {
          $field.val(values[0]);
          return;
        }
        
        // Multiple fields (arrays like product rows)
        $field.each(function(idx) {
          if (values[idx] !== undefined) {
            $(this).val(values[idx]);
          }
        });
      });

      // Special: Recreate product rows if needed
      const productCount = grouped['product_id[]']?.length || 0;
      if (productCount > 0) {
        $('#salesTableBody').empty();
        for (let i = 0; i < productCount; i++) {
          addNewRow();
        }
        
        // Populate after rows created
        setTimeout(function() {
          // Product IDs & Names
          grouped['product_id[]']?.forEach(function(val, i) {
            if (!val) return;
            const $row = $('#salesTableBody tr').eq(i);
            const $select = $row.find('.product-select');
            const name = grouped['product_search[]']?.[i] || 'Product ' + val;
            
            const newOption = new Option(name, val, true, true);
            $select.empty().append(newOption).trigger('change');
            $row.find('.product_name_hidden').val(name);
          });
          
          // Warehouse
          grouped['warehouse_name[]']?.forEach(function(val, i) {
            $('#salesTableBody tr').eq(i).find('.warehouse').val(val);
          });
          
          // Stock
          grouped['stock[]']?.forEach(function(val, i) {
            $('#salesTableBody tr').eq(i).find('.stock').val(val);
          });
          
          // Sales Price
          grouped['sales-price[]']?.forEach(function(val, i) {
            $('#salesTableBody tr').eq(i).find('.sales-price').val(val);
          });
          
          // Qty
          grouped['sales-qty[]']?.forEach(function(val, i) {
            $('#salesTableBody tr').eq(i).find('.sales-qty').val(val);
          });
          
          // Retail Price
          grouped['retail-price[]']?.forEach(function(val, i) {
            $('#salesTableBody tr').eq(i).find('.retail-price').val(val);
          });
          
          // Discount Mode
          grouped['discount_mode[]']?.forEach(function(val, i) {
            const $row = $('#salesTableBody tr').eq(i);
            $row.find('.discount-mode').val(val);
            $row.find('.disc-mode-btn').removeClass('active');
            $row.find('.disc-mode-btn[data-mode="'+val+'"]').addClass('active');
          });
          
          // Discount Percent
          grouped['discount-percent[]']?.forEach(function(val, i) {
            $('#salesTableBody tr').eq(i).find('.discount-percent').val(val);
          });
          
          // Discount Amount
          grouped['discount-amount[]']?.forEach(function(val, i) {
            const $row = $('#salesTableBody tr').eq(i);
            $row.find('.discount-amount').val(val);
            $row.find('.discount-amount-display').val(val);
            const mode = $row.find('.discount-mode').val();
            $row.find('.discount-value').val(mode === 'amount' ? val : grouped['discount-percent[]'][i]);
          });
          
          // Sales Rate
          grouped['sales-rate[]']?.forEach(function(val, i) {
            $('#salesTableBody tr').eq(i).find('.sales-rate').val(val);
          });
          
          // Sales Amount
          grouped['sales-amount[]']?.forEach(function(val, i) {
            $('#salesTableBody tr').eq(i).find('.sales-amount').val(val);
          });
          
          // Recalculate totals
          updateGrandTotals();
        }, 100);
      }

      // Special: Recreate receipt rows
      const receiptCount = grouped['receipt_account_id[]']?.length || 0;
      if (receiptCount > 0) {
        $('#rvWrapper .rv-row').remove();
        for (let i = 0; i < receiptCount; i++) {
          $('#btnAddRV').click();
        }
        
        setTimeout(function() {
          grouped['receipt_account_id[]']?.forEach(function(val, i) {
            $('#rvWrapper .rv-row').eq(i).find('.rv-account').val(val);
          });
          grouped['receipt_amount[]']?.forEach(function(val, i) {
            $('#rvWrapper .rv-row').eq(i).find('.rv-amount').val(val);
          });
          grouped['receipt_narration[]']?.forEach(function(val, i) {
            const $select = $('#rvWrapper .rv-row').eq(i).find('.rv-narration');
            $select.attr('data-selected', val);
            loadNarrationsInto($select);
          });
          recomputeReceipts();
        }, 200);
      }

      return true;
    } catch(e) {
      console.error('Restore failed:', e);
      return false;
    }
  }

  // Save on EVERY change (immediate, no debounce)
  $(document).on('change input', '#saleForm input, #saleForm select, #saleForm textarea', function() {
    saveFormState();
  });

  // Restore on page load
  $(function() {
    // Init existing Select2
    $('#salesTableBody tr').each(function() {
        if(window.initProductSelect) window.initProductSelect($(this));
    });

    if ($('#salesTableBody tr').length === 0) {
      if (!restoreFormState()) {
        addNewRow();
      }
    }
  });

  // Full Grid Navigation (Arrows Up/Down/Left/Right)
  $(document).on('keydown', '#salesTableBody input, #rvWrapper input', function(e) {
      if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].indexOf(e.key) === -1) return;
      
      var $this = $(this);
      var $row = $this.closest('tr');
      // For rvWrapper it might be a div with class rv-row, let's just use closest('tr, .rv-row')
      if ($row.length === 0) $row = $this.closest('.rv-row');
      var $inputs = $row.find('input:visible:not([readonly])'); 
      var currentIndex = $inputs.index($this);

      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
          e.preventDefault(); // Stop page scroll
          var classList = $this.attr('class').split(' ');
          var ignore = ['form-control', 'form-control-sm', 'text-end', 'text-center', 'input-readonly', 'fw-bold', 'loading-indicator'];
          var specificClass = classList.find(c => ignore.indexOf(c) === -1);
          
          if (specificClass) {
              var $targetRow = (e.key === 'ArrowDown') ? $row.next('tr, .rv-row') : $row.prev('tr, .rv-row');
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

</script>
@endsection