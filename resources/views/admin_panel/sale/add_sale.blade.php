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
    background: #f8f9fa;
    text-align: center;
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
</style>

<div class="container-fluid py-4">
  <div class="main-container bg-white border shadow-sm mx-auto p-3 rounded-3">

    <div id="alertBox" class="alert d-none mb-3" role="alert"></div>

    <form id="saleForm" autocomplete="off">
      @csrf
      <input type="hidden" id="booking_id" name="booking_id" value="">
      <input type="hidden" id="action" name="action" value="save">

      {{-- HEADER --}}
      <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
        <div>
          <small class="text-secondary" id="entryDateTime">Entry Date_Time: --</small> <br>
          <a href="{{ route('sale.index') }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary" title="Sales List (opens new tab)">
            Sales List
          </a>
        </div>


        <h2 class="header-text text-secondary fw-bold mb-0">Sales</h2>


        <div class="d-flex align-items-center gap-2">
          <small class="text-secondary me-2" id="entryDate">Date: --</small>
          <button type="button" class="btn btn-sm btn-light border" id="btnHeaderPosted" disabled>Posted</button>
        </div>
      </div>

      <div class="d-flex gap-3 align-items-start border-bottom py-3">
        {{-- LEFT: Invoice & Customer --}}
        <div class="p-3 border rounded-3 minw-350">
          <div class="section-title mb-3">Invoice & Customer</div>

          <div class="mb-2 d-flex align-items-center gap-2">
            <label class="form-label fw-bold mb-0">Invoice No.</label>
            <input type="text" class="form-control input-readonly" name="Invoice_no" style="width:150px" value="{{ $nextInvoiceNumber }}" readonly>
            <label class="form-label fw-bold mb-0">M. Inv#</label>
            <input type="text" class="form-control" name="Invoice_main" placeholder="Manual invoice">
          </div>

          {{-- Type toggle --}}
          <div class="mb-2">
            <label class="form-label fw-bold mb-1 d-block">Type</label>
            <div class="btn-group" role="group" aria-label="Customer type" id="partyTypeGroup">
              <input type="radio" class="btn-check" name="partyType" id="typeCustomers" value="customer" checked>
              <label class="btn btn-outline-primary btn-sm" for="typeCustomers">Customers</label>

              <input type="radio" class="btn-check" name="partyType" id="typeWalkin" value="walking">
              <label class="btn btn-outline-primary btn-sm" for="typeWalkin">Walk-in</label>

              <input type="radio" class="btn-check" name="partyType" id="typeVendors" value="vendor">
              <label class="btn btn-outline-primary btn-sm" for="typeVendors">Vendors</label>
            </div>
          </div>

          <div class="mb-2">
            <label class="form-label fw-bold mb-1">Select Customer</label>
            <select class="form-select js-customer" name="customer" id="customerSelect">
              <option selected disabled>Loading…</option>
            </select>
            <small class="text-muted d-block mt-1" id="customerCountHint"></small>
          </div>

          <div class="mb-2">
            <label class="form-label fw-bold mb-1">Address</label>
            <textarea class="form-control" id="address" name="address" rows="2"></textarea>
          </div>

          <div class="d-flex align-items-center mb-2 gap-2">
            <label class="form-label fw-bold mb-0">Tel#</label>
            <input type="text" class="form-control" id="tel" name="tel">
          </div>

          <div class="mb-2">
            <label class="form-label fw-bold mb-1">Remarks</label>
            <textarea class="form-control" id="remarks" name="remarks" rows="2"></textarea>
          </div>

          <div class="d-flex justify-content-between mt-2">
            <span class="badge rounded-pill badge-soft">Prev Balance</span>
            <input type="text" class="form-control text-end w-150" id="previousBalance" name="previousBalance" value="0">
          </div>

          <div class="text-end mt-3">
            <button id="clearCustomerData" type="button" class="btn btn-sm btn-secondary">Clear</button>
          </div>
        </div>

        {{-- RIGHT: Items --}}
        <div class="flex-grow-1">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="section-title mb-0">Items</div>
            <button type="button" class="btn btn-sm btn-primary" id="btnAdd">Add Row</button>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered mb-0" style="min-width:1000px">
              <thead>
                <tr>
                  <th style="width:10px">Warehouse</th>
                  <th style="width:10px">Product</th>
                  <th style="width:10px">Stock</th>
                  <th style="width:10px">Sales Price</th>
                  <th style="width:10px">Qty</th>
                  <th style="width:10px">Retail Price</th>
                  <th style="width:10px">Disc %</th>
                  <th style="width:10px">Disc Amt</th>
                  <th style="width:10px">Amount</th>
                  <th style="width:10px">—</th>
                </tr>
              </thead>
              <tbody id="salesTableBody">

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

      {{-- Totals + Receipts --}}
      <div class="row g-3 mt-3">
        <div class="col-lg-7">
          <div class="section-title mb-2">Receipt Vouchers</div>
          <div id="rvWrapper" class="border rounded-3 p-2">
            <div class="d-flex gap-2 align-items-center mb-2 rv-row">
              <select class="form-select rv-account" name="receipt_account_id[]" style="max-width: 320px">
                @foreach ($accounts as $acc)
                <option value="" disabled>Select account</option>
                <option value="{{ $acc->id }}">{{ $acc->title }}</option>
                @endforeach
              </select>
              <input type="text" class="form-control text-end rv-amount" name="receipt_amount[]" placeholder="0.00" style="max-width:160px">
              <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddRV">Add more</button>
            </div>
            <div class="text-end">
              <span class="me-2">Receipts Total:</span>
              <span class="fw-bold" id="receiptsTotal">0.00</span>
            </div>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="section-title mb-2">Totals</div>
          <div class="totals-card p-3">
            <div class="row py-1">
              <div class="col-7 text-muted">Total Qty</div>
              <div class="col-5 text-end"><span id="tQty">0</span></div>
            </div>
            <div class="row py-1">
              <div class="col-7 text-muted">Invoice Gross (Σ Sales Price × Qty)</div>
              <div class="col-5 text-end"><span id="tGross">0.00</span></div>
            </div>
            <div class="row py-1">
              <div class="col-7 text-muted">Line Discount (on Retail)</div>
              <div class="col-5 text-end"><span id="tLineDisc">0.00</span></div>
            </div>
            <div class="row py-1">
              <div class="col-7 fw-semibold">Sub-Total</div>
              <div class="col-5 text-end fw-semibold"><span id="tSub">0.00</span></div>
            </div>
            <div class="row py-1">
              <div class="col-7">Order Discount %</div>
              <div class="col-5 text-end">
                <input type="text" class="form-control text-end" name="discountPercent" id="discountPercent" value="0" style="max-width:120px; margin-left:auto">
              </div>
            </div>
            <div class="row py-1">
              <div class="col-7 text-muted">Order Discount Rs</div>
              <div class="col-5 text-end"><span id="tOrderDisc">0.00</span></div>
            </div>
            <div class="row py-1">
              <div class="col-7 text-danger">Previous Balance</div>
              <div class="col-5 text-end text-danger"><span id="tPrev">0.00</span></div>
            </div>
            <div class="row py-2">
              <div class="col-7 fw-bold text-primary">Payable / Total Balance</div>
              <div class="col-5 text-end fw-bold text-primary"><span id="tPayable">0.00</span></div>
            </div>

            {{-- hidden mirrors for backend --}}
            <input type="hidden" name="subTotal1" id="subTotal1" value="0">
            <input type="hidden" name="subTotal2" id="subTotal2" value="0">
            <input type="hidden" name="discountAmount" id="discountAmount" value="0">
            <input type="hidden" name="totalBalance" id="totalBalance" value="0">
          </div>
        </div>
      </div>

      {{-- Buttons --}}
      <div class="d-flex flex-wrap gap-2 justify-content-center p-3 mt-3 border-top">
        <button type="button" class="btn btn-sm btn-primary" id="btnEdit">Edit</button>
        <button type="button" class="btn btn-sm btn-warning" id="btnRevert">Revert</button>

        <button type="button" class="btn btn-sm btn-success" id="btnSave">Save</button>
        <button type="button" class="btn btn-sm btn-outline-success" id="btnPosted" disabled>Posted</button>

        <button type="button" class="btn btn-sm btn-secondary" id="btnPrint">Print</button>
        <button type="button" class="btn btn-sm btn-secondary" id="btnPrint2">Print-2</button>
        <button type="button" class="btn btn-sm btn-secondary" id="btnDCPrint">DC Print</button>

        <button type="button" class="btn btn-sm btn-danger" id="btnDelete">Delete</button>
        <button type="button" class="btn btn-sm btn-dark" id="btnExit">Exit</button>
      </div>
    </form>
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

  function addNewRow() {
    // if there's already rows, require last row to be meaningful
    const $last = $('#salesTableBody tr:last-child');
    if ($last.length) {
      if (!isRowMeaningful($last)) {
        // focus last row product for user to fill instead of creating new
        $last.find('.product').focus();
        showAlert('danger', 'Please complete the current row before adding a new one.');
        return;
      }
    }

    // original append content (keep identical to your template)
    $('#salesTableBody').append(`
    <tr>
      <td>
        <select class="form-select warehouse" name="warehouse_name[]">
          <option value="">Select</option>
          @foreach ($warehouses as $wh)
            <option value="{{ $wh->id }}">{{ $wh->warehouse_name }}</option>
          @endforeach
        </select>
      </td>
      <td><select class="form-select product" name="product_name[]"><option value="">Select Product</option></select></td>
      <td><input type="text" class="form-control stock text-center input-readonly" name="stock[]" readonly></td>
      <td><input type="text" class="form-control text-end sales-price input-readonly" name="sales-price[]" value="0" readonly></td>
      <td><input type="text" class="form-control text-end sales-qty"   name="sales-qty[]"   value=""></td>
      <td><input type="text" class="form-control text-end retail-price input-readonly" name="retail-price[]" value="0" readonly></td>
      <td><input type="text" class="form-control text-end discount-percent" name="discount-percent[]" value=""></td>
      <td><input type="text" class="form-control text-end discount-amount"  name="discount-amount[]"  value=""></td>
      <td><input type="text" class="form-control text-end sales-amount input-readonly" name="sales-amount[]" value="0" readonly></td>
      <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger del-row">&times;</button></td>
    </tr>
  `);
    refreshPostedState();
  }



  function canPost() {
    let ok = false;
    $('#salesTableBody tr').each(function() {
      const pid = $(this).find('.product').val();
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
  $('#btnSave').on('click', function() {
    ensureSaved();
  });
  $('#btnPrint').on('click', function() {
    ensureSaved().then(id => window.open('{{ url("booking/print") }}/' + id, '_blank'));
  });
  $('#btnPrint2').on('click', function() {
    ensureSaved().then(id => window.open('{{ url("booking/print2") }}/' + id, '_blank'));
  });
  $('#btnDCPrint').on('click', function() {
    ensureSaved().then(id => window.open('{{ url("booking/dc") }}/' + id, '_blank'));
  });
  $('#btnExit').on('click', function() {
    ensureSaved().finally(() => {
      window.location.href = "{{ route('sale.index') }}";
    });
  });
  $('#btnHeaderPosted, #btnPosted').on('click', function() {
    if (!canPost()) return;
    ensureSaved().then(postNow);
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

  /* ---------- Warehouse -> products ---------- */
  $(document).on('change', '.warehouse', function() {
    var wid = $(this).val();
    var $row = $(this).closest('tr');
    var $product = $row.find('.product');
    var $stock = $row.find('.stock');
    var $sp = $row.find('.sales-price');
    var $rp = $row.find('.retail-price');

    $product.prop('disabled', true).empty().append('<option value="">Loading...</option>');
    $stock.val('');
    $sp.val('0');
    $rp.val('0');

    if (!wid) {
      $product.empty().append('<option value="">Select Product</option>').prop('disabled', false);
      return;
    }

    $.get('{{ url("get-products-by-warehouse") }}/' + wid, function(list) {
      $product.empty().append('<option value="">Select Product</option>');
      (list || []).forEach(p => $product.append('<option value="' + p.id + '">' + p.name + '</option>'));
      $product.prop('disabled', false);
    }).fail(function() {
      $product.empty().append('<option value="">Error loading</option>').prop('disabled', false);
    });
  });

  /* ---------- Product -> stock + prices ---------- */
  $(document).on('change', '.product', function() {
    var $row = $(this).closest('tr');
    var pid = $(this).val();
    var $stock = $row.find('.stock');
    var $sp = $row.find('.sales-price');
    var $rp = $row.find('.retail-price');
    if (!pid) {
      $stock.val('');
      $sp.val('0');
      $rp.val('0');
      refreshPostedState();
      return;
    }

    $.get('{{ url("get-stock") }}/' + pid, function(d) {
      $stock.val((d.stock ?? 0));
      $sp.val((+d.sales_price || 0).toFixed(2));
      $rp.val((+d.retail_price || 0).toFixed(2));
      computeRow($row);
      updateGrandTotals();
      refreshPostedState();
    }).fail(function() {
      $stock.val('');
      $sp.val('0');
      $rp.val('0');
      computeRow($row);
      updateGrandTotals();
      refreshPostedState();
    });
  });

  /* ---------- Row compute ---------- */
  function toNum(v) {
    return parseFloat(v || 0) || 0;
  }

  function computeRow($row, preferManualDiscAmt = false) {
    const sp = toNum($row.find('.sales-price').val()); // sale_net_amount
    const rp = toNum($row.find('.retail-price').val()); // sale_retail_price
    const qty = toNum($row.find('.sales-qty').val());
    const pct = toNum($row.find('.discount-percent').val());
    let dam = toNum($row.find('.discount-amount').val());

    const gross = sp * qty;
    if (!preferManualDiscAmt) { // default: % drives amount
      dam = ((rp * qty) * pct) / 100.0;
      $row.find('.discount-amount').val(dam.toFixed(2));
    }
    const net = Math.max(0, gross - dam);
    $row.find('.sales-amount').val(net.toFixed(2));
  }

  $(document).on('input', '.sales-price, .sales-qty, .discount-percent', function() {
    const $row = $(this).closest('tr');
    computeRow($row, false); // % drives amount
    updateGrandTotals();
    refreshPostedState();
  });
  $(document).on('input', '.discount-amount', function() {
    const $row = $(this).closest('tr');
    computeRow($row, true); // manual amount respected
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
      const rp = toNum($r.find('.retail-price').val());
      const qty = toNum($r.find('.sales-qty').val());
      const pct = toNum($r.find('.discount-percent').val());
      const dam = toNum($r.find('.discount-amount').val());

      const gross = sp * qty;
      const lineDisc = (pct > 0 && dam == 0) ? ((rp * qty) * pct / 100.0) : dam; // safeguard
      const net = Math.max(0, gross - lineDisc);

      tQty += qty;
      tGross += gross;
      tLineDisc += lineDisc;
      tNet += net;
    });

    // Order discount
    const orderPct = toNum($('#discountPercent').val());
    const orderDisc = (tGross * orderPct) / 100.0;

    const prev = toNum($('#previousBalance').val());
    const receipts = toNum($('#receiptsTotal').text());

    const subTotal = Math.max(0, tGross - tLineDisc);
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
  $(document).on('input', '#previousBalance, #discountPercent', updateGrandTotals);

  /* ---------- Row auto-add ---------- */
  $('#salesTableBody').on('input', '.sales-qty', function() {
    const $row = $(this).closest('tr');
    computeRow($row);
    updateGrandTotals();
    refreshPostedState();
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
        setTimeout(() => $newRow.find('.warehouse').focus(), 0);
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
    $('#rvWrapper .rv-row:last').after(`
    <div class="d-flex gap-2 align-items-center mb-2 rv-row">
      <select class="form-select rv-account" name="receipt_account_id[]" style="max-width:320px">
        <option value="">Select account</option>
      </select>
      <input type="text" class="form-control text-end rv-amount" name="receipt_amount[]" placeholder="0.00" style="max-width:160px">
      <button type="button" class="btn btn-outline-danger btn-sm btnRemRV">&times;</button>
    </div>
  `);

    // Load accounts into the newly added row
    loadAccountsInto($('#rvWrapper .rv-row:last .rv-account'));
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
      const $prod = $row.find('.product');
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
    const prod = $row.find('.product').val();
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
      const prod = $r.find('.product').val();
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
</script>
@endsection