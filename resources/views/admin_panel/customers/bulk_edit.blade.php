@extends('admin_panel.layout.app')

@section('content')
<style>
    .bulk-card {
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        background: #fff;
    }
    .bulk-table thead th {
        background-color: #f1f5f9 !important;
        color: #334155 !important;
        font-weight: 700;
        vertical-align: middle;
        font-size: 12px;
        text-transform: uppercase;
        padding: 9px 10px !important;
        border-bottom: 2px solid #cbd5e1;
    }
    .bulk-table tbody td {
        vertical-align: middle;
        font-size: 12px;
        padding: 6px 10px !important;
    }
    .bulk-table tbody tr:hover {
        background-color: #f8fafc;
    }
    .quick-bar {
        background: linear-gradient(to right, #f8fafc, #ffffff);
        border: 1px solid #cbd5e1;
        border-radius: 8px;
    }
    .sticky-actions {
        position: sticky;
        bottom: 0;
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(6px);
        border-top: 1px solid #cbd5e1;
        padding: 12px 20px;
        z-index: 100;
        box-shadow: 0 -4px 12px rgba(0,0,0,0.06);
    }

    /* Bootstrap 5 Clean Pagination Fix */
    .pagination {
        margin: 0 !important;
        padding: 0;
        display: flex;
        align-items: center;
        gap: 3px;
        list-style: none;
    }
    .pagination .page-item .page-link {
        border-radius: 6px !important;
        padding: 5px 12px !important;
        font-size: 12px !important;
        color: #334155 !important;
        background-color: #ffffff !important;
        border: 1px solid #cbd5e1 !important;
        line-height: 1.4 !important;
        text-decoration: none !important;
        font-weight: 500;
        transition: all 0.15s ease;
    }
    .pagination .page-item .page-link:hover {
        background-color: #f1f5f9 !important;
        border-color: #94a3b8 !important;
        color: #0f172a !important;
    }
    .pagination .page-item.active .page-link {
        background-color: #0d6efd !important;
        border-color: #0d6efd !important;
        color: #ffffff !important;
        font-weight: 700 !important;
    }
    .pagination .page-item.disabled .page-link {
        color: #94a3b8 !important;
        background-color: #f8fafc !important;
        border-color: #e2e8f0 !important;
        cursor: not-allowed !important;
        pointer-events: none;
    }
    .pagination svg {
        width: 14px !important;
        height: 14px !important;
        vertical-align: middle;
    }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-3">

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show py-2 px-3 small shadow-sm mb-3" role="alert">
                <i class="fa fa-check-circle me-1"></i> {{ session('success') }}
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show py-2 px-3 small shadow-sm mb-3" role="alert">
                <i class="fa fa-exclamation-triangle me-1"></i> {{ session('error') }}
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-1 text-dark"><i class="fa fa-users text-primary me-2"></i>Bulk Customer Edit</h5>
                    <p class="text-muted small mb-0">Select customers and assign Sales Officer in bulk (100 per page, etc.) or change them individually.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm">
                        <i class="fa fa-arrow-left me-1"></i> Back to Customers
                    </a>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="bulk-card mb-3">
                <div class="card-body p-3">
                    <form action="{{ route('customers.bulk-edit') }}" method="GET" class="row g-2 align-items-end">
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label small fw-bold text-muted mb-1">Search Customer</label>
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Name, Code, Mobile..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2 col-sm-6">
                            <label class="form-label small fw-bold text-muted mb-1">Customer Type</label>
                            <select name="customer_type" class="form-select form-select-sm">
                                <option value="">All Types</option>
                                <option value="Main Customer" {{ request('customer_type') == 'Main Customer' ? 'selected' : '' }}>Main Customer</option>
                                <option value="Walking Customer" {{ request('customer_type') == 'Walking Customer' ? 'selected' : '' }}>Walking Customer</option>
                            </select>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label small fw-bold text-muted mb-1">Current Sales Officer</label>
                            <select name="sales_officer" class="form-select form-select-sm">
                                <option value="">All Sales Officers</option>
                                @foreach($salesOfficers as $so)
                                    <option value="{{ $so->name }}" {{ request('sales_officer') == $so->name ? 'selected' : '' }}>{{ $so->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 col-sm-6">
                            <label class="form-label small fw-bold text-muted mb-1">Per Page</label>
                            <select name="per_page" class="form-select form-select-sm fw-bold">
                                <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50</option>
                                <option value="100" {{ request('per_page', 100) == '100' ? 'selected' : '' }}>100</option>
                                <option value="200" {{ request('per_page') == '200' ? 'selected' : '' }}>200</option>
                                <option value="all" {{ request('per_page') == 'all' ? 'selected' : '' }}>All (Entire List)</option>
                            </select>
                        </div>
                        <div class="col-md-2 col-sm-6 d-flex gap-1">
                            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3 flex-fill shadow-sm">
                                <i class="fa fa-filter me-1"></i> Filter
                            </button>
                            <a href="{{ route('customers.bulk-edit') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-2" title="Reset Filters">
                                <i class="fa fa-refresh"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Quick Batch Apply Bar -->
            <div class="quick-bar p-3 mb-3 shadow-sm">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge bg-primary px-2 py-1"><i class="fa fa-bolt me-1"></i> Quick Batch Action:</span>
                        
                        <div style="min-width: 240px;">
                            <select id="batchSalesOfficerSelect" class="form-select form-select-sm">
                                <option value="">-- Choose Sales Officer --</option>
                                @foreach($salesOfficers as $so)
                                    <option value="{{ $so->name }}">{{ $so->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="button" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm" id="btnApplyBatchSO">
                            <i class="fa fa-check-circle me-1"></i> Apply to Selected Customers
                        </button>
                    </div>

                    <div>
                        <button type="submit" form="bulkUpdateForm" class="btn btn-warning btn-sm rounded-pill px-4 shadow-sm fw-bold text-dark">
                            <i class="fa fa-save me-1"></i> Save All Changes
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main Edit Table Form -->
            <form action="{{ route('customers.bulk-update') }}" method="POST" id="bulkUpdateForm">
                @csrf
                <div class="bulk-card mb-3">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered bulk-table align-middle w-100 mb-0" id="bulkTable">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;" class="text-center">
                                            <input type="checkbox" class="form-check-input" id="checkAll">
                                        </th>
                                        <th style="width: 50px;" class="text-center">#</th>
                                        <th style="width: 100px;">Cust ID</th>
                                        <th>Customer Name</th>
                                        <th style="width: 120px;">Type</th>
                                        <th style="width: 280px;" class="bg-light-subtle">
                                            <i class="fa fa-user-circle me-1 text-primary"></i> Sales Officer
                                        </th>
                                        <th style="width: 140px;">Mobile</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $items = $customers instanceof \Illuminate\Pagination\LengthAwarePaginator ? $customers->items() : $customers;
                                    @endphp

                                    @forelse($items as $idx => $c)
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input row-checkbox" data-row-idx="{{ $idx }}">
                                            <input type="hidden" name="customers[{{ $idx }}][id]" value="{{ $c->id }}">
                                        </td>
                                        <td class="text-center text-muted small">
                                            {{ ($customers instanceof \Illuminate\Pagination\LengthAwarePaginator) ? ($customers->firstItem() + $idx) : ($idx + 1) }}
                                        </td>
                                        <td class="fw-bold text-primary">{{ $c->customer_id }}</td>
                                        <td>
                                            <span class="fw-bold text-dark">{{ $c->customer_name }}</span>
                                            @if($c->customer_name_ur)
                                                <small class="text-muted d-block text-end" dir="rtl">{{ $c->customer_name_ur }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($c->customer_type == 'Main Customer')
                                                <span class="badge bg-success-subtle text-success border border-success px-2 py-0">Main</span>
                                            @elseif($c->customer_type == 'Walking Customer')
                                                <span class="badge bg-warning-subtle text-warning border border-warning px-2 py-0">Walking</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary border px-2 py-0">{{ $c->customer_type }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $currentSo = $c->sales_officer ?? $c->sales_oficer;
                                            @endphp
                                            <select name="customers[{{ $idx }}][sales_officer]" class="form-select form-select-sm fw-bold row-so {{ $currentSo ? 'border-primary' : '' }}">
                                                <option value="">-- Unassigned --</option>
                                                @foreach($salesOfficers as $so)
                                                    <option value="{{ $so->name }}" {{ $currentSo == $so->name ? 'selected' : '' }}>
                                                        {{ $so->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="small text-muted">{{ $c->mobile ?? '-' }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            No customers found matching the filters.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Sticky Footer Action Bar -->
                <div class="sticky-actions d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="small text-muted"><strong id="selectedCount" class="text-primary">0</strong> of {{ count($items) }} selected</span>
                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" id="btnSelectAllOnPage">Select All</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" id="btnDeselectAll">Deselect All</button>
                    </div>

                    @if($customers instanceof \Illuminate\Pagination\LengthAwarePaginator)
                        <div class="d-flex align-items-center gap-3">
                            <span class="small text-muted d-none d-md-inline">
                                Showing <strong>{{ $customers->firstItem() }}</strong> to <strong>{{ $customers->lastItem() }}</strong> of <strong>{{ $customers->total() }}</strong> customers
                            </span>
                            <div>
                                {{ $customers->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    @endif

                    <div>
                        <button type="submit" class="btn btn-warning px-4 py-2 rounded-pill shadow-sm fw-bold text-dark">
                            <i class="fa fa-save me-1"></i> Save All Changes
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Update selected count
        function updateCount() {
            var count = $('.row-checkbox:checked').length;
            $('#selectedCount').text(count);
        }

        // Header CheckAll
        $('#checkAll').on('change', function() {
            var isChecked = $(this).is(':checked');
            $('.row-checkbox').prop('checked', isChecked);
            updateCount();
        });

        // Individual row checkbox
        $(document).on('change', '.row-checkbox', function() {
            var total = $('.row-checkbox').length;
            var checked = $('.row-checkbox:checked').length;
            $('#checkAll').prop('checked', total === checked);
            updateCount();
        });

        $('#btnSelectAllOnPage').on('click', function() {
            $('#checkAll').prop('checked', true);
            $('.row-checkbox').prop('checked', true);
            updateCount();
        });

        $('#btnDeselectAll').on('click', function() {
            $('#checkAll').prop('checked', false);
            $('.row-checkbox').prop('checked', false);
            updateCount();
        });

        // Batch Apply Sales Officer
        $('#btnApplyBatchSO').on('click', function() {
            var selectedSO = $('#batchSalesOfficerSelect').val();
            if (!selectedSO) {
                alert('Please choose a Sales Officer to apply.');
                return;
            }

            var $checked = $('.row-checkbox:checked');
            if ($checked.length === 0) {
                if (confirm('No specific rows checked. Do you want to apply "' + selectedSO + '" to ALL customers shown on this page?')) {
                    $('.row-so').val(selectedSO).addClass('border-primary bg-warning-subtle');
                }
            } else {
                $checked.each(function() {
                    var $tr = $(this).closest('tr');
                    $tr.find('.row-so').val(selectedSO).addClass('border-primary bg-warning-subtle');
                });
            }
        });
    });
</script>
@endsection
