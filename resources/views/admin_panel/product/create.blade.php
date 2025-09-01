
@extends('admin_panel.layout.app')
@section('content')
    
<div class="main-wrapper">
  
    <div class="page-wrapper">
        <div class="content">
            <div class="page-header">
                <div class="page-title">
                    <h4>Create Product</h4>
                    {{-- <h6>Manage Products</h6> --}}
                </div>
                <div class="page-btn">
                    {{-- <button class="btn btn-added" data-bs-toggle="modal" data-bs-target="#addProductModal">
                        <img src="assets/img/icons/plus.svg" class="me-1" alt="img">Add Product
                    </button> --}}
                </div>
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
                                                {{-- <input type="text" class="form-control" name="category" placeholder="Category"> --}}
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
                                                {{-- <input type="text" class="form-control" name="sub_category" placeholder="Sub-category"> --}}
                                                   <select name="sub_category" class="form-control @error('sub_category') is-invalid  @enderror" id="subcategory-dropdown">
                                                        <option selected disabled>Select Subcategory</option>
                                                    </select>
                                                    @error('sub_category')
                                                        <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                                    @enderror
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">Brand</label>
                                                {{-- <input type="text" class="form-control @error('brand') is-invalid  @enderror" name="brand" placeholder="Brand"> --}}
                                                <select name="brand" class="form-control @error('brand') is-invalid  @enderror" id="category-dropdown">
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
                                                {{-- <input type="checkbox" class="form-control @error('alert_qty') is-invalid  @enderror" name="alert_qty" placeholder="Alert Quantity"> --}}
                                                <select name="status" class="form-control">
                                                    <option value="1">Active</option>
                                                    <option value="0">Inactive</option>
                                                </select>
                                                  {{-- @error('alert_qty')
                                                        <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                                    @enderror --}}
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

                                    {{-- SALE --}}
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
                                                    <label class="form-label">After Tax Amount</label>
                                                    <input type="text" class="form-control @error('sale_tax_amount') is-invalid  @enderror" name="sale_tax_amount" placeholder="Tax Amount" readonly>
                                                    @error('sale_tax_amount')
                                                        <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                <div class="mb-3 col-4">
                                                    <label class="form-label">Withholding Tax (%)</label>
                                                    <input type="number" step="0.01" class="form-control @error('sale_wht_percent') is-invalid  @enderror" name="sale_wht_percent" placeholder="WHT %">
                                                    @error('sale_wht_percent')
                                                        <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                {{-- <div class="mb-3 col-4">
                                                    <label class="form-label">Withholding Tax (%) testing</label>
                                                    <input type="number" step="0.01" class="form-control" name="sale_wht_percent_only_show"  readonly>
                                                </div> --}}
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

                                    <div class="col-12 modal-footer mt-4   border-0 ">
                                        <button type="submit" class="btn btn-primary">Save</button>
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
            // alert("ads");   
            // $("#form").submit(function(e){
            //     e.preventDefault();
            //     $(this).submit();
            //     alert("Asd");
            // });

$(document).ready(function() {

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

    // Utility function to calculate values
    function calculateValues(section) {
        const retailPrice = parseFloat($(`[name="${section}_retail_price"]`).val()) || 0;
        const taxPercent = parseFloat($(`[name="${section}_tax_percent"]`).val()) || 0;
        const discountPercent = parseFloat($(`[name="${section}_discount_percent"]`).val()) || 0;

        const taxAmount = (retailPrice * taxPercent / 100).toFixed(2);
        const discountAmount = (retailPrice * discountPercent / 100).toFixed(2);
        const netAmount = (retailPrice + parseFloat(taxAmount) - parseFloat(discountAmount)).toFixed(2);

        $(`[name="${section}_tax_amount"]`).val(taxAmount);
        $(`[name="${section}_discount_amount"]`).val(discountAmount);
        $(`[name="${section}_net_amount"]`).val(netAmount);
    }

function calculateSaleValues() {
    // Step 1: Get Input Values
    const retailPrice = parseFloat($('[name="sale_retail_price"]').val()) || 0;
    const taxPercent = parseFloat($('[name="sale_tax_percent"]').val()) || 0;
    const whtPercent = parseFloat($('[name="sale_wht_percent"]').val()) || 0.5; // Default 0.5%
    const discountPercent = parseFloat($('[name="sale_discount_percent"]').val()) || 0;

    // Step 2: Calculate Tax (on Retail Price)
    const taxAmount = (retailPrice * taxPercent / 100).toFixed(2); // 23,000 × 18% = 4,140

    // Step 3: Calculate WHT (on Retail Price)
    // const whtAmount = (retailPrice * whtPercent / 100).toFixed(2); // 23,000 × 0.5% = 115
    const whtAmount = (taxAmount * whtPercent / 100).toFixed(2); // 23,000 × 0.5% = 115
    // $("")
    // Step 4: Calculate Discount (on Retail Price)
    const discountAmount = (retailPrice * discountPercent / 100).toFixed(2); // 23,000 × 7% = 1,610

    // Step 5: Net Amount = (Retail + Tax + WHT) - Discount
    const netAmount = (
        retailPrice + 
        parseFloat(taxAmount) + 
        parseFloat(whtAmount) - 
        parseFloat(discountAmount)
    ).toFixed(2); // 23,000 + 4,140 + 115 - 1,610 = 25,645

    // Update Fields
    $('[name="sale_tax_amount"]').val(taxAmount);
    $('[name="sale_wht_amount"]').val(whtAmount);
    $('[name="sale_discount_amount"]').val(discountAmount);
    $('[name="sale_net_amount"]').val(netAmount);
}
    // Bind events for Purchase section
    $('[name="purchase_retail_price"], [name="purchase_tax_percent"], [name="purchase_discount_percent"]').on('input', function() {
        calculateValues('purchase');
    });

    // Bind events for Sale section
    $('[name="sale_retail_price"], [name="sale_tax_percent"], [name="sale_discount_percent"], [name="sale_wht_percent"]').on('input', function() {
        calculateSaleValues();
    });

});
</script>
@endsection

