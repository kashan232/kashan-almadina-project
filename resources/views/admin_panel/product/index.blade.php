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
                                        <label class="column-picker-item"><input type="checkbox" data-column="4" checked> Weight</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="5" checked> Stock</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="6" checked> Base Price</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="7" checked> Disc (%)</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="8" checked> Disc (PKR)</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="9" checked> Tax (%)</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="10" checked> Tax (PKR)</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="11" checked> WHT (%)</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="12" checked> Sale Net Amount</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="13" checked> Status</label>
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
                                            <th>Weight</th>
                                            <th>Stock</th>
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
                                            <td>{{ $product->weight }}</td>
                                            <td>{{ $product->stock }}</td>
                                            <td class="text-end">{{ number_format($product->latestPrice->sale_retail_price ?? 0, 0) }}</td>
                                            <td class="text-center">{{ $product->latestPrice->purchase_discount_percent ?? '0' }}%</td>
                                            <td class="text-end">{{ number_format($product->latestPrice->purchase_discount_amount ?? 0, 0) }}</td>
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
                <div class="row g-4 mb-4">
                    {{-- Purchase Section --}}
                    <div class="col-lg-6">
                        <div class="card h-100 border-0 shadow-sm overflow-hidden">
                            <div class="card-header bg-primary text-white py-2">
                                <h6 class="mb-0 fs-14 fw-bold"><i class="fa fa-shopping-cart me-2"></i>Current Purchase Details</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-4">
                                        <div class="p-2 border-start border-primary border-4 bg-light rounded">
                                            <small class="text-muted d-block text-uppercase fw-bold" style="font-size:10px;">Retail Price</small>
                                            <span class="fw-bold fs-15" id="view_purchase_retail">0.00</span>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2 border-start border-info border-4 bg-light rounded">
                                            <small class="text-muted d-block text-uppercase fw-bold" style="font-size:10px;">Tax % / Amt</small>
                                            <span class="fw-bold fs-15"><span id="view_purchase_tax_pct">0%</span> <small class="text-muted">/ <span id="view_purchase_tax_amt">0</span></small></span>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2 border-start border-warning border-4 bg-light rounded">
                                            <small class="text-muted d-block text-uppercase fw-bold" style="font-size:10px;">Disc % / Amt</small>
                                            <span class="fw-bold fs-15"><span id="view_purchase_disc_pct">0%</span> <small class="text-muted">/ <span id="view_purchase_disc_amt">0</span></small></span>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="p-2 border-start border-success border-4 bg-info-subtle rounded text-center">
                                            <small class="text-muted d-block text-uppercase fw-bold" style="font-size:10px;">Net Purchase Cost (Final)</small>
                                            <span class="fw-bold fs-18 text-primary" id="view_purchase_net">0.00</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Sale Section --}}
                    <div class="col-lg-6">
                        <div class="card h-100 border-0 shadow-sm overflow-hidden text-white" style="background: linear-gradient(135deg, #198754 0%, #0d6efd 100%);">
                            <div class="card-header bg-transparent border-bottom border-white border-opacity-25 py-2">
                                <h6 class="mb-0 fs-14 fw-bold text-white"><i class="fa fa-line-chart me-2"></i>Current Sale Details</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-4">
                                        <div class="p-2 bg-white bg-opacity-10 rounded">
                                            <small class="text-white-50 d-block text-uppercase fw-bold" style="font-size:10px;">Retail Price</small>
                                            <span class="fw-bold fs-15 text-white" id="view_sale_retail">0.00</span>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2 bg-white bg-opacity-10 rounded">
                                            <small class="text-white-50 d-block text-uppercase fw-bold" style="font-size:10px;">Tax % / Amt</small>
                                            <span class="fw-bold fs-15 text-white"><span id="view_sale_tax_pct">0%</span> <small class="text-white-50">/ <span id="view_sale_tax_amt">0</span></small></span>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2 bg-white bg-opacity-10 rounded">
                                            <small class="text-white-50 d-block text-uppercase fw-bold" style="font-size:10px;">WHT % / Amt</small>
                                            <span class="fw-bold fs-15 text-white"><span id="view_sale_wht_pct">0%</span> <small class="text-white-50">/ <span id="view_sale_wht_amt">0</span></small></span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-2 bg-white bg-opacity-10 rounded">
                                            <small class="text-white-50 d-block text-uppercase fw-bold" style="font-size:10px;">Disc % / Amt</small>
                                            <span class="fw-bold fs-15 text-white"><span id="view_sale_disc_pct">0%</span> <small class="text-white-50">/ <span id="view_sale_disc_amt">0</span></small></span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-2 bg-white rounded text-center">
                                            <small class="text-success d-block text-uppercase fw-bold" style="font-size:10px;">Net Sale Value (Final)</small>
                                            <span class="fw-bold fs-18 text-success" id="view_sale_net">0.00</span>
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
                                        <th class="py-3">Pur. Net</th>
                                        <th class="py-3">Sale Net</th>
                                        <th class="py-3">Taxes</th>
                                        <th class="py-3">Discounts</th>
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

         // Column Persistence with LocalStorage
         const storageKey = 'product_table_columns_v1';
         
         // Load initial state
         const savedState = localStorage.getItem(storageKey);
         if (savedState) {
             const columns = JSON.parse(savedState);
             $('#columnPickerMenu input').each(function() {
                 const colIdx = $(this).data('column');
                 if (columns.hasOwnProperty(colIdx)) {
                     $(this).prop('checked', columns[colIdx]);
                     toggleColumn(colIdx, columns[colIdx]);
                 }
             });
         }

         // Handle Checkbox Change
         $('#columnPickerMenu input').on('change', function() {
             const colIdx = $(this).data('column');
             const isChecked = $(this).is(':checked');
             
             toggleColumn(colIdx, isChecked);
             saveState();
         });

         function toggleColumn(index, show) {
             const table = $('#example');
             const cells = table.find(`th:nth-child(${index}), td:nth-child(${index})`);
             if (show) {
                 cells.removeClass('column-hidden');
             } else {
                 cells.addClass('column-hidden');
             }
         }

         function saveState() {
             const state = {};
             $('#columnPickerMenu input').each(function() {
                 state[$(this).data('column')] = $(this).is(':checked');
             });
             localStorage.setItem(storageKey, JSON.stringify(state));
         }

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
                    $('#modalProductName').text(res.product_name || 'Product Details');
                    
                    var history = res.prices || [];
                    var latest = history.length > 0 ? history[0] : null;

                    if (latest) {
                        // Current Details
                        $('#view_purchase_retail').text(parseFloat(latest.purchase_retail_price || 0).toLocaleString());
                        $('#view_purchase_tax_pct').text((latest.purchase_tax_percent || 0) + '%');
                        $('#view_purchase_tax_amt').text(parseFloat(latest.purchase_tax_amount || 0).toLocaleString());
                        $('#view_purchase_disc_pct').text((latest.purchase_discount_percent || 0) + '%');
                        $('#view_purchase_disc_amt').text(parseFloat(latest.purchase_discount_amount || 0).toLocaleString());
                        $('#view_purchase_net').text(parseFloat(latest.purchase_net_amount || 0).toLocaleString());

                        $('#view_sale_retail').text(parseFloat(latest.sale_retail_price || 0).toLocaleString());
                        $('#view_sale_tax_pct').text((latest.sale_tax_percent || 0) + '%');
                        $('#view_sale_tax_amt').text(parseFloat(latest.sale_tax_amount || 0).toLocaleString());
                        $('#view_sale_wht_pct').text((latest.sale_wht_percent || 0) + '%');
                        $('#view_sale_wht_amt').text(parseFloat(latest.sale_wht_amount || 0).toLocaleString());
                        $('#view_sale_disc_pct').text((latest.sale_discount_percent || 0) + '%');
                        $('#view_sale_disc_amt').text(parseFloat(latest.sale_discount_amount || 0).toLocaleString());
                        $('#view_sale_net').text(parseFloat(latest.sale_net_amount || 0).toLocaleString());
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
                            
                            tbody += `
                                <tr class="${i === 0 ? 'bg-light-primary' : ''}">
                                    <td class="ps-3 py-3">
                                        <div class="fw-bold text-dark">${p.start_date || 'N/A'}</div>
                                        <small class="text-muted">${p.end_date ? 'to ' + p.end_date : 'present'}</small>
                                    </td>
                                    <td class="py-3 fw-bold text-primary">₨ ${parseFloat(p.purchase_net_amount || 0).toLocaleString()}</td>
                                    <td class="py-3 fw-bold text-success">₨ ${parseFloat(p.sale_net_amount || 0).toLocaleString()}</td>
                                    <td class="py-3">
                                        <small class="d-block text-muted">Pur: ${p.purchase_tax_percent || 0}%</small>
                                        <small class="d-block text-muted">Sale: ${p.sale_tax_percent || 0}%</small>
                                    </td>
                                    <td class="py-3">
                                        <small class="d-block text-muted">Pur: ${p.purchase_discount_percent || 0}%</small>
                                        <small class="d-block text-muted">Sale: ${p.sale_discount_percent || 0}%</small>
                                    </td>
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
