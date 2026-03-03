@extends('admin_panel.layout.app')

@section('content')
<style>
    .view-card {
        border-radius: 15px;
        overflow: hidden;
    }
    .view-header {
        background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
        color: white;
        padding: 25px;
    }
    .info-label {
        font-size: 12px;
        text-transform: uppercase;
        font-weight: 700;
        color: #64748b;
        margin-bottom: 4px;
    }
    .info-value {
        font-size: 15px;
        font-weight: 600;
        color: #1e293b;
    }
    .flow-container {
        background: #f8fafc;
        border-radius: 12px;
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 30px;
        border: 1px solid #e2e8f0;
    }
    .flow-node {
        text-align: center;
        flex: 1;
    }
    .flow-arrow {
        font-size: 24px;
        color: #3b82f6;
        animation: slideRight 1.5s infinite;
    }
    @keyframes slideRight {
        0% { transform: translateX(-5px); opacity: 0.5; }
        50% { transform: translateX(5px); opacity: 1; }
        100% { transform: translateX(-5px); opacity: 0.5; }
    }
    .table-custom thead th {
        background-color: #f1f5f9;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.05em;
        color: #475569;
        border-top: none;
    }
    .total-section {
        background: #fdfdfd;
        border-top: 2px solid #e2e8f0;
    }
    @media print {
        .main-content { padding: 0 !important; }
        .btn-area { display: none !important; }
        .view-card { box-shadow: none !important; border: none !important; }
        .view-header { background: #000 !important; color: white !important; -webkit-print-color-adjust: exact; }
    }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-4">

            {{-- Action Buttons --}}
            <div class="d-flex justify-content-between align-items-center mb-4 btn-area">
                <a href="{{ route('stock_transfers.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
                    <i class="fa fa-arrow-left me-2"></i> Back to List
                </a>
                <div class="d-flex gap-2">
                    <a href="{{ route('stock_transfers.print', $transfers->id) }}" target="_blank" class="btn btn-dark rounded-pill px-4">
                        <i class="fa fa-print me-2"></i> Print Voucher
                    </a>
                    @if($transfers->status != 'Posted')
                    <a href="{{ route('stock_transfers.edit', $transfers->id) }}" class="btn btn-warning rounded-pill px-4">
                        <i class="fa fa-pencil me-2"></i> Edit Transfer
                    </a>
                    @endif
                    <a href="{{ route('stock_transfers.create') }}" class="btn btn-primary rounded-pill px-4">
                        <i class="fa fa-plus me-2"></i> New Transfer
                    </a>
                </div>
            </div>

            <div class="card view-card shadow-sm border-0 mb-5">
                {{-- Dynamic Header --}}
                <div class="view-header d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1 fw-bold">Stock Transfer Voucher</h3>
                        <p class="mb-0 opacity-75">Tracking ID: #{{ $transfers->id }}</p>
                    </div>
                    <div class="text-end">
                        <span class="badge rounded-pill px-4 py-2 shadow-sm {{ $transfers->status == 'Posted' ? 'bg-success' : 'bg-warning text-dark' }}" style="font-size: 14px;">
                            <i class="fa {{ $transfers->status == 'Posted' ? 'fa-check-circle' : 'fa-pencil-square' }} me-2"></i>
                            {{ strtoupper($transfers->status) }}
                        </span>
                    </div>
                </div>

                <div class="card-body p-4">
                    {{-- Transfer Visualization --}}
                    <div class="flow-container mb-5">
                        <div class="flow-node">
                            <div class="info-label">Source Location</div>
                            <div class="info-value text-primary" style="font-size: 18px;">
                                <i class="fa {{ $transfers->from_shop ? 'fa-shopping-cart' : 'fa-building' }} me-2"></i>
                                {{ $transfers->from_shop ? 'Main Shop' : ($transfers->fromWarehouse->warehouse_name ?? 'Unknown') }}
                            </div>
                        </div>
                        <div class="flow-arrow">
                            <i class="fa fa-long-arrow-right"></i>
                        </div>
                        <div class="flow-node">
                            <div class="info-label">Destination Location</div>
                            <div class="info-value text-success" style="font-size: 18px;">
                                <i class="fa {{ $transfers->to_shop ? 'fa-shopping-cart' : 'fa-building' }} me-2"></i>
                                {{ $transfers->to_shop ? 'Main Shop' : ($transfers->toWarehouse->warehouse_name ?? 'Unknown') }}
                            </div>
                        </div>
                    </div>

                    {{-- Metadata Grid --}}
                    <div class="row g-4 mb-5">
                        <div class="col-md-3">
                            <div class="info-label">Voucher Date</div>
                            <div class="info-value">{{ \Carbon\Carbon::parse($transfers->created_at)->format('d F, Y') }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-label">Time</div>
                            <div class="info-value">{{ \Carbon\Carbon::parse($transfers->created_at)->format('h:i A') }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-label">Prepared By</div>
                            <div class="info-value">
                                <i class="fa fa-user-circle-o me-1"></i>
                                {{ $transfers->creator->name ?? 'System' }}
                            </div>
                        </div>
                        <div class="col-md-3 text-md-end">
                            <div class="info-label">Total Quantity</div>
                            <div class="info-value text-primary" style="font-size: 20px;">
                                {{ number_format($transfers->items->sum('quantity'), 2) }}
                            </div>
                        </div>
                    </div>

                    {{-- Items Table --}}
                    <div class="table-responsive">
                        <table class="table table-custom border">
                            <thead>
                                <tr>
                                    <th class="ps-3" style="width: 60px;">S#</th>
                                    <th style="width: 120px;">Item ID</th>
                                    <th>Product Description</th>
                                    <th class="text-center" style="width: 150px;">Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transfers->items as $idx => $item)
                                <tr>
                                    <td class="ps-3 fw-bold text-muted">{{ $idx + 1 }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $item->product_id }}</span></td>
                                    <td class="fw-semibold">{{ $item->product->name ?? 'N/A' }}</td>
                                    <td class="text-center fw-bold text-primary">{{ number_format($item->quantity, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Remarks Section --}}
                    @if($transfers->remarks)
                    <div class="mt-4 p-3 rounded" style="background: #fffcf0; border-left: 4px solid #facc15;">
                        <div class="info-label" style="color: #854d0e;">REMARKS / NOTES</div>
                        <div class="info-value" style="color: #713f12;">{{ $transfers->remarks }}</div>
                    </div>
                    @endif

                    {{-- Footer/Approvals --}}
                    <div class="row mt-5 pt-5 text-center">
                        <div class="col-md-4">
                            <div style="border-top: 1px solid #e2e8f0; width: 80%; margin: auto; padding-top: 8px;" class="info-label">Prepared By</div>
                        </div>
                        <div class="col-md-4">
                            <div style="border-top: 1px solid #e2e8f0; width: 80%; margin: auto; padding-top: 8px;" class="info-label">Warehouse In-Charge</div>
                        </div>
                        <div class="col-md-4">
                            <div style="border-top: 1px solid #e2e8f0; width: 80%; margin: auto; padding-top: 8px;" class="info-label">Authorized Signature</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection