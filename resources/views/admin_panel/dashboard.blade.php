@extends('admin_panel.layout.app')
@section('content')

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<style>
    body {
        font-family: 'Poppins', sans-serif;
        background: #f8f9fa;
    }
    
    .dashboard-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 15px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    }
    
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        transition: transform 0.3s, box-shadow 0.3s;
        border-left: 4px solid;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .stat-card.primary { border-left-color: #667eea; }
    .stat-card.success { border-left-color: #28a745; }
    .stat-card.info { border-left-color: #17a2b8; }
    .stat-card.warning { border-left-color: #ffc107; }
    .stat-card.danger { border-left-color: #dc3545; }
    .stat-card.purple { border-left-color: #6f42c1; }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: white;
    }
    
    .stat-icon.primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .stat-icon.success { background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%); }
    .stat-icon.info { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); }
    .stat-icon.warning { background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%); }
    .stat-icon.danger { background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%); }
    .stat-icon.purple { background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%); }
    
    .stat-value {
        font-size: 28px;
        font-weight: 700;
        margin: 10px 0 5px 0;
    }
    
    .stat-label {
        font-size: 13px;
        color: #6c757d;
        font-weight: 500;
    }
    
    .section-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 20px;
        color: #333;
    }
    
    .table-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    }
    
    .badge-custom {
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 500;
        font-size: 11px;
    }
</style>

<div class="main-content">
    <div class="container-fluid p-4">
        
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <h2 class="mb-2 fw-bold">Dashboard Overview</h2>
            <p class="mb-0 opacity-75">Complete Business Analytics at a Glance</p>
        </div>

        <!-- Products Section -->
        <h5 class="section-title"><i class="bi bi-box-seam me-2"></i>Products</h5>
        <div class="row">
            <div class="col-lg-12">
                <div class="stat-card primary">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon primary">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <div class="stat-value">{{ $stats['total_products'] }}</div>
                            <div class="stat-label">Total Products</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inward Gatepass Section -->
        <h5 class="section-title mt-4"><i class="bi bi-box-arrow-in-down me-2"></i>Inward Gatepass</h5>
        <div class="row">
            <div class="col-lg-4 col-md-6">
                <div class="stat-card info">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon info">
                            <i class="bi bi-clipboard-data"></i>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <div class="stat-value">{{ $stats['total_inward'] }}</div>
                            <div class="stat-label">Total Inward Gatepass</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="stat-card success">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon success">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <div class="stat-value">{{ $stats['inward_with_bills'] }}</div>
                            <div class="stat-label">Bills Added</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="stat-card warning">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon warning">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <div class="stat-value">{{ $stats['inward_pending_bills'] }}</div>
                            <div class="stat-label">Pending Bills</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Purchases & Vendors Section -->
        <h5 class="section-title mt-4"><i class="bi bi-cart-plus me-2"></i>Purchases & Vendors</h5>
        <div class="row">
            <div class="col-lg-4 col-md-6">
                <div class="stat-card purple">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon purple">
                            <i class="bi bi-bag-check"></i>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <div class="stat-value">{{ $stats['total_purchases'] }}</div>
                            <div class="stat-label">Total Purchases</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="stat-card success">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon success">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <div class="stat-value">Rs.{{ number_format($stats['total_purchase_amount'], 0) }}</div>
                            <div class="stat-label">Purchase Amount</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="stat-card info">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon info">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <div class="stat-value">{{ $stats['total_vendors'] }}</div>
                            <div class="stat-label">Total Vendors</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales Section -->
        <h5 class="section-title mt-4"><i class="bi bi-graph-up-arrow me-2"></i>Sales</h5>
        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card primary">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon primary">
                            <i class="bi bi-receipt-cutoff"></i>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <div class="stat-value">{{ $stats['total_sales'] }}</div>
                            <div class="stat-label">Total Sales</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card success">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon success">
                            <i class="bi bi-currency-rupee"></i>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <div class="stat-value">Rs.{{ number_format($stats['total_sales_amount'], 0) }}</div>
                            <div class="stat-label">Total Sales Amount</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card warning">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon warning">
                            <i class="bi bi-calendar-event"></i>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <div class="stat-value">{{ $stats['today_sales'] }}</div>
                            <div class="stat-label">Today's Sales</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card info">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon info">
                            <i class="bi bi-cash"></i>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <div class="stat-value">Rs.{{ number_format($stats['today_sales_amount'], 0) }}</div>
                            <div class="stat-label">Today's Amount</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales & Purchases Charts -->
        <h5 class="section-title mt-5"><i class="bi bi-graph-up me-2"></i>Sales & Purchases Analysis</h5>
        <div class="row">
            <!-- Sales Chart -->
            <div class="col-lg-6">
                <div class="table-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0 fw-bold text-primary">
                            <i class="bi bi-graph-up-arrow"></i> Sales Trend
                        </h6>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-sm btn-primary active" onclick="updateSalesChart('daily')">
                                <i class="bi bi-calendar-day"></i> Daily
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="updateSalesChart('weekly')">
                                <i class="bi bi-calendar-week"></i> Weekly
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="updateSalesChart('monthly')">
                                <i class="bi bi-calendar-month"></i> Monthly
                            </button>
                        </div>
                    </div>
                    <canvas id="salesChart" height="100"></canvas>
                </div>
            </div>
            
            <!-- Purchases Chart -->
            <div class="col-lg-6">
                <div class="table-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0 fw-bold text-danger">
                            <i class="bi bi-cart-plus"></i> Purchases Trend
                        </h6>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-sm btn-danger active" onclick="updatePurchasesChart('daily')">
                                <i class="bi bi-calendar-day"></i> Daily
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="updatePurchasesChart('weekly')">
                                <i class="bi bi-calendar-week"></i> Weekly
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="updatePurchasesChart('monthly')">
                                <i class="bi bi-calendar-month"></i> Monthly
                            </button>
                        </div>
                    </div>
                    <canvas id="purchasesChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- Stock Holds Section -->
        <h5 class="section-title mt-4"><i class="bi bi-pause-circle me-2"></i>Stock Holds</h5>
        <div class="row">
            <div class="col-lg-12">
                <div class="stat-card danger">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon danger">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <div class="stat-value">{{ $stats['total_stock_holds'] }}</div>
                            <div class="stat-label">Pending Stock Holds</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customers & Credit Section -->
        <h5 class="section-title mt-4"><i class="bi bi-person-badge me-2"></i>Customers & Credit</h5>
        <div class="row">
            <div class="col-lg-4 col-md-6">
                <div class="stat-card primary">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon primary">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <div class="stat-value">{{ $stats['total_customers'] }}</div>
                            <div class="stat-label">Total Customers</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="stat-card danger">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon danger">
                            <i class="bi bi-credit-card"></i>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <div class="stat-value">Rs.{{ number_format($stats['total_customer_credit'], 0) }}</div>
                            <div class="stat-label">Total Credit</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="stat-card warning">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon warning">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <div class="stat-value">Rs.{{ number_format($stats['pending_payments'], 0) }}</div>
                            <div class="stat-label">Pending Payments</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Holds Details Table -->
        @if($stock_holds_details->count() > 0)
        <h5 class="section-title mt-5"><i class="bi bi-list-ul me-2"></i>Stock Hold Details</h5>
        <div class="table-card">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Party Type</th>
                            <th>Product ID</th>
                            <th>Hold Qty</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stock_holds_details as $index => $hold)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <strong>{{ ucfirst($hold->party_type ?? 'N/A') }}</strong>
                                @if($hold->party_id)
                                <small class="text-muted">(ID: {{ $hold->party_id }})</small>
                                @endif
                            </td>
                            <td>{{ $hold->product_id ?? '-' }}</td>
                            <td><strong>{{ $hold->hold_qty ?? 0 }}</strong></td>
                            <td>{{ $hold->created_at->format('d-M-Y') }}</td>
                            <td><span class="badge-custom bg-warning text-dark">Pending</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Recent Sales & Purchases -->
        <div class="row mt-4">
            <div class="col-lg-6">
                <h5 class="section-title"><i class="bi bi-clock-history me-2"></i>Recent Sales</h5>
                <div class="table-card">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Invoice</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recent_sales as $sale)
                                <tr>
                                    <td><strong>{{ $sale->invoice_no }}</strong></td>
                                    <td>{{ $sale->customer->customer_name ?? 'Walk-in' }}</td>
                                    <td><strong>Rs.{{ number_format($sale->total_balance, 2) }}</strong></td>
                                    <td><small>{{ $sale->created_at->format('d-M-Y') }}</small></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <h5 class="section-title"><i class="bi bi-clock-history me-2"></i>Recent Purchases</h5>
                <div class="table-card">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Invoice</th>
                                    <th>Vendor</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recent_purchases as $purchase)
                                <tr>
                                    <td><strong>{{ $purchase->invoice_no ?? '-' }}</strong></td>
                                    <td>{{ $purchase->vendor->name ?? 'N/A' }}</td>
                                    <td><strong>Rs.{{ number_format($purchase->net_amount ?? 0, 2) }}</strong></td>
                                    <td><small>{{ $purchase->created_at->format('d-M-Y') }}</small></td>
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

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
    // Chart Data from PHP
    const chartData = @json($chartData);
    
    let salesChart;
    let purchasesChart;
    
    // Initialize Sales Chart
    function initSalesChart() {
        const ctx = document.getElementById('salesChart').getContext('2d');
        
        salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.daily.labels,
                datasets: [{
                    label: 'Sales Amount (Rs.)',
                    data: chartData.daily.sales,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.15)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 3,
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#667eea',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(102, 126, 234, 0.95)',
                        padding: 12,
                        titleFont: {
                            family: 'Poppins',
                            size: 13,
                            weight: '600'
                        },
                        bodyFont: {
                            family: 'Poppins',
                            size: 12
                        },
                        callbacks: {
                            label: function(context) {
                                return 'Sales: Rs.' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            font: {
                                family: 'Poppins',
                                size: 10
                            },
                            callback: function(value) {
                                return 'Rs.' + (value/1000) + 'K';
                            }
                        },
                        grid: {
                            color: 'rgba(102, 126, 234, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                family: 'Poppins',
                                size: 10,
                                weight: '500'
                            }
                        },
                        grid: {
                            display: false
                        }
                    }
                },
                animation: {
                    duration: 1200,
                    easing: 'easeInOutQuart'
                }
            }
        });
    }
    
    // Initialize Purchases Chart
    function initPurchasesChart() {
        const ctx = document.getElementById('purchasesChart').getContext('2d');
        
        purchasesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.daily.labels,
                datasets: [{
                    label: 'Purchase Amount (Rs.)',
                    data: chartData.daily.purchases,
                    borderColor: '#f56565',
                    backgroundColor: 'rgba(245, 101, 101, 0.15)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    pointBackgroundColor: '#f56565',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 3,
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#f56565',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(245, 101, 101, 0.95)',
                        padding: 12,
                        titleFont: {
                            family: 'Poppins',
                            size: 13,
                            weight: '600'
                        },
                        bodyFont: {
                            family: 'Poppins',
                            size: 12
                        },
                        callbacks: {
                            label: function(context) {
                                return 'Purchases: Rs.' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            font: {
                                family: 'Poppins',
                                size: 10
                            },
                            callback: function(value) {
                                return 'Rs.' + (value/1000) + 'K';
                            }
                        },
                        grid: {
                            color: 'rgba(245, 101, 101, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                family: 'Poppins',
                                size: 10,
                                weight: '500'
                            }
                        },
                        grid: {
                            display: false
                        }
                    }
                },
                animation: {
                    duration: 1200,
                    easing: 'easeInOutQuart'
                }
            }
        });
    }
    
    // Update Sales Chart
    function updateSalesChart(filter) {
        // Update button states
        const btnGroup = event.target.closest('.btn-group');
        btnGroup.querySelectorAll('button').forEach(btn => {
            btn.classList.remove('btn-primary', 'active');
            btn.classList.add('btn-outline-primary');
        });
        event.target.closest('button').classList.remove('btn-outline-primary');
        event.target.closest('button').classList.add('btn-primary', 'active');
        
        // Update chart data
        salesChart.data.labels = chartData[filter].labels;
        salesChart.data.datasets[0].data = chartData[filter].sales;
        salesChart.update();
    }
    
    // Update Purchases Chart
    function updatePurchasesChart(filter) {
        // Update button states
        const btnGroup = event.target.closest('.btn-group');
        btnGroup.querySelectorAll('button').forEach(btn => {
            btn.classList.remove('btn-danger', 'active');
            btn.classList.add('btn-outline-danger');
        });
        event.target.closest('button').classList.remove('btn-outline-danger');
        event.target.closest('button').classList.add('btn-danger', 'active');
        
        // Update chart data
        purchasesChart.data.labels = chartData[filter].labels;
        purchasesChart.data.datasets[0].data = chartData[filter].purchases;
        purchasesChart.update();
    }
    
    // Initialize charts when page loads
    document.addEventListener('DOMContentLoaded', function() {
        initSalesChart();
        initPurchasesChart();
    });
</script>

@endsection