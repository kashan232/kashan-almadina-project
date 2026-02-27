@extends('admin_panel.layout.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

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
    font-size: .85rem;
    padding: .4rem .6rem;
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
</style>

<div class="container-fluid py-4">
  <div class="main-container bg-white border shadow-sm mx-auto p-2 rounded-3" style="max-width: 98%;">

    <div id="alertBox" class="alert d-none mb-3" role="alert"></div>

    <div class="d-flex justify-content-between align-items-center mb-3 bg-light p-2 rounded shadow-sm">
      <div style="min-width:80px;"></div>

      <div class="d-flex align-items-center gap-2 justify-content-center flex-grow-1">
          <h6 class="page-title mb-0 fw-bold">Create Sale</h6>
          <span class="badge bg-primary px-3 py-2 rounded-pill shadow-sm" style="font-size:12px;">
              <i class="fa fa-receipt me-1"></i> {{ $nextInvoiceNumber }}
          </span>
      </div>

      <div class="d-flex align-items-center gap-2">
          <span id="bookingBadge" class="badge bg-warning text-dark d-none" style="font-size: 10px;">Unposted</span>
          <a href="{{ route('sale.index') }}" id="listBtn" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
              <i class="fa fa-list me-1"></i> List <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+L</kbd>
          </a>
      </div>
    </div>

    <form id="saleForm" autocomplete="off" action="{{ route('sale.ajax.save') }}" method="POST">
      @csrf
      <input type="hidden" id="booking_id" name="booking_id" value="">


      <div class="d-flex gap-3 align-items-start border-bottom py-3">
        {{-- LEFT: Invoice & Customer --}}
        <div class="bg-light border rounded-3 p-2 shadow-sm" style="min-width: 300px; max-width: 300px; font-size: 0.8rem;">
          <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
            <h6 class="mb-0 fw-bold text-primary">
              <i class="bi bi-receipt me-1"></i>Invoice & Customer
            </h6>
          </div>

          {{-- Invoice Numbers - Grid Layout --}}
          <div class="row g-1 mb-2">
            <div class="col-6">
              <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">Invoice No.</label>
              <input type="text" class="form-control form-control-sm bg-white border-0 shadow-sm fw-bold text-primary py-0" 
                     name="Invoice_no" value="{{ $nextInvoiceNumber }}" readonly style="font-size: 0.8rem;">
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">Manual Inv#</label>
              <input type="text" class="form-control form-control-sm py-0" 
                     name="Invoice_main" placeholder="Optional" value="{{ old('Invoice_main') }}" style="font-size: 0.8rem;">
            </div>
          </div>

          {{-- Customer Type Toggle --}}
          <div class="mb-2">
            <div class="btn-group w-100" role="group">
              <input type="radio" class="btn-check" name="partyType" id="typeCustomers" value="customer" {{ old('partyType', 'customer') == 'customer' ? 'checked' : '' }}>
              <label class="btn btn-outline-primary btn-sm py-0" for="typeCustomers" style="font-size: 0.75rem;">
                Customers
              </label>

              <input type="radio" class="btn-check" name="partyType" id="typeWalkin" value="walking" {{ old('partyType') == 'walking' ? 'checked' : '' }}>
              <label class="btn btn-outline-primary btn-sm py-0" for="typeWalkin" style="font-size: 0.75rem;">
                Walk-in
              </label>

              <input type="radio" class="btn-check" name="partyType" id="typeVendors" value="vendor" {{ old('partyType') == 'vendor' ? 'checked' : '' }}>
              <label class="btn btn-outline-primary btn-sm py-0" for="typeVendors" style="font-size: 0.75rem;">
                Vendors
              </label>
            </div>
          </div>

          {{-- Select Customer --}}
          <div class="mb-2">
            <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">Select Party</label>
            <select class="form-select form-select-sm py-0" name="customer" id="customerSelect" data-old-val="{{ old('customer') }}" style="font-size: 0.8rem;">
              <option selected disabled>Loading…</option>
            </select>
          </div>

          {{-- Address --}}
          <div class="mb-2">
            <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">Address</label>
            <textarea class="form-control form-control-sm py-1" id="address" name="address" rows="1" placeholder="Address" style="font-size: 0.75rem;">{{ old('address') }}</textarea>
          </div>

          {{-- Tel & Rewards --}}
          <div class="row g-1 mb-2">
            <div class="col-6">
              <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">Tel#</label>
              <input type="text" class="form-control form-control-sm py-0" id="tel" name="tel" placeholder="Phone" value="{{ old('tel') }}" style="font-size: 0.8rem;">
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">Prev Bal</label>
              <input type="text" class="form-control form-control-sm text-end fw-bold py-0" id="previousBalance" 
                     name="previousBalance" value="{{ old('previousBalance', '0') }}" placeholder="0.00" style="font-size: 0.8rem;">
            </div>
          </div>

          {{-- Remarks --}}
          <div class="mb-2">
            <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">Remarks</label>
            <textarea class="form-control form-control-sm py-1" id="remarks" name="remarks" rows="1" placeholder="Notes" style="font-size: 0.75rem;">{{ old('remarks') }}</textarea>
          </div>

          <div class="text-end mt-2">
            <button id="clearCustomerData" type="button" class="btn btn-xs btn-outline-secondary py-0" style="font-size: 0.7rem;">
              Clear All
            </button>
          </div>
        </div>

        {{-- RIGHT: Items --}}
        <div class="flex-grow-1">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="section-title mb-0">Items</div>
            <button type="button" class="btn btn-sm btn-primary" id="btnAdd">Add Row</button>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0" style="width: 100%; font-size: 0.9rem;">
              <thead class="table-light">
                <tr>
                  <th style="width:7%">Item ID</th>
                  <th style="width:18%">Product</th>
                  <th style="width:11%">Warehouse</th>
                  <th style="width:8%" class="text-center">Stock</th>
                  <th style="width:10%" class="text-end">Sales Price</th>
                  <th style="width:7%" class="text-center">Qty</th>
                  <th style="width:10%" class="text-end">Retail Price</th>
                  <th style="width:14%" class="text-center">Discount</th>
                  <th style="width:9%" class="text-end">Disc. Amt</th>
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
                          @if($pid)
                            <option value="{{ $pid }}" selected>{{ $pSearch }}</option>
                          @else
                            <option value="" disabled selected>Select Product</option>
                          @endif
                        </select>
                        <input type="hidden" name="product_search[]" class="product_name_hidden" value="{{ $pSearch }}">
                      </td>
                      <td style="width: 120px;">
                        <select class="form-select form-select-sm warehouse" name="warehouse_name[]">
                          <option value="">Select</option>
                          @foreach ($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ $whId == $wh->id ? 'selected' : '' }}>{{ $wh->warehouse_name }}</option>
                          @endforeach
                        </select>
                      </td>
                      <td style="width: 80px;"><input type="text" class="form-control form-control-sm stock text-center input-readonly" name="stock[]" value="{{ $stock }}" readonly></td>
                      <td style="width: 100px;"><input type="text" class="form-control form-control-sm text-end sales-price input-readonly" name="sales-price[]" value="{{ $sPrice }}" readonly></td>
                      <td style="width: 70px;"><input type="text" class="form-control form-control-sm text-center sales-qty" name="sales-qty[]" value="{{ $qty }}"></td>
                      <td style="width: 100px;"><input type="text" class="form-control form-control-sm text-end retail-price input-readonly" name="retail-price[]" value="{{ $rPrice }}" readonly></td>
                      <td style="width: 130px;">
                        <div class="discount-wrapper">
                          <input type="number" step="0.01" class="form-control form-control-sm text-end discount-value px-1" placeholder="0" value="{{ $displayValue }}" style="width: 55px;">
                          <div class="btn-group">
                            <button type="button" class="btn btn-outline-primary btn-xs disc-mode-btn {{ $dMode == 'percent' ? 'active' : '' }}" data-mode="percent">%</button>
                            <button type="button" class="btn btn-outline-primary btn-xs disc-mode-btn {{ $dMode == 'amount' ? 'active' : '' }}" data-mode="amount">Rs</button>
                          </div>
                          <input type="hidden" class="discount-mode" name="discount_mode[]" value="{{ $dMode }}">
                          <input type="hidden" class="discount-percent" name="discount-percent[]" value="{{ $dPct }}">
                          <input type="hidden" class="discount-amount" name="discount-amount[]" value="{{ $dAmt }}">
                        </div>
                      </td>
                      <td style="width: 80px;"><input type="text" class="form-control form-control-sm text-end discount-amount-display input-readonly" value="{{ $dAmt }}" readonly></td>
                      <td style="width: 100px;"><input type="text" class="form-control form-control-sm text-end sales-amount input-readonly" name="sales-amount[]" value="{{ $sAmount }}" readonly></td>
                      <td class="text-center" style="width: 40px;"><button type="button" class="btn btn-xs btn-outline-danger del-row">&times;</button></td>
                    </tr>

                  @endforeach
                @endif
              </tbody>
              <tfoot>
                <tr>
                  <td colspan="7" class="text-end fw-bold">Total:</td>
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
          <div class="bg-light border rounded-3 p-3 shadow-sm">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
              <h5 class="mb-0 fw-bold text-success">
                <i class="bi bi-cash-stack me-2"></i>Receipt Vouchers
              </h5>
              <button type="button" class="btn btn-success btn-sm" id="btnAddRV">
                <i class="bi bi-plus-circle me-1"></i>Add Receipt
              </button>
            </div>
            
            <div id="rvWrapper">
              @if(old('receipt_account_id'))
                  @foreach(old('receipt_account_id') as $index => $accId)
                      <div class="receipt-row bg-white border rounded-3 p-2 mb-2 shadow-sm">
                        <div class="row g-2 align-items-center">
                          <div class="col-md-4">
                            <label class="form-label text-muted small mb-1">Account</label>
                            <select class="form-select form-select-sm rv-account" name="receipt_account_id[]">
                              <option value="" disabled>Select account</option>
                              @foreach ($accounts as $acc)
                              <option value="{{ $acc->id }}" {{ $accId == $acc->id ? 'selected' : '' }}>{{ $acc->title }}</option>
                              @endforeach
                            </select>
                          </div>
                          <div class="col-md-2">
                            <label class="form-label text-muted small mb-1">Amount</label>
                            <input type="text" class="form-control form-control-sm text-end fw-bold rv-amount" 
                                   name="receipt_amount[]" placeholder="0.00" value="{{ old('receipt_amount')[$index] ?? '' }}">
                          </div>
                          <div class="col-md-5">
                            <label class="form-label text-muted small mb-1">Narration</label>
                            <select class="form-select form-select-sm rv-narration" name="receipt_narration[]" 
                                    data-selected="{{ old('receipt_narration')[$index] ?? '' }}">
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
                  <div class="receipt-row bg-white border rounded-3 p-2 mb-2 shadow-sm rv-row">
                    <div class="row g-2 align-items-center">
                      <div class="col-md-4">
                        <label class="form-label text-muted small mb-1">Account</label>
                        <select class="form-select form-select-sm rv-account" name="receipt_account_id[]">
                          <option value="" disabled selected>Select account</option>
                          @foreach ($accounts as $acc)
                          <option value="{{ $acc->id }}">{{ $acc->title }}</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-md-2">
                        <label class="form-label text-muted small mb-1">Amount</label>
                        <input type="text" class="form-control form-control-sm text-end fw-bold rv-amount" 
                               name="receipt_amount[]" placeholder="0.00">
                      </div>
                      <div class="col-md-5">
                        <label class="form-label text-muted small mb-1">Narration</label>
                        <select class="form-select form-select-sm rv-narration" name="receipt_narration[]">
                          <option value="">Select narration...</option>
                        </select>
                      </div>
                      <div class="col-md-1"></div>
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
          <div class="bg-light border rounded-3 p-3 shadow-sm">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
              <h5 class="mb-0 fw-bold text-info">
                <i class="bi bi-calculator me-2"></i>Totals
              </h5>
            </div>

            <div class="totals-card">
              <!-- Total Qty -->
              <div class="d-flex justify-content-between py-2 border-bottom">
                <span class="text-muted small">Total Qty</span>
                <span class="fw-semibold" id="tQty">0</span>
              </div>

              <!-- Invoice Gross -->
              <div class="d-flex justify-content-between py-2 border-bottom">
                <span class="text-muted small">Invoice Gross (Σ Sales Price × Qty)</span>
                <span class="fw-semibold" id="tGross">0.00</span>
              </div>

              <!-- Line Discount -->
              <div class="d-flex justify-content-between py-2 border-bottom">
                <span class="text-muted small">Line Discount (on Retail)</span>
                <span class="fw-semibold text-danger" id="tLineDisc">0.00</span>
              </div>

              <!-- Sub-Total -->
              <div class="d-flex justify-content-between py-2 border-bottom bg-white rounded px-2">
                <span class="fw-bold">Sub-Total</span>
                <span class="fw-bold text-primary" id="tSub">0.00</span>
              </div>

              <!-- Order Discount Input -->
              <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <span class="text-muted small">Order Discount</span>
                <div class="d-flex align-items-center gap-1">
                  <input type="number" step="0.01" class="form-control form-control-sm text-end" 
                         id="orderDiscountValue" name="order_discount_value" 
                         value="{{ old('order_discount_value', '0') }}" style="width:70px">
                  <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-primary order-disc-btn {{ old('order_discount_mode', 'percent') == 'percent' ? 'active' : '' }}" data-mode="percent">%</button>
                    <button type="button" class="btn btn-outline-primary order-disc-btn {{ old('order_discount_mode') == 'amount' ? 'active' : '' }}" data-mode="amount">₨</button>
                  </div>
                </div>
                <input type="hidden" id="orderDiscountMode" name="order_discount_mode" value="{{ old('order_discount_mode', 'percent') }}">
                <input type="hidden" id="discountPercent" name="discountPercent" value="{{ old('discountPercent', '0') }}">
                <input type="hidden" id="discountAmountHidden" value="0">
              </div>

              <!-- Order Discount Rs -->
              <div class="d-flex justify-content-between py-2 border-bottom">
                <span class="text-muted small">Order Discount Rs</span>
                <span class="fw-semibold text-danger" id="tOrderDisc">0.00</span>
              </div>

              <!-- Previous Balance -->
              <div class="d-flex justify-content-between py-2 border-bottom">
                <span class="text-warning small fw-semibold">Previous Balance</span>
                <span class="fw-semibold text-warning" id="tPrev">0.00</span>
              </div>

              <!-- Payable / Total Balance -->
              <div class="d-flex justify-content-between py-3 bg-primary bg-opacity-10 rounded-3 px-2 mt-2">
                <span class="fw-bold text-primary">Payable / Total Balance</span>
                <span class="fw-bold fs-4 text-primary" id="tPayable">0.00</span>
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

      {{-- BOTTOM BUTTONS (Purchase style) --}}
      <div class="d-flex gap-2 mt-4 justify-content-end border-top pt-3">
        
        {{-- Save Draft --}}
        <button type="button" id="saveDraftBtn" class="btn btn-sm btn-warning rounded-pill px-4 shadow-sm">
          <i class="fa fa-floppy-o me-1"></i> Save Draft
          <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>
        </button>

        {{-- Print Preview / Real Print --}}
        <button type="button" id="previewPrintBtn" class="btn btn-sm btn-outline-dark rounded-pill px-4">
          <i class="fa fa-print me-1"></i> Print Preview
          <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+P</kbd>
        </button>

        {{-- Post --}}
        <button type="button" id="postBtn" class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm">
          <i class="fa fa-send me-1"></i> Post
          <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+&#8629;</kbd>
        </button>

      </div>
    </form>
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


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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
        width: '100%',
        dropdownParent: $select.parent()
      });

    }).fail(function(xhr, status, error) {
      console.error('Error loading narrations:', status, error);
      $select.empty().append('<option value="">Error loading narrations</option>').prop('disabled', false);
    });
  }

  // Page load initialization
  $(function() {
    $('.rv-narration').each(function() {
      loadNarrationsInto($(this));
    });

    // If rows exist (from old input), recalculate totals
    if ($('#salesTableBody tr').length > 0) {
        updateGrandTotals();
        recomputeReceipts();
    } else {
        // If no rows, add one empty row
        addNewRow();
    }
  });

  function isRowMeaningful($row) {
    const productId = $row.find('.product-select').val();
    const qty = parseFloat($row.find('.sales-qty').val() || '0');
    return productId && qty > 0;
  }

  function addNewRow() {
    const $last = $('#salesTableBody tr:last-child');
    if ($last.length) {
      if (!isRowMeaningful($last)) {
        $last.find('.item-id-input').focus();
        showAlert('danger', 'Please complete the current row before adding a new one.');
        return;
      }
    }

    const rowId = 'row-' + Date.now();
    $('#salesTableBody').append(`
    <tr data-row-id="${rowId}">
      <td style="width: 70px;">
        <input type="text" class="form-control form-control-sm item-id-input text-center" placeholder="ID">
      </td>
      <td>
        <select name="product_id[]" class="form-control form-control-sm product-select" style="width: 100%;">
            <option value="" disabled selected>Select Product</option>
        </select>
        <input type="hidden" name="product_search[]" class="product_name_hidden">
      </td>
      <td style="width: 120px;">
        <select class="form-select form-select-sm warehouse" name="warehouse_name[]">
          <option value="">Select</option>
          @foreach ($warehouses as $wh)
            <option value="{{ $wh->id }}">{{ $wh->warehouse_name }}</option>
          @endforeach
        </select>
      </td>
      <td style="width: 80px;"><input type="text" class="form-control form-control-sm stock text-center input-readonly" name="stock[]" readonly></td>
      <td style="width: 100px;"><input type="text" class="form-control form-control-sm text-end sales-price input-readonly" name="sales-price[]" value="0" readonly></td>
      <td style="width: 70px;"><input type="text" class="form-control form-control-sm text-center sales-qty" name="sales-qty[]" value=""></td>
      <td style="width: 100px;"><input type="text" class="form-control form-control-sm text-end retail-price input-readonly" name="retail-price[]" value="0" readonly></td>
      <td style="width: 130px;">
        <div class="discount-wrapper">
          <input type="number" step="0.01" class="form-control form-control-sm text-end discount-value px-1" placeholder="0" value="0" style="width: 55px;">
          <div class="btn-group">
            <button type="button" class="btn btn-outline-primary btn-xs disc-mode-btn active" data-mode="percent">%</button>
            <button type="button" class="btn btn-outline-primary btn-xs disc-mode-btn" data-mode="amount">Rs</button>
          </div>
          <input type="hidden" class="discount-mode" name="discount_mode[]" value="percent">
          <input type="hidden" class="discount-percent" name="discount-percent[]" value="0">
          <input type="hidden" class="discount-amount" name="discount-amount[]" value="0">
        </div>
      </td>
      <td style="width: 80px;"><input type="text" class="form-control form-control-sm text-end discount-amount-display input-readonly" value="0" readonly></td>
      <td style="width: 100px;"><input type="text" class="form-control form-control-sm text-end sales-amount input-readonly" name="sales-amount[]" value="0" readonly></td>
      <td class="text-center" style="width: 40px;"><button type="button" class="btn btn-xs btn-outline-danger del-row">&times;</button></td>
    </tr>
  `);

    const $row = $('#salesTableBody tr:last-child');
    if (window.initProductSelect) window.initProductSelect($row);
    
    setTimeout(() => {
        $row.find('.item-id-input').focus();
    }, 50);

    refreshPostedState();
  }



  function canPost() {
    let ok = false;
    $('#salesTableBody tr').each(function() {
      const pid = $(this).find('.product-select').val(); // Updated for Select2
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

      // AJAX Save Draft
      function ajaxSaveDraft(showMsg = true) {
          if (!canPost()) {
              if(showMsg) Swal.fire({ icon: 'error', title: 'Error', text: 'Add at least one item with quantity.' });
              return Promise.reject();
          }

          $('#saveDraftBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Saving...');
          
          return $.ajax({
              url: '{{ route("sale.ajax.save") }}',
              type: 'POST',
              data: $('#saleForm').serialize(),
              success: function(res) {
                  if (res.ok) {
                      _savedBookingId = res.booking_id;
                      $('#booking_id').val(res.booking_id);
                      if (showMsg) {
                          Swal.fire({ 
                              icon: 'success', 
                              title: 'Draft Saved', 
                              text: 'Sale saved as Unposted (Booking).',
                              timer: 2000, 
                              showConfirmButton: false 
                          });
                      }
                      
                      // UI Updates
                      $('#postBtn').html('<i class="fa fa-send me-1"></i> Post <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+&#8629;</kbd>')
                                 .removeClass('btn-primary').addClass('btn-success');
                      
                      // Change Title to Edit if it was New
                      $('.page-title').text('Edit Sale (Unposted)');
                  } else {
                      Swal.fire({
                          icon: 'error',
                          title: 'Error',
                          text: res.error || 'Save failed'
                      });
                  }
              },
              error: function(xhr) {
                  let msg = 'Save failed.';
                  try {
                      msg = JSON.parse(xhr.responseText).message || msg;
                  } catch(e) {}
                  Swal.fire({
                      icon: 'error',
                      title: 'Save Failed',
                      text: msg
                  });
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
                          text: 'Sale posted successfully. Redirecting...', 
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
  });


  /* ---------- Customer type & list ---------- */
  function loadCustomersByType(type) {
    const $sel = $('#customerSelect').prop('disabled', true).empty().append('<option selected disabled>Loading...</option>');
    $.get('{{ route("customers.filter") }}', {
      type
    }, function(list) {
      $sel.empty().append('<option selected disabled>Select ' + (type === 'vendor' ? 'vendor' : (type === 'walking' ? 'walk-in customer' : 'customer')) + '</option>');
      list.forEach(r => $sel.append('<option value="' + r.id + '">' + r.text + '</option>'));
      $('#customerCountHint').text(list.length + ' ' + type + (list.length === 1 ? ' found' : 's found'));
      $sel.prop('disabled', false);
    }).fail(function() {
      $sel.empty().append('<option selected disabled>Error loading</option>').prop('disabled', false);
      $('#customerCountHint').text('');
    });
  }

  loadCustomersByType('customer');
  $(document).on('change', 'input[name="partyType"]', function() {
    $('#customerSelect').val(null).trigger('change');
    $('#address,#tel,#remarks').val('');
    loadCustomersByType(this.value);
  });
  $(document).on('change', '#customerSelect', function() {
    let id = $(this).val();
    if (!id) return;

    let type = $('input[name="partyType"]:checked').val(); // Get the selected type (customer/vendor)

    $.get('{{ route("customers.show", ["id" => "__ID__"]) }}'.replace('__ID__', id) + '?type=' + type, function(d) {
      // Fill in the customer/vendor details
      $('#address').val(d.address || '');
      $('#tel').val(d.mobile || '');
      $('#remarks').val(d.remarks || '');
      $('#previousBalance').val((+d.previous_balance || 0).toFixed(2)); // Set previous balance for customer
      updateGrandTotals(); // Update other totals if needed
    });
  });

  $('#clearCustomerData').on('click', function() {
    $('#customerSelect').val(null).trigger('change');
    $('#address,#tel,#remarks').val('');
    $('#previousBalance').val('0');
    updateGrandTotals();
  });



  /* ---------- Select2 Product Initialization ---------- */
  window.initProductSelect = function($row) {
    const $select = $row.find('.product-select');
    
    $select.select2({
      placeholder: "Select Product",
      allowClear: true,
      width: '100%',
      ajax: {
        url: '{{ route("search-products") }}',
        dataType: 'json',
        delay: 250,
        data: function(params) {
          return { q: params.term };
        },
        processResults: function(data) {
          return {
            results: data.map(function(item) {
              return {
                id: item.id,
                text: item.name,
                stock: item.stock,
                sale_price: item.sale_price,
                retail_price: item.retail_price
              };
            })
          };
        },
        cache: true
      }
    });

    /* Unified helper to update row with product data */
    window.updateRowWithProductData = function($row, data) {
      if (!data) return;
      
      $row.find('.item-id-input').val(data.id);
      $row.find('.product_name_hidden').val(data.text || data.name);
      
      // Select2 sync (if needed)
      const $select = $row.find('.product-select');
      if ($select.val() !== String(data.id)) {
          const newOption = new Option(data.text || data.name, data.id, true, true);
          $select.empty().append(newOption).trigger('change');
      }
      
      // Populate fields
      $row.find('.stock').val(data.stock || 0);
      $row.find('.sales-price').val(parseFloat(data.sale_price || 0).toFixed(2));
      $row.find('.retail-price').val(parseFloat(data.retail_price || 0).toFixed(2));
      
      computeRow($row);
      updateGrandTotals();
      refreshPostedState();
    };

    $select.on('select2:select', function(e) {
      const data = e.params.data;
      const $currentRow = $(this).closest('tr');
      
      window.updateRowWithProductData($currentRow, data);

      if ($currentRow.is(':last-child')) {
          addNewRow();
      } else {
          setTimeout(() => $currentRow.find('.sales-qty').focus(), 50);
      }
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
  };

  /* ---------- Item ID Lookup Logic ---------- */
  $(document).on('keydown', '.item-id-input', function(e) {
      if (e.key === 'Enter' || e.key === 'Tab') {
          const $input = $(this);
          const id = $input.val().trim();
          const $row = $input.closest('tr');
          const $select = $row.find('.product-select');
          
          if (!id) return;

          // If current selection is already same, just move focus
          if ($select.val() === id) {
             return;
          }

          $.get('{{ route("search-products") }}', { q: id }, function(res) {
              if (res && res.length > 0) {
                  const item = res.find(i => String(i.id) === String(id)) || res[0];
                  
                  // Use unified helper
                  window.updateRowWithProductData($row, {
                      id: item.id,
                      name: item.name,
                      text: item.name,
                      stock: item.stock,
                      sale_price: item.sale_price,
                      retail_price: item.retail_price
                  });

                  if ($row.is(':last-child')) {
                      addNewRow();
                  } else {
                      setTimeout(() => $row.find('.sales-qty').focus(), 50);
                  }
              } else {
                  if (typeof Swal !== 'undefined') {
                      Swal.fire({
                          icon: 'error',
                          title: 'Not Found',
                          text: 'No product found with ID: ' + id,
                          timer: 1500,
                          showConfirmButton: false
                      });
                  }
                  $input.select();
              }
          });

          if (e.key === 'Enter') e.preventDefault();
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
    const mode = $row.find('.discount-mode').val();
    const value = toNum($row.find('.discount-value').val());

    let discAmt = 0;
    let discPct = 0;

    if (mode === 'percent') {
      // Percentage mode
      discPct = value;
      discAmt = ((rp * qty) * value) / 100.0;
    } else {
      // Amount mode (PKR)
      discAmt = value;
      // Calculate equivalent percentage
      const retailTotal = rp * qty;
      discPct = retailTotal > 0 ? (value / retailTotal) * 100 : 0;
    }

    // Update hidden fields
    $row.find('.discount-percent').val(discPct.toFixed(2));
    $row.find('.discount-amount').val(discAmt.toFixed(2));
    
    // Update visible discount amount display
    $row.find('.discount-amount-display').val(discAmt.toFixed(2));

    // Calculate final amount
    const gross = sp * qty;
    const net = Math.max(0, gross - discAmt);
    $row.find('.sales-amount').val(net.toFixed(2));
  }

  // Discount Toggle Button Click
  $(document).on('click', '.disc-mode-btn', function() {
    const $btn = $(this);
    const $wrapper = $btn.closest('.discount-wrapper');
    const $row = $btn.closest('tr');
    const mode = $btn.data('mode');

    // Update UI
    $wrapper.find('.disc-mode-btn').removeClass('active');
    $btn.addClass('active');

    // Update mode
    $wrapper.find('.discount-mode').val(mode);

    // Recalculate
    computeRow($row);
    updateGrandTotals();
    refreshPostedState();
  });

  // Discount Value Input
  $(document).on('input', '.discount-value', function() {
    const $row = $(this).closest('tr');
    computeRow($row);
    updateGrandTotals();
    refreshPostedState();
  });

  // Other inputs (price, qty)
  $(document).on('input', '.sales-price, .sales-qty, .retail-price', function() {
    const $row = $(this).closest('tr');
    computeRow($row);
    updateGrandTotals();
    refreshPostedState();
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
    let tQty = 0,
      tGross = 0,
      tLineDisc = 0,
      tNet = 0;

    $('#salesTableBody tr').each(function() {
      const $r = $(this);
      const sp = toNum($r.find('.sales-price').val());
      const qty = toNum($r.find('.sales-qty').val());
      const dam = toNum($r.find('.discount-amount').val()); // Use calculated discount amount

      const gross = sp * qty;
      const net = Math.max(0, gross - dam);

      tQty += qty;
      tGross += gross;
      tLineDisc += dam;
      tNet += net;
    });

    // Calculate subtotal first
    const subTotal = Math.max(0, tGross - tLineDisc);

    // Order discount with toggle support
    const orderMode = $('#orderDiscountMode').val();
    const orderValue = toNum($('#orderDiscountValue').val());
    let orderDisc = 0;
    let orderPct = 0;

    if (orderMode === 'percent') {
      orderPct = orderValue;
      orderDisc = (subTotal * orderValue) / 100.0;
    } else {
      orderDisc = orderValue;
      orderPct = subTotal > 0 ? (orderValue / subTotal) * 100 : 0;
    }

    // Update hidden fields
    $('#discountPercent').val(orderPct.toFixed(2));
    $('#discountAmountHidden').val(orderDisc.toFixed(2));

    const prev = toNum($('#previousBalance').val());
    const receipts = toNum($('#receiptsTotal').text());

    const payable = Math.max(0, subTotal - orderDisc + prev - receipts);

    // UI
    $('#tQty').text(tQty.toFixed(0));
    $('#tGross').text(tGross.toFixed(2));
    $('#tLineDisc').text(tLineDisc.toFixed(2));
    $('#tSub').text(subTotal.toFixed(2));
    $('#tOrderDisc').text(orderDisc.toFixed(2));
    $('#tPrev').text(prev.toFixed(2));
    $('#tPayable').text(payable.toFixed(2));
    $('#totalAmount').text(tNet.toFixed(2));

    // mirrors for backend
    $('#subTotal1').val(tGross.toFixed(2));
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
  $(document).on('input', '#orderDiscountValue', updateGrandTotals);
  
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
      setTimeout(() => $newRow.find('.productSearch').focus(), 100);
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
        setTimeout(() => $newRow.find('.productSearch').focus(), 100);
      }
    }
  });


  /* ---------- Receipts (accounts) ---------- */
  function loadAccountsInto($select) {
    $select.prop('disabled', true).empty().append('<option value="">Loading...</option>');

    // Get the list of accounts
    $.get('{{ route("accounts.list") }}', {
      scope: 'cashbank'
    }, function(rows) {
      $select.empty().append('<option value="">Select account</option>');
      (rows || []).forEach(function(a) {
        $select.append('<option value="' + a.id + '">' + a.title + '</option>'); // Add account options
      });
      $select.prop('disabled', false); // Enable the select input after loading
    }).fail(function() {
      // If there's an error, display an error message
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
    $('#rvWrapper').append(`
    <div class="receipt-row bg-white border rounded-3 p-2 mb-2 shadow-sm rv-row">
      <div class="row g-2 align-items-center">
        <div class="col-md-4">
          <label class="form-label text-muted small mb-1">Account</label>
          <select class="form-select form-select-sm rv-account" name="receipt_account_id[]">
            <option value="">Select account</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label text-muted small mb-1">Amount</label>
          <input type="text" class="form-control form-control-sm text-end fw-bold rv-amount" 
                 name="receipt_amount[]" placeholder="0.00">
        </div>
        <div class="col-md-5">
          <label class="form-label text-muted small mb-1">Narration</label>
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

    // Load accounts and narrations into the newly added row
    loadAccountsInto($('#rvWrapper .rv-row:last .rv-account'));
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
    addNewRow();
    loadCustomersByType('customer');
    loadAccountsInto($('.rv-account').first());
    loadNarrationsInto($('.rv-narration').first());
    updateGrandTotals();
    refreshPostedState();
  }

  init();

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
      const $acc = $row.find('.rv-account');
      const $amt = $row.find('.rv-amount');
      const amtVal = parseFloat($amt.val() || '0') || 0;

      if (amtVal > 0 && (!$acc.val() || $acc.val() === "")) {
        ok = false;
        if (!firstMessage) {
          firstMessage = 'Please select Account for receipt row ' + (i + 1);
          firstEl = $acc;
        }
        markInvalid($acc);
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
        
        if ($field.length === 0) return;
        
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

</script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

@endsection