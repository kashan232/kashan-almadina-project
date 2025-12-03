@extends('admin_panel.layout.app')
@section('content')
<style>
    #price-history-table th,
    #price-history-table td {
        white-space: nowrap;
    }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 ">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">Product List</h4>
                            <a class="btn btn-primary" href="{{ route('products.create') }}">
                                Add Product
                            </a>
                        </div>
                        <div class="card-body">
                            @if (session()->has('success'))
                            <div class="alert alert-success">
                                <strong>Success!</strong> {{ session('success') }}.
                            </div>
                            @endif
                            <div class="table-responsive">
                                <table id="example" class="display" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>
                                                <div class="mr-3">
                                                    <input type="checkbox" id="select-all">
                                                </div>
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
                                            <td>
                                                <input type="checkbox" class="row-checkbox " value="{{ $product->id }}">
                                            </td>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $product->name }}</td>
                                            <td>{{ $product->weight }}</td>
                                            <td>{{ $product->stock }}</td>
                                            <td>{{ $product->latestPrice->sale_retail_price ?? '0' }}</td>
                                            <td>Rs. {{ $product->latestPrice->purchase_discount_percent ?? '0' }}</td>
                                            <td>{{ $product->latestPrice->purchase_discount_amount ?? '0' }}</td>
                                            <td>{{ $product->latestPrice->sale_tax_percent ?? '0' }}%</td>
                                            <td>Rs. {{ $product->latestPrice->sale_tax_amount ?? '0' }}</td>
                                            <td>{{ $product->latestPrice->sale_wht_percent ?? '0' }}%</td>
                                            <td>Rs. {{ $product->latestPrice->sale_net_amount ?? '0' }}</td>
                                            <td>
                                                @if($product->status == 1)
                                                <span class="badge bg-success">Active</span>
                                                @else
                                                <span class="badge bg-danger">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                {{-- <a class="btn btn-primary btn-sm" href="{{ route('products.edit', $product->id) }}">Set New Price</a>
                                                <a href="javascript:void(0);"
                                                    class="btn btn-secondary btn-sm view-history-btn"
                                                    data-product-id="{{ $product->id }}">
                                                    Price History
                                                </a> --}}
                                                <div class="btn-group-vertical" role="group" aria-label="Vertical button group">
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-primary dropdown-toggle"
                                                            data-bs-toggle="dropdown" aria-expanded="false">
                                                            More <i class="fa-solid fa-ellipsis-vertical"></i>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li>
                                                                <a href="{{ route('products.edit', $product->id) }}" class="dropdown-item">
                                                                    <i class="fa fa-edit"></i> Edit Product
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a href="{{ route('products.prices', $product->id) }}" class="dropdown-item">
                                                                    Price History
                                                                </a>
                                                            </li>

                                                            <li>
                                                                <a class="dropdown-item" href="/products/bulk-set-price?ids={{ $product->id }}">
                                                                    Set New Price
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>

                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <div class="mt-3">
                                    <select id="bulk-action" class="form-control" style="width:200px;">
                                        <option value="">Select Bulk Action</option>
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
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkConfirmModalLabel">Confirm Bulk Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to perform this action on the selected products?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="confirm-bulk-action" class="btn btn-primary">Yes, Continue</button>
            </div>
        </div>
    </div>
</div>

<!-- Price History Modal -->
<div class="modal fade" id="priceHistoryModal" tabindex="-1" role="dialog" aria-labelledby="priceHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="priceHistoryModalLabel">Price History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            {{-- <div class="modal-body" id="price-history-body">
        <p>Loading...</p>
      </div> --}}
            <div class="modal-body modal-xl" id="price-history-body">
                <div id="price-history-content"></div>
            </div>
            <div class="modal-body" id="price-history-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped text-nowrap" id="price-history-table">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                {{-- <th>Retail Price</th> --}}
                                <th>Purchase Retail Price</th>
                                <th>
                                    Purchase Tax (%) <br>
                                    Purchase Tax (PKR)
                                </th>
                                {{-- <th>Purchase Tax (PKR)</th> --}}
                                <th>
                                    Purchase Discount (%) <br>
                                    Purchase Discount (PKR)
                                </th>
                                <th>Sale Retail Price</th>
                                <th>
                                    Sale Tax (%) <br>
                                    Sale Tax (PKR)
                                </th>
                                <th>
                                    Sale Discount (%) <br>
                                    Sale Discount (PKR)
                                </th>
                                <th>WHT (Sale Only)</th>
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
        $('.view-history-btn').click(function() {
            var productId = $(this).data('product-id');

            $.ajax({
                url: '/products/' + productId + '/prices',
                type: 'GET',
                success: function(html) {
                    $('#price-history-body').html(html);
                    $('#priceHistoryModal').modal('show');
                },
                error: function() {
                    $('#price-history-body').html('<p class="text-danger">Failed to load data.</p>');
                }
            });
        });

        $('#default-datatable').DataTable({
            pageLength: 10,
            lengthMenu: [5, 10, 25, 50, 100],
            order: [
                [0, 'desc']
            ],
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries"
            }
        });
    });


    $('.view-history-btn').click(function() {
        var productId = $(this).data('product-id');

        $.ajax({
            url: '/products/' + productId + '/prices',
            type: 'GET',
            success: function(response) {
                const prices = response.prices;
                const productName = response.product_name;

                $('#priceHistoryModalLabel').text('Price History for: ' + productName);

                let rows = '';
                prices.forEach((price, index) => {
                    const discountPKR = (price.price * price.discount_percent) / 100;
                    const taxPKR = (price.price * price.tax_percent) / 100;
                    const whtPKR = (price.price * price.wht_percent) / 100;

                    const netAmount = (price.price - discountPKR) + taxPKR;

                    rows += `<tr>
                    <td class="text-center">${index + 1}</td>
                    <td class="text-center">${price.purchase_retail_price || '0.00'}</td>
                    <td class="text-center">
                        ${price.purchase_tax_percent}%  <br>
                        Rs. ${price.purchase_tax_amount}
                    </td>
                    <td class="text-center">
                        ${price.purchase_discount_percent}% <br>
                        Rs. ${price.purchase_discount_amount}
                    </td>
                    <td class="text-center">${price.sale_retail_price || '0.00'}</td>
                    <td class="text-center">
                            ${price.sale_tax_percent}% <br>
                            Rs. ${price.sale_tax_amount}
                    </td>
                    <td class="text-center">
                        ${price.sale_discount_percent}% <br>
                        Rs. ${price.sale_discount_amount}
                    </td>
                    <td class="text-center">Rs. ${price.sale_wht_percent}</td>
                    <td class="text-center">${price.start_date}</td>
                    <td class="text-center">
                        ${price.end_date !== null 
                            ? `<span class="text-danger fw-semibold">${price.end_date}</span>` 
                            : `<span class="badge bg-success">Active</span>`}
                    </td>
                </tr>`;
                });

                $('#price-history-tbody').html(rows);
                $('#priceHistoryModal').modal('show');
            },
            error: function() {
                $('#price-history-content').html('<p class="text-danger">Failed to load price history.</p>');
            }
        });
    });

    // Bulk Action

    $('#select-all').on('change', function() {
        $('.row-checkbox').prop('checked', $(this).is(':checked'));
    });

    // When bulk action is chosen
    $('#bulk-action').on('change', function() {
        let action = $(this).val();
        if (action) {
            let selectedIds = $('.row-checkbox:checked').map(function() {
                return $(this).val();
            }).get();

            if (selectedIds.length === 0) {
                // alert('Please select at least one product.');
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    html: 'Please select at least one product.',
                    timer: 3000,
                    showConfirmButton: false
                });
                $(this).val('');
                return;
            }

            // Show confirmation modal
            $('#bulkConfirmModal').modal('show');

            // On confirm
            $('#confirm-bulk-action').off('click').on('click', function() {
                if (action === 'set-new-prices') {
                    let idsString = selectedIds.join(',');
                    window.location.href = `/products/bulk-set-price?ids=${idsString}`;
                } else {
                    // 🔥 Actual AJAX for delete or deactivate
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
</script>
@endsection