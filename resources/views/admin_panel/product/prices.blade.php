@extends('admin_panel.layout.app')
@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div class="page-title">
        <h4>Price History — {{ $product->name }}</h4>
    </div>
     <div class="page-btn">
        <a href="{{ route('products.index') }}" class="btn btn-added"><i class="fa fa-arrow-left"></i> Back</a>
    </div>
</div>

<div class="card mt-3">
    <div class="card-body">
        <div class="table-responsive">
            <table id="example"  class="display table table-bordered table-striped text-nowrap">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Purchase Retail</th>
                        <th>Purchase Tax (%) / (PKR)</th>
                        <th>Purchase Discount (%) / (PKR)</th>
                        <th>Sale Retail</th>
                        <th>Sale Tax (%) / (PKR)</th>
                        <th>Sale Discount (%) / (PKR)</th>
                        <th>WHT (%)</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($product->prices as $i => $price)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ number_format($price->purchase_retail_price ?? 0, 2) }}</td>
                        <td>
                            {{ $price->purchase_tax_percent ?? 0 }}% <br>
                            Rs. {{ number_format($price->purchase_tax_amount ?? 0, 2) }}
                        </td>
                        <td>
                            {{ $price->purchase_discount_percent ?? 0 }}% <br>
                            Rs. {{ number_format($price->purchase_discount_amount ?? 0, 2) }}
                        </td>
                        <td>{{ number_format($price->sale_retail_price ?? 0, 2) }}</td>
                        <td>
                            {{ $price->sale_tax_percent ?? 0 }}% <br>
                            Rs. {{ number_format($price->sale_tax_amount ?? 0, 2) }}
                        </td>
                        <td>
                            {{ $price->sale_discount_percent ?? 0 }}% <br>
                            Rs. {{ number_format($price->sale_discount_amount ?? 0, 2) }}
                        </td>
                        <td>{{ $price->sale_wht_percent ?? 0 }}%</td>
                        <td>{{ optional($price->start_date)->toDateString() ?? ($price->start_date ?? '-') }}</td>
                        <td>
                            @if($price->end_date)
                                <span class="text-danger fw-semibold">{{ $price->end_date }}</span>
                            @else
                                <span class="badge bg-success">Active</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center">No price history found for this product.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Optional: actions -->
        <div class="mt-3">
            <a href="{{ route('products.edit', $product->id) }}" class="btn btn-primary">Set New Price</a>
        </div>
    </div>
</div>
@endsection
