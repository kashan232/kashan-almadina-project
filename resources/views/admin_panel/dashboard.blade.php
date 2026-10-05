@extends('admin_panel.layout.app')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    :root {
        --dash-bg: #f8fafc;
        --card-radius: 16px;
    }

    body {
        background-color: var(--dash-bg);
        font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
    }

    .dashboard-container {
        padding: 24px;
    }

    /* Top Banner / Header */
    .dash-hero-card {
        background: #ffffff;
        border-radius: var(--card-radius);
        padding: 20px 24px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        margin-bottom: 24px;
    }

    .dash-title {
        font-size: 22px;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* KPI Summary Cards */
    .kpi-box {
        background: #ffffff;
        border-radius: var(--card-radius);
        padding: 20px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
        transition: all 0.25s ease;
        height: 100%;
    }

    .kpi-box:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.06);
        border-color: #cbd5e1;
    }

    .kpi-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }

    .kpi-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: #64748b;
    }

    .kpi-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .icon-emerald { background: #d1fae5; color: #059669; }
    .icon-blue    { background: #dbeafe; color: #2563eb; }
    .icon-amber   { background: #fef3c7; color: #d97706; }
    .icon-purple  { background: #f3e8ff; color: #9333ea; }

    .kpi-value {
        font-size: 26px;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.5px;
        margin-bottom: 6px;
    }

    .kpi-foot {
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    /* Section Cards */
    .section-card {
        background: #ffffff;
        border-radius: var(--card-radius);
        padding: 24px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
        margin-bottom: 24px;
    }

    .section-title {
        font-size: 16px;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* User Cards Grid */
    .user-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 16px;
        transition: all 0.2s;
    }

    .user-card:hover {
        background: #ffffff;
        border-color: #3b82f6;
        box-shadow: 0 8px 16px -4px rgba(59, 130, 246, 0.12);
        transform: translateY(-2px);
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: white;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }

    .user-revenue-badge {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 8px 12px;
        margin-top: 12px;
    }
</style>

<div class="main-content">
    <div class="dashboard-container">
        
        <!-- Header Controls -->
        <div class="dash-hero-card">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h3 class="dash-title">
                        <i class="fa-solid fa-chart-pie text-primary"></i>
                        {{ $isAdmin ? 'Executive Sales Analytics' : 'My Performance Dashboard' }}
                    </h3>
                    <span class="text-muted small fw-medium">Real-time overview of orders & team performance</span>
                </div>
                
                <div class="d-flex align-items-center gap-2 bg-light p-1.5 rounded-3 border">
                    <span class="text-muted small fw-bold ms-2 me-1"><i class="fa-solid fa-calendar-days text-primary me-1"></i> Period:</span>
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="{{ route('home', ['period' => 'daily']) }}" class="btn btn-sm {{ $period === 'daily' ? 'btn-primary fw-bold' : 'btn-light text-dark' }}">Daily</a>
                        <a href="{{ route('home', ['period' => 'weekly']) }}" class="btn btn-sm {{ $period === 'weekly' ? 'btn-primary fw-bold' : 'btn-light text-dark' }}">Weekly</a>
                        <a href="{{ route('home', ['period' => 'monthly']) }}" class="btn btn-sm {{ $period === 'monthly' ? 'btn-primary fw-bold' : 'btn-light text-dark' }}">Monthly</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top KPI Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="kpi-box">
                    <div class="kpi-header">
                        <span class="kpi-label">Total Revenue ({{ ucfirst($period) }})</span>
                        <div class="kpi-icon icon-emerald">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                    </div>
                    <div class="kpi-value">Rs. {{ number_format($filteredSalesAmount, 2) }}</div>
                    <div class="kpi-foot">
                        <span class="badge bg-success-subtle text-success fw-bold px-2 py-0.5 rounded-pill"><i class="fa-solid fa-arrow-up me-1"></i>Active</span>
                        <span>Live Sync</span>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="kpi-box">
                    <div class="kpi-header">
                        <span class="kpi-label">Total Orders ({{ ucfirst($period) }})</span>
                        <div class="kpi-icon icon-blue">
                            <i class="fa-solid fa-receipt"></i>
                        </div>
                    </div>
                    <div class="kpi-value">{{ number_format($filteredSalesCount) }} <span class="fs-6 text-muted fw-semibold">Orders</span></div>
                    <div class="kpi-foot">
                        <span class="badge bg-primary-subtle text-primary fw-bold px-2 py-0.5 rounded-pill"><i class="fa-solid fa-circle-check me-1"></i>Completed</span>
                        <span>Invoices recorded</span>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="kpi-box">
                    <div class="kpi-header">
                        <span class="kpi-label">Account Access</span>
                        <div class="kpi-icon icon-amber">
                            <i class="fa-solid fa-user-shield"></i>
                        </div>
                    </div>
                    <div class="kpi-value text-truncate" style="font-size: 20px; line-height: 1.4;">
                        {{ $isAdmin ? 'All Staff Members' : Auth::user()->name }}
                    </div>
                    <div class="kpi-foot">
                        <span class="badge bg-warning-subtle text-warning-emphasis fw-bold px-2 py-0.5 rounded-pill">{{ $isAdmin ? 'Admin Rights' : 'User Rights' }}</span>
                        <span>{{ $isAdmin ? count($userCards).' Active Users' : 'Personal View' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Sales Breakdown Grid -->
        <div class="section-card">
            <h4 class="section-title">
                <i class="fa-solid fa-users text-primary"></i>
                {{ $isAdmin ? 'User Sales Breakdown' : 'My Performance Overview' }}
                <span class="badge bg-light text-dark border ms-auto small font-monospace">{{ ucfirst($period) }} View</span>
            </h4>

            <div class="row g-3">
                @forelse($userCards as $uc)
                    <div class="col-sm-6 col-md-4 col-xl-3">
                        <div class="user-card">
                            <div class="d-flex align-items-center gap-3">
                                <div class="user-avatar">
                                    {{ strtoupper(substr($uc['name'], 0, 1)) }}
                                </div>
                                <div class="text-truncate">
                                    <h6 class="fw-bold mb-0 text-dark text-truncate">{{ $uc['name'] }}</h6>
                                    <small class="text-muted d-block text-truncate">{{ $uc['email'] }}</small>
                                </div>
                            </div>
                            <div class="user-revenue-badge d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted d-block" style="font-size: 10px; font-weight: 700; text-transform: uppercase;">Total Revenue</span>
                                    <strong class="text-success" style="font-size: 15px;">Rs. {{ number_format($uc['total_amount'], 0) }}</strong>
                                </div>
                                <div class="text-end">
                                    <span class="text-muted d-block" style="font-size: 10px; font-weight: 700; text-transform: uppercase;">Orders</span>
                                    <span class="badge bg-primary text-white fw-bold px-2 py-1">{{ $uc['total_orders'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="p-4 text-center text-muted">
                            <i class="fa-solid fa-inbox fs-3 mb-2 d-block"></i> No sales activity found for this period.
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Sales Analytics Chart -->
        <div class="section-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="section-title mb-0">
                    <i class="fa-solid fa-chart-column text-primary"></i> Sales Performance Graph
                </h4>
                <span class="text-muted small font-monospace">Revenue (Rs.) over {{ $period }}</span>
            </div>
            <div style="height: 320px;">
                <canvas id="salesAnalyticsChart"></canvas>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('salesAnalyticsChart').getContext('2d');
    const chartLabels = @json($chartData['labels']);
    const chartSales = @json($chartData['sales']);

    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(37, 99, 235, 0.85)');
    gradient.addColorStop(1, 'rgba(37, 99, 235, 0.1)');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Revenue (Rs.)',
                data: chartSales,
                backgroundColor: gradient,
                borderColor: '#2563eb',
                borderWidth: 2,
                borderRadius: 8,
                barThickness: 30
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        label: function(ctx) {
                            return ' Sales: Rs. ' + (ctx.raw || 0).toLocaleString();
                        }
                    }
                }
            },
            scales: {
                x: { grid: { display: false } },
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        callback: function(v) {
                            if (v >= 1000000) return 'Rs. ' + (v/1000000).toFixed(1) + 'M';
                            if (v >= 1000) return 'Rs. ' + (v/1000).toFixed(0) + 'k';
                            return 'Rs. ' + v;
                        }
                    }
                }
            }
        }
    });
});
</script>
@endsection
