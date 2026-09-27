@extends('admin_panel.layout.app')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="main-content" style="background-color: #f8fafc; min-height: 100vh;">
    <div class="container-fluid p-4">
        
        <!-- Header & Dropdown Filter Bar -->
        <div class="card border-0 shadow-sm rounded-4 mb-4" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
            <div class="card-body p-4 text-white">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary bg-opacity-25 p-3 rounded-4 border border-primary border-opacity-25 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                            <i class="fa-solid fa-chart-pie fs-3 text-primary"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-1 text-white">
                                {{ $isAdmin ? 'Executive Sales Analytics' : 'My Performance Dashboard' }}
                            </h4>
                            <p class="text-white-50 mb-0 small">
                                {{ $isAdmin ? 'Real-time overview of user performance & overall revenue.' : 'Track your personal sales volume & performance trends.' }}
                            </p>
                        </div>
                    </div>

                    <!-- Dropdown Filter -->
                    <div class="d-flex align-items-center gap-2 bg-white bg-opacity-10 p-2 rounded-3 border border-white border-opacity-10">
                        <label for="periodSelect" class="text-white-50 small fw-semibold mb-0 ms-1">
                            <i class="fa-solid fa-filter text-primary me-1"></i> Period:
                        </label>
                        <select id="periodSelect" class="form-select form-select-sm border-0 shadow-none fw-bold bg-white text-dark" style="min-width: 140px; cursor: pointer;" onchange="location = this.value;">
                            <option value="{{ route('home', ['period' => 'daily']) }}" {{ $period === 'daily' ? 'selected' : '' }}>
                                📅 Daily
                            </option>
                            <option value="{{ route('home', ['period' => 'weekly']) }}" {{ $period === 'weekly' ? 'selected' : '' }}>
                                📆 Weekly
                            </option>
                            <option value="{{ route('home', ['period' => 'monthly']) }}" {{ $period === 'monthly' ? 'selected' : '' }}>
                                📊 Monthly
                            </option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric KPI Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm rounded-4 h-100 bg-white hover-up">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                    Total Revenue ({{ ucfirst($period) }})
                                </span>
                                <h3 class="fw-extrabold text-dark mt-1 mb-0" style="letter-spacing: -0.5px;">
                                    Rs. {{ number_format($filteredSalesAmount, 2) }}
                                </h3>
                            </div>
                            <div class="rounded-3 p-3 text-success" style="background-color: #ecfdf5;">
                                <i class="fa-solid fa-wallet fs-4"></i>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge rounded-pill bg-success-subtle text-success fw-bold px-2 py-1" style="font-size: 0.75rem;">
                                <i class="fa-solid fa-arrow-up me-1"></i>Active Period
                            </span>
                            <span class="text-muted small">Updated in real-time</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm rounded-4 h-100 bg-white hover-up">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                    Total Orders ({{ ucfirst($period) }})
                                </span>
                                <h3 class="fw-extrabold text-dark mt-1 mb-0" style="letter-spacing: -0.5px;">
                                    {{ number_format($filteredSalesCount) }} <span class="fs-6 fw-normal text-muted">Orders</span>
                                </h3>
                            </div>
                            <div class="rounded-3 p-3 text-primary" style="background-color: #eff6ff;">
                                <i class="fa-solid fa-receipt fs-4"></i>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge rounded-pill bg-primary-subtle text-primary fw-bold px-2 py-1" style="font-size: 0.75rem;">
                                <i class="fa-solid fa-box me-1"></i>Completed
                            </span>
                            <span class="text-muted small">Invoices recorded</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-12 col-xl-4">
                <div class="card border-0 shadow-sm rounded-4 h-100 bg-white hover-up">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                    Account Access
                                </span>
                                <h4 class="fw-bold text-dark mt-1 mb-0 text-truncate" style="max-width: 200px;">
                                    {{ $isAdmin ? 'All Staff Members' : Auth::user()->name }}
                                </h4>
                            </div>
                            <div class="rounded-3 p-3 text-warning" style="background-color: #fffbeb;">
                                <i class="fa-solid fa-user-shield fs-4"></i>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis fw-bold px-2 py-1" style="font-size: 0.75rem;">
                                {{ $isAdmin ? 'Admin Rights' : 'Standard User' }}
                            </span>
                            <span class="text-muted small">{{ $isAdmin ? count($userCards).' Active Users' : 'Restricted Scope' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Wise Sales Cards Section -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="fa-solid fa-users-gear text-primary me-2"></i>
                    {{ $isAdmin ? 'User Sales Breakdown' : 'My Performance Overview' }}
                </h5>
                <p class="text-muted small mb-0">Sales figures for selected filter ({{ ucfirst($period) }})</p>
            </div>
        </div>

        <div class="row g-3 mb-4">
            @forelse($userCards as $uc)
                <div class="col-sm-6 col-md-4 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100 bg-white user-sales-card">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="avatar-gradient shadow-sm">
                                    {{ strtoupper(substr($uc['name'], 0, 1)) }}
                                </div>
                                <div class="text-truncate">
                                    <h6 class="fw-bold mb-0 text-dark text-truncate">{{ $uc['name'] }}</h6>
                                    <span class="text-muted" style="font-size: 0.75rem;">{{ $uc['email'] }}</span>
                                </div>
                            </div>
                            <div class="bg-light p-2.5 rounded-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted d-block" style="font-size: 0.7rem; text-transform: uppercase; font-weight: 600;">Total Revenue</span>
                                    <strong class="text-success fs-6">
                                        Rs. {{ number_format($uc['total_amount'], 0) }}
                                    </strong>
                                </div>
                                <div class="text-end">
                                    <span class="text-muted d-block" style="font-size: 0.7rem; text-transform: uppercase; font-weight: 600;">Orders</span>
                                    <span class="badge bg-white text-dark border shadow-xs fw-bold px-2 py-1">{{ $uc['total_orders'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-light border rounded-4 text-center text-muted py-4">
                        <i class="fa-solid fa-inbox fs-3 d-block mb-2 text-secondary"></i>
                        No sales activity found for the selected period.
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Sales Trend Chart Section -->
        <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
            <div class="card-header bg-transparent border-0 p-4 pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold text-dark mb-1">
                        <i class="fa-solid fa-chart-column text-primary me-2"></i>
                        Sales Performance Graph
                    </h5>
                    <p class="text-muted small mb-0">Graphical presentation of revenue over {{ $period }} intervals.</p>
                </div>
                <div class="badge bg-primary-subtle text-primary fw-bold px-3 py-2 rounded-pill">
                    <i class="fa-solid fa-clock-rotate-left me-1"></i> Filter: {{ ucfirst($period) }} View
                </div>
            </div>
            <div class="card-body p-4">
                <div style="height: 350px;">
                    <canvas id="salesAnalyticsChart"></canvas>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
    .hover-up {
        transition: transform 0.25s cubic-bezier(0.165, 0.84, 0.44, 1), box-shadow 0.25s ease;
    }
    .hover-up:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08) !important;
    }
    .user-sales-card {
        transition: all 0.2s ease;
        border: 1px solid rgba(0,0,0,0.04) !important;
    }
    .user-sales-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.06) !important;
        border-color: rgba(13, 110, 253, 0.2) !important;
    }
    .avatar-gradient {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: #ffffff;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('salesAnalyticsChart').getContext('2d');
    const chartLabels = @json($chartData['labels']);
    const chartSales = @json($chartData['sales']);

    // Create Gradient for Chart Bars
    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(59, 130, 246, 0.85)');
    gradient.addColorStop(1, 'rgba(59, 130, 246, 0.15)');

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
                borderSkipped: false,
                barThickness: 32,
                maxBarThickness: 45,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    backgroundColor: '#0f172a',
                    titleFont: { size: 13, weight: 'bold' },
                    bodyFont: { size: 12 },
                    padding: 12,
                    cornerRadius: 10,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            let value = context.raw || 0;
                            return ' Total Sales: Rs. ' + value.toLocaleString('en-US', {minimumFractionDigits: 2});
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        font: { family: 'sans-serif', size: 11, weight: '600' },
                        color: '#64748b'
                    }
                },
                y: {
                    grid: {
                        color: '#f1f5f9',
                        drawBorder: false,
                    },
                    beginAtZero: true,
                    ticks: {
                        font: { family: 'sans-serif', size: 11 },
                        color: '#64748b',
                        callback: function(value) {
                            if (value >= 1000000) return 'Rs. ' + (value/1000000).toFixed(1) + 'M';
                            if (value >= 1000) return 'Rs. ' + (value/1000).toFixed(0) + 'k';
                            return 'Rs. ' + value;
                        }
                    }
                }
            }
        }
    });
});
</script>
@endsection

