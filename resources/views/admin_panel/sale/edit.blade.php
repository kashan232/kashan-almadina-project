@extends('admin_panel.layout.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
  .main-container {
    font-size: .85rem;
    max-width: 1400px;
  }
  .form-control, .form-select, .btn {
    font-size: .85rem;
    padding: .4rem .6rem;
    height: auto;
  }
  .input-readonly { background: #f9fbff; }
  .form-locked input, .form-locked select, .form-locked textarea, .form-locked button {
    pointer-events: none !important; opacity: 0.65 !important;
  }
  .form-locked input:not([type="hidden"]), .form-locked select, .form-locked textarea {
    background-color: #e9ecef !important;
  }
  .table-responsive { max-height: 360px; overflow: auto; border: 1px solid #eee; border-radius: .5rem; }
  .totals-card { background: #fcfcfe; border: 1px solid #eee; border-radius: .5rem; }
  .loading-indicator { background-color: #fff9c4 !important; }
</style>

<div class="container-fluid py-4">
  <div class="main-container bg-white border shadow-sm mx-auto p-2 rounded-3" style="max-width: 98%;">
    <div class="d-flex justify-content-between align-items-center mb-3 bg-light p-2 rounded shadow-sm">
      <div style="min-width:80px;"></div>
      <div class="d-flex align-items-center gap-2 justify-content-center flex-grow-1">
          <h6 class="mb-0 fw-bold">Edit Posted Sale</h6>
          <span class="badge bg-success px-3 py-2 rounded-pill shadow-sm"><i class="fa fa-check"></i> Posted</span>
          <span class="badge bg-primary px-3 py-2 rounded-pill shadow-sm"><i class="fa fa-tag"></i> ID: {{ $sale->id }}</span>
      </div>
      <a href="{{ route('sale.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i class="fa fa-list"></i> List</a>
    </div>

    <form id="saleForm" action="{{ route('sale.update', $sale->id) }}" method="POST">
      @csrf
      <div class="d-flex gap-3 align-items-start border-bottom py-3">
        <div class="bg-light border rounded-3 p-2 shadow-sm" style="min-width: 300px; max-width: 300px;">
          <h6 class="fw-bold text-primary mb-2 border-bottom pb-1">Invoice & Customer</h6>
          <div class="row g-1 mb-2">
            <div class="col-6">
              <label class="small text-muted mb-0">Inv#</label>
              <input type="text" class="form-control form-control-sm bg-white border-0 fw-bold" value="{{ $sale->invoice_no }}" readonly>
            </div>
            <div class="col-6">
              <label class="small text-muted mb-0">Manual#</label>
              <input type="text" class="form-control form-control-sm" name="Invoice_main" value="{{ $sale->manual_invoice }}">
            </div>
          </div>
          <div class="mb-2">
             <select class="form-select form-select-sm" name="customer" id="customerSelect" data-old-val="{{ $sale->customer_id }}">
                <option selected disabled>Loading...</option>
             </select>
          </div>
          <div class="mb-2">
            <label class="small text-muted mb-0">Address</label>
            <textarea class="form-control form-control-sm" name="address" rows="1">{{ $sale->address }}</textarea>
          </div>
          <div class="row g-1 mb-2">
            <div class="col-6"><label class="small text-muted mb-0">Tel#</label><input type="text" class="form-control form-control-sm" name="tel" value="{{ $sale->tel }}"></div>
            <div class="col-6"><label class="small text-muted mb-0">Balance</label><input type="text" id="previousBalance" class="form-control form-control-sm text-end fw-bold input-readonly" value="{{ $sale->previous_balance }}" readonly></div>
          </div>
        </div>

        <div class="flex-grow-1">
          <div class="d-flex justify-content-between align-items-center mb-2"><div class="section-title">Items</div><button type="button" class="btn btn-sm btn-primary" id="btnAdd">Add Row</button></div>
          <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0">
              <thead class="table-light"><tr><th>ID</th><th>Product</th><th>Warehouse</th><th>Stock</th><th>Price</th><th>Qty</th><th>Retail</th><th>Disc%</th><th>Amount</th><th>—</th></tr></thead>
              <tbody id="salesTableBody">
                @foreach($sale->items as $item)
                <tr>
                  <td><input type="text" class="form-control form-control-sm item-id-input text-center" value="{{ $item->product_id }}"></td>
                  <td><select name="product_id[]" class="form-control product-select"><option value="{{ $item->product_id }}" selected>{{ $item->product->name ?? '' }}</option></select></td>
                  <td><select class="form-select form-select-sm warehouse" name="warehouse_name[]">
                      <option value="0" {{ $item->warehouse_id == 0 ? 'selected' : '' }}>🏠 Shop</option>
                      @foreach ($warehouses as $wh)<option value="{{ $wh->id }}" {{ $item->warehouse_id == $wh->id ? 'selected' : '' }}>📦 {{ $wh->warehouse_name }}</option>@endforeach
                  </select></td>
                  <td><input type="text" class="form-control form-control-sm stock text-center bg-light" value="{{ $item->stock }}" readonly></td>
                  <td><input type="text" class="form-control form-control-sm text-end sales-price bg-light" value="{{ $item->sales_price }}" readonly></td>
                  <td><input type="number" step="any" class="form-control form-control-sm text-center sales-qty" name="sales-qty[]" value="{{ $item->sales_qty }}"></td>
                  <td><input type="text" class="form-control form-control-sm text-end retail-price bg-light" value="{{ $item->retail_price }}" readonly></td>
                  <td><input type="number" class="form-control text-end discount-value" value="{{ $item->discount_percent }}"><input type="hidden" name="discount-percent[]" class="discount-percent" value="{{ $item->discount_percent }}"><input type="hidden" name="discount-amount[]" class="discount-amount" value="{{ $item->discount_amount }}"></td>
                  <td><input type="text" class="form-control form-control-sm text-end sales-amount bg-light" value="{{ $item->amount }}" readonly></td>
                  <td class="text-center"><button type="button" class="btn btn-xs btn-outline-danger del-row">&times;</button></td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="row g-3 mt-3">
        {{-- Receipt Vouchers Section --}}
        <div class="col-lg-7">
          <div class="bg-light border rounded-3 p-3 shadow-sm">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
              <h6 class="mb-0 fw-bold text-success"><i class="fa fa-money me-2"></i>Receipt Vouchers</h6>
              <button type="button" class="btn btn-success btn-sm rounded-pill" id="btnAddRV"><i class="fa fa-plus me-1"></i>Add Row</button>
            </div>
            <div id="rvWrapper">
                @php
                    $rHeads = json_decode($sale->receipt_heads, true) ?? [];
                    $rAccounts = json_decode($sale->receipt_accounts, true) ?? [];
                    $rNarrations = json_decode($sale->receipt_narrations, true) ?? [];
                    $rAmounts = json_decode($sale->receipt_amounts_json, true) ?? [];
                    
                    if (empty($rAmounts) && $sale->receipt1 > 0) {
                        $rAmounts = [$sale->receipt1];
                        $rHeads = [null]; $rAccounts = [null]; $rNarrations = [null];
                    }
                @endphp

                @foreach($rAmounts as $idx => $amt)
                <div class="receipt-row bg-white border rounded-3 p-2 mb-2 shadow-sm rv-row">
                  <div class="row g-2 align-items-center">
                    <div class="col-md-3">
                      <label class="small text-muted mb-1">Head</label>
                      <select class="form-select form-select-sm rv-head" name="receipt_head_id[]">
                        <option value="" disabled {{ !isset($rHeads[$idx]) ? 'selected' : '' }}>Select Head</option>
                        @foreach ($accountHeads as $head)
                          <option value="{{ $head->id }}" {{ (isset($rHeads[$idx]) && $rHeads[$idx] == $head->id) ? 'selected' : '' }}>{{ $head->name }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-md-3">
                      <label class="small text-muted mb-1">Account</label>
                      <select class="form-select form-select-sm rv-account" name="receipt_account_id[]">
                        <option value="" disabled>Select account</option>
                        @if(isset($rHeads[$idx]))
                            @foreach(\App\Models\Account::where('head_id', $rHeads[$idx])->get() as $acc)
                                <option value="{{ $acc->id }}" {{ ($rAccounts[$idx] == $acc->id) ? 'selected' : '' }}>{{ $acc->title }}</option>
                            @endforeach
                        @endif
                      </select>
                    </div>
                    <div class="col-md-2">
                      <label class="small text-muted mb-1">Amount</label>
                      <input type="text" class="form-control form-control-sm text-end fw-bold rv-amount" name="receipt_amount[]" value="{{ $amt }}">
                    </div>
                    <div class="col-md-3">
                      <label class="small text-muted mb-1">Narration</label>
                      <select class="form-select form-select-sm rv-narration" name="receipt_narration[]">
                        <option value="">Select narration...</option>
                        @if(isset($rNarrations[$idx]))
                            <option value="{{ $rNarrations[$idx] }}" selected>{{ $rNarrations[$idx] }}</option>
                        @endif
                      </select>
                    </div>
                    <div class="col-md-1 text-center"><button type="button" class="btn btn-sm btn-outline-danger border-0 del-rv-row">&times;</button></div>
                  </div>
                </div>
                @endforeach
            </div>
            <div class="d-flex justify-content-between p-2 bg-success bg-opacity-10 rounded border mt-2">
              <span class="fw-bold text-success">Total Receipts:</span>
              <span class="fw-bold text-success" id="receiptsTotalDisplay">0.00</span>
            </div>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="totals-card p-3 shadow-sm bg-light border">
            <div class="d-flex justify-content-between py-1 border-bottom"><span>Total Qty</span><span id="tQty" class="fw-bold">0</span></div>
            <div class="d-flex justify-content-between py-2 border-bottom bg-info bg-opacity-10 rounded px-2"><span>Sub-Total</span><span id="tSub" class="fw-bold text-primary">0.00</span></div>
            <div class="d-flex justify-content-between align-items-center py-1 mt-2">
              <span>Order Discount</span>
              <input type="number" step="0.01" class="form-control form-control-sm text-end" id="orderDiscountValue" name="order_discount_value" value="{{ $sale->discount_amount }}" style="width:80px">
            </div>
            <div class="d-flex justify-content-between py-2 bg-primary bg-opacity-10 rounded-3 px-2 mt-2">
              <span class="fw-bold">Payable Total</span>
              <span id="tPayable" class="fw-bold fs-4 text-primary">0.00</span>
            </div>
            <input type="hidden" name="subTotal1" id="subTotal1">
            <input type="hidden" name="subTotal2" id="subTotal2">
            <input type="hidden" name="discountAmount" id="discountAmount">
            <input type="hidden" name="totalBalance" id="totalBalance">
          </div>
        </div>
      </div>
        <div class="d-flex gap-2 mt-4 justify-content-end border-top pt-3">
            <button type="submit" class="btn btn-primary rounded-pill px-5">Update Sale</button>
            <button type="button" id="editBtn" class="btn btn-warning rounded-pill px-5" style="display:none;">Edit</button>
            <a href="{{ route('sale.index') }}" class="btn btn-danger rounded-pill px-5">Cancel</a>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  function toNum(v) { return parseFloat(v || 0) || 0; }
  function updateGrandTotals() {
    let tQty = 0, tSub = 0;
    $('#salesTableBody tr').each(function() {
      const $r = $(this), q = toNum($r.find('.sales-qty').val());
      const sp = toNum($r.find('.sales-price').val()), disc = toNum($r.find('.discount-amount').val());
      const net = (sp * q) - disc; $r.find('.sales-amount').val(net.toFixed(2));
      tQty += q; tSub += net;
    });
    
    let tRV = 0;
    $('.rv-amount').each(function() { tRV += toNum($(this).val()); });
    $('#receiptsTotalDisplay').text(tRV.toFixed(2));

    const od = toNum($('#orderDiscountValue').val()), prev = toNum($('#previousBalance').val());
    const payable = (tSub - od + prev) - tRV;

    $('#tQty').text(tQty); $('#tSub').text(tSub.toFixed(2)); $('#tPayable').text(payable.toFixed(2));
    
    $('#subTotal1').val(tSub); $('#subTotal2').val(tSub); $('#discountAmount').val(od); 
    $('#totalBalance').val(payable.toFixed(2));
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
      } else if (existingVal) { $select.val(existingVal); }
      $select.prop('disabled', false).select2({ tags: true, width: '100%', dropdownParent: $select.parent() });
    });
  }

  $(document).on('change', '.rv-head', function() {
    const $row = $(this).closest('.rv-row'), $acc = $row.find('.rv-account').empty().append('<option disabled selected>Loading...</option>');
    $.get('{{ url("get-accounts-by-head") }}/' + $(this).val()).done(list => {
      $acc.empty().append('<option disabled selected>Select account</option>');
      list.forEach(i => $acc.append(new Option(i.title, i.id)));
    });
  });

  $(document).on('input', '.rv-amount', updateGrandTotals);
  $(document).on('click', '#btnAddRV', function() {
    const html = `<div class="receipt-row bg-white border rounded-3 p-2 mb-2 shadow-sm rv-row"><div class="row g-2 align-items-center"><div class="col-md-3"><label class="small text-muted mb-1">Head</label><select class="form-select form-select-sm rv-head" name="receipt_head_id[]"><option value="" disabled selected>Select Head</option>@foreach($accountHeads as $h)<option value="{{$h->id}}">{{$h->name}}</option>@endforeach</select></div><div class="col-md-3"><label class="small text-muted mb-1">Account</label><select class="form-select form-select-sm rv-account" name="receipt_account_id[]" disabled><option value="" disabled selected>Select account</option></select></div><div class="col-md-2"><label class="small text-muted mb-1">Amount</label><input type="text" class="form-control form-control-sm text-end fw-bold rv-amount" name="receipt_amount[]" placeholder="0.00"></div><div class="col-md-3"><label class="small text-muted mb-1">Narration</label><select class="form-select form-select-sm rv-narration" name="receipt_narration[]"><option value="">Select narration...</option></select></div><div class="col-md-1 text-center"><button type="button" class="btn btn-sm btn-outline-danger border-0 del-rv-row">&times;</button></div></div></div>`;
    const $nr = $(html); $('#rvWrapper').append($nr); loadNarrationsInto($nr.find('.rv-narration'));
  });
  $(document).on('click', '.del-rv-row', function() { $(this).closest('.rv-row').remove(); updateGrandTotals(); });

  function computeRow($row) {
    const rp = toNum($row.find('.retail-price').val()), q = toNum($row.find('.sales-qty').val()), v = toNum($row.find('.discount-value').val());
    const amt = (rp * q * v) / 100; $row.find('.discount-percent').val(v.toFixed(2)); $row.find('.discount-amount').val(amt.toFixed(2));
    updateGrandTotals();
  }
  function initProductSelect($row) {
    $row.find('.product-select').select2({ ajax: { url: '{{ route("search-products") }}', data: (p) => ({ q: p.term, warehouse_id: $row.find('.warehouse').val() }), processResults: (d) => ({ results: d.map(i => ({ id: i.id, text: i.name, stock: i.stock, sale_price: i.sale_price, retail_price: i.retail_price })) }) } }).on('select2:select', function(e) {
      const d = e.params.data; $row.find('.item-id-input').val(d.id); $row.find('.stock').val(d.stock); $row.find('.sales-price').val(d.sale_price); $row.find('.retail-price').val(d.retail_price); computeRow($row); if($row.is(':last-child')) addNewRow(); $row.find('.sales-qty').focus();
    });
  }
  function addNewRow() {
    const wh = $('#salesTableBody tr:last .warehouse').val() || 0;
    const html = `<tr><td><input type="text" class="form-control form-control-sm item-id-input text-center"></td><td><select name="product_id[]" class="form-control product-select"></select></td><td><select class="form-select form-select-sm warehouse" name="warehouse_name[]"><option value="0" ${wh==0?'selected':''}>🏠 Shop</option>@foreach($warehouses as $w)<option value="{{$w->id}}" ${wh=={{$w->id}}?'selected':''}>📦 {{$w->warehouse_name}}</option>@endforeach</select></td><td><input type="text" class="form-control form-control-sm stock bg-light" readonly></td><td><input type="text" class="form-control form-control-sm text-end sales-price bg-light" readonly></td><td><input type="number" class="form-control form-control-sm text-center sales-qty"></td><td><input type="text" class="form-control form-control-sm text-end retail-price bg-light" readonly></td><td><input type="number" class="form-control text-end discount-value"><input type="hidden" name="discount-percent[]" class="discount-percent"><input type="hidden" name="discount-amount[]" class="discount-amount"></td><td><input type="text" class="form-control form-control-sm text-end sales-amount bg-light" readonly></td><td class="text-center"><button type="button" class="btn btn-xs btn-outline-danger del-row">&times;</button></td></tr>`;
    const $nr = $(html); $('#salesTableBody').append($nr); initProductSelect($nr);
  }
  $(document).on('input', '.sales-qty, .discount-value, #orderDiscountValue', function() { computeRow($(this).closest('tr')); });
  $(document).on('click', '.del-row', function() { $(this).closest('tr').remove(); updateGrandTotals(); });
  $(document).on('click', '#btnAdd', () => addNewRow());
  $(function() {
    $.get('{{ route("customers.filter") }}', { type: 'customer' }).done(list => {
      const $s = $('#customerSelect').empty().append('<option disabled selected>Select...</option>');
      list.forEach(i => $s.append(new Option(i.text, i.id)));
      $s.val($s.data('old-val')).trigger('change').select2();
    });
    $('#salesTableBody tr').each(function() { initProductSelect($(this)); computeRow($(this)); });
    $('.rv-narration').each(function() { loadNarrationsInto($(this)); });
    if($('#salesTableBody tr').length === 0) addNewRow();
    updateGrandTotals();
    $('#saleForm').addClass('form-locked'); $('#editBtn').show();
  });
  $('#editBtn').click(function() { $('#saleForm').removeClass('form-locked'); $(this).hide(); });

  $(document).on('keydown', function(e) {
    if (e.ctrlKey && (e.key === 'e' || e.key === 'E')) { e.preventDefault(); e.stopPropagation(); $('#editBtn').click(); }
    if (e.ctrlKey && (e.key === 'l' || e.key === 'L')) { e.preventDefault(); e.stopPropagation(); window.location.href = '{{ route("sale.index") }}'; }
    if (e.ctrlKey && e.key === 'Enter') { e.preventDefault(); e.stopPropagation(); $('#saleForm').submit(); }
    if (e.key === 'Escape') window.location.href = '{{ route("sale.index") }}';
  });
</script>
@endsection
