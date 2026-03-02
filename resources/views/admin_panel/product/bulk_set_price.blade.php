@extends('admin_panel.layout.app')

@section('content')
<style>
    :root {
        --purchase-bg: #f0f7ff;
        --purchase-border: #0d6efd;
        --sale-bg: #f0fdf4;
        --sale-border: #198754;
        --border-color: #e9ecef;
        --text-dark: #2d3748;
    }

    .main-wrapper {
        background: #f8f9fa;
        min-height: 100vh;
        padding-bottom: 50px;
    }

    .page-header-custom {
        background: #fff;
        padding: 20px 30px;
        border-bottom: 1px solid var(--border-color);
        margin-bottom: 25px;
    }

    .pricing-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        border: none;
        margin: 0 30px;
        overflow: hidden;
    }

    .table-responsive-custom {
        overflow-x: auto;
    }

    .table-pricing {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .table-pricing thead th {
        background: #fdfdfd;
        padding: 15px 10px;
        font-size: 11px;
        text-transform: uppercase;
        font-weight: 800;
        color: #718096;
        border-bottom: 2px solid var(--border-color);
        letter-spacing: 0.05em;
        white-space: nowrap;
    }

    .table-pricing thead th.section-label {
        font-size: 13px;
        padding: 10px;
        border-bottom: 1px solid var(--border-color);
    }

    .purchase-header { background-color: var(--purchase-bg) !important; color: var(--purchase-border) !important; }
    .sale-header { background-color: var(--sale-bg) !important; color: var(--sale-border) !important; }

    .table-pricing tbody td {
        padding: 12px 8px;
        border-bottom: 1px solid var(--border-color);
        vertical-align: middle;
    }

    .product-row:hover { background-color: #fbfcfd; }

    .product-info { display: flex; align-items: center; gap: 12px; min-width: 250px; }
    .product-icon { width: 35px; height: 35px; background: #edf2f7; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #4a5568; }
    .product-name { font-weight: 600; font-size: 14px; color: var(--text-dark); display: block; }
    .product-meta { font-size: 11px; color: #a0aec0; }

    .price-input {
        width: 100%;
        min-width: 85px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 8px 10px;
        font-size: 13px;
        text-align: center;
        transition: all 0.2s;
        font-weight: 500;
        background: #fff;
    }

    .price-input:focus {
        border-color: #3182ce;
        box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.15);
        outline: none;
    }

    .price-input[readonly] {
        background-color: #f7fafc;
        border-color: #edf2f7;
        color: #4a5568;
        cursor: not-allowed;
    }

    .net-input { font-weight: 700 !important; }
    .purchase-net-field { color: var(--purchase-border) !important; border-color: #cfe2ff !important; background: #f8fbff !important; }
    .sale-net-field { color: var(--sale-border) !important; border-color: #d1e7dd !important; background: #f9fffb !important; }

    .action-bar {
        background: #fff;
        padding: 20px 30px;
        border-top: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .btn-save-master {
        padding: 12px 30px;
        font-weight: 700;
        border-radius: 8px;
        text-transform: uppercase;
        font-size: 13px;
        letter-spacing: 0.5px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .purchase-theme .btn-brand { background: var(--purchase-border); color: white; border: none; }
    .sale-theme .btn-brand { background: var(--sale-border); color: white; border: none; }
    .both-theme .btn-brand { background: #4e73df; color: white; border: none; }

    .btn-brand:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); opacity: 0.9; color: #fff; }

    .sticky-th { position: sticky; left: 0; background: #fff !important; z-index: 5; border-right: 1px solid var(--border-color); }
</style>

<div class="main-wrapper {{ $type }}-theme">
    <div class="page-header-custom d-flex justify-content-between align-items-center">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('products.index') }}" class="text-decoration-none">Products</a></li>
                    <li class="breadcrumb-item active">Bulk Pricing</li>
                </ol>
            </nav>
            <h3 class="mb-0 fw-bold">
                @if($type == 'purchase') <i class="fa fa-shopping-basket text-primary me-2"></i> Update Purchase Pricing
                @elseif($type == 'sale') <i class="fa fa-chart-line text-success me-2"></i> Update Sale Pricing
                @else <i class="fa fa-tags text-secondary me-2"></i> Update Master Pricing
                @endif
            </h3>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="date-selector-wrapper">
                <label class="text-muted small fw-bold d-block mb-1">Effective Date</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="fa fa-calendar-alt text-muted small"></i></span>
                    <input type="date" name="start_date" form="bulkPriceForm" class="form-control border-start-0 ps-0 fw-bold bg-white" value="{{ date('Y-m-d') }}" style="border-radius: 0 8px 8px 0; font-size: 13px;">
                </div>
            </div>
        </div>
    </div>

    <div class="pricing-card">
        <form action="{{ route('products.bulkUpdatePrices.update') }}" method="POST" id="bulkPriceForm">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">
            
            <div class="table-responsive-custom">
                <table class="table-pricing">
                    <thead>
                        <tr>
                            <th class="section-label sticky-th" rowspan="2" style="min-width: 250px;">Product Name & Info</th>
                            @if($type == 'purchase' || $type == 'both')
                                <th colspan="6" class="section-label purchase-header text-center">Purchase Parameters</th>
                            @endif
                            @if($type == 'sale' || $type == 'both')
                                <th colspan="8" class="section-label sale-header text-center">Sale Parameters</th>
                            @endif
                        </tr>
                        <tr>
                            @if($type == 'purchase' || $type == 'both')
                                <th class="text-center purchase-header">Retail</th>
                                <th class="text-center purchase-header">Tax %</th>
                                <th class="text-center purchase-header">Tax Amt</th>
                                <th class="text-center purchase-header">Disc %</th>
                                <th class="text-center purchase-header">Disc Amt</th>
                                <th class="text-center purchase-header">Net Purchase</th>
                            @endif
                            @if($type == 'sale' || $type == 'both')
                                <th class="text-center sale-header">Retail</th>
                                <th class="text-center sale-header">Tax %</th>
                                <th class="text-center sale-header">Tax Amt</th>
                                <th class="text-center sale-header">WHT %</th>
                                <th class="text-center sale-header">WHT Amt</th>
                                <th class="text-center sale-header">Disc %</th>
                                <th class="text-center sale-header">Disc Amt</th>
                                <th class="text-center sale-header">Net Sale</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                        <tr class="product-row" data-product-id="{{ $product->id }}">
                            <td class="sticky-th">
                                <div class="product-info">
                                    <div class="product-icon"><i class="fa fa-box-open"></i></div>
                                    <div style="width: calc(100% - 47px);">
                                        <span class="product-name text-truncate" title="{{ $product->name }}">{{ $product->name }}</span>
                                        <span class="product-meta">ID #{{ $product->id }} &bull; STK: {{ $product->stock }}</span>
                                    </div>
                                </div>
                                <input type="hidden" name="product_id[]" value="{{ $product->id }}">
                            </td>

                            @if($type == 'purchase' || $type == 'both')
                                <td><input type="number" step="0.01" class="price-input purchase-retail" value="{{ $product->latestPrice->purchase_retail_price ?? ''}}" name="purchase_retail_price[]"></td>
                                <td><input type="number" step="0.01" class="price-input purchase-tax" value="{{ $product->latestPrice->purchase_tax_percent ?? ''}}" name="purchase_tax_percent[]"></td>
                                <td><input type="text" class="price-input purchase-after-tax" value="{{ $product->latestPrice->purchase_tax_amount ?? '' }}" name="purchase_tax_amount[]" readonly></td>
                                <td><input type="number" step="0.01" class="price-input purchase-discount" value="{{ $product->latestPrice->purchase_discount_percent ?? '' }}" name="purchase_discount_percent[]"></td>
                                <td><input type="text" class="price-input purchase-discount-amount" name="purchase_discount_amount[]" value="{{ $product->latestPrice->purchase_discount_amount ?? ''}}" readonly></td>
                                <td><input type="text" class="price-input net-input purchase-net purchase-net-field" value="{{ $product->latestPrice->purchase_net_amount ?? ''}}" name="purchase_net_amount[]" readonly></td>
                            @endif

                            @if($type == 'sale' || $type == 'both')
                                <td><input type="number" step="0.01" class="price-input sale-retail" value="{{ $product->latestPrice->sale_retail_price ?? ''}}" name="sale_retail_price[]"></td>
                                <td><input type="number" step="0.01" class="price-input sale-tax" value="{{ $product->latestPrice->sale_tax_percent ?? ''}}" name="sale_tax_percent[]"></td>
                                <td><input type="text" class="price-input sale-after-tax" value="{{ $product->latestPrice->sale_tax_amount ?? ''}}" name="sale_tax_amount[]" readonly></td>
                                <td><input type="number" step="0.01" class="price-input sale-wht" value="{{ $product->latestPrice->sale_wht_percent ?? 0.5 }}" name="sale_wht_percent[]"></td>
                                <td><input type="text" class="price-input sale-wht-amount" name="sale_wht_amount[]" value="{{ $product->latestPrice->sale_wht_amount ?? ''}}" readonly></td>
                                <td><input type="number" step="0.01" class="price-input sale-discount" value="{{ $product->latestPrice->sale_discount_percent ?? ''}}" name="sale_discount_percent[]"></td>
                                <td><input type="text" class="price-input sale-discount-amount" name="sale_discount_amount[]" value="{{ $product->latestPrice->sale_discount_amount?? '' }}" readonly></td>
                                <td><input type="text" class="price-input net-input sale-net sale-net-field" value="{{ $product->latestPrice->sale_net_amount ?? ''}}" name="sale_net_amount[]" readonly></td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="action-bar">
                <div class="text-muted small">
                    <i class="fa fa-keyboard me-2"></i> Use <kbd>TAB</kbd> to move between fields quickly.
                </div>
                <button type="submit" class="btn btn-save-master btn-brand shadow-sm">
                    <i class="fa fa-save me-2"></i> Update Selected Prices
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function(){
  function toNum(v){ if (v===null||v===undefined||v==='') return 0; let n=parseFloat(v); return isNaN(n)?0:n; }
  function fmt(v){ return (Math.round((v + Number.EPSILON) * 100) / 100).toFixed(2); }

  function calculatePurchaseRow(row){
    try {
      let retail = toNum(row.querySelector('.purchase-retail').value);
      let taxPct = toNum(row.querySelector('.purchase-tax').value);
      let discPct = toNum(row.querySelector('.purchase-discount').value);
      
      let taxAmt = retail * taxPct / 100;
      let discAmt = retail * discPct / 100;
      let netAmt = retail + taxAmt - discAmt;
      
      row.querySelector('.purchase-after-tax').value = fmt(taxAmt);
      row.querySelector('.purchase-discount-amount').value = fmt(discAmt);
      row.querySelector('.purchase-net').value = fmt(netAmt);
    } catch(e){}
  }

  function calculateSaleRow(row){
    try {
      let retail = toNum(row.querySelector('.sale-retail').value);
      let taxPct = toNum(row.querySelector('.sale-tax').value);
      let whtPct = toNum(row.querySelector('.sale-wht').value);
      let discPct = toNum(row.querySelector('.sale-discount').value);
      
      let taxAmt = retail * taxPct / 100;
      let afterTax = retail + taxAmt;
      let whtAmt = afterTax * whtPct / 100;
      let discAmt = retail * discPct / 100;
      let netAmt = retail + taxAmt + whtAmt - discAmt;
      
      row.querySelector('.sale-after-tax').value = fmt(taxAmt);
      row.querySelector('.sale-wht-amount').value = fmt(whtAmt);
      row.querySelector('.sale-discount-amount').value = fmt(discAmt);
      row.querySelector('.sale-net').value = fmt(netAmt);
    } catch(e){}
  }

  function initializeAllRows(){
    document.querySelectorAll('.product-row').forEach(function(tr){
      if(tr.querySelector('.purchase-retail')) calculatePurchaseRow(tr);
      if(tr.querySelector('.sale-retail')) calculateSaleRow(tr);
    });
  }

  function attachListeners(){
    const table = document.querySelector('table');
    if (!table) return;
    table.addEventListener('input', function(e){
      const t = e.target;
      const tr = t.closest('.product-row');
      if(!tr) return;
      if (t.classList.contains('purchase-retail') || t.classList.contains('purchase-tax') || t.classList.contains('purchase-discount')) calculatePurchaseRow(tr);
      if (t.classList.contains('sale-retail') || t.classList.contains('sale-tax') || t.classList.contains('sale-wht') || t.classList.contains('sale-discount')) calculateSaleRow(tr);
    }, { passive: true });
  }

  function start() {
    attachListeners();
    initializeAllRows();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
</script>
@endsection


