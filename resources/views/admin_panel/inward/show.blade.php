@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">

                  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Inward Gatepass #{{ $gatepass->id }}</h3>
    <div>
        <a href="{{ route('InwardGatepass.home') }}" class="btn btn-secondary">Back</a>
        <button onclick="window.print()" class="btn btn-success">Print</button>
        <a href="{{ route('InwardGatepass.pdf', $gatepass->id) }}" class="btn btn-danger">Download PDF</a>
    </div>
</div>

                    <div class="border shadow rounded p-4 mb-4" style="background-color: white;">
                        <h5>Gatepass Details</h5>
                        <table class="table table-bordered">
                            <tr>
                                <th>Branch</th>
                                <td>{{ $gatepass->branch->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Warehouse</th>
                                <td>{{ $gatepass->warehouse->warehouse_name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Vendor</th>
                                <td>{{ $gatepass->vendor->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Date</th>
                                <td>{{ $gatepass->gatepass_date }}</td>
                            </tr>
                            <tr>
                                <th>Note</th>
                                <td>{{ $gatepass->note ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>

                    <div class="border shadow rounded p-4" style="background-color: white;">
                        <h5>Items</h5>
                        <table class="table table-striped">
                            <thead class="text-center" style="background:#add8e6">
                                <tr>
                                    <th>#</th>
                                    <th>Product</th>
                                    <th>Qty</th>
                                </tr>
                            </thead>
                            <tbody class="text-center">
                                @foreach($gatepass->items as $i => $item)
                                    <tr>
                                        <td>{{ $i+1 }}</td>
                                        <td>{{ $item->product->item_name ?? 'N/A' }}</td>
                                        <td>{{ $item->qty }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
