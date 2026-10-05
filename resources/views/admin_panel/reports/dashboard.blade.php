@extends('admin_panel.layout.app')
@section('content')

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<style>
    :root {
        --dash-bg: #f4f6fb;
        --dash-card-bg: #ffffff;
        --dash-text-dark: #1e293b;
        --dash-text-muted: #64748b;
        --dash-primary: #4f46e5;
        --dash-success: #10b981;
        --dash-warning: #f59e0b;
        --dash-danger: #ef4444;
        --dash-info: #06b6d4;
        --dash-purple: #8b5cf6;
    }

    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        background-color: var(--dash-bg);
        color: var(--dash-text-dark);
    }

    .dash-hero {
        background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);
        border-radius: 20px;
        padding: 32px;
        color: white;
        margin-bottom: 28px;
        box-shadow: 0 20px 25px -5px rgba(49, 46, 129, 0.15), 0 8px 10px -6px rgba(49, 46, 129, 0.1);
        position: relative;
        overflow: hidden;
    }

    .dash-hero::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 400px;
        height: 400px;
        background: radial-gradient(circle, rgba(99, 102, 241, 0.25) 0%, rgba(255, 255, 255, 0) 70%);
        border-radius: 50%;
        pointer-events: none;
    }

    .dash-hero-title {
        font-size: 26px;
        font-weight: 800;
        letter-spacing: -0.5px;
    }

    .dash-hero-sub {
        font-size: 14px;
        color: #c7d2fe;
        font-weight: 500;
    }

    .kpi-card {
        background: var(--dash-card-bg);
        border-radius: 16px;
        padding: 20px 22px;
        height: 100%;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03), 0 2px 4px -2px rgba(0, 0, 0, 0.03);
        transition: all 0.25s ease;
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .kpi-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 20px -5px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
        border-color: #cbd5e1;
    }

    .kpi-icon-wrapper {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
    }

    .kpi-icon-primary { background: #e0e7ff; color: #4338ca; }
    .kpi-icon-success { background: #d1fae5; color: #047857; }
    .kpi-icon-warning { background: #fef3c7; color: #b45309; }
    .kpi-icon-danger  { background: #fee2e2; color: #b91c1c; }
    .kpi-icon-info    { background: #cff4fc; color: #0891b2; }
    .kpi-icon-purple  { background: #f3e8ff; color: #6b21a8; }

    .kpi-val {
        font-size: 26px;
        font-weight: 800;
        color: var(--dash-text-dark);
        letter-spacing: -0.5px;
        line-height: 1.2;
        margin-top: 14px;
        margin-bottom: 4px;
    }

    .kpi-lbl {
        font-size: 13px;
        font-weight: 600;
        color: var(--dash-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .dash-card {
        background: var(--dash-card-bg);
        border-radius: 16px;
        padding: 24px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);
        margin-bottom: 24px;
    }

    .dash-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f1f5f9;
    }

    .dash-card-title {
        font-size: 16px;
        font-weight: 700;
        color: var(--dash-text-dark);
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
    }

    .btn-time-filter {
        font-size: 12px;
        font-weight: 600;
        padding: 5px 12px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #475569;
        transition: all 0.2s;
    }

    .btn-time-filter.active, .btn-time-filter:hover {
        background: var(--dash-primary);
        color: white;
        border-color: var(--dash-primary);
    }

    .table-modern {
        width: 100%;
        margin: 0;
    }

    .table-modern thead th {
        background: #f8fafc;
        color: #475569;
        font-weight: 700;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 16px;
        border-bottom: 1px solid #e2e8f0;
    }

    .table-modern tbody td {
        padding: 14px 16px;
        font-size: 13px;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .table-modern tbody tr:last-child td {
        border-bottom: none;
    }

    .table-modern tbody tr:hover {
        background-color: #f8fafc;
    }

    .badge-pill-custom {
        font-size: 11px;
        font-weight: 700;
        padding: 5px 10px;
        border-radius: 20px;
    }
</style>

<div class="main-content">
    <div class="container-fluid p-4">
        
        <!-- Hero Header -->
        <div class="dash-hero">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2 class="dash-hero-title mb-1">Business Intelligence Dashboard</h2>
                    <p class="dash-hero-sub mb-0"><i class="bi bi-shield-check me-1"></i> Real-time analytics, revenue performance & stock metrics</p>
                </div>
                <div>
                    <span class="badge bg-white text-dark px-3 py-2 rounded-pill shadow-sm fw-bold" style="font-size: 12px;">
                        <i class="bi bi-calendar3 me-1 text-primary"></i> {{ date('F d, Y') }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Quick Summary Row (Sales & Revenue) -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="kpi-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="kpi-lbl">Total Revenue</span>
                        <div class="kpi-icon-wrapper kpi-icon-success">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                    </div>
                    <div class="kpi-val">Rs. {{ number_format($stats['total_sales_amount'], 0) }}</div>
                    <div class="small text-muted fw-semibold"><i class="bi bi-receipt me-1"></i>{{ $stats['total_sales'] }} Orders Processed</div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="kpi-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="kpi-lbl">Today Sales</span>
                        <div class="kpi-icon-wrapper kpi-icon-primary">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                    </div>
                    <div class="kpi-val">Rs. {{ number_format($stats['today_sales_amount'], 0) }}</div>
                    <div class="small text-muted fw-semibold"><i class="bi bi-cart-check me-1"></i>{{ $stats['today_sales'] }} Sales Today</div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="kpi-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="kpi-lbl">Total Purchases</span>
                        <div class="kpi-icon-wrapper kpi-icon-purple">
                            <i class="bi bi-bag-check-fill"></i>
                        </div>
                    </div>
                    <div class="kpi-val">Rs. {{ number_format($stats['total_purchase_amount'], 0) }}</div>
                    <div class="small text-muted fw-semibold"><i class="bi bi-truck me-1"></i>{{ $stats['total_purchases'] }} Purchase Orders</div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="kpi-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="kpi-lbl">Pending Receivables</span>
                        <div class="kpi-icon-wrapper kpi-icon-danger">
                            <i class="bi bi-exclamation-octagon-fill"></i>
                        </div>
                    </div>
                    <div class="kpi-val">Rs. {{ number_format($stats['pending_payments'], 0) }}</div>
                    <div class="small text-muted fw-semibold"><i class="bi bi-people me-1"></i>{{ $stats['total_customers'] }} Total Customers</div>
                </div>
            </div>
        </div>

        <!-- Secondary KPIs (Operations & Inventory) -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="kpi-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="kpi-lbl">Total Products</span>
                        <div class="kpi-icon-wrapper kpi-icon-info">
                            <i class="bi bi-box-seam-fill"></i>
                        </div>
                    </div>
                    <div class="kpi-val">{{ number_format($stats['total_products']) }}</div>
                    <div class="small text-muted fw-semibold">Active Inventory SKUs</div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="kpi-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="kpi-lbl">Inward Gatepasses</span>
                        <div class="kpi-icon-wrapper kpi-icon-warning">
                            <i class="bi bi-arrow-down-left-square-fill"></i>
                        </div>
                    </div>
                    <div class="kpi-val">{{ $stats['total_inward'] }}</div>
                    <div class="small text-muted fw-semibold">
                        <span class="text-success fw-bold">{{ $stats['inward_with_bills'] }} Linked</span> | 
                        <span class="text-warning fw-bold">{{ $stats['inward_pending_bills'] }} Pending</span>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="kpi-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="kpi-lbl">Total Vendors</span>
                        <div class="kpi-icon-wrapper kpi-icon-primary">
                            <i class="bi bi-building"></i>
                        </div>
                    </div>
                    <div class="kpi-val">{{ $stats['total_vendors'] }}</div>
                    <div class="small text-muted fw-semibold">Registered Suppliers</div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="kpi-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="kpi-lbl">Stock Holds</span>
                        <div class="kpi-icon-wrapper kpi-icon-danger">
                            <i class="bi bi-pause-circle-fill"></i>
                        </div>
                    </div>
                    <div class="kpi-val">{{ $stats['total_stock_holds'] }}</div>
                    <div class="small text-muted fw-semibold">Pending Stock Hold Items</div>
                </div>
            </div>
        </div>

        <!-- Sales & Purchases Trend Charts -->
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h3 class="dash-card-title text-primary">
                            <i class="bi bi-graph-up-arrow"></i> Sales Performance Trend
                        </h3>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn-time-filter active" onclick="updateSalesChart('daily')">Daily</button>
                            <button type="button" class="btn-time-filter" onclick="updateSalesChart('weekly')">Weekly</button>
                            <button type="button" class="btn-time-filter" onclick="updateSalesChart('monthly')">Monthly</button>
                        </div>
                    </div>
                    <canvas id="salesChart" height="120"></canvas>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h3 class="dash-card-title text-danger">
                            <i class="bi bi-cart-plus-fill"></i> Purchases Performance Trend
                        </h3>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn-time-filter active" onclick="updatePurchasesChart('daily')">Daily</button>
                            <button type="button" class="btn-time-filter" onclick="updatePurchasesChart('weekly')">Weekly</button>
                            <button type="button" class="btn-time-filter" onclick="updatePurchasesChart('monthly')">Monthly</button>
                        </div>
                    </div>
                    <canvas id="purchasesChart" height="120"></canvas>
                </div>
            </div>
        </div>

        <!-- Tables Section: Recent Sales & Purchases -->
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h3 class="dash-card-title">
                            <i class="bi bi-clock-history text-primary"></i> Recent Sales Orders
                        </h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-modern">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Customer</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-center">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recent_sales as $sale)
                                <tr>
                                    <td><span class="fw-bold text-primary">{{ $sale->invoice_no }}</span></td>
                                    <td>{{ $sale->customer->customer_name ?? 'Walk-in Customer' }}</td>
                                    <td class="text-end fw-bold">Rs. {{ number_format($sale->total_balance, 2) }}</td>
                                    <td class="text-center text-muted"><small>{{ $sale->created_at->format('d-M-Y') }}</small></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="dash-card">
                    <div class="dash-card-header">
                        <h3 class="dash-card-title">
                            <i class="bi bi-clock-history text-purple"></i> Recent Purchase Orders
                        </h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-modern">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Vendor</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-center">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recent_purchases as $purchase)
                                <tr>
                                    <td><span class="fw-bold text-purple">{{ $purchase->invoice_no ?? '-' }}</span></td>
                                    <td>{{ $purchase->vendor->name ?? 'N/A' }}</td>
                                    <td class="text-end fw-bold">Rs. {{ number_format($purchase->net_amount ?? 0, 2) }}</td>
                                    <td class="text-center text-muted"><small>{{ $purchase->created_at->format('d-M-Y') }}</small></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Holds Table Section -->
        @if($stock_holds_details->count() > 0)
        <div class="dash-card mb-4">
            <div class="dash-card-header">
                <h3 class="dash-card-title text-danger">
                    <i class="bi bi-list-stars"></i> Pending Stock Holds Detail
                </h3>
            </div>
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Party Type</th>
                            <th>Product ID</th>
                            <th class="text-center">Hold Qty</th>
                            <th class="text-center">Date</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stock_holds_details as $index => $hold)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <strong>{{ ucfirst($hold->party_type ?? 'N/A') }}</strong>
                                @if($hold->party_id)
                                <small class="text-muted ms-1">(ID: {{ $hold->party_id }})</small>
                                @endif
                            </td>
                            <td>{{ $hold->product_id ?? '-' }}</td>
                            <td class="text-center"><span class="badge bg-danger rounded-pill px-3 py-1">{{ $hold->hold_qty ?? 0 }}</span></td>
                            <td class="text-center text-muted">{{ $hold->created_at->format('d-M-Y') }}</td>
                            <td class="text-center"><span class="badge-pill-custom bg-warning text-dark">Pending</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
    const chartData = @json($chartData);
    let salesChart, purchasesChart;

    function initSalesChart() {
        const ctx = document.getElementById('salesChart').getContext('2d');
        salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.daily.labels,
                datasets: [{
                    label: 'Sales Amount (Rs.)',
                    data: chartData.daily.sales,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: '#4f46e5'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    function initPurchasesChart() {
        const ctx = document.getElementById('purchasesChart').getContext('2d');
        purchasesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.daily.labels,
                datasets: [{
                    label: 'Purchase Amount (Rs.)',
                    data: chartData.daily.purchases,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: '#ef4444'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    function updateSalesChart(filter) {
        const btnGroup = event.target.closest('.btn-group');
        btnGroup.querySelectorAll('.btn-time-filter').forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');
        salesChart.data.labels = chartData[filter].labels;
        salesChart.data.datasets[0].data = chartData[filter].sales;
        salesChart.update();
    }

    function updatePurchasesChart(filter) {
        const btnGroup = event.target.closest('.btn-group');
        btnGroup.querySelectorAll('.btn-time-filter').forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');
        purchasesChart.data.labels = chartData[filter].labels;
        purchasesChart.data.datasets[0].data = chartData[filter].purchases;
        purchasesChart.update();
    }

    document.addEventListener('DOMContentLoaded', function() {
        initSalesChart();
        initPurchasesChart();
    });
</script>

@endsection