@extends('admin_panel.layout.app')
@section('content')
    <style>
       label{
        font-size: 12px;
        font-weight: 900;
       }
    </style>
<div class="main-wrapper">
    <div class="page-wrapper">
        <div class="content">
            <div class="page-header">
                <div class="page-title mt-2 ml-4 mr-4 d-flex justify-content-between align-items-center">
                    {{-- <h4>Bulk Set Prices</h4> --}}
                    <h5>Update Prices for Multiple Products</h5>
                    <input type="date" class="form-control form-control-sm" value="{{ now()->setTimezone('Asia/Karachi')->toDateString() }}" style="width: 150px; display: inline-block;" readonly>
                </div>
            </div>

            <div class="card">
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
                                    <td><input type="number" class="form-control form-control-sm p-1 purchase-tax" value="{{ $product->latestPrice->purchase_tax_percent ?? ''}}"  name="purchase_tax_percent[]" style="width:90px"></td>
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

@section('js')
<script>
$(document).ready(function() {
    $('table').on('input', '.purchase-retail, .purchase-tax, .purchase-discount', function(){
            let row = $(this).closest('tr');
            let retail = parseFloat(row.find('.purchase-retail').val()) || 0;
            let taxPct = parseFloat(row.find('.purchase-tax').val()) || 0;
            let discPct = parseFloat(row.find('.purchase-discount').val()) || 0;

            let taxAmt = (retail * taxPct / 100).toFixed(2);
            let discAmt = (retail * discPct / 100).toFixed(2);
            let netAmt = (retail + parseFloat(taxAmt) - parseFloat(discAmt)).toFixed(2);

            row.find('.purchase-after-tax').val(taxAmt);
            row.find('.purchase-discount-amount').val(discAmt); // ✅ Discount amount show
            row.find('.purchase-net').val(netAmt);
        });

        $('table').on('input', '.sale-retail, .sale-tax, .sale-wht, .sale-discount', function(){
            let row = $(this).closest('tr');
            let retail = parseFloat(row.find('.sale-retail').val()) || 0;
            let taxPct = parseFloat(row.find('.sale-tax').val()) || 0;
            let whtPct = parseFloat(row.find('.sale-wht').val()) || 0;
            let discPct = parseFloat(row.find('.sale-discount').val()) || 0;

            let taxAmt = (retail * taxPct / 100).toFixed(2);
            // let whtAmt = (taxAmt * whtPct / 100).toFixed(2);
            let whtAmt = ((retail + parseFloat(taxAmt)) * whtPct / 100).toFixed(2);
            let discAmt = (retail * discPct / 100).toFixed(2);
            // let netAmt = (retail + parseFloat(taxAmt) + parseFloat(whtAmt) - (retail * discPct / 100)).toFixed(2);
            let netAmt = (retail + parseFloat(taxAmt) + parseFloat(whtAmt) - parseFloat(discAmt)).toFixed(2);

             
            
            row.find('.sale-after-tax').val(taxAmt);
            row.find('.sale-discount-amount').val(discAmt); // ✅ Discount amount show
            row.find('.sale-net').val(netAmt);
        });
    });
    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: "{{ session('success') }}",
            timer: 2000,
            showConfirmButton: false
        });
    @endif
    
    @if ($errors->any())
        let errorMessages = `{!! implode('<br>', $errors->all()) !!}`;
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            html: errorMessages,
            timer: 3000,
            showConfirmButton: false
        });
    @endif
     // Current date ko YYYY-MM-DD format me convert karke set karna
       
</script>
@endsection