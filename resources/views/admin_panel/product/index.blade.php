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
                                        <label class="column-picker-item"><input type="checkbox" data-column="12" checked> Net Amount</label>
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
                                            <th>Net Amount (PKR)</th>
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
                                                            <a href="{{ route('products.edit', $product->id) }}" class="dropdown-item py-2">
                                                                <i class="fa fa-edit me-2 text-warning"></i> Edit Product
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="javascript:void(0);" class="dropdown-item py-2 view-history-btn" data-product-id="{{ $product->id }}">
                                                                <i class="fa fa-history me-2 text-info"></i> Price History
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item py-2" href="/products/bulk-set-price?ids={{ $product->id }}">
                                                                <i class="fa fa-tag me-2 text-success"></i> Set New Price
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
                                        <option value="set-new-prices">Set New Price</option>
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

<!-- Price History Modal -->
<div class="modal fade" id="priceHistoryModal" tabindex="-1" role="dialog" aria-labelledby="priceHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="priceHistoryModalLabel">Price History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0" id="price-history-table">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>Pur. Retail</th>
                                <th>Pur. Tax (%) / (PKR)</th>
                                <th>Pur. Disc (%) / (PKR)</th>
                                <th>Sale Retail</th>
                                <th>Sale Tax (%) / (PKR)</th>
                                <th>Sale Disc (%) / (PKR)</th>
                                <th>WHT (Sale)</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                            </tr>
                        </thead>
                        <tbody id="price-history-tbody">
                            <!-- Table rows inserted via JS -->
                        </tbody>
                    </table>
                </div>
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
            scrollX: true,
            autoWidth: false,
            pageLength: 25,
            order: [[1, 'asc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search products..."
            }
        });

        // Price History Logic
        $(document).on('click', '.view-history-btn', function() {
            var productId = $(this).data('product-id');
            
            $.ajax({
                url: '/products/' + productId + '/prices',
                type: 'GET',
                success: function(response) {
                    const prices = response.prices;
                    const productName = response.product_name;

                    $('#priceHistoryModalLabel').text('Price History: ' + productName);

                    let rows = '';
                    if (prices && prices.length > 0) {
                        prices.forEach((price, index) => {
                            rows += `<tr>
                                <td class="text-center">${index + 1}</td>
                                <td class="text-end">${price.purchase_retail_price || '0'}</td>
                                <td class="text-center">
                                    ${price.purchase_tax_percent}% <br>
                                    <small class="text-muted">Rs. ${price.purchase_tax_amount || '0'}</small>
                                </td>
                                <td class="text-center">
                                    ${price.purchase_discount_percent}% <br>
                                    <small class="text-muted">Rs. ${price.purchase_discount_amount || '0'}</small>
                                </td>
                                <td class="text-end">${price.sale_retail_price || '0'}</td>
                                <td class="text-center">
                                    ${price.sale_tax_percent}% <br>
                                    <small class="text-muted">Rs. ${price.sale_tax_amount || '0'}</small>
                                </td>
                                <td class="text-center">
                                    ${price.sale_discount_percent}% <br>
                                    <small class="text-muted">Rs. ${price.sale_discount_amount || '0'}</small>
                                </td>
                                <td class="text-end">Rs. ${price.sale_wht_percent || '0'}</td>
                                <td class="text-center">${price.start_date}</td>
                                <td class="text-center">
                                    ${price.end_date 
                                        ? `<span class="text-danger small">${price.end_date}</span>` 
                                        : `<span class="badge bg-success">Active</span>`}
                                </td>
                            </tr>`;
                        });
                    } else {
                        rows = '<tr><td colspan="10" class="text-center">No price history found.</td></tr>';
                    }

                    $('#price-history-tbody').html(rows);
                    $('#priceHistoryModal').modal('show');
                },
                error: function() {
                    Swal.fire('Error', 'Failed to load price history.', 'error');
                }
            });
        });

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
                    if (action === 'set-new-prices') {
                        let idsString = selectedIds.join(',');
                        window.location.href = `/products/bulk-set-price?ids=${idsString}`;
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
