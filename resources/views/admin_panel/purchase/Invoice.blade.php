@extends('admin_panel.layout.app')

@section('content')
<style>
    .voucher-container {
        max-width: 900px;
        margin: 20px auto;
        background: #fff;
        padding: 30px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #333;
    }
    .voucher-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        border-bottom: 2px solid #333;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }
    .company-name {
        font-size: 28px;
        font-weight: 800;
        margin: 0;
        color: #000;
    }
    .voucher-title {
        font-size: 14px;
        color: #666;
        margin-top: 2px;
    }
    .voucher-meta {
        text-align: right;
    }
    .meta-item {
        font-size: 13px;
        margin-bottom: 2px;
    }
    .meta-label {
        font-weight: 700;
        min-width: 80px;
        display: inline-block;
    }

    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
        font-size: 13px;
    }
    .info-group {
        display: flex;
        flex-wrap: wrap;
    }
    .info-label {
        font-weight: 700;
        width: 100px;
        margin-bottom: 5px;
    }
    .info-value {
        flex: 1;
        border-bottom: 1px dotted #ccc;
        margin-bottom: 5px;
        padding-bottom: 2px;
    }

    .voucher-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
        font-size: 13px;
    }
    .voucher-table th {
        background-color: #f4f4f4;
        border: 1px solid #ddd;
        padding: 8px;
        text-align: center;
        font-weight: 700;
        text-transform: uppercase;
    }
    .voucher-table td {
        border: 1px solid #ddd;
        padding: 6px 8px;
        text-align: center;
    }
    .text-start { text-align: left !important; }
    .text-end { text-align: right !important; }

    .totals-section {
        display: grid;
        grid-template-columns: 1fr 300px;
        gap: 40px;
        font-size: 13px;
    }
    .allocations-box h6, .wht-box h6 {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 8px;
        border-bottom: 1px solid #eee;
        padding-bottom: 4px;
    }
    .totals-table {
        width: 100%;
    }
    .totals-table td {
        padding: 4px 0;
    }
    .totals-label {
        font-weight: 600;
        text-align: right;
        padding-right: 15px;
    }
    .totals-value {
        text-align: right;
        font-weight: 700;
        width: 120px;
        border-bottom: 1px solid #eee;
    }
    .net-amount-row td {
        font-size: 16px;
        color: #d32f2f;
        padding-top: 10px;
    }

    .signature-section {
        margin-top: 60px;
        display: flex;
        justify-content: space-between;
    }
    .signature-box {
        width: 200px;
        text-align: center;
        border-top: 1.5px solid #333;
        padding-top: 5px;
        font-size: 13px;
        font-weight: 600;
    }

    @media print {
        body { background: none; }
        .voucher-container { margin: 0; box-shadow: none; width: 100%; max-width: none; }
        .no-print { display: none !important; }
        .rt_nav_header, .sidebar, .footer { display: none !important; }
        .main-content { padding: 0 !important; margin: 0 !important; }
    }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-3">
            
            <div class="text-end mb-3 no-print">
                <button onclick="window.print()" class="btn btn-primary px-4 shadow-sm">
                    <i class="fa fa-print me-2"></i> Print Voucher
                </button>
            </div>

            <div class="voucher-container" id="printable-voucher">
                <!-- Header -->
                <div class="voucher-header">
                    <div>
                        <h1 class="company-name">Al-Madina Traders</h1>
                        <div class="voucher-title">Purchase Transaction Voucher</div>
                    </div>
                    <div class="voucher-meta">
                        <div class="meta-item"><span class="meta-label">INV ID:</span> {{ $purchase->invoice_no }}</div>
                        <div class="meta-item"><span class="meta-label">STATUS:</span> <span class="fw-bold">{{ strtoupper($purchase->status) }}</span></div>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="info-grid">
                    <div class="info-column">
                        <div class="info-group">
                            <div class="info-label">Date:</div>
                            <div class="info-value">{{ \Carbon\Carbon::parse($purchase->current_date)->format('Y-m-d') }}</div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Warehouse:</div>
                            <div class="info-value">{{ $purchase->warehouse->warehouse_name ?? 'Shop Stock' }}</div>
                        </div>
                    </div>
                    <div class="info-column">
                        <div class="info-group">
                            <div class="info-label">Supplier:</div>
                            <div class="info-value">{{ $purchase->vendor->name ?? 'N/A' }}</div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Phone:</div>
                            <div class="info-value">{{ $purchase->vendor->phone ?? '-' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Item Table -->
                <table class="voucher-table">
                    <thead>
                        <tr>
                            <th width="50">S#</th>
                            <th width="100">Item ID</th>
                            <th class="text-start">Product Description</th>
                            <th width="80">Qty</th>
                            <th width="100">Rate</th>
                            <th width="120">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchase->items as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $item->product_id }}</td>
                            <td class="text-start">{{ $item->product->name ?? 'N/A' }}</td>
                            <td>{{ number_format($item->qty, 0) }}</td>
                            <td class="text-end">{{ number_format($item->price, 2) }}</td>
                            <td class="text-end fw-bold">{{ number_format($item->line_total, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Bottom Section -->
                <div class="totals-section">
                    <div class="allocations-column">
                        <div class="allocations-box mb-3">
                            <h6>Account Allocations</h6>
                            <table class="table table-sm table-borderless small mb-0">
                                @forelse($purchase->accountAllocations as $alloc)
                                <tr>
                                    <td>{{ $alloc->account->title ?? 'N/A' }}</td>
                                    <td class="text-end fw-bold">{{ number_format($alloc->amount, 2) }}</td>
                                </tr>
                                @empty
                                <tr><td class="text-muted italic">No specific allocations found</td></tr>
                                @endforelse
                            </table>
                        </div>

                        @if($purchase->wht > 0)
                        <div class="wht-box">
                            <h6>WHT Details</h6>
                            <div class="small">
                                <div><strong>Account:</strong> {{ $purchase->whtAccount->title ?? 'N/A' }}</div>
                                <div><strong>Calculation:</strong> {{ $purchase->wht_type == 'percent' ? $purchase->wht_percent.'%' : 'Fixed PKR' }}</div>
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="summary-column">
                        <table class="totals-table">
                            <tr>
                                <td class="totals-label">Gross Total:</td>
                                <td class="totals-value">{{ number_format($purchase->subtotal, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="totals-label">Total Discount:</td>
                                <td class="totals-value">{{ number_format($purchase->discount, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="totals-label">WHT Addon:</td>
                                <td class="totals-value">+ {{ number_format($purchase->wht, 2) }}</td>
                            </tr>
                            <tr class="net-amount-row">
                                <td class="totals-label">Net Payable:</td>
                                <td class="totals-value">{{ number_format($purchase->net_amount, 2) }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Signatures -->
                <div class="signature-section">
                    <div class="signature-box">Prepared By</div>
                    <div class="signature-box">Approved By</div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection