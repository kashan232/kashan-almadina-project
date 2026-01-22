@extends('admin_panel.layout.app')
@section('content')

<div class="main-wrapper">
    <div class="page-wrapper">
        <div class="content">
            <div class="page-header d-flex justify-content-between align-items-center">
                <div class="page-title">
                    <h4>Edit Product</h4>
                </div>
                <div class="page-btn">
                    <a href="javascript:void(0)" onclick="window.history.back()" class="btn btn-added btn-danger btn-sm">
                        <i class="fa fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    @if (session()->has('success'))
                    <div class="alert alert-success">
                        <strong>Success!</strong> {{ session('success') }}.
                    </div>
                    @endif
                    <form action="{{ route('products.update', $product->id) }}" method="POST" id="form">
                        @csrf
                        @method('PUT')
                        <div class="modal-body">

                            <div class="row">
                                {{-- LEFT SIDE: General Fields --}}
                                <div class="col-md-12 mb-4">
                                    <h5 class="text-dark">Product Information</h5>
                                    <div class="row border rounded  bg-light">
                                        <div class="col-md-2 mb-3">
                                            <label class="form-label">Item Name</label>
                                            <input type="text" class="form-control @error('name') is-invalid  @enderror" name="name" placeholder="Product Name" value="{{ old('name', $product->name) }}">
                                            @error('name')
                                            <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label class="form-label">Category</label>
                                            <select name="category" class="form-control @error('category') is-invalid  @enderror" id="category-dropdown">
                                                <option selected disabled>Select Category</option>
                                                @foreach ($categories as $cat)
                                                <option value="{{ $cat->id }}" {{ old('category', $product->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
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
                                                @foreach ($subCategories as $subCat)
                                                <option value="{{ $subCat->id }}" {{ old('sub_category', $product->sub_category_id) == $subCat->id ? 'selected' : '' }}>{{ $subCat->name }}</option>
                                                @endforeach
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
                                                <option value="{{ $brand->id }}" {{ old('brand', $product->brand_id) == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('brand')
                                            <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label class="form-label">Alert Quantity</label>
                                            <input type="number" class="form-control @error('alert_qty') is-invalid  @enderror" name="alert_qty" placeholder="Alert Quantity" value="{{ old('alert_qty', $product->alert_qty) }}">
                                            @error('alert_qty')
                                            <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="col-md-1 mb-3">
                                            <label class="form-label">Stock</label>
                                            <input type="number" class="form-control @error('stock') is-invalid  @enderror" name="stock" placeholder="Stock Quantity" value="{{ old('stock', $product->stock) }}">
                                            @error('stock')
                                            <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="col-md-1 mb-3">
                                            <label class="form-label">Status</label>
                                            <select name="status" class="form-control">
                                                <option value="1" {{ old('status', $product->status) == 1 ? 'selected' : '' }}>Active</option>
                                                <option value="0" {{ old('status', $product->status) == 0 ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                        </div>

                                        <div class="col-md-2 mb-3">
                                            <label class="form-label">Weight</label>
                                            <input type="text" name="weight" class="form-control" value="{{ old('weight', $product->weight) }}">
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
                                            @php $price = $product->latestPrice; @endphp
                                            <div class="mb-3 col-4">
                                                <label class="form-label">Retail Price</label>
                                                <input type="number" step="0.01" class="form-control @error('purchase_retail_price') is-invalid  @enderror" name="purchase_retail_price" placeholder="Retail Price" value="{{ old('purchase_retail_price', $price ? $price->purchase_retail_price : '') }}">
                                                @error('purchase_retail_price')
                                                <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="mb-3 col-4">
                                                <label class="form-label">Tax (%)</label>
                                                <input type="number" step="0.01" class="form-control @error('purchase_tax_percent') is-invalid  @enderror" name="purchase_tax_percent" placeholder="Tax %" value="{{ old('purchase_tax_percent', $price ? $price->purchase_tax_percent : '') }}">
                                                @error('purchase_tax_percent')
                                                <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="mb-3 col-4">
                                                <label class="form-label">After Tax Amount</label>
                                                <input type="text" class="form-control @error('purchase_tax_amount') is-invalid  @enderror" name="purchase_tax_amount" placeholder="Tax Amount" readonly value="{{ old('purchase_tax_amount', $price ? $price->purchase_tax_amount : '') }}">
                                                @error('purchase_tax_amount')
                                                <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="mb-3 col-4">
                                                <label class="form-label">Discount (%)</label>
                                                <input type="number" step="0.01" class="form-control @error('purchase_discount_percent') is-invalid  @enderror" name="purchase_discount_percent" placeholder="Discount %" value="{{ old('purchase_discount_percent', $price ? $price->purchase_discount_percent : '') }}">
                                                @error('purchase_discount_percent')
                                                <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="mb-3 col-4">
                                                <label class="form-label">Discount Amount</label>
                                                <input type="text" class="form-control @error('purchase_discount_amount') is-invalid  @enderror" name="purchase_discount_amount" placeholder="Discount Amount" readonly value="{{ old('purchase_discount_amount', $price ? $price->purchase_discount_amount : '') }}">
                                                @error('purchase_discount_amount')
                                                <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="mb-3 col-4">
                                                <label class="form-label">Net Amount</label>
                                                <input type="text" class="form-control @error('purchase_net_amount') is-invalid  @enderror" name="purchase_net_amount" placeholder="Net Amount" readonly value="{{ old('purchase_net_amount', $price ? $price->purchase_net_amount : '') }}">
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
                                                <input type="number" step="0.01" class="form-control @error('sale_retail_price') is-invalid  @enderror" name="sale_retail_price" placeholder="Retail Price" value="{{ old('sale_retail_price', $price ? $price->sale_retail_price : '') }}">
                                                @error('sale_retail_price')
                                                <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="mb-3 col-4">
                                                <label class="form-label">Tax (%)</label>
                                                <input type="number" step="0.01" class="form-control @error('sale_tax_percent') is-invalid  @enderror" name="sale_tax_percent" placeholder="Tax %" value="{{ old('sale_tax_percent', $price ? $price->sale_tax_percent : '') }}">
                                                @error('sale_tax_percent')
                                                <span class="text-danger " style="font-size: 13px;">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="mb-3 col-4">
                                                <label class="form-label">Sales Tax Amount</label>
                                                <input type="text" class="form-control" name="sale_tax_amount" placeholder="Sales Tax Amount" readonly value="{{ old('sale_tax_amount', $price ? $price->sale_tax_amount : '') }}">
                                            </div>

                                            <div class="mb-3 col-4">
                                                <label class="form-label">After Tax Amount</label>
                                                <input type="text" class="form-control" name="sale_after_tax_amount" placeholder="After Tax Amount" readonly value="{{ old('sale_after_tax_amount', $price ? $price->sale_after_tax_amount : '') }}">
                                            </div>

                                            <div class="mb-3 col-4">
                                                <label class="form-label">Withholding Tax (%)</label>
                                                <input type="number" step="0.01" class="form-control @error('sale_wht_percent') is-invalid  @enderror" name="sale_wht_percent" placeholder="WHT %" value="{{ old('sale_wht_percent', $price ? $price->sale_wht_percent : '') }}">
                                                @error('sale_wht_percent')
                                                <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="mb-3 col-4">
                                                <label class="form-label">WHT Amount</label>
                                                <input type="text" class="form-control" name="sale_wht_amount" placeholder="WHT Amount" readonly value="{{ old('sale_wht_amount', $price ? $price->sale_wht_amount : '') }}">
                                            </div>

                                            <div class="mb-3 col-4">
                                                <label class="form-label">Discount (%)</label>
                                                <input type="number" step="0.01" class="form-control @error('sale_discount_percent') is-invalid  @enderror" name="sale_discount_percent" placeholder="Discount %" value="{{ old('sale_discount_percent', $price ? $price->sale_discount_percent : '') }}">
                                                @error('sale_discount_percent')
                                                <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="mb-3 col-4">
                                                <label class="form-label">Discount Amount</label>
                                                <input type="text" class="form-control @error('sale_discount_amount') is-invalid  @enderror" name="sale_discount_amount" placeholder="Discount Amount" readonly value="{{ old('sale_discount_amount', $price ? $price->sale_discount_amount : '') }}">
                                                @error('sale_discount_amount')
                                                <span class="text-danger" style="font-size: 13px;">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="mb-3 col-4">
                                                <label class="form-label">Net Amount</label>
                                                <input type="text" class="form-control @error('sale_net_amount') is-invalid  @enderror" name="sale_net_amount" placeholder="Net Amount" readonly value="{{ old('sale_net_amount', $price ? $price->sale_net_amount : '') }}">
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
@section('scripts')
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

    $(document).ready(function() {

        // safe passing of PHP values into JS
        const initialCategory = @json(old('category', $product->category_id ?? null));
        const initialSubCategory = @json(old('sub_category', $product->sub_category_id ?? null));

        // helper to populate subcategory select from returned data and optionally set selected
        function populateSubcategories(data, selectedId) {
            const $sub = $('#subcategory-dropdown');
            $sub.empty();
            $sub.append('<option disabled>Select Subcategory</option>');
            if (!Array.isArray(data) || data.length === 0) {
                // keep only "Select Subcategory" (disabled)
                return;
            }
            data.forEach(function(item) {
                const isSelected = selectedId && String(item.id) === String(selectedId) ? 'selected' : '';
                $sub.append('<option value="' + item.id + '" ' + isSelected + '>' + item.name + '</option>');
            });
        }

        // When category changes (user interaction)
        $('#category-dropdown').on('change', function() {
            const categoryId = $(this).val();
            if (!categoryId) {
                $('#subcategory-dropdown').empty().append('<option disabled selected>Select Subcategory</option>');
                return;
            }

            $.ajax({
                url: '/get-subcategories/' + categoryId,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    populateSubcategories(response, null); // no auto-select on user change
                },
                error: function() {
                    $('#subcategory-dropdown').empty().append('<option disabled selected>Select Subcategory</option>');
                }
            });
        });

        // On initial load: prefer server-provided options (Blade $subCategories), but if empty, fall back to AJAX.
        // We still call populateSubcategories with data rendered by Blade if available.
        (function initSubcategories() {
            // 1) Try to use server-rendered options: check if #subcategory-dropdown already has >1 option (the server passed them)
            const $sub = $('#subcategory-dropdown');
            if ($sub.find('option').length > 1) {
                // server already rendered subcategories — just ensure correct one is selected
                if (initialSubCategory) {
                    $sub.val(String(initialSubCategory));
                }
                return;
            }

            // 2) Otherwise, fetch via AJAX and set the initial selected subcategory
            const catId = initialCategory || $('#category-dropdown').val();
            if (!catId) {
                // nothing selected; keep placeholder
                return;
            }

            $.ajax({
                url: '/get-subcategories/' + catId,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    populateSubcategories(response, initialSubCategory);
                },
                error: function() {
                    $sub.empty().append('<option disabled selected>Select Subcategory</option>');
                }
            });
        })();

        // (optional) block Enter from submitting accidentally — keep your existing logic if you want this
        $('#form').on('keydown', 'input, select', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                return false;
            }
        });

    });
    
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