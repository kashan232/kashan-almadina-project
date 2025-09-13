@extends('admin_panel.layout.app')
@section('content')
    
<div class="main-wrapper">
  
    <div class="page-wrapper">
        <div class="content">
            <div class="page-header">
                <div class="page-title">
                    <h4>Create Product</h4>
                </div>
                <div class="page-btn"></div>
            </div>

            <div class="card">
                <div class="card-body">
                        @if (session()->has('success'))
                            <div class="alert alert-success">
                                <strong>Success!</strong> {{ session('success') }}.
                            </div>
                        @endif
                        <form action="{{ route('products.store') }}" method="POST" id="form">
                            @csrf
                            <div class="modal-body">
                            
                                <div class="row">
                                    {{-- LEFT SIDE: General Fields --}}
                                    <div class="col-md-12 mb-4">
                                        <h5 class="text-dark">Product Information</h5>
                                        <div class="row border rounded  bg-light">
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">Item Name</label>
                                                <input type="text" class="form-control @error('name') is-invalid  @enderror" name="name" placeholder="Product Name">
                                                @error('name')
                                                    <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">Category</label>
                                                <select name="category" class="form-control @error('category') is-invalid  @enderror" id="category-dropdown">
                                                    <option selected disabled>Select Category</option>
                                                    @foreach ($categories as $cat)
                                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('category')
                                                    <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">Sub-Category</label>
                                                <select name="sub_category" class="form-control @error('sub_category') is-invalid  @enderror" id="subcategory-dropdown">
                                                    <option selected disabled>Select Subcategory</option>
                                                </select>
                                                @error('sub_category')
                                                    <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">Brand</label>
                                                <select name="brand" class="form-control @error('brand') is-invalid  @enderror">
                                                    <option selected disabled>Select Brand</option>
                                                    @foreach ($brands as $brand)
                                                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('brand')
                                                    <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">Alert Quantity</label>
                                                <input type="number" class="form-control @error('alert_qty') is-invalid  @enderror" name="alert_qty" placeholder="Alert Quantity">
                                                @error('alert_qty')
                                                    <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="col-md-1 mb-3">
                                                <label class="form-label">Stock</label>
                                                <input type="number" class="form-control @error('stock') is-invalid  @enderror" name="stock" placeholder="Stock Quantity">
                                                @error('stock')
                                                    <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="col-md-1 mb-3">
                                                <label class="form-label">Status</label>
                                                <select name="status" class="form-control">
                                                    <option value="1">Active</option>
                <option value="0">Inactive</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row gx-4">
                                    {{-- PURCHASE --}}
                                    <div class="col-md-6">
                                        <h5 class="text-primary mb-2">Purchase Details</h5>
                                        <div class="border rounded p-3 bg-light h-100">
                                            <div class="row">
                                                <div class="mb-3 col-4">
                                                    <label class="form-label">Retail Price</label>
                                                    <input type="number" step="0.01" class="form-control @error('purchase_retail_price') is-invalid  @enderror" name="purchase_retail_price" placeholder="Retail Price">
                                                    @error('purchase_retail_price')
                                                        <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                <div class="mb-3 col-4">
                                                    <label class="form-label">Tax (%)</label>
                                                    <input type="number" step="0.01" class="form-control @error('purchase_tax_percent') is-invalid  @enderror" name="purchase_tax_percent" placeholder="Tax %">
                                                    @error('purchase_tax_percent')
                                                        <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                <div class="mb-3 col-4">
                                                    <label class="form-label">After Tax Amount</label>
                                                    <input type="text" class="form-control @error('purchase_tax_amount') is-invalid  @enderror" name="purchase_tax_amount" placeholder="Tax Amount" readonly>
                                                    @error('purchase_tax_amount')
                                                        <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                <div class="mb-3 col-4">
                                                    <label class="form-label">Discount (%)</label>
                                                    <input type="number" step="0.01" class="form-control @error('purchase_discount_percent') is-invalid  @enderror" name="purchase_discount_percent" placeholder="Discount %">
                                                    @error('purchase_discount_percent')
                                                        <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                <div class="mb-3 col-4">
                                                    <label class="form-label">Discount Amount</label>
                                                    <input type="text" class="form-control @error('purchase_discount_amount') is-invalid  @enderror" name="purchase_discount_amount" placeholder="Discount Amount" readonly>
                                                    @error('purchase_discount_amount')
                                                        <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                <div class="mb-3 col-4">
                                                    <label class="form-label">Net Amount</label>
                                                    <input type="text" class="form-control @error('purchase_net_amount') is-invalid  @enderror" name="purchase_net_amount" placeholder="Net Amount" readonly>
                                                    @error('purchase_net_amount')
                                                        <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- SALE (UPDATED) --}}
                                    <div class="col-md-6">
                                        <h5 class="text-success mb-2">Sale Details</h5>
                                        <div class="border rounded p-3 bg-light h-100">
                                            <div class="row">
                                                <div class="mb-3 col-4">
                                                    <label class="form-label">Retail Price</label>
                                                    <input type="number" step="0.01" class="form-control @error('sale_retail_price') is-invalid  @enderror" name="sale_retail_price" placeholder="Retail Price">
                                                    @error('sale_retail_price')
                                                        <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                                    @enderror
                                                </div>

                                                <div class="mb-3 col-4">
                                                    <label class="form-label">Tax (%)</label>
                                                    <input type="number" step="0.01" class="form-control @error('sale_tax_percent') is-invalid  @enderror" name="sale_tax_percent" placeholder="Tax %">
                                                    @error('sale_tax_percent')
                                                        <span class="text-danger " style="font-size: 13px;">{{ $message }}</span>
                                                    @enderror
                                                </div>

                                                <div class="mb-3 col-4">
                                                    <label class="form-label">Sales Tax Amount</label>
                                                    <input type="text" class="form-control" name="sale_tax_amount" placeholder="Sales Tax Amount" readonly>
                                                </div>

                                                <div class="mb-3 col-4">
                                                    <label class="form-label">After Tax Amount</label>
                                                    <input type="text" class="form-control" name="sale_after_tax_amount" placeholder="After Tax Amount" readonly>
                                                </div>

                                                <div class="mb-3 col-4">
                                                    <label class="form-label">Withholding Tax (%)</label>
                                                    <input type="number" step="0.01" class="form-control @error('sale_wht_percent') is-invalid  @enderror" name="sale_wht_percent" placeholder="WHT %">
                                                    @error('sale_wht_percent')
                                                        <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                                    @enderror
                                                </div>

                                                <div class="mb-3 col-4">
                                                    <label class="form-label">WHT Amount</label>
                                                    <input type="text" class="form-control" name="sale_wht_amount" placeholder="WHT Amount" readonly>
                                                </div>

                                                <div class="mb-3 col-4">
                                                    <label class="form-label">Discount (%)</label>
                                                    <input type="number" step="0.01" class="form-control @error('sale_discount_percent') is-invalid  @enderror" name="sale_discount_percent" placeholder="Discount %">
                                                    @error('sale_discount_percent')
                                                        <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                                    @enderror
                                                </div>

                                                <div class="mb-3 col-4">
                                                    <label class="form-label">Discount Amount</label>
                                                    <input type="text" class="form-control @error('sale_discount_amount') is-invalid  @enderror" name="sale_discount_amount" placeholder="Discount Amount" readonly>
                                                    @error('sale_discount_amount')
                                                        <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                                    @enderror
                                                </div>

                                                <div class="mb-3 col-4">
                                                    <label class="form-label">Net Amount</label>
                                                    <input type="text" class="form-control @error('sale_net_amount') is-invalid  @enderror" name="sale_net_amount" placeholder="Net Amount" readonly>
                                                    @error('sale_net_amount')
                                                        <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 modal-footer mt-4 border-0">
                                        <button type="submit" id="btnSave" class="btn btn-primary">Save</button>

                                    </div>
                                </div>

                            </div>
                        </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
    @if(session('errors'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'errors',
                text: "{{ session('errors') }}",
                timer: 2000,
                showConfirmButton: false
            });
        </script>
    @endif
    @if ($errors->any())
        <script>
            let errorMessages = `{!! implode('<br>', $errors->all()) !!}`;
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                html: errorMessages,
                timer: 3000,
                showConfirmButton: false
            });
        </script>
    @endif
    @if(session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: "{{ session('success') }}",
                timer: 2000,
                showConfirmButton: false
            });
        </script>
    @endif

<script>
    // ❶ Enter key se submit ko block kar do (inputs/select par)
$('#form').on('keydown', 'input, select', function (e) {
    if (e.key === 'Enter') {
        e.preventDefault();   // Enter press pe submit NA ho
        return false;
    }
});

// ❷ Extra safety: sirf Save button se hi submit allow
$('#form').on('submit', function (e) {
    // Modern browsers: jis button se submit hua
    const submitter = e.originalEvent && e.originalEvent.submitter;
    if (!submitter || submitter.id !== 'btnSave') {
        e.preventDefault();   // agar Save nahin, to block
    }
});

$(document).ready(function() {

    // Category -> Subcategory
    $('#category-dropdown').on('change', function() {
        var categoryId = $(this).val();
        if (categoryId) {
            $.ajax({
                url: '/get-subcategories/' + categoryId,
                type: "GET",
                dataType: "json",
                success: function(data) {
                    $('#subcategory-dropdown').empty();
                    $('#subcategory-dropdown').append('<option selected disabled>Select Subcategory</option>');
                    $.each(data, function(key, value) {
                        $('#subcategory-dropdown').append('<option value="' + value.id + '">' + value.name + '</option>');
                    });
                }
            });
        } else {
            $('#subcategory-dropdown').empty();
        }
    });

    // Generic calculator for purchase (as-is)
    function calculateValues(section) {
        const retailPrice     = parseFloat($(`[name="${section}_retail_price"]`).val()) || 0;
        const taxPercent      = parseFloat($(`[name="${section}_tax_percent"]`).val()) || 0;
        const discountPercent = parseFloat($(`[name="${section}_discount_percent"]`).val()) || 0;

        const taxAmount      = (retailPrice * taxPercent / 100).toFixed(2);
        const discountAmount = (retailPrice * discountPercent / 100).toFixed(2);
        const netAmount      = (retailPrice + parseFloat(taxAmount) - parseFloat(discountAmount)).toFixed(2);

        $(`[name="${section}_tax_amount"]`).val(taxAmount);
        $(`[name="${section}_discount_amount"]`).val(discountAmount);
        $(`[name="${section}_net_amount"]`).val(netAmount);
    }

    // ---- SALE CALC (Correct formula)
    function calculateSaleValues() {
        const retail  = parseFloat($('[name="sale_retail_price"]').val())   || 0;
        const taxPct  = parseFloat($('[name="sale_tax_percent"]').val())    || 0;
        const whtPct  = parseFloat($('[name="sale_wht_percent"]').val())    || 0;
        const discPct = parseFloat($('[name="sale_discount_percent"]').val()) || 0;

        // 1) Sales Tax on retail
        const taxAmount = retail * (taxPct / 100);
        $('[name="sale_tax_amount"]').val(taxAmount.toFixed(2));

        // 2) After Tax
        const afterTax = retail + taxAmount;
        $('[name="sale_after_tax_amount"]').val(afterTax.toFixed(2));

        // 3) WHT on AfterTax
        const whtAmount = afterTax * (whtPct / 100);
        $('[name="sale_wht_amount"]').val(whtAmount.toFixed(2));

        // 4) Discount on retail
        const discountAmount = retail * (discPct / 100);
        $('[name="sale_discount_amount"]').val(discountAmount.toFixed(2));

        // 5) Net = AfterTax + WHT − Discount
        const net = afterTax + whtAmount - discountAmount;
        $('[name="sale_net_amount"]').val(net.toFixed(2));
    }

    // Bind changes
    $('[name="purchase_retail_price"], [name="purchase_tax_percent"], [name="purchase_discount_percent"]').on('input', function() {
        calculateValues('purchase');
    });

    $('[name="sale_retail_price"], [name="sale_tax_percent"], [name="sale_wht_percent"], [name="sale_discount_percent"]').on('input', calculateSaleValues);

    // Init on load
    calculateValues('purchase');
    calculateSaleValues();
});
</script>
@endsection
