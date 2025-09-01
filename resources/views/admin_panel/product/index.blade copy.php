@extends('admin_panel.layout.app')
@section('content')

<div class="container-fluid mt-4">
    <div class="card shadow-sm border-0">
        <div class="card-header d-flex justify-content-between align-items-center bg-light">
            <div>
                <h5 class="mb-0">Product List</h5>
                <small class="text-muted">Manage Products</small>
            </div>
            <div>
                <a class="btn btn-primary" href="{{ route('products.create') }}">Add Product</a>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="default-datatable" class="table table-striped table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th><input type="checkbox"></th>
                            <th>#</th>
                            <th>Product Name</th>
                            <th>Description</th>
                            <th>Base Price (PKR)</th>
                            <th>Discount (%)</th>
                            <th>Discount (PKR)</th>
                            <th>Tax (%)</th>
                            <th>Tax (PKR)</th>
                            <th>WHT (%)</th>
                            <th>WHT (PKR)</th>
                            <th>Net Amount (PKR)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $index => $product)
                            @php
                                $price = $product->latest_price->sale_retail_price ?? 0;
                                $discountPercent = $product->latest_price->sale_discount_percent ?? 0;
                                $taxPercent = $product->latest_price->sale_tax_percent ?? 0;
                                $whtPercent = $product->latest_price->sale_wht_percent ?? 0;

                                $discountPKR = ($price * $discountPercent) / 100;
                                $taxPKR = ($price * $taxPercent) / 100;
                                $whtPKR = ($taxPKR * $whtPercent) / 100;

                                $netAmount = ($price + $taxPKR + $whtPKR - $discountPKR);
                            @endphp
                            <tr>
                                <td><input type="checkbox"></td>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->description }}</td>
                                <td>{{ number_format($price, 2) }}</td>
                                <td>{{ $discountPercent }}%</td>
                                <td>Rs. {{ number_format($discountPKR, 2) }}</td>
                                <td>{{ $taxPercent }}%</td>
                                <td>Rs. {{ number_format($taxPKR, 2) }}</td>
                                <td>{{ $whtPercent }}%</td>
                                <td>Rs. {{ number_format($whtPKR, 2) }}</td>
                                <td><strong>Rs. {{ number_format($netAmount, 2) }}</strong></td>
                                <td>
                                    <a class="btn btn-sm btn-primary" href="{{ route('products.edit', $product->id) }}">Set New Price</a>
                                    <button class="btn btn-sm btn-secondary view-history-btn" data-product-id="{{ $product->id }}">
                                        Price History
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <select>
            <option>Set new Price</option>
            <option>delete selected</option>
            <option>deacivate selected</option>
        </select>   
    </div>
</div>

<!-- Price History Modal -->
<div class="modal fade" id="priceHistoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Price History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="price-history-body">
                <p>Loading...</p>
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
<script>
$(document).ready(function () {
    $('#default-datatable').DataTable({
        pageLength: 10,
        lengthMenu: [5,10,25,50],
        order: [[0,'desc']]
    });

    $('.view-history-btn').click(function () {
        const productId = $(this).data('product-id');
        $('#price-history-body').html('<p>Loading...</p>');

        $.ajax({
            url: '/products/' + productId + '/prices',
            type: 'GET',
            success: function (res) {
                let html = '<table class="table table-bordered"><thead><tr><th>Date</th><th>Sale Price</th><th>Discount %</th><th>Net Amount</th></tr></thead><tbody>';
                res.prices.forEach(p => {
                    let discount = (p.sale_retail_price * p.sale_discount_percent/100).toFixed(2);
                    let tax = (p.sale_retail_price * p.sale_tax_percent/100).toFixed(2);
                    let wht = (tax * p.sale_wht_percent/100).toFixed(2);
                    let net = (p.sale_retail_price + parseFloat(tax) + parseFloat(wht) - parseFloat(discount)).toFixed(2);
                    html += `<tr>
                        <td>${p.created_at}</td>
                        <td>${p.sale_retail_price}</td>
                        <td>${p.sale_discount_percent}%</td>
                        <td>${net}</td>
                    </tr>`;
                });
                html += '</tbody></table>';
                $('#price-history-body').html(html);
                $('#priceHistoryModal').modal('show');
            },
            error: function () {
                $('#price-history-body').html('<p class="text-danger">Failed to load data.</p>');
            }
        });
    });
});
</script>
@endsection
