@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">

                    {{-- Header --}}
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="fw-bold">
                            Inward Gatepass #{{ $gatepass->invoice_no }}
                        </h3>
                        <div>
                            <a href="{{ route('InwardGatepass.home') }}" class="btn btn-sm btn-secondary">Back</a>
                            <button onclick="window.print()" class="btn btn-sm btn-success">Print</button>
                            <a href="{{ route('InwardGatepass.pdf', $gatepass->id) }}" class="btn btn-sm btn-danger">PDF</a>
                        </div>
                    </div>

                    {{-- Gatepass Details --}}
                    <table class="table table-bordered mb-4">
                        <thead>
                            <tr>
                                <th colspan="4" class="text-center bg-light">Gatepass Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th style="width: 20%;">Branch</th>
                                <td style="width: 30%;">{{ $gatepass->branch->name ?? 'N/A' }}</td>
                                <th style="width: 20%;">Warehouse</th>
                                <td style="width: 30%;">{{ $gatepass->warehouse->warehouse_name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Vendor</th>
                                <td>{{ $gatepass->vendor->name ?? 'N/A' }}</td>
                                <th>Transport</th>
                                <td>{{ $gatepass->transport_name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Date</th>
                                <td>{{ \Carbon\Carbon::parse($gatepass->gatepass_date)->format('d M, Y') }}</td>
                                <th>Note</th>
                                <td>{{ $gatepass->remarks ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>

                    {{-- Items Table --}}
                    <table class="table table-bordered">
                        <thead>
                            <tr class="text-center bg-light">
                                <th style="width: 60px;">#</th>
                                <th>Product</th>
                                <th style="width: 150px;">Quantity</th>
                            </tr>
                        </thead>
                        <tbody class="text-center">
                            @forelse($gatepass->items as $i => $item)
                            <tr>
                                <td>{{ $i+1 }}</td>
                                <td>{{ $item->product->name ?? 'N/A' }}</td>
                                <td>{{ $item->qty }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-muted">No items found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>

                    {{-- Footer for Signatures --}}
                    <div class="row mt-5">
                        <div class="col-md-6 text-center">
                            <p>______________________</p>
                            <small>Received By</small>
                        </div>
                        <div class="col-md-6 text-center">
                            <p>______________________</p>
                            <small>Authorized By</small>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
