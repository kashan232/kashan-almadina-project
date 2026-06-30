@extends('admin_panel.layout.app')

@section('content')
<style>
    /* Table Responsive & Scroll Enhancements */
    .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 1rem;
    }
    
    #example thead th {
        white-space: nowrap;
        background-color: #f8f9fa;
        color: #333;
        font-weight: 600;
        vertical-align: middle;
    }
    
    #example tbody td {
        white-space: nowrap;
        vertical-align: middle;
    }

    /* Column Picker Styles */
    .column-picker-dropdown {
        position: relative;
        display: inline-block;
    }
    .column-picker-menu {
        position: absolute;
        top: 100%;
        right: 0;
        z-index: 1000;
        display: none;
        min-width: 200px;
        padding: 5px 0;
        margin: 2px 0 0;
        font-size: 14px;
        text-align: left;
        list-style: none;
        background-color: #fff;
        background-clip: padding-box;
        border: 1px solid rgba(0,0,0,.15);
        border-radius: 4px;
        box-shadow: 0 6px 12px rgba(0,0,0,.175);
        max-height: 400px;
        overflow-y: auto;
    }
    .column-picker-menu.show {
        display: block;
    }
    .column-picker-item {
        display: block;
        padding: 5px 15px;
        clear: both;
        font-weight: 400;
        line-height: 1.42857143;
        color: #333;
        white-space: nowrap;
        cursor: pointer;
    }
    .column-picker-item:hover {
        background-color: #f5f5f5;
    }
    .column-picker-item input {
        margin-right: 10px;
        cursor: pointer;
    }
    .column-hidden {
        display: none !important;
    }

    /* Card styling */
    .card {
        border-radius: 8px;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    
    .card-header {
        background-color: #fff;
        border-bottom: 1px solid #edf2f9;
    }

    #price-history-table th,
    #price-history-table td {
        white-space: nowrap;
    }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-4">
            <!-- Optional Filter Section (Can be expanded if needed) -->
            {{-- <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-3">
                            <form action="{{ route('products.index') }}" method="GET" class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted">Status</label>
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="">All Status</option>
                                        <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-sm px-4 rounded-pill">
                                            <i class="fa fa-filter me-1"></i> Filter
                                        </button>
                                        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm px-4 rounded-pill">
                                            <i class="fa fa-refresh me-1"></i> Reset
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div> --}}

            <div class="row">
                <div class="col-12">
                    <div class="card border-0">
                        <div class="card-header d-flex justify-content-between align-items-center py-3">
                            <h4 class="card-title mb-0 fw-bold text-dark">Product Management</h4>
                            <div class="d-flex gap-2">
                                <!-- Column Picker Button -->
                                <div class="column-picker-dropdown">
                                    <button class="btn btn-outline-secondary btn-sm px-3 rounded-pill" type="button" id="columnPickerBtn">
                                        <i class="fa fa-columns me-1"></i> Columns
                                    </button>
                                    <div class="column-picker-menu shadow" id="columnPickerMenu">
                                        <div class="p-2 border-bottom fw-bold small text-muted">Show/Hide Columns</div>
                                        <label class="column-picker-item"><input type="checkbox" data-column="1" checked> Select</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="2" checked> #</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="3" checked> Product Name</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="4" checked> Brand</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="5" checked> Weight</label>
                                        @if(auth()->user()->canAccessShop())
                                            <label class="column-picker-item"><input type="checkbox" data-column="6" checked> Stock</label>
                                        @endif
                                        <label class="column-picker-item"><input type="checkbox" data-column="7" checked> Base Price</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="8" checked> Disc (%)</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="9" checked> Disc (PKR)</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="10" checked> Tax (%)</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="11" checked> Tax (PKR)</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="12" checked> WHT (%)</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="13" checked> Sale Net Amount</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="14" checked> Status</label>
                                    </div>
                                </div>

                                <a class="btn btn-primary btn-sm px-4 rounded-pill" href="{{ route('products.create') }}">
                                    <i class="fa fa-plus me-1"></i> Add Product
                                </a>
                            </div>
                        </div>

                        <div class="card-body p-3">
                            @if (session()->has('success'))
                            <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
                                <strong>Success!</strong> {{ session('success') }}.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            @endif

                            <div class="table-responsive">
                                <table id="example" class="table table-striped table-bordered display w-100">
                                    <thead>
                                        <tr>
                                            <th>
                                                <input type="checkbox" id="select-all">
                                            </th>
                                            <th>#</th>
                                            <th>Product Name</th>
                                            <th>Brand</th>
                                            <th>Weight</th>
                                            @if(auth()->user()->canAccessShop())
                                                <th>Stock</th>
                                            @endif
                                            <th>Base Price (PKR)</th>
                                            <th>Discount (%)</th>
                                            <th>Discount (PKR)</th>
                                            <th>Tax (%)</th>
                                            <th>Tax (PKR)</th>
                                            <th>WHT (%)</th>
                                            <th>Sale Net Amount</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($products as $index => $product)
                                        <tr>
                                            <td class="text-center">
                                                <input type="checkbox" class="row-checkbox" value="{{ $product->id }}">
                                            </td>
                                            <td>{{ $index + 1 }}</td>
                                            <td class="fw-bold">{{ $product->name }}</td>
                                            <td>{{ $product->brandRelation->name ?? 'N/A' }}</td>
                                            <td>{{ $product->weight }}</td>
                                            @if(auth()->user()->canAccessShop())
                                                <td>{{ $product->stock }}</td>
                                            @endif
                                            <td class="text-end">{{ number_format($product->latestPrice->sale_retail_price ?? 0, 0) }}</td>
                                            <td class="text-center">{{ $product->latestPrice->sale_discount_percent ?? '0' }}%</td>
                                            <td class="text-end">{{ number_format($product->latestPrice->sale_discount_amount ?? 0, 0) }}</td>
                                            <td class="text-center">{{ $product->latestPrice->sale_tax_percent ?? '0' }}%</td>
                                            <td class="text-end">{{ number_format($product->latestPrice->sale_tax_amount ?? 0, 0) }}</td>
                                            <td class="text-center">{{ $product->latestPrice->sale_wht_percent ?? '0' }}%</td>
                                            <td class="text-end fw-bold">{{ number_format($product->latestPrice->sale_net_amount ?? 0, 0) }}</td>
                                            <td class="text-center">
                                                @if($product->status == 1)
                                                <span class="badge bg-success">Active</span>
                                                @else
                                                <span class="badge bg-danger">Inactive</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                        Actions
                                                    </button>
                                                    <ul class="dropdown-menu shadow border-0">
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item py-2 view-product-btn" data-product-id="{{ $product->id }}">
                                                                <i class="fa fa-eye me-2 text-primary"></i> View Product
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="{{ route('products.edit', $product->id) }}" class="dropdown-item py-2">
                                                                <i class="fa fa-edit me-2 text-warning"></i> Edit Product
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                             <a class="dropdown-item py-2" href="/products/bulk-set-price?type=purchase&ids={{ $product->id }}">
                                                                 <i class="fa fa-tag me-2 text-primary"></i> Set Purchase Price
                                                             </a>
                                                         </li>
                                                         <li>
                                                             <a class="dropdown-item py-2" href="/products/bulk-set-price?type=sale&ids={{ $product->id }}">
                                                                 <i class="fa fa-tag me-2 text-success"></i> Set Sale Price
                                                             </a>
                                                         </li>
                                                     </ul>
                                                 </div>
                                             </td>
                                         </tr>
                                         @endforeach
                                     </tbody>
                                 </table>
                             </div>

                             <div class="mt-4 pt-3 border-top">
                                 <div class="d-flex align-items-center gap-3">
                                     <label class="fw-bold text-muted small mb-0">Bulk Action:</label>
                                     <select id="bulk-action" class="form-select form-select-sm" style="width:200px;">
                                         <option value="">Select Action</option>
                                         <option value="set-purchase-prices">Set Purchase Price</option>
                                         <option value="set-sale-prices">Set Sale Price</option>
                                         <option value="delete">Delete Selected</option>
                                         <option value="deactivate">Deactivate Selected</option>
                                     </select>
                                 </div>
                             </div>
                         </div>
                     </div>
                 </div>
             </div>
         </div>
     </div>
 </div>

 {{-- Bulk Action Modal --}}
 <div class="modal fade" id="bulkConfirmModal" tabindex="-1" aria-labelledby="bulkConfirmModalLabel" aria-hidden="true">
     <div class="modal-dialog">
         <div class="modal-content border-0 shadow">
             <div class="modal-header bg-light">
                 <h5 class="modal-title fw-bold" id="bulkConfirmModalLabel">Confirm Bulk Action</h5>
                 <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
             </div>
             <div class="modal-body p-4">
                 <p class="mb-0">Are you sure you want to perform this action on the selected products?</p>
             </div>
             <div class="modal-footer border-0">
                 <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                 <button type="button" id="confirm-bulk-action" class="btn btn-primary rounded-pill px-4">Yes, Continue</button>
             </div>
         </div>
     </div>
 </div>

{{-- ===== VIEW PRODUCT MODAL ===== --}}
<div class="modal fade" id="viewProductModal" tabindex="-1" aria-labelledby="viewProductLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold" id="viewProductLabel">
                    <i class="fa fa-cube me-2 text-info"></i> <span id="modalProductName">Product Details</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                
                {{-- Pricing Summary Cards --}}
                <div class="row justify-content-center mb-4">
                    {{-- Sale Section Only --}}
                    <div class="col-lg-10">
                        <div class="card h-100 border-0 shadow-sm overflow-hidden text-white" style="background: linear-gradient(135deg, #198754 0%, #0d6efd 100%);">
                            <div class="card-header bg-transparent border-bottom border-white border-opacity-25 py-3">
                                <h6 class="mb-0 fs-5 fw-bold text-white text-center"><i class="fa fa-line-chart me-2"></i>Current Sale Details</h6>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-4">
                                    <div class="col-md-4">
                                        <div class="p-3 bg-white bg-opacity-10 rounded text-center h-100">
                                            <small class="text-white-50 d-block text-uppercase fw-bold mb-2">Retail Price</small>
                                            <span class="fw-bold fs-4 text-white" id="view_sale_retail">0.00</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="p-3 bg-white bg-opacity-10 rounded text-center h-100">
                                            <small class="text-white-50 d-block text-uppercase fw-bold mb-2">Tax % / Amt</small>
                                            <span class="fw-bold fs-4 text-white"><span id="view_sale_tax_pct">0%</span> <small class="text-white-50 fs-6">/ <span id="view_sale_tax_amt">0</span></small></span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="p-3 bg-white bg-opacity-10 rounded text-center h-100">
                                            <small class="text-white-50 d-block text-uppercase fw-bold mb-2">WHT % / Amt</small>
                                            <span class="fw-bold fs-4 text-white"><span id="view_sale_wht_pct">0%</span> <small class="text-white-50 fs-6">/ <span id="view_sale_wht_amt">0</span></small></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-3 bg-white bg-opacity-10 rounded text-center h-100">
                                            <small class="text-white-50 d-block text-uppercase fw-bold mb-2">Discount % / Amt</small>
                                            <span class="fw-bold fs-4 text-white"><span id="view_sale_disc_pct">0%</span> <small class="text-white-50 fs-6">/ <span id="view_sale_disc_amt">0</span></small></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-3 bg-white rounded text-center h-100 d-flex flex-column justify-content-center shadow-sm">
                                            <small class="text-success d-block text-uppercase fw-bold mb-2">Net Sale Value (Final)</small>
                                            <span class="fw-bold fs-3 text-success" id="view_sale_net">0.00</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Price History Log --}}
                <div class="card border-0 shadow-sm overflow-hidden mt-2">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-dark"><i class="fa fa-history me-2 text-warning"></i>Price Transaction Log</h6>
                        <span class="badge bg-light text-dark border px-3">Recent Changes First</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="view_history_table">
                                <thead class="bg-light text-muted small text-uppercase fw-bold">
                                    <tr>
                                        <th class="ps-3 py-3">Date Range</th>
                                        <th class="py-3">Retail Price</th>
                                        <th class="py-3">Taxes / WHT</th>
                                        <th class="py-3">Discounts</th>
                                        <th class="py-3">Sale Net</th>
                                        <th class="py-3 text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="view_history_tbody">
                                    {{-- Data will be injected here --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer bg-white py-3 border-top">
                <button type="button" class="btn btn-secondary rounded-pill px-4 shadow-sm" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

 @endsection

 @section('scripts')
 <script>
     $(document).ready(function() {
         // Toggle Column Picker Menu
         $('#columnPickerBtn').on('click', function(e) {
             e.stopPropagation();
             $('#columnPickerMenu').toggleClass('show');
         });

         // Close menu when clicking outside
         $(document).on('click', function(e) {
             if (!$(e.target).closest('.column-picker-dropdown').length) {
                 $('#columnPickerMenu').removeClass('show');
             }
         });

         // Initialize DataTable
         var table = $('#example').DataTable({
             destroy: true, // Allow re-initialization if already handled by global layout
             scrollX: true,
             autoWidth: false,
             pageLength: 25,
             order: [[1, 'asc']],
             language: {
                 search: "_INPUT_",
                 searchPlaceholder: "Search products..."
             }
         });

         // Column Persistence with LocalStorage
         const storageKey = 'product_table_columns_v1';
         
         // Load initial state
         const savedState = localStorage.getItem(storageKey);
         if (savedState) {
             const columns = JSON.parse(savedState);
             $('#columnPickerMenu input').each(function() {
                 const colIdx = parseInt($(this).data('column'));
                 if (columns.hasOwnProperty(colIdx)) {
                     const isChecked = columns[colIdx];
                     $(this).prop('checked', isChecked);
                     table.column(colIdx - 1).visible(isChecked);
                 }
             });
         }

         // Handle Checkbox Change
         $('#columnPickerMenu input').on('change', function() {
             const colIdx = parseInt($(this).data('column'));
             const isChecked = $(this).is(':checked');
             
             table.column(colIdx - 1).visible(isChecked);
             saveState();
             table.columns.adjust().draw(false);
         });

         function saveState() {
             const state = {};
             $('#columnPickerMenu input').each(function() {
                 state[$(this).data('column')] = $(this).is(':checked');
             });
             localStorage.setItem(storageKey, JSON.stringify(state));
         }

        // =============================================
        //  VIEW PRODUCT MODAL LOGIC
        // =============================================
        $(document).on('click', '.view-product-btn', function() {
            var productId = $(this).data('product-id');
            var $modal = $('#viewProductModal');
            
            $modal.modal('show');
            $('#view_history_tbody').html('<tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>');

            $.ajax({
                url: '/products/' + productId + '/prices',
                type: 'GET',
                success: function(res) {
                    $('#modalProductName').text((res.product_name || 'Product Details') + (res.brand_name && res.brand_name !== 'N/A' ? ' - ' + res.brand_name : ''));
                    
                    var history = res.prices || [];
                    var latest = history.length > 0 ? history[0] : null;

                    if (latest) {
                        // Current Details
                        $('#view_purchase_retail').text(parseFloat(latest.purchase_retail_price || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                        $('#view_purchase_tax_pct').text((latest.purchase_tax_percent || 0) + '%');
                        $('#view_purchase_tax_amt').text(parseFloat(latest.purchase_tax_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                        $('#view_purchase_disc_pct').text((latest.purchase_discount_percent || 0) + '%');
                        $('#view_purchase_disc_amt').text(parseFloat(latest.purchase_discount_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                        $('#view_purchase_net').text(parseFloat(latest.purchase_net_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));

                        var retail = parseFloat(latest.sale_retail_price || 0);
                        var wht_pct = parseFloat(latest.sale_wht_percent || 0);
                        var wht_amt = parseFloat(latest.sale_wht_amount || 0);
                        if (!wht_amt && wht_pct > 0) wht_amt = (retail * wht_pct) / 100;

                        $('#view_sale_retail').text(retail.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                        $('#view_sale_tax_pct').text((latest.sale_tax_percent || 0) + '%');
                        $('#view_sale_tax_amt').text(parseFloat(latest.sale_tax_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                        $('#view_sale_wht_pct').text(wht_pct + '%');
                        $('#view_sale_wht_amt').text(wht_amt.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                        $('#view_sale_disc_pct').text((latest.sale_discount_percent || 0) + '%');
                        $('#view_sale_disc_amt').text(parseFloat(latest.sale_discount_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                        $('#view_sale_net').text(parseFloat(latest.sale_net_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                    } else {
                        // Clear if no latest price
                        $('#view_purchase_retail, #view_purchase_tax_pct, #view_purchase_tax_amt, #view_purchase_disc_pct, #view_purchase_disc_amt, #view_purchase_net, #view_sale_retail, #view_sale_tax_pct, #view_sale_tax_amt, #view_sale_wht_pct, #view_sale_wht_amt, #view_sale_disc_pct, #view_sale_disc_amt, #view_sale_net').text('N/A');
                    }

                    // Log / History
                    var tbody = '';
                    if (history.length === 0) {
                        tbody = '<tr><td colspan="6" class="text-center py-4 text-muted">No price history found.</td></tr>';
                    } else {
                        history.forEach(function(p, i) {
                            var statusBadge = i === 0 
                                ? '<span class="badge bg-success rounded-pill px-3">Current</span>' 
                                : '<span class="badge bg-light text-muted border rounded-pill px-3">Expired</span>';
                            
                            var p_retail = parseFloat(p.sale_retail_price || 0);
                            var p_wht_pct = parseFloat(p.sale_wht_percent || 0);
                            var p_wht_amt = parseFloat(p.sale_wht_amount || 0);
                            if (!p_wht_amt && p_wht_pct > 0) p_wht_amt = (p_retail * p_wht_pct) / 100;

                            tbody += `
                                <tr class="${i === 0 ? 'bg-light-primary' : ''}">
                                    <td class="ps-3 py-3">
                                        <div class="fw-bold text-dark">${p.start_date || 'N/A'}</div>
                                        <small class="text-muted">${p.end_date ? 'to ' + p.end_date : 'present'}</small>
                                    </td>
                                    <td class="py-3 fw-bold text-dark">₨ ${p_retail.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                    <td class="py-3">
                                        <small class="d-block text-muted">Tax: ${p.sale_tax_percent || 0}% (₨ ${parseFloat(p.sale_tax_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})})</small>
                                        <small class="d-block text-muted">WHT: ${p_wht_pct}% (₨ ${p_wht_amt.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})})</small>
                                    </td>
                                    <td class="py-3">
                                        <small class="d-block text-muted">${p.sale_discount_percent || 0}% (₨ ${parseFloat(p.sale_discount_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})})</small>
                                    </td>
                                    <td class="py-3 fw-bold text-success">₨ ${parseFloat(p.sale_net_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                    <td class="py-3 text-center">${statusBadge}</td>
                                </tr>
                            `;
                        });
                    }
                    $('#view_history_tbody').html(tbody);
                },
                error: function() {
                    $('#view_history_tbody').html('<tr><td colspan="6" class="text-center py-4 text-danger">Failed to load data.</td></tr>');
                    $('#modalProductName').text('Error loading product');
                    $('#view_purchase_retail, #view_purchase_tax_pct, #view_purchase_tax_amt, #view_purchase_disc_pct, #view_purchase_disc_amt, #view_purchase_net, #view_sale_retail, #view_sale_tax_pct, #view_sale_tax_amt, #view_sale_wht_pct, #view_sale_wht_amt, #view_sale_disc_pct, #view_sale_disc_amt, #view_sale_net').text('N/A');
                }
            });
        });
        // End Modal Logic

         // Select All Checkbox
         $('#select-all').on('change', function() {
             $('.row-checkbox').prop('checked', $(this).is(':checked'));
         });

         // Bulk Action Logic
         $('#bulk-action').on('change', function() {
             let action = $(this).val();
             if (action) {
                 let selectedIds = $('.row-checkbox:checked').map(function() {
                     return $(this).val();
                 }).get();

                 if (selectedIds.length === 0) {
                     Swal.fire({
                         icon: 'error',
                         title: 'Validation Error',
                         text: 'Please select at least one product.',
                         timer: 3000,
                         showConfirmButton: false
                     });
                     $(this).val('');
                     return;
                 }

                 $('#bulkConfirmModal').modal('show');

                 $('#confirm-bulk-action').off('click').on('click', function() {
                     if (action === 'set-purchase-prices') {
                         let idsString = selectedIds.join(',');
                         window.location.href = `/products/bulk-set-price?type=purchase&ids=${idsString}`;
                     } else if (action === 'set-sale-prices') {
                         let idsString = selectedIds.join(',');
                         window.location.href = `/products/bulk-set-price?type=sale&ids=${idsString}`;
                     } else {
                         $.ajax({
                             url: "{{ route('products.bulkAction') }}",
                             method: "POST",
                             data: {
                                 _token: "{{ csrf_token() }}",
                                 action: action,
                                 ids: selectedIds
                             },
                             success: function(response) {
                                 Swal.fire({
                                     icon: 'success',
                                     title: 'Success',
                                     text: response.message,
                                     timer: 2000,
                                     showConfirmButton: false
                                 });
                                 $('#bulkConfirmModal').modal('hide');
                                 $('#bulk-action').val('');
                                 setTimeout(() => {
                                     location.reload();
                                 }, 2000);
                             },
                             error: function(xhr) {
                                 const res = xhr.responseJSON;
                                 Swal.fire({
                                     icon: 'error',
                                     title: 'Error',
                                     text: res.message || 'Something went wrong.',
                                     timer: 3000,
                                     showConfirmButton: false
                                 });
                             }
                         });
                     }
                 });
             }
         });
     });
 </script>
 @endsection
