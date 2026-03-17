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
    border: 1px solid #e3342f !important;
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

  .totals-card {
    background: #fcfcfe;
    border: 1px solid #eee;
    border-radius: .5rem;
  }

  .totals-card .row+.row {
    border-top: 1px dashed #e5e7eb;
  }

  .searchResults {
    position: fixed !important;
    z-index: 99999 !important;
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
    background-color: #fff9c4 !important;
    border-color: #fdd835 !important;
    transition: background-color 0.3s ease;
  }
</style>

<div class="container-fluid py-4">
  <div class="main-container bg-white border shadow-sm mx-auto p-2 rounded-3" style="max-width: 98%;">

    <div id="alertBox" class="alert d-none mb-3" role="alert"></div>

    <div class="d-flex justify-content-between align-items-center mb-3 bg-light p-2 rounded shadow-sm">
      <div style="min-width:80px;"></div>

      <div class="d-flex align-items-center gap-2 justify-content-center flex-grow-1">
          <h6 class="page-title mb-0 fw-bold">Edit Sale</h6>
          <span id="statusBadge" class="badge bg-warning text-dark px-3 py-2 rounded-pill shadow-sm" style="font-size:12px;">
              <i class="fa fa-pencil me-1"></i> Unposted
          </span>
          <span id="idBadge" class="badge bg-primary px-3 py-2 rounded-pill shadow-sm" style="font-size:12px;">
              <i class="fa fa-tag me-1"></i> ID: {{ $booking->id }}
          </span>
      </div>

      <div class="d-flex align-items-center gap-2">
          <a href="{{ route('sale.index') }}" id="listBtn" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
              <i class="fa fa-list me-1"></i> List <kbd style="font-size:9px;opacity:.8;margin-left:4px;">Ctrl+L</kbd>
          </a>
      </div>
    </div>

    <form id="saleForm" autocomplete="off" action="{{ route('sale.ajax.save') }}" method="POST">
      @csrf
      <input type="hidden" id="booking_id" name="booking_id" value="{{ $booking->id }}">


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
                     name="Invoice_no" value="{{ $booking->invoice_no }}" readonly style="font-size: 0.8rem;">
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">Manual Inv#</label>
              <input type="text" class="form-control form-control-sm py-0" 
                     name="Invoice_main" placeholder="Optional" value="{{ $booking->manual_invoice }}" style="font-size: 0.8rem;">
            </div>
          </div>

          {{-- Party Type Toggle --}}
          <div class="mb-2">
            <div class="btn-group w-100" role="group">
              @php $pType = $booking->party_type ?? 'customer'; @endphp
              <input type="radio" class="btn-check" name="partyType" id="typeCustomers" value="customer" {{ $pType == 'customer' ? 'checked' : '' }}>
              <label class="btn btn-outline-primary btn-sm py-0" for="typeCustomers" style="font-size: 0.75rem;">
                Customers
              </label>

              <input type="radio" class="btn-check" name="partyType" id="typeWalkin" value="walking" {{ $pType == 'walking' ? 'checked' : '' }}>
              <label class="btn btn-outline-primary btn-sm py-0" for="typeWalkin" style="font-size: 0.75rem;">
                Walk-in
              </label>

              <input type="radio" class="btn-check" name="partyType" id="typeVendors" value="vendor" {{ $pType == 'vendor' ? 'checked' : '' }}>
              <label class="btn btn-outline-primary btn-sm py-0" for="typeVendors" style="font-size: 0.75rem;">
                Vendors
              </label>
            </div>
          </div>

          {{-- Party Identification & Selection --}}
          <div class="row g-1 mb-2">
            <div class="col-4">
              <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">Party ID</label>
              <input type="text" class="form-control form-control-sm py-0 fw-bold text-danger" id="partyIdInput" placeholder="ID" style="font-size: 0.8rem;">
            </div>
            <div class="col-8">
              <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">Select Party</label>
              <select class="form-select form-select-sm py-0" name="customer" id="customerSelect" data-old-val="{{ $booking->customer_id }}" style="font-size: 0.8rem;">
                <option selected disabled>Loading…</option>
              </select>
            </div>
          </div>

          {{-- Address --}}
          <div class="mb-2">
            <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">Address</label>
            <textarea class="form-control form-control-sm py-1" id="address" name="address" rows="1" placeholder="Address" style="font-size: 0.75rem;">{{ $booking->address }}</textarea>
          </div>

          {{-- Tel & Balance --}}
          <div class="row g-1 mb-2">
            <div class="col-5">
              <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">Tel#</label>
              <input type="text" class="form-control form-control-sm py-0" id="tel" name="tel" placeholder="Phone" value="{{ $booking->tel }}" style="font-size: 0.8rem;">
            </div>
            <div class="col-7">
              <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">Current Balance</label>
              <input type="text" class="form-control form-control-sm text-end fw-bold py-0 input-readonly" id="previousBalance" 
                     name="previousBalance" value="{{ $booking->previous_balance }}" placeholder="0.00" readonly 
                     style="font-size: 1.1rem; color: #d63384; background: #fffcfd;">
            </div>
          </div>

          {{-- Remarks --}}
          <div class="mb-1">
            <label class="form-label text-muted small mb-0" style="font-size: 0.7rem;">Remarks</label>
            <textarea class="form-control form-control-sm py-1" id="remarks" name="remarks" rows="1" placeholder="Notes" style="font-size: 0.75rem;">{{ $booking->remarks }}</textarea>
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
                  <th style="width:20%" class="text-center">Discount (% | Amt)</th>
                  <th style="width:10%" class="text-end">Amount</th>
                  <th style="width:3%" class="text-center">—</th>
                </tr>
              </thead>
              <tbody id="salesTableBody">
                @foreach($booking->items as $item)
                    @php $rowId = 'row-' . $item->id; @endphp
                    <tr data-row-id="{{ $rowId }}">
                      <td style="width: 70px;">
                        <input type="text" class="form-control form-control-sm item-id-input text-center" placeholder="ID" value="{{ $item->product_id }}">
                      </td>
                      <td>
                        <select name="product_id[]" class="form-control form-control-sm product-select" style="width: 100%;">
                          <option value="{{ $item->product_id }}" selected>{{ $item->product->name ?? '' }}</option>
                        </select>
                        <input type="hidden" name="product_search[]" class="product_name_hidden" value="{{ $item->product->name ?? '' }}">
                      </td>
                      <td style="width: 120px;">
                        <select class="form-select form-select-sm warehouse" name="warehouse_name[]">
                            <option value="0" {{ $item->warehouse_id == 0 ? 'selected' : '' }}>🏠 Shop Stock</option>
                          @foreach ($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ $item->warehouse_id == $wh->id ? 'selected' : '' }}>📦 {{ $wh->warehouse_name }}</option>
                          @endforeach
                        </select>
                      </td>
                      <td style="width: 80px;"><input type="text" class="form-control form-control-sm stock text-center input-readonly" name="stock[]" value="{{ $item->stock }}" readonly></td>
                      <td style="width: 100px;"><input type="text" class="form-control form-control-sm text-end sales-price input-readonly" name="sales-price[]" value="{{ $item->sales_price }}" readonly></td>
                      <td style="width: 70px;"><input type="number" step="any" class="form-control form-control-sm text-center sales-qty" name="sales-qty[]" value="{{ $item->sales_qty }}"></td>
                      <td style="width: 100px;"><input type="text" class="form-control form-control-sm text-end retail-price input-readonly" name="retail-price[]" value="{{ $item->retail_price }}" readonly></td>
                      <td style="width:165px;">
                        <div class="input-group input-group-sm">
                          <input type="number" step="0.01" class="form-control text-end discount-value" placeholder="%" value="{{ $item->discount_percent }}" style="max-width: 65px;">
                          <span class="input-group-text px-1" style="font-size: 0.7rem;">%</span>
                          <input type="text" class="form-control text-end discount-amount-display input-readonly" value="{{ $item->discount_amount }}" readonly style="background: #f8f9fa;">
                        </div>
                        <input type="hidden" class="discount-mode" name="discount_mode[]" value="percent">
                        <input type="hidden" class="discount-percent" name="discount-percent[]" value="{{ $item->discount_percent }}">
                        <input type="hidden" class="discount-amount" name="discount-amount[]" value="{{ $item->discount_amount }}">
                      </td>
                      <td style="width: 100px;"><input type="text" class="form-control form-control-sm text-end sales-amount input-readonly" name="sales-amount[]" value="{{ $item->amount }}" readonly></td>
                      <td class="text-center" style="width: 40px;"><button type="button" class="btn btn-xs btn-outline-danger del-row">&times;</button></td>
                    </tr>
                @endforeach
              </tbody>
              <tfoot>
                <tr>
                  <td colspan="8" class="text-end fw-bold">Total:</td>
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
                @php
                    $rHeads = json_decode($booking->receipt_heads, true) ?? [];
                    $rAccounts = json_decode($booking->receipt_accounts, true) ?? [];
                    $rNarrations = json_decode($booking->receipt_narrations, true) ?? [];
                    $rAmounts = json_decode($booking->receipt_amounts_json, true) ?? [];
                    
                    // Fallback to receipt1 if JSON is empty but receipt1 has value
                    if (empty($rAmounts) && $booking->receipt1 > 0) {
                        $rAmounts = [$booking->receipt1];
                        $rHeads = [null];
                        $rAccounts = [null];
                        $rNarrations = [null];
                    }
                @endphp

                @if(count($rAmounts) > 0)
                    @foreach($rAmounts as $idx => $amt)
                    <div class="receipt-row bg-white border rounded-3 p-2 mb-2 shadow-sm rv-row">
                      <div class="row g-2 align-items-center">
                        <div class="col-md-3">
                          <label class="form-label text-muted small mb-1">Head</label>
                          <select class="form-select form-select-sm rv-head" name="receipt_head_id[]">
                            <option value="" disabled {{ !isset($rHeads[$idx]) ? 'selected' : '' }}>Select Head</option>
                            @foreach ($accountHeads as $head)
                              <option value="{{ $head->id }}" {{ (isset($rHeads[$idx]) && $rHeads[$idx] == $head->id) ? 'selected' : '' }}>{{ $head->name }}</option>
                            @endforeach
                          </select>
                        </div>
                        <div class="col-md-3">
                          <label class="form-label text-muted small mb-1">Account</label>
                          <select class="form-select form-select-sm rv-account" name="receipt_account_id[]" {{ isset($rAccounts[$idx]) ? '' : 'disabled' }}>
                            <option value="" disabled {{ !isset($rAccounts[$idx]) ? 'selected' : '' }}>Select account</option>
                            @if(isset($rHeads[$idx]))
                                @php $accounts = \App\Models\Account::where('head_id', $rHeads[$idx])->get(); @endphp
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}" {{ ($rAccounts[$idx] == $acc->id) ? 'selected' : '' }}>{{ $acc->title }}</option>
                                @endforeach
                            @endif
                          </select>
                        </div>
                        <div class="col-md-2">
                          <label class="form-label text-muted small mb-1">Amount</label>
                          <input type="text" class="form-control form-control-sm text-end fw-bold rv-amount" 
                                 name="receipt_amount[]" placeholder="0.00" value="{{ $amt }}">
                        </div>
                        <div class="col-md-3">
                          <label class="form-label text-muted small mb-1">Narration</label>
                          <select class="form-select form-select-sm rv-narration" name="receipt_narration[]">
                            <option value="">Select narration...</option>
                            @if(isset($rNarrations[$idx]))
                                <option value="{{ $rNarrations[$idx] }}" selected>{{ $rNarrations[$idx] }}</option>
                            @endif
                          </select>
                        </div>
                        <div class="col-md-1 text-center pt-3">
                            <button type="button" class="btn btn-sm btn-outline-danger border-0 del-rv-row">&times;</button>
                        </div>
                      </div>
                    </div>
                    @endforeach
                @else
                    {{-- Default empty row --}}
                    <div class="receipt-row bg-white border rounded-3 p-2 mb-2 shadow-sm rv-row">
                      <div class="row g-2 align-items-center">
                        <div class="col-md-3">
                          <label class="form-label text-muted small mb-1">Head</label>
                          <select class="form-select form-select-sm rv-head" name="receipt_head_id[]">
                            <option value="" disabled selected>Select Head</option>
                            @foreach ($accountHeads as $head)
                              <option value="{{ $head->id }}">{{ $head->name }}</option>
                            @endforeach
                          </select>
                        </div>
                        <div class="col-md-3">
                          <label class="form-label text-muted small mb-1">Account</label>
                          <select class="form-select form-select-sm rv-account" name="receipt_account_id[]" disabled>
                            <option value="" disabled selected>Select account</option>
                          </select>
                        </div>
                        <div class="col-md-2">
                          <label class="form-label text-muted small mb-1">Amount</label>
                          <input type="text" class="form-control form-control-sm text-end fw-bold rv-amount" name="receipt_amount[]" placeholder="0.00" disabled>
                        </div>
                        <div class="col-md-3">
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

              <!-- Retail Total -->
              <div class="d-flex justify-content-between py-2 border-bottom">
                <span class="text-muted small">Total Retail Price</span>
                <span class="fw-semibold" id="tRetail">0.00</span>
              </div>

              <!-- Sub-Total (Net) -->
              <div class="d-flex justify-content-between py-3 border-bottom bg-info bg-opacity-10 rounded px-2">
                <span class="fw-bold fs-6">Sub-Total (Net)</span>
                <span class="fw-bold fs-6 text-primary" id="tSub">0.00</span>
              </div>

              <!-- Order Discount Input -->
              <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <span class="text-muted small">Order Discount</span>
                <div class="d-flex align-items-center gap-1">
                  <input type="number" step="0.01" class="form-control form-control-sm text-end" 
                         id="orderDiscountValue" name="order_discount_value" 
                         value="{{ $booking->discount_amount }}" style="width:70px">
                  <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-primary order-disc-btn" data-mode="percent">%</button>
                    <button type="button" class="btn btn-outline-primary order-disc-btn active" data-mode="amount">₨</button>
                  </div>
                </div>
                  <input type="hidden" id="orderDiscountMode" name="order_discount_mode" value="amount">
                  <input type="hidden" id="discountPercent" name="discountPercent" value="{{ $booking->discount_percent }}">
                <input type="hidden" id="discountAmountHidden" value="{{ $booking->discount_amount }}">
              </div>

              <!-- Order Discount Rs -->
              <div class="d-flex justify-content-between py-2 border-bottom">
                <span class="text-muted small">Order Discount Rs</span>
                <span class="fw-semibold text-danger" id="tOrderDisc">0.00</span>
              </div>

              <!-- Current Invoice Total -->
              <div class="d-flex justify-content-between py-2 border-bottom bg-light">
                <span class="text-dark small fw-bold">Current Invoice</span>
                <span class="fw-bold" id="tCurrentInvoice">0.00</span>
              </div>

              <!-- Previous Balance -->
              <div class="d-flex justify-content-between py-2 border-bottom">
                <span class="text-warning small fw-semibold">Previous Balance</span>
                <span class="fw-semibold text-warning" id="tPrev">0.00</span>
              </div>

              <!-- Total Receipts -->
              <div class="d-flex justify-content-between py-2 border-bottom">
                <span class="text-success small fw-semibold">Less: Receipts</span>
                <span class="fw-semibold text-success" id="tReceiptsMirror">0.00</span>
              </div>

              <!-- Balance After Receipt -->
              <div class="d-flex justify-content-between py-2 border-bottom bg-light">
                <span class="text-muted small">Balance After Receipt</span>
                <span class="fw-semibold" id="tBalAfterReceipt">0.00</span>
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
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  /* ---------- helpers ---------- */
  function pad(n) { return n < 10 ? '0' + n : n }

  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': '{{ csrf_token() }}'
    }
  });

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
  }

  /* ---------- Select2 Product Initialization ---------- */
  function initProductSelect($row) {
    const $select = $row.find('.product-select');
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
  function addNewRow(focusNewRow = true) {
    const wh = $('#salesTableBody tr:last .warehouse').val() || 0;
    const template = `
    <tr>
      <td style="width: 70px;"><input type="text" class="form-control form-control-sm item-id-input text-center" placeholder="ID"></td>
      <td><select name="product_id[]" class="form-control form-control-sm product-select" style="width: 100%;"><option value=""></option></select><input type="hidden" name="product_search[]" class="product_name_hidden"></td>
      <td style="width: 120px;"><select class="form-select form-select-sm warehouse" name="warehouse_name[]">
          <option value="0" ${wh==0?'selected':''}>🏠 Shop Stock</option>
          @foreach ($warehouses as $wh)<option value="{{ $wh->id }}" ${wh=={{$wh->id}}?'selected':''}>📦 {{ $wh->warehouse_name }}</option>@endforeach
      </select></td>
      <td style="width: 80px;"><input type="text" class="form-control form-control-sm stock text-center input-readonly" name="stock[]" readonly></td>
      <td style="width: 100px;"><input type="text" class="form-control form-control-sm text-end sales-price input-readonly" name="sales-price[]" value="0" readonly></td>
      <td style="width: 70px;"><input type="number" step="any" class="form-control form-control-sm text-center sales-qty" name="sales-qty[]"></td>
      <td style="width: 100px;"><input type="text" class="form-control form-control-sm text-end retail-price input-readonly" name="retail-price[]" value="0" readonly></td>
      <td style="width: 165px;"><div class="input-group input-group-sm">
          <input type="number" step="0.01" class="form-control text-end discount-value" value="0"><span class="input-group-text px-1">%</span>
          <input type="text" class="form-control text-end discount-amount-display input-readonly" value="0" readonly style="background: #f8f9fa;">
        </div>
        <input type="hidden" class="discount-percent" name="discount-percent[]" value="0">
        <input type="hidden" class="discount-amount" name="discount-amount[]" value="0">
      </td>
      <td style="width: 100px;"><input type="text" class="form-control form-control-sm text-end sales-amount input-readonly" name="sales-amount[]" value="0" readonly></td>
      <td class="text-center" style="width: 40px;"><button type="button" class="btn btn-xs btn-outline-danger del-row">&times;</button></td>
    </tr>`;
    const $nr = $(template); $('#salesTableBody').append($nr); initProductSelect($nr);
    if(focusNewRow) $nr.find('.item-id-input').focus();
  }

  function loadNarrationsInto($select) {
    if ($select.hasClass('select2-hidden-accessible')) $select.select2('destroy');
    const existingVal = $select.val();
    $select.prop('disabled', true).empty().append('<option value="">Loading...</option>');
    $.get('{{ route("narrations.receipts") }}', function(data) {
      $select.empty().append('<option value="">Select narration...</option>');
      (data || []).forEach(n => {
          const text = n.narration_text || n.narration;
          $select.append(new Option(text, text));
      });
      if (existingVal && !$select.find('option[value="'+existingVal+'"]').length) {
          $select.append(new Option(existingVal, existingVal, true, true));
      } else if (existingVal) {
          $select.val(existingVal);
      }
      $select.prop('disabled', false).select2({ tags: true, width: '100%', dropdownParent: $select.parent() });
    });
  }

  /* ---------- Totals ---------- */
  function recomputeReceipts() {
    let t = 0;
    $('.rv-amount').each(function() { t += toNum($(this).val()); });
    $('#receiptsTotal').text(t.toFixed(2));
    return t;
  }

  function updateGrandTotals() {
    let tQty = 0, tSub = 0, tRetail = 0;
    $('#salesTableBody tr').each(function() {
      const $r = $(this), q = toNum($r.find('.sales-qty').val());
      const net = toNum($r.find('.sales-amount').val());
      tQty += q; tSub += net; tRetail += (toNum($r.find('.retail-price').val()) * q);
    });

    const orderMode = $('#orderDiscountMode').val(), orderVal = toNum($('#orderDiscountValue').val());
    let orderDisc = (orderMode === 'percent') ? (tRetail * orderVal / 100) : orderVal;

    const tRV = recomputeReceipts();
    const prev = toNum($('#previousBalance').val());
    const payable = (tSub - orderDisc) + (prev - tRV);

    $('#tQty').text(tQty); $('#tRetail').text(tRetail.toFixed(2)); $('#tSub').text(tSub.toFixed(2));
    $('#tOrderDisc').text(orderDisc.toFixed(2)); $('#tCurrentInvoice').text((tSub - orderDisc).toFixed(2));
    $('#tPrev').text(prev.toFixed(2)); $('#tReceiptsMirror').text(tRV.toFixed(2));
    $('#tBalAfterReceipt').text((prev - tRV).toFixed(2)); $('#tPayable').text(payable.toFixed(2));
    $('#totalAmount').text(tSub.toFixed(2));
    
    $('#subTotal1').val(tRetail.toFixed(2)); $('#subTotal2').val(tSub.toFixed(2));
    $('#discountAmount').val(orderDisc.toFixed(2)); $('#totalBalance').val(payable.toFixed(2));
  }

  $(document).on('change', '.rv-head', function() {
    const headId = $(this).val();
    const $row = $(this).closest('.rv-row');
    const $accSelect = $row.find('.rv-account');
    
    $accSelect.prop('disabled', true).empty().append('<option disabled selected>Loading...</option>');
    
    $.get('{{ url("get-accounts-by-head") }}/' + headId).done(function(data) {
        $accSelect.empty().append('<option disabled selected>Select account</option>');
        data.forEach(acc => $accSelect.append(new Option(acc.title, acc.id)));
        $accSelect.prop('disabled', false).focus();
    });
  });

  $(document).on('input', '.rv-amount', updateGrandTotals);

  $(document).on('click', '#btnAddRV', function() {
    const template = `
    <div class="receipt-row bg-white border rounded-3 p-2 mb-2 shadow-sm rv-row">
      <div class="row g-2 align-items-center">
        <div class="col-md-3">
          <label class="form-label text-muted small mb-1">Head</label>
          <select class="form-select form-select-sm rv-head" name="receipt_head_id[]">
            <option value="" disabled selected>Select Head</option>
            @foreach ($accountHeads as $head)<option value="{{ $head->id }}">{{ $head->name }}</option>@endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label text-muted small mb-1">Account</label>
          <select class="form-select form-select-sm rv-account" name="receipt_account_id[]" disabled><option value="" disabled selected>Select account</option></select>
        </div>
        <div class="col-md-2">
          <label class="form-label text-muted small mb-1">Amount</label>
          <input type="text" class="form-control form-control-sm text-end fw-bold rv-amount" name="receipt_amount[]" placeholder="0.00">
        </div>
        <div class="col-md-3">
          <label class="form-label text-muted small mb-1">Narration</label>
          <select class="form-select form-select-sm rv-narration" name="receipt_narration[]"><option value="">Select narration...</option></select>
        </div>
        <div class="col-md-1 text-center pt-3"><button type="button" class="btn btn-sm btn-outline-danger border-0 del-rv-row">&times;</button></div>
      </div>
    </div>`;
    const $nr = $(template); $('#rvWrapper').append($nr); loadNarrationsInto($nr.find('.rv-narration'));
  });

  $(document).on('click', '.del-rv-row', function() { $(this).closest('.rv-row').remove(); updateGrandTotals(); });

  function computeRow($row) {
    const sp = toNum($row.find('.sales-price').val()), rp = toNum($row.find('.retail-price').val());
    const qty = toNum($row.find('.sales-qty').val()), val = toNum($row.find('.discount-value').val());
    const discAmt = (rp * qty * val) / 100;
    const net = (sp * qty) - discAmt;
    $row.find('.discount-percent').val(val.toFixed(2)); $row.find('.discount-amount').val(discAmt.toFixed(2));
    $row.find('.discount-amount-display').val(discAmt.toFixed(2)); $row.find('.sales-amount').val(net.toFixed(2));
  }

  function toNum(v) { return parseFloat(v || 0) || 0; }

  // Events
  $(document).on('click', '#btnAdd', () => addNewRow());
  $(document).on('click', '.del-row', function() { $(this).closest('tr').remove(); updateGrandTotals(); });
  $(document).on('input', '.sales-qty, .discount-value', function() { computeRow($(this).closest('tr')); updateGrandTotals(); });
  $(document).on('input', '#orderDiscountValue', updateGrandTotals);
  $(document).on('click', '.order-disc-btn', function() { $('.order-disc-btn').removeClass('active'); $(this).addClass('active'); $('#orderDiscountMode').val($(this).data('mode')); updateGrandTotals(); });

  // ID search
  $(document).on('keydown', '.item-id-input', function(e) {
    if(e.key === 'Enter' || e.key === 'Tab') {
      const $input = $(this);
      const id = $input.val().trim();
      const $row = $input.closest('tr');
      const $select = $row.find('.product-select');
      
      if (!id) {
          if (e.key === 'Enter') e.preventDefault();
          return;
      }

      if ($select.val() === String(id)) {
          if ($row.is(':last-child')) addNewRow(false);
          setTimeout(() => $row.find('.sales-qty').focus(), 50);
          e.preventDefault();
          return;
      }

      e.preventDefault();
      $input.addClass('loading-indicator');
      
      $.get('{{ route("search-products") }}', { 
          q: id, 
          warehouse_id: $row.find('.warehouse').val() 
      }).done(function(res) {
        $input.removeClass('loading-indicator');
        if (res && res.length > 0) {
            // Precise matching prioritize: Exact ID -> Exact Name (Case Insensitive) -> First Result
            let item = res.find(i => String(i.id) === String(id)) 
                      || res.find(i => i.name.toLowerCase() === id.toLowerCase());
            
            if (!item && res.length === 1) {
                item = res[0];
            }

            if(item) { 
                updateRowWithProductData($row, {
                    id: item.id,
                    name: item.name,
                    text: item.name,
                    stock: item.stock,
                    sale_price: item.sale_price,
                    retail_price: item.retail_price
                }); 
                if($row.is(':last-child')) {
                    addNewRow(false);
                }
                setTimeout(() => $row.find('.sales-qty').focus(), 50);
            } else {
                Swal.fire({ icon: 'error', title: 'Not Found', text: 'Product ID ' + id + ' not found.', timer: 2000, showConfirmButton: false });
                $input.select().focus();
            }
        } else {
            Swal.fire({ icon: 'error', title: 'Not Found', text: 'Product ID ' + id + ' not found.', timer: 2000, showConfirmButton: false });
            $input.select().focus();
        }
      }).fail(function() {
        $input.removeClass('loading-indicator');
        Swal.fire({ icon: 'error', title: 'Error', text: 'Server error while fetching product.' });
        $input.select().focus();
      });
    }
  });

  // Enter on discount adds row
  $(document).on('keydown', '.discount-value', function(e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      const $current = $(this).closest('tr');
      computeRow($current);
      updateGrandTotals();
      addNewRow();
      const $newRow = $('#salesTableBody tr:last-child');
      setTimeout(() => $newRow.find('.item-id-input').focus(), 100);
    }
  });

  // Sync warehouse across rows
  $(document).on('change', '.warehouse', function() {
      const selectedWH = $(this).val();
      $('.warehouse').not(this).val(selectedWH);
      $('#salesTableBody tr').each(function() {
          const $r = $(this), productId = $r.find('.product-select').val();
          if (!productId) return;
          const $stock = $r.find('.stock');
          $stock.addClass('loading-indicator');
          $.get('{{ route("search-products") }}', { q: productId, warehouse_id: selectedWH }).done(res => {
              $stock.removeClass('loading-indicator');
              if (res && res.length > 0) {
                  const item = res.find(i => String(i.id) === String(productId));
                  if (item) $stock.val(item.stock || 0);
              }
          }).fail(() => $stock.removeClass('loading-indicator'));
      });
  });

  // Party ID Lookup
  $('#partyIdInput').on('keydown', function(e) {
      if (e.key === 'Tab' || e.key === 'Enter') {
          const pid = $(this).val().trim();
          if (!pid) return;

          let foundId = null;
          $('#customerSelect option').each(function() {
              const text = $(this).text();
              const val = $(this).val();
              if (text.startsWith(pid + ' -') || val === pid) {
                  foundId = val;
                  return false;
              }
          });

          if (foundId) {
              if ($('#customerSelect').val() !== foundId) {
                  $('#customerSelect').val(foundId).trigger('change');
              }
              e.preventDefault();
              setTimeout(() => $('#salesTableBody tr:first-child .item-id-input').focus(), 100);
          } else {
              $(this).addClass('is-invalid');
              setTimeout(() => $(this).removeClass('is-invalid'), 1000);
          }
      }
  });

  // Customer logic
  function loadCustomers(type) {
    $.get('{{ route("customers.filter") }}', { type }).done(list => {
      const $s = $('#customerSelect').empty().append('<option disabled selected>Select...</option>');
      list.forEach(i => $s.append(new Option(i.text, i.id)));
      const old = $s.data('old-val'); if(old) $s.val(old).trigger('change');
      $s.select2();
    });
  }

  $(document).on('change', 'input[name="partyType"]', function() { loadCustomers(this.value); });

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

    const selectedText = $("#customerSelect option:selected").text();
    const parts = selectedText.split(' - ');
    if (parts.length > 1) {
        $('#partyIdInput').val(parts[0]);
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

  // Save/Post
  function ajaxSave() {
    $('#saveDraftBtn').prop('disabled', true).text('Saving...');
    $.post('{{ route("sale.ajax.save") }}', $('#saleForm').serialize()).done(res => {
      if(res.ok) { Swal.fire('Saved', 'Sale saved successfully!', 'success'); $('#saleForm').addClass('form-locked'); $('#editBtn').show(); }
    }).always(() => $('#saveDraftBtn').prop('disabled', false).html('<i class="fa fa-floppy-o me-1"></i> Save Draft'));
  }

  $('#saveDraftBtn').click(ajaxSave);
  $('#editBtn').click(function() { $('#saleForm').removeClass('form-locked'); $(this).hide(); });
  $('#postBtn').click(function() {
      const bid = $('#booking_id').val();
      $('#postBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Posting...');
      $.post('{{ route("sale.ajax.post") }}', { booking_id: bid }).done(res => {
          if(res.ok) {
              Swal.fire({
                  icon: 'success',
                  title: 'Posted!',
                  text: 'Sale posted successfully. Redirecting...',
                  timer: 2000,
                  showConfirmButton: false
              }).then(() => {
                  window.location.href = '{{ route("sale.index") }}';
              });
          } else {
              Swal.fire('Error', res.error || 'Post failed', 'error');
              $('#postBtn').prop('disabled', false).html('<i class="fa fa-check pointer me-1"></i> Post');
          }
      }).fail(() => {
          Swal.fire('Error', 'Post request failed.', 'error');
          $('#postBtn').prop('disabled', false).html('<i class="fa fa-check pointer me-1"></i> Post');
      });
  });

  $(function() {
    loadCustomers($('input[name="partyType"]:checked').val());
    $('#salesTableBody tr').each(function() { initProductSelect($(this)); computeRow($(this)); });
    loadNarrationsInto($('.rv-narration'));
    updateGrandTotals();
    // Initially lock because it's an existing record
    $('#saleForm').addClass('form-locked'); $('#editBtn').show();
  });

  $(document).on('keydown', function(e) {
    if (e.ctrlKey && (e.key === 's' || e.key === 'S')) { e.preventDefault(); e.stopPropagation(); ajaxSave(); }
    if (e.ctrlKey && (e.key === 'e' || e.key === 'E')) { e.preventDefault(); e.stopPropagation(); $('#editBtn').click(); }
    if (e.ctrlKey && e.key === 'Enter') { e.preventDefault(); e.stopPropagation(); $('#postBtn').click(); }
    if (e.key === 'Escape') window.location.href = '{{ route("sale.index") }}';
  });
</script>
@endsection
