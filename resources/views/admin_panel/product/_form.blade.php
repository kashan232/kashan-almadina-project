@php
    $isEdit = isset($product) && $product;
    $price = $isEdit ? ($product->latestPrice ?? null) : null;
    $warehouseStockMap = $warehouseStockMap ?? [];
    $totalOpeningStock = old(
        'stock',
        $isEdit
            ? ((float) $product->stock + (float) collect($warehouseStockMap)->sum())
            : 0
    );
    $initialCategory = old('category', $isEdit ? $product->category_id : null);
    $initialSubCategory = old('sub_category', $isEdit ? $product->sub_category_id : null);
@endphp

<style>
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

    .page-wrapper { padding: 10px !important; }
    .content { padding: 0 !important; }
</style>

<form action="{{ $isEdit ? route('products.update', $product->id) : route('products.store') }}" method="POST" id="form">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="card shadow-sm">
        <div class="card-header py-2 d-flex justify-content-between align-items-center bg-white border-bottom">
            <h6 class="mb-0 fw-bold">
                <i class="fa {{ $isEdit ? 'fa-edit' : 'fa-plus-circle' }} me-1"></i>
                {{ $isEdit ? 'Edit Product' : 'Create Product' }}
            </h6>
            <div class="d-flex gap-2">
                <a href="javascript:void(0)" onclick="window.history.back()" class="btn btn-secondary btn-sm py-1 px-3">Back</a>
                <button type="submit" id="btnSave" class="btn btn-primary btn-sm py-1 px-4 btn-save">
                    {{ $isEdit ? 'Update Product' : 'Save Product' }}
                </button>
            </div>
        </div>

        <div class="card-body">
            @if (session()->has('success'))
            <div class="alert alert-success p-1 mb-2 small">
                {{ session('success') }}
            </div>
            @endif

            <div class="section-container mb-2">
                <h5 class="text-secondary">General Information</h5>
                <div class="row g-1">
                    <div class="col-md-3 mb-2">
                        <label class="form-label d-flex justify-content-between">
                            <span>Item Name <span class="text-danger">*</span></span>
                            @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
                        </label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $isEdit ? $product->name : '') }}" placeholder="Enter Name" required>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label d-flex justify-content-between">
                            <span>Category</span>
                            @error('category') <span class="text-danger small">{{ $message }}</span> @enderror
                        </label>
                        <select name="category" class="form-select @error('category') is-invalid @enderror" id="category-dropdown">
                            <option value="" selected disabled>Select</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" {{ (string) $initialCategory === (string) $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
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
                            @if($isEdit && !empty($subCategories))
                                @foreach($subCategories as $subCat)
                                    <option value="{{ $subCat->id }}" {{ (string) $initialSubCategory === (string) $subCat->id ? 'selected' : '' }}>{{ $subCat->name }}</option>
                                @endforeach
                            @endif
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
                                <option value="{{ $brand->id }}" {{ (string) old('brand', $isEdit ? $product->brand_id : '') === (string) $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label d-flex justify-content-between">
                            <span>Total Opening Stock</span>
                            @error('stock') <span class="text-danger small">{{ $message }}</span> @enderror
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control @error('stock') is-invalid @enderror" name="stock" id="total-stock-input" value="{{ $totalOpeningStock }}">
                            <span class="input-group-text bg-info text-white fw-bold" id="shop-stock-display" title="Net Shop Stock">0</span>
                        </div>
                        <small class="text-muted" style="font-size: 9px;">Remaining for Shop</small>
                    </div>
                    <div class="col-md-1 mb-2">
                        <label class="form-label d-flex justify-content-between">
                            <span>Alert Qty</span>
                            @error('alert_qty') <span class="text-danger small">{{ $message }}</span> @enderror
                        </label>
                        <input type="number" class="form-control @error('alert_qty') is-invalid @enderror" name="alert_qty" value="{{ old('alert_qty', $isEdit ? $product->alert_qty : '') }}">
                    </div>
                    <div class="col-md-1 mb-2">
                        <label class="form-label d-flex justify-content-between">
                            <span>Weight</span>
                            @error('weight') <span class="text-danger small">{{ $message }}</span> @enderror
                        </label>
                        <input type="text" name="weight" class="form-control @error('weight') is-invalid @enderror" value="{{ old('weight', $isEdit ? $product->weight : '') }}">
                    </div>
                    <input type="hidden" name="status" value="{{ old('status', $isEdit ? $product->status : 1) }}">
                </div>
            </div>

            <div class="section-container mb-2" style="background-color: #f8fafc; border: 1px solid #cbd5e1;">
                <h5 class="text-dark fw-bold border-bottom pb-2 mb-3" style="font-size: 14px;">
                    <i class="fa fa-warehouse me-2 text-primary"></i> Warehouse Opening Stock
                    <small class="text-muted fw-normal ms-2" style="font-size: 11px;">(Distribution from Total Stock)</small>
                </h5>
                <div class="row g-3">
                    @forelse($warehouses as $wh)
                    @php
                        $whQty = old('warehouse_stocks.' . $loop->index, $warehouseStockMap[$wh->id] ?? 0);
                    @endphp
                    <div class="col-md-3 col-sm-6">
                        <div class="p-2 border rounded bg-white shadow-sm hover-shadow-sm transition-all" style="border-left: 4px solid #3b82f6 !important;">
                            <label class="form-label text-truncate d-block mb-2 fw-bold text-dark" title="{{ $wh->warehouse_name }}" style="font-size: 12px;">
                                {{ $wh->warehouse_name }}
                            </label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-end-0"><i class="fa fa-boxes-stacked text-muted" style="font-size: 10px;"></i></span>
                                <input type="hidden" name="warehouse_ids[]" value="{{ $wh->id }}">
                                <input type="number" name="warehouse_stocks[]"
                                       class="form-control form-control-sm text-center fw-bold warehouse-stock-input"
                                       value="{{ $whQty }}"
                                       style="height: 36px; font-size: 15px; border-color: #e2e8f0;">
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="col-12 text-center py-3">
                        <div class="text-muted small p-3 border rounded bg-light border-dashed">
                            <i class="fa fa-info-circle me-1"></i> No warehouses found in the system.
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>

            <div class="row g-2">
                <div class="col-md-5">
                    <div class="section-container border-primary border-opacity-25 bg-soft-primary">
                        <h5 class="text-primary">Purchase Pricing</h5>
                        <div class="row g-1">
                            <div class="col-4 mb-2">
                                <label class="form-label d-flex justify-content-between">
                                    <span>Retail Price</span>
                                    @error('purchase_retail_price') <span class="text-danger small">{{ $message }}</span> @enderror
                                </label>
                                <input type="number" step="0.01" class="form-control @error('purchase_retail_price') is-invalid @enderror" name="purchase_retail_price" value="{{ old('purchase_retail_price', optional($price)->purchase_retail_price) }}">
                            </div>
                            <div class="col-4 mb-2">
                                <label class="form-label d-flex justify-content-between">
                                    <span>Tax (%)</span>
                                    @error('purchase_tax_percent') <span class="text-danger small">{{ $message }}</span> @enderror
                                </label>
                                <input type="number" step="0.01" class="form-control @error('purchase_tax_percent') is-invalid @enderror" name="purchase_tax_percent" value="{{ old('purchase_tax_percent', optional($price)->purchase_tax_percent) }}">
                            </div>
                            <div class="col-4 mb-2">
                                <label class="form-label">Tax Amt</label>
                                <input type="text" class="form-control bg-light" name="purchase_tax_amount" readonly tabindex="-1" value="{{ old('purchase_tax_amount', optional($price)->purchase_tax_amount) }}">
                            </div>
                            <div class="col-4 mb-2">
                                <label class="form-label d-flex justify-content-between">
                                    <span>Disc (%)</span>
                                    @error('purchase_discount_percent') <span class="text-danger small">{{ $message }}</span> @enderror
                                </label>
                                <input type="number" step="0.01" class="form-control @error('purchase_discount_percent') is-invalid @enderror" name="purchase_discount_percent" value="{{ old('purchase_discount_percent', optional($price)->purchase_discount_percent) }}">
                            </div>
                            <div class="col-4 mb-2">
                                <label class="form-label">Disc Amt</label>
                                <input type="text" class="form-control bg-light" name="purchase_discount_amount" readonly tabindex="-1" value="{{ old('purchase_discount_amount', optional($price)->purchase_discount_amount) }}">
                            </div>
                            <div class="col-4 mb-2">
                                <label class="form-label fw-bold text-primary">Net Purchase</label>
                                <input type="text" class="form-control fw-bold border-primary" name="purchase_net_amount" readonly tabindex="-1" value="{{ old('purchase_net_amount', optional($price)->purchase_net_amount) }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-7">
                    <div class="section-container border-success border-opacity-25 bg-soft-success">
                        <h5 class="text-success">Sale Pricing</h5>
                        <div class="row g-1">
                            <div class="col-3 mb-2">
                                <label class="form-label d-flex justify-content-between">
                                    <span>Retail Price</span>
                                    @error('sale_retail_price') <span class="text-danger small">{{ $message }}</span> @enderror
                                </label>
                                <input type="number" step="0.01" class="form-control @error('sale_retail_price') is-invalid @enderror" name="sale_retail_price" value="{{ old('sale_retail_price', optional($price)->sale_retail_price) }}">
                            </div>
                            <div class="col-3 mb-2">
                                <label class="form-label d-flex justify-content-between">
                                    <span>Tax (%)</span>
                                    @error('sale_tax_percent') <span class="text-danger small">{{ $message }}</span> @enderror
                                </label>
                                <input type="number" step="0.01" class="form-control @error('sale_tax_percent') is-invalid @enderror" name="sale_tax_percent" value="{{ old('sale_tax_percent', optional($price)->sale_tax_percent) }}">
                            </div>
                            <div class="col-3 mb-2">
                                <label class="form-label">Tax Amt</label>
                                <input type="text" class="form-control bg-light" name="sale_tax_amount" readonly tabindex="-1" value="{{ old('sale_tax_amount', optional($price)->sale_tax_amount) }}">
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
                                <input type="number" step="0.01" class="form-control @error('sale_wht_percent') is-invalid @enderror" name="sale_wht_percent" value="{{ old('sale_wht_percent', optional($price)->sale_wht_percent) }}">
                            </div>
                            <div class="col-3 mb-2">
                                <label class="form-label">WHT Amt</label>
                                <input type="text" class="form-control bg-light" name="sale_wht_amount" readonly tabindex="-1" value="{{ old('sale_wht_amount', optional($price)->sale_wht_amount) }}">
                            </div>
                            <div class="col-3 mb-2">
                                <label class="form-label d-flex justify-content-between">
                                    <span>Disc (%)</span>
                                    @error('sale_discount_percent') <span class="text-danger small">{{ $message }}</span> @enderror
                                </label>
                                <input type="number" step="0.01" class="form-control @error('sale_discount_percent') is-invalid @enderror" name="sale_discount_percent" value="{{ old('sale_discount_percent', optional($price)->sale_discount_percent) }}">
                            </div>
                            <div class="col-3 mb-2">
                                <label class="form-label">Disc Amt</label>
                                <input type="text" class="form-control bg-light" name="sale_discount_amount" readonly tabindex="-1" value="{{ old('sale_discount_amount', optional($price)->sale_discount_amount) }}">
                            </div>
                            <div class="col-12 mt-1">
                                <div class="d-flex align-items-center bg-white p-1 border rounded">
                                    <label class="form-label mb-0 me-3 fw-bold text-success">NET SALE PRICE:</label>
                                    <input type="text" class="form-control form-control-sm fw-bold border-success text-success" style="width: 150px;" name="sale_net_amount" readonly tabindex="-1" value="{{ old('sale_net_amount', optional($price)->sale_net_amount) }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
