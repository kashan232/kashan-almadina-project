    @extends('admin_panel.layout.app')
    @section('content')
    <style>
        label {
            font-size: 12px;
            font-weight: 900;
        }
    </style>
    <div class="main-wrapper">
        <div class="page-wrapper">
            <div class="content">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Update Prices for Multiple Products</h4>
                        <input type="date" class="form-control form-control-sm" value="{{ now()->setTimezone('Asia/Karachi')->toDateString() }}" style="width: 150px; display: inline-block;">
                    </div>

                    <div class="card-body">
                        @if (session()->has('success'))
                        <div class="alert alert-success">
                            <strong>Success!</strong> {{ session('success') }}.
                        </div>
                        @endif

                        <form action="{{ route('products.bulkUpdatePrices.update') }}" method="POST" id="bulkPriceForm">
                            @csrf
                            <table class="table table-bordered table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>

                                        <!-- Purchase -->
                                        <th colspan="5" class="text-primary text-center">Purchase</th>

                                        <!-- Sale -->
                                        <th colspan="6" class="text-success text-center">Sale</th>
                                    </tr>
                                    <tr>
                                        <th></th>

                                        <!-- Purchase headings -->
                                        <th>Retail</th>
                                        <th>Tax %</th>
                                        <th>After Tax</th>
                                        <th>Disc %</th>
                                        {{-- <th>Disc amuont</th> --}}
                                        <th>Net Amt</th>

                                        <!-- Sale headings -->
                                        <th>Retail</th>
                                        <th>Tax %</th>
                                        <th>After Tax</th>
                                        <th>WHT %</th>
                                        <th>Disc %</th>
                                        {{-- <th>Disc Amt</th> --}}
                                        <th>Net Amt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($products as $product)
                                    <tr data-product-id="{{ $product->id }}">
                                        <td class="fw-bold">{{ $product->name }}</td>
                                        <td class="d-none"><input type="number" name="product_id[]" value="{{ $product->id }}"></td>

                                        <!-- Purchase Inputs -->
                                        <td><input type="number" class="form-control form-control-sm p-1 purchase-retail" value="{{ $product->latestPrice->purchase_retail_price ?? ''}}" name="purchase_retail_price[]" style="width:90px"></td>
                                        <td><input type="number" class="form-control form-control-sm p-1 purchase-tax" value="{{ $product->latestPrice->purchase_tax_percent ?? ''}}" name="purchase_tax_percent[]" style="width:90px"></td>
                                        <td><input type="text" class="form-control form-control-sm p-1 purchase-after-tax" value="{{ $product->latestPrice->purchase_tax_amount ?? '' }}" name="purchase_tax_amount[]" readonly style="width:90px"></td>
                                        <td><input type="number" class="form-control form-control-sm p-1 purchase-discount" value="{{ $product->latestPrice->purchase_discount_percent ?? '' }}" name="purchase_discount_percent[]" style="width:90px"></td>
                                        <td class="d-none"><input type="text" class="form-control form-control-sm p-1 purchase-discount-amount" value="{{ $product->latestPrice->purchase_discount_amount ?? ''}}" name="purchase_discount_amount[]" readonly style="width:90px"></td>
                                        <td><input type="text" class="form-control form-control-sm p-1 purchase-net" value="{{ $product->latestPrice->purchase_net_amount ?? ''}}" name="purchase_net_amount[]" readonly style="width:90px"></td>

                                        <!-- Sale Inputs -->
                                        <td><input type="number" class="form-control form-control-sm p-1 sale-retail" value="{{ $product->latestPrice->sale_retail_price ?? ''}}" name="sale_retail_price[]" style="width:90px"></td>
                                        <td><input type="number" class="form-control form-control-sm p-1 sale-tax" value="{{ $product->latestPrice->sale_tax_percent ?? ''}}" name="sale_tax_percent[]" style="width:90px"></td>
                                        <td><input type="text" class="form-control form-control-sm p-1 sale-after-tax" value="{{ $product->latestPrice->sale_tax_amount ?? ''}}" name="sale_tax_amount[]" readonly style="width:90px"></td>
                                        <td><input type="number" class="form-control form-control-sm p-1 sale-wht" value="{{ $product->latestPrice->sale_wht_percent ?? 0.5 }}" name="sale_wht_percent[]" style="width:90px"></td>
                                        <td><input type="number" class="form-control form-control-sm p-1 sale-discount" value="{{ $product->latestPrice->sale_discount_percent ?? ''}}" name="sale_discount_percent[]" style="width:90px"></td>
                                        <td class="d-none"><input type="text" class="form-control form-control-sm p-1 sale-discount-amount" value="{{ $product->latestPrice->sale_discount_amount?? '' }}" name="sale_discount_amount[]" readonly style="width:90px"></td>
                                        <td><input type="text" class="form-control form-control-sm p-1 sale-net" value="{{ $product->latestPrice->sale_net_amount ?? ''}}" name="sale_net_amount[]" readonly style="width:90px"></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            {{-- Update Button --}}
                            <div class="mt-3 text-end d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-sync-alt"></i> Update Prices
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @endsection

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>


@section('scripts')
<script>
(function(){
  
  // small helpers
  function toNum(v){ if (v===null||v===undefined||v==='') return 0; let n=parseFloat(v); return isNaN(n)?0:n; }
  function fmt(v){ return (Math.round((v + Number.EPSILON) * 100) / 100).toFixed(2); }

  function calculatePurchaseRow(row){
    try {
      let retail = toNum(row.querySelector('.purchase-retail')?.value);
      let taxPct = toNum(row.querySelector('.purchase-tax')?.value);
      let discPct = toNum(row.querySelector('.purchase-discount')?.value);
      let taxAmt = retail * taxPct / 100;
      let discAmt = retail * discPct / 100;
      let netAmt = retail + taxAmt - discAmt;
      row.querySelector('.purchase-after-tax') && (row.querySelector('.purchase-after-tax').value = fmt(taxAmt));
      row.querySelector('.purchase-discount-amount') && (row.querySelector('.purchase-discount-amount').value = fmt(discAmt));
      row.querySelector('.purchase-net') && (row.querySelector('.purchase-net').value = fmt(netAmt));
    } catch(e){ console.error('purchase calc err', e); }
  }

  function calculateSaleRow(row){
    try {
      let retail = toNum(row.querySelector('.sale-retail')?.value);
      let taxPct = toNum(row.querySelector('.sale-tax')?.value);
      let whtPct = toNum(row.querySelector('.sale-wht')?.value);
      let discPct = toNum(row.querySelector('.sale-discount')?.value);
      let taxAmt = retail * taxPct / 100;
      let afterTax = retail + taxAmt;
      let whtAmt = afterTax * whtPct / 100;
      let discAmt = retail * discPct / 100;
      let netAmt = retail + taxAmt + whtAmt - discAmt;
      row.querySelector('.sale-after-tax') && (row.querySelector('.sale-after-tax').value = fmt(taxAmt));
      row.querySelector('.sale-discount-amount') && (row.querySelector('.sale-discount-amount').value = fmt(discAmt));
      row.querySelector('.sale-net') && (row.querySelector('.sale-net').value = fmt(netAmt));
    } catch(e){ console.error('sale calc err', e); }
  }

  function initializeAllRows(){
    document.querySelectorAll('table tbody tr').forEach(function(tr){
      calculatePurchaseRow(tr);
      calculateSaleRow(tr);
    });
  }

  function attachListeners(){
    const table = document.querySelector('table');
    if (!table) return;
    table.addEventListener('input', function(e){
      const t = e.target;
      const tr = t.closest('tr');
      if(!tr) return;
      if (t.classList.contains('purchase-retail') || t.classList.contains('purchase-tax') || t.classList.contains('purchase-discount')) calculatePurchaseRow(tr);
      if (t.classList.contains('sale-retail') || t.classList.contains('sale-tax') || t.classList.contains('sale-wht') || t.classList.contains('sale-discount')) calculateSaleRow(tr);
    }, { passive: true });
  }

  // If jQuery exists, run when ready; otherwise use DOMContentLoaded
  function start() {
    console.log('Starting page script init');
    attachListeners();
    initializeAllRows();
  }

  if (typeof jQuery === 'function' && typeof jQuery.fn.ready === 'function') {
    jQuery(start);
  } else {
    document.addEventListener('DOMContentLoaded', start);
    if (document.readyState === 'interactive' || document.readyState === 'complete') {
      setTimeout(start, 20);
    }
  }
})();
</script>
@endsection


