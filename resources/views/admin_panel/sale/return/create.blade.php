@extends('admin_panel.layout.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  .sr-wrap{max-width:1100px}
  .table-sm td,.table-sm th{padding:.45rem .5rem;vertical-align:middle}
  .form-control-plaintext{padding-left:0;padding-right:0}
  .w-80{width:80px}.w-90{width:90px}.w-110{width:110px}.w-140{width:140px}
</style>

<div class="container-fluid py-4">
  <div class="sr-wrap mx-auto">
    <div class="card shadow-sm">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Sale Return</h6>
        <small class="text-muted">{{ now()->format('d-m-Y') }}</small>
      </div>

      <form action="{{ route('sale.return.store') }}" method="POST">
        @csrf
        <input type="hidden" name="source_sale_id" value="{{ $sale->id }}">

        <div class="card-body">
          {{-- Top --}}
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <div class="row g-2">
                <div class="col-6">
                  <label class="form-label mb-1">Return Inv#</label>
                  <input class="form-control" name="Invoice_no" value="{{ $nextInvoiceNumber }}" readonly>
                </div>
                <div class="col-6">
                  <label class="form-label mb-1">Original Inv#</label>
                  <input class="form-control" name="Invoice_main" value="{{ $sale->manual_invoice }}" readonly>
                </div>
                <div class="col-12">
                  <label class="form-label mb-1">Customer</label>
                  <input class="form-control" value="{{ optional($sale->customer)->customer_name }}" readonly>
                  <input type="hidden" name="customer" value="{{ $sale->customer_id }}">
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="row g-2">
                <div class="col-12">
                  <label class="form-label mb-1">Address</label>
                  <input class="form-control" name="address" value="{{ $sale->address }}">
                </div>
                <div class="col-6">
                  <label class="form-label mb-1">Tel#</label>
                  <input class="form-control" name="tel" value="{{ $sale->tel }}">
                </div>
                <div class="col-6">
                  <label class="form-label mb-1">Remarks</label>
                  <input class="form-control" name="remarks" value="{{ $sale->remarks }}">
                </div>
              </div>
            </div>
          </div>

          {{-- Items --}}
          <div class="table-responsive">
            <table class="table table-bordered table-sm" id="itemsTable">
              <thead class="table-light">
                <tr>
                  <th class="w-140">Return Warehouse</th>
                  <th>Product</th>
                  <th class="w-80 text-center">Stock</th>
                  <th class="w-90 text-end">Price Lvl</th>
                  <th class="w-90 text-end">Return Price</th>
                  <th class="w-90 text-end">Sold Qty</th>
                  <th class="w-110 text-end">Sold Amt</th>    {{-- NEW --}}
                  <th class="w-90 text-end">Return Qty</th>
                  <th class="w-90 text-end">Left Qty</th>
                  <th class="w-90 text-end">Disc %</th>
                  <th class="w-110 text-end">Disc Amt</th>
                  <th class="w-110 text-end">Return Amt</th>
                  <th class="w-80 text-center">—</th>
                </tr>
              </thead>
              <tbody id="tbody">
                @foreach ($sale->items as $it)
                  @php
                    $sold    = (float)($it->sales_qty ?? 0);
                    $soldAmt = (float)($it->amount ?? (($it->sales_price ?? 0) * $sold - ($it->discount_amount ?? 0)));
                  @endphp
                  <tr data-sold="{{ $sold }}">
                    <input type="hidden" name="source_sale_item_id[]" value="{{ $it->id }}">

                    {{-- SELECTABLE return warehouse --}}
                    <td>
                      <select class="form-select return-warehouse" name="warehouse_id[]">
                        @foreach ($warehouses as $w)
                          <option value="{{ $w->id }}" {{ $w->id == $it->warehouse_id ? 'selected' : '' }}>
                            {{ $w->warehouse_name }}
                          </option>
                        @endforeach
                      </select>
                    </td>

                    {{-- Product locked (readonly) --}}
                    <td>
                      <input class="form-control-plaintext" value="{{ $it->product?->name }}" readonly>
                      <input type="hidden" name="product_id[]" value="{{ $it->product_id }}">
                    </td>

                    <td><input class="form-control text-center stock" name="stock[]"
                               value="{{ (float)($it->product->stock ?? $it->stock ?? 0) }}" readonly></td>
                    <td><input class="form-control text-end price"  name="price[]"
                               value="{{ (float)($it->price_level ?? 0) }}" readonly></td>
                    <td><input class="form-control text-end sales-price" name="sales-price[]"
                               value="{{ (float)($it->sales_price ?? 0) }}"></td>

                    <td><input class="form-control text-end sold-qty" value="{{ $sold }}" readonly></td>
                    <td><input class="form-control text-end sold-amt" value="{{ $soldAmt }}" readonly></td> {{-- NEW --}}

                    <td><input class="form-control text-end return-qty" name="return_qty[]" value="0" min="0" max="{{ $sold }}"></td>
                    <td><input class="form-control text-end left-qty" value="{{ $sold }}" readonly></td>

                    <td><input class="form-control text-end discount-percent" name="discount-percent[]"
                               value="{{ (float)($it->discount_percent ?? 0) }}"></td>
                    <td><input class="form-control text-end discount-amount"  name="discount-amount[]"
                               value="{{ (float)($it->discount_amount ?? 0) }}"></td>
                    <td><input class="form-control text-end line-amount" name="sales-amount[]" value="0" readonly></td>

                    <td class="text-center">
                      <button type="button" class="btn btn-sm btn-outline-danger del-row">&times;</button>
                    </td>
                  </tr>
                @endforeach
              </tbody>
              <tfoot>
                <tr>
                  <td colspan="9" class="text-end fw-semibold">SubTotal (Gross):</td>
                  <td colspan="2"><input class="form-control text-end" id="subTotal1" name="subTotal1" value="0" readonly></td>
                  <td></td>
                </tr>
                <tr>
                  <td colspan="9" class="text-end fw-semibold">SubTotal (Net):</td>
                  <td colspan="2"><input class="form-control text-end" id="subTotal2" name="subTotal2" value="0" readonly></td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>

          {{-- Order totals --}}
          <div class="row g-2 mt-2">
            <div class="col-md-3 ms-auto">
              <label class="form-label mb-1">Order Discount %</label>
              <input class="form-control text-end" id="discountPercent" name="discountPercent" value="0">
            </div>
            <div class="col-md-3">
              <label class="form-label mb-1">Order Discount Rs</label>
              <input class="form-control text-end" id="discountAmount" name="discountAmount" value="0" readonly>
            </div>
            <div class="col-md-3">
              <label class="form-label mb-1">Total Return Amount</label>
              <input class="form-control text-end fw-semibold" id="totalBalance" name="totalBalance" value="0" readonly>
            </div>
          </div>
        </div>

        <div class="card-footer d-flex justify-content-between">
          <div class="small text-muted">* Product locked. Stock will be added in the selected warehouse.</div>
          <div>
            <a href="{{ route('sale.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-sm btn-success">Save Sale Return</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function num(v){ return parseFloat(v||0) || 0; }

function clampReturn(tr){
  const sold = num(tr.dataset.sold);
  const el   = tr.querySelector('.return-qty');
  let val    = num(el.value);
  if(val > sold){ val = sold; el.value = sold; }
  if(val < 0){ val = 0; el.value = 0; }
  tr.querySelector('.left-qty').value = (sold - val).toFixed(2);
  return val;
}

function computeRow(tr){
  const price = num(tr.querySelector('.sales-price').value);
  const qty   = clampReturn(tr);
  const gross = price * qty;

  const pctEl = tr.querySelector('.discount-percent');
  const amtEl = tr.querySelector('.discount-amount');
  let discAmt = num(amtEl.value);
  const pct   = num(pctEl.value);
  if(pct > 0){ discAmt = (gross * pct)/100.0; amtEl.value = discAmt.toFixed(2); }

  tr.querySelector('.line-amount').value = (gross - discAmt).toFixed(2);
}

function computeTotals(){
  let grossSum=0, netSum=0;
  document.querySelectorAll('#tbody tr').forEach(tr=>{
    const price=num(tr.querySelector('.sales-price').value);
    const qty  =num(tr.querySelector('.return-qty').value);
    const disc =num(tr.querySelector('.discount-amount').value);
    const g=price*qty, n=g-disc;
    grossSum+=g; netSum+=n;
  });
  document.getElementById('subTotal1').value = grossSum.toFixed(2);
  document.getElementById('subTotal2').value = netSum.toFixed(2);

  const orderPct=num(document.getElementById('discountPercent').value);
  const orderDisc=(grossSum*orderPct)/100.0;
  document.getElementById('discountAmount').value=orderDisc.toFixed(2);
  document.getElementById('totalBalance').value=(netSum-orderDisc).toFixed(2);
}

function wireRow(tr){
  ['input','change'].forEach(ev=>{
    tr.addEventListener(ev, e=>{
      if(e.target.matches('.sales-price, .return-qty, .discount-percent, .discount-amount')){
        computeRow(tr); computeTotals();
      }
    });
  });
  tr.querySelector('.del-row').addEventListener('click', ()=>{
    if(document.querySelectorAll('#tbody tr').length>1){ tr.remove(); computeTotals(); }
  });
}

document.querySelectorAll('#tbody tr').forEach(tr=>{ wireRow(tr); computeRow(tr); });
computeTotals();
document.getElementById('discountPercent').addEventListener('input', computeTotals);
</script>
@endsection
