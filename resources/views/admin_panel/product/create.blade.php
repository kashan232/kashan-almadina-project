@extends('admin_panel.layout.app')

<style>
    /* Ultra-Compact form styling */
    .card { border: none; shadow: none; margin-bottom: 0; }
    .card-body { padding: 8px 15px !important; }
    
    .form-label {
        font-size: 11px;
        font-weight: 700;
        margin-bottom: 1px;
        color: #555;
    }
    
    .form-control, .form-select {
        font-size: 13px;
        padding: 6px 10px;
        height: 34px;
    }
    
    .mb-2 { margin-bottom: 4px !important; }
    .g-1 { --bs-gutter-x: 0.4rem; --bs-gutter-y: 0.4rem; }
    
    h5 {
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 6px;
        padding-bottom: 3px;
        border-bottom: 1px solid #eee;
    }
    
    .section-container {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        padding: 8px;
        height: 100%;
        background-color: #fcfcfc;
    }

    .btn-save {
        padding: 6px 30px;
        font-size: 13px;
    }
    
    /* Hide scrollbar if possible but allow if needed */
    .page-wrapper { padding: 10px !important; }
    .content { padding: 0 !important; }
</style>

@section('content')

<div class="main-wrapper">
    <div class="page-wrapper">
        <div class="content">
            <form action="{{ route('products.store') }}" method="POST" id="form">
                @csrf
                <div class="card shadow-sm">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center bg-white border-bottom">
                        <h6 class="mb-0 fw-bold"><i class="fa fa-plus-circle me-1"></i> Create Product</h6>
                        <div class="d-flex gap-2">
                             <a href="javascript:void(0)" onclick="window.history.back()" class="btn btn-secondary btn-sm py-1 px-3">Back</a>
                             <button type="submit" id="btnSave" class="btn btn-primary btn-sm py-1 px-4 btn-save">Save Product</button>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        @if (session()->has('success'))
                        <div class="alert alert-success p-1 mb-2 small">
                            {{ session('success') }}
                        </div>
                        @endif

                        {{-- ROW 1: General Info --}}
                        <div class="section-container mb-2">
                            <h5 class="text-secondary">General Information</h5>
                            <div class="row g-1">
                                <div class="col-md-3 mb-2">
                                    <label class="form-label d-flex justify-content-between">
                                        <span>Item Name <span class="text-danger">*</span></span>
                                        @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" placeholder="Enter Name" required>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <label class="form-label d-flex justify-content-between">
                                        <span>Category</span>
                                        @error('category') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </label>
                                    <select name="category" class="form-select @error('category') is-invalid @enderror" id="category-dropdown">
                                        <option value="" selected disabled>Select</option>
                                        @foreach ($categories as $cat)
                                            <option value="{{ $cat->id }}" {{ old('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <label class="form-label d-flex justify-content-between">
                                        <span>Sub-Category</span>
                                        @error('sub_category') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </label>
                                    <select name="sub_category" class="form-select @error('sub_category') is-invalid @enderror" id="subcategory-dropdown">
                                        <option selected disabled>Select</option>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <label class="form-label d-flex justify-content-between">
                                        <span>Brand</span>
                                        @error('brand') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </label>
                                    <select name="brand" class="form-select @error('brand') is-invalid @enderror">
                                        <option value="" selected disabled>Select</option>
                                        @foreach ($brands as $brand)
                                            <option value="{{ $brand->id }}" {{ old('brand') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-1 mb-2">
                                    <label class="form-label d-flex justify-content-between">
                                        <span>Stock</span>
                                        @error('stock') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </label>
                                    <input type="number" class="form-control @error('stock') is-invalid @enderror" name="stock" value="{{ old('stock', 0) }}">
                                </div>
                                <div class="col-md-1 mb-2">
                                    <label class="form-label d-flex justify-content-between">
                                        <span>Alert Qty</span>
                                        @error('alert_qty') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </label>
                                    <input type="number" class="form-control @error('alert_qty') is-invalid @enderror" name="alert_qty" value="{{ old('alert_qty') }}">
                                </div>
                                <div class="col-md-1 mb-2">
                                    <label class="form-label d-flex justify-content-between">
                                        <span>Weight</span>
                                        @error('weight') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </label>
                                    <input type="text" name="weight" class="form-control @error('weight') is-invalid @enderror" value="{{ old('weight') }}">
                                </div>
                                 {{-- Status hidden or default active. If needed add col. --}}
                                <input type="hidden" name="status" value="1">
                            </div>
                        </div>

                        {{-- ROW 2: Pricing Split --}}
                        <div class="row g-2">
                            {{-- Purchase --}}
                            <div class="col-md-5">
                                <div class="section-container border-primary border-opacity-25 bg-soft-primary">
                                    <h5 class="text-primary">Purchase Pricing</h5>
                                    <div class="row g-1">
                                        <div class="col-4 mb-2">
                                            <label class="form-label d-flex justify-content-between">
                                                <span>Retail Price</span>
                                                @error('purchase_retail_price') <span class="text-danger small">{{ $message }}</span> @enderror
                                            </label>
                                            <input type="number" step="0.01" class="form-control @error('purchase_retail_price') is-invalid @enderror" name="purchase_retail_price" value="{{ old('purchase_retail_price') }}">
                                        </div>
                                        <div class="col-4 mb-2">
                                            <label class="form-label d-flex justify-content-between">
                                                <span>Tax (%)</span>
                                                @error('purchase_tax_percent') <span class="text-danger small">{{ $message }}</span> @enderror
                                            </label>
                                            <input type="number" step="0.01" class="form-control @error('purchase_tax_percent') is-invalid @enderror" name="purchase_tax_percent" value="{{ old('purchase_tax_percent') }}">
                                        </div>
                                        <div class="col-4 mb-2">
                                            <label class="form-label">Tax Amt</label>
                                            <input type="text" class="form-control bg-light" name="purchase_tax_amount" readonly tabindex="-1">
                                        </div>
                                        
                                        <div class="col-4 mb-2">
                                            <label class="form-label d-flex justify-content-between">
                                                <span>Disc (%)</span>
                                                @error('purchase_discount_percent') <span class="text-danger small">{{ $message }}</span> @enderror
                                            </label>
                                            <input type="number" step="0.01" class="form-control @error('purchase_discount_percent') is-invalid @enderror" name="purchase_discount_percent" value="{{ old('purchase_discount_percent') }}">
                                        </div>
                                        <div class="col-4 mb-2">
                                            <label class="form-label">Disc Amt</label>
                                            <input type="text" class="form-control bg-light" name="purchase_discount_amount" readonly tabindex="-1">
                                        </div>
                                        <div class="col-4 mb-2">
                                            <label class="form-label fw-bold text-primary">Net Purchase</label>
                                            <input type="text" class="form-control fw-bold border-primary" name="purchase_net_amount" readonly tabindex="-1">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Sale --}}
                            <div class="col-md-7">
                                <div class="section-container border-success border-opacity-25 bg-soft-success">
                                    <h5 class="text-success">Sale Pricing</h5>
                                    <div class="row g-1">
                                        <div class="col-3 mb-2">
                                            <label class="form-label d-flex justify-content-between">
                                                <span>Retail Price</span>
                                                @error('sale_retail_price') <span class="text-danger small">{{ $message }}</span> @enderror
                                            </label>
                                            <input type="number" step="0.01" class="form-control @error('sale_retail_price') is-invalid @enderror" name="sale_retail_price" value="{{ old('sale_retail_price') }}">
                                        </div>
                                        <div class="col-3 mb-2">
                                            <label class="form-label d-flex justify-content-between">
                                                <span>Tax (%)</span>
                                                @error('sale_tax_percent') <span class="text-danger small">{{ $message }}</span> @enderror
                                            </label>
                                            <input type="number" step="0.01" class="form-control @error('sale_tax_percent') is-invalid @enderror" name="sale_tax_percent" value="{{ old('sale_tax_percent') }}">
                                        </div>
                                        <div class="col-3 mb-2">
                                            <label class="form-label">Tax Amt</label>
                                            <input type="text" class="form-control bg-light" name="sale_tax_amount" readonly tabindex="-1">
                                        </div>
                                        <div class="col-3 mb-2">
                                            <label class="form-label">After Tax</label>
                                            <input type="text" class="form-control bg-light" name="sale_after_tax_amount" readonly tabindex="-1">
                                        </div>

                                        <div class="col-3 mb-2">
                                            <label class="form-label d-flex justify-content-between">
                                                <span>WHT (%)</span>
                                                @error('sale_wht_percent') <span class="text-danger small">{{ $message }}</span> @enderror
                                            </label>
                                            <input type="number" step="0.01" class="form-control @error('sale_wht_percent') is-invalid @enderror" name="sale_wht_percent" value="{{ old('sale_wht_percent') }}">
                                        </div>
                                        <div class="col-3 mb-2">
                                            <label class="form-label">WHT Amt</label>
                                            <input type="text" class="form-control bg-light" name="sale_wht_amount" readonly tabindex="-1">
                                        </div>
                                        <div class="col-3 mb-2">
                                            <label class="form-label d-flex justify-content-between">
                                                <span>Disc (%)</span>
                                                @error('sale_discount_percent') <span class="text-danger small">{{ $message }}</span> @enderror
                                            </label>
                                            <input type="number" step="0.01" class="form-control @error('sale_discount_percent') is-invalid @enderror" name="sale_discount_percent" value="{{ old('sale_discount_percent') }}">
                                        </div>
                                        <div class="col-3 mb-2">
                                            <label class="form-label">Disc Amt</label>
                                            <input type="text" class="form-control bg-light" name="sale_discount_amount" readonly tabindex="-1">
                                        </div>

                                        <div class="col-12 mt-1">
                                            <div class="d-flex align-items-center bg-white p-1 border rounded">
                                                <label class="form-label mb-0 me-3 fw-bold text-success">NET SALE PRICE:</label>
                                                <input type="text" class="form-control form-control-sm fw-bold border-success text-success" style="width: 150px;" name="sale_net_amount" readonly tabindex="-1">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    {{-- Footer removed, button moved to header for compactness --}}
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
@section('scripts')



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
    $(document).ready(function() {

        // ❶ Enter key se submit ko block kar do (inputs/select par)
        $('#form').on('keydown', 'input, select', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                return false;
            }
        });

        // ❷ Sirf Save button se hi submit allow
        $('#form').on('submit', function(e) {
            const submitter = e.originalEvent && e.originalEvent.submitter;
            if (!submitter || submitter.id !== 'btnSave') {
                e.preventDefault();
            }
        });

        // Category -> Subcategory
        $('#category-dropdown').on('change', function() {
            var categoryId = $(this).val();
            loadSubcategories(categoryId);
        });

        function loadSubcategories(categoryId, selectedSubId = null) {
            if (categoryId) {
                $.ajax({
                    url: '/get-subcategories/' + categoryId,
                    type: "GET",
                    dataType: "json",
                    success: function(data) {
                        $('#subcategory-dropdown').empty();
                        $('#subcategory-dropdown').append('<option selected disabled>Select</option>');
                        $.each(data, function(key, value) {
                            let isSelected = (selectedSubId && selectedSubId == value.id) ? 'selected' : '';
                            $('#subcategory-dropdown').append('<option value="' + value.id + '" '+isSelected+'>' + value.name + '</option>');
                        });
                    }
                });
            } else {
                $('#subcategory-dropdown').empty();
            }
        }

        // On Load: Check if we have old value for category to reload subcategories
        var oldCategory = "{{ old('category') }}";
        var oldSubCategory = "{{ old('sub_category') }}";
        if(oldCategory) {
            loadSubcategories(oldCategory, oldSubCategory);
        }

        // Purchase calc
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

        // Sale calc
        function calculateSaleValues() {
            const retail = parseFloat($('[name="sale_retail_price"]').val()) || 0;
            const taxPct = parseFloat($('[name="sale_tax_percent"]').val()) || 0;
            const whtPct = parseFloat($('[name="sale_wht_percent"]').val()) || 0;
            const discPct = parseFloat($('[name="sale_discount_percent"]').val()) || 0;

            const taxAmount = retail * (taxPct / 100);
            $('[name="sale_tax_amount"]').val(taxAmount.toFixed(2));

            const afterTax = retail + taxAmount;
            $('[name="sale_after_tax_amount"]').val(afterTax.toFixed(2));

            const whtAmount = afterTax * (whtPct / 100);
            $('[name="sale_wht_amount"]').val(whtAmount.toFixed(2));

            const discountAmount = retail * (discPct / 100);
            $('[name="sale_discount_amount"]').val(discountAmount.toFixed(2));

            const net = afterTax + whtAmount - discountAmount;
            $('[name="sale_net_amount"]').val(net.toFixed(2));
        }

        $('[name="purchase_retail_price"], [name="purchase_tax_percent"], [name="purchase_discount_percent"]').on('input', function() {
            calculateValues('purchase');
        });

        $('[name="sale_retail_price"], [name="sale_tax_percent"], [name="sale_wht_percent"], [name="sale_discount_percent"]').on('input', calculateSaleValues);

        calculateValues('purchase');
        calculateSaleValues();
    });
</script>
@endsection