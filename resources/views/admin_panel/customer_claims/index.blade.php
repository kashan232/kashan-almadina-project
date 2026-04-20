@extends('admin_panel.layout.app')

@section('content')
<style>
    /* Table Responsive & Scroll Enhancements */
    .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 1rem;
    }
    
    #claimsTable thead th {
        white-space: nowrap;
        background-color: #f8f9fa;
        color: #333;
        font-weight: 600;
        vertical-align: middle;
    }
    
    #claimsTable tbody td {
        white-space: nowrap;
        vertical-align: middle;
    }

    /* Column Picker Styles */
    .column-picker-dropdown {
        position: relative;
        display: inline-block;
    }
    .column-picker-menu {
        position: absolute;
        top: 100%;
        right: 0;
        z-index: 1000;
        display: none;
        min-width: 200px;
        padding: 5px 0;
        margin: 2px 0 0;
        font-size: 14px;
        text-align: left;
        list-style: none;
        background-color: #fff;
        background-clip: padding-box;
        border: 1px solid rgba(0,0,0,.15);
        border-radius: 4px;
        box-shadow: 0 6px 12px rgba(0,0,0,.175);
        max-height: 400px;
        overflow-y: auto;
    }
    .column-picker-menu.show {
        display: block;
    }
    .column-picker-item {
        display: block;
        padding: 5px 15px;
        clear: both;
        font-weight: 400;
        line-height: 1.42857143;
        color: #333;
        white-space: nowrap;
        cursor: pointer;
    }
    .column-picker-item:hover {
        background-color: #f5f5f5;
    }
    .column-picker-item input {
        margin-right: 10px;
        cursor: pointer;
    }
    .column-hidden {
        display: none !important;
    }

    .card {
        border-radius: 8px;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-4">
            <!-- Filter Section -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-3">
                            <form action="{{ route('customer-claims.index') }}" method="GET" class="row g-2 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted">Start Date</label>
                                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted">End Date</label>
                                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold text-muted">Status</label>
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="">All Status</option>
                                        <option value="Draft" {{ request('status') == 'Draft' ? 'selected' : '' }}>Draft</option>
                                        <option value="Posted" {{ request('status') == 'Posted' ? 'selected' : '' }}>Posted</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-sm px-4 rounded-pill shadow-sm">
                                            <i class="fa fa-filter me-1"></i> Filter
                                        </button>
                                        <a href="{{ route('customer-claims.index') }}" class="btn btn-outline-secondary btn-sm px-4 rounded-pill">
                                            <i class="fa fa-refresh me-1"></i> Reset
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 border-bottom">
                            <h4 class="card-title mb-0 fw-bold text-dark"><i class="fa fa-shield me-2 text-primary"></i>Customer Claims</h4>
                            <div class="d-flex gap-2">
                                <div class="column-picker-dropdown">
                                    <button class="btn btn-outline-secondary btn-sm px-3 rounded-pill" type="button" id="columnPickerBtn">
                                        <i class="fa fa-columns me-1"></i> Columns
                                    </button>
                                    <div class="column-picker-menu shadow" id="columnPickerMenu">
                                        <div class="p-2 border-bottom fw-bold small text-muted">Show/Hide Columns</div>
                                        <label class="column-picker-item"><input type="checkbox" data-column="1" checked> Claim No</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="2" checked> Date</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="3" checked> Type</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="4" checked> Party Name</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="5" checked> Item</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="6" checked> Serial / Card</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="7" checked> Income</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="8" checked> Status</label>
                                    </div>
                                </div>
                                <a class="btn btn-primary btn-sm px-4 rounded-pill shadow-sm" href="{{ route('customer-claims.create') }}">
                                    <i class="fa fa-plus me-1"></i> New Claim
                                </a>
                            </div>
                        </div>

                        <div class="card-body p-3 bg-white">
                            <div class="table-responsive">
                                <table id="claimsTable" class="table table-striped table-bordered display w-100">
                                    <thead>
                                        <tr>
                                            <th>Claim No</th>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Party Name</th>
                                            <th>Item</th>
                                            <th>Serial / Card</th>
                                            <th class="text-end">Income</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($claims as $claim)
                                        <tr>
                                            <td class="fw-bold">{{ $claim->claim_no }}</td>
                                            <td>{{ \Carbon\Carbon::parse($claim->claim_date)->format('d-M-Y') }}</td>
                                            <td>
                                                @if($claim->claim_type === 'item_return')
                                                    <span class="badge bg-soft-info text-info border border-info px-2">Item Return</span>
                                                @elseif($claim->claim_type === 'credit_note')
                                                    <span class="badge bg-soft-primary text-primary border border-primary px-2">Credit Note</span>
                                                @else
                                                    <span class="badge bg-soft-secondary text-secondary border border-secondary px-2">Hold</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="fw-bold">{{ $claim->party->customer_name ?? ($claim->party->name ?? 'N/A') }}</div>
                                                <small class="text-muted text-uppercase" style="font-size: 10px;">{{ $claim->party_type }}</small>
                                            </td>
                                            <td>{{ $claim->product->name ?? 'N/A' }}</td>
                                            <td>{{ $claim->card_no ?: '-' }}</td>
                                            <td class="text-end fw-bold text-success">{{ number_format($claim->claim_income, 2) }}</td>
                                            <td class="text-center">
                                                @if($claim->status === 'Posted')
                                                    <span class="badge bg-success rounded-pill px-3">Posted</span>
                                                @else
                                                    <span class="badge bg-warning text-dark rounded-pill px-3">Draft</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                     @if($claim->status === 'Draft')
                                                        <form action="{{ route('customer-claims.post', $claim->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill px-2" title="Post Now">
                                                                <i class="fa fa-send"></i> Post
                                                            </button>
                                                        </form>
                                                         <a href="{{ route('customer-claims.edit', $claim->id) }}" class="btn btn-outline-warning btn-sm rounded-circle" title="Edit">
                                                             <i class="fa fa-edit"></i>
                                                         </a>
                                                     @endif
                                                    <a href="#" class="btn btn-outline-dark btn-sm rounded-circle" title="Print">
                                                        <i class="fa fa-print"></i>
                                                    </a>
                                                </div>
                                            </td>
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
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Toggle Column Picker Menu
        $('#columnPickerBtn').on('click', function(e) {
            e.stopPropagation();
            $('#columnPickerMenu').toggleClass('show');
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('.column-picker-dropdown').length) {
                $('#columnPickerMenu').removeClass('show');
            }
        });

        const storageKey = 'claims_table_cols_v1';
        
        // Load initial state
        const savedState = localStorage.getItem(storageKey);
        if (savedState) {
            const columns = JSON.parse(savedState);
            $('#columnPickerMenu input').each(function() {
                const colIdx = $(this).data('column');
                if (columns.hasOwnProperty(colIdx)) {
                    $(this).prop('checked', columns[colIdx]);
                }
            });
        }

        // Initialize DataTable
        var dt = $('#claimsTable').DataTable({
            scrollX: true,
            autoWidth: false,
            pageLength: 25,
            order: [[0, 'desc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search claims..."
            }
        });

        // Apply column visibility
        const applyVisibility = () => {
            $('#columnPickerMenu input').each(function() {
                const colIdx = parseInt($(this).data('column')) - 1;
                const checked = $(this).is(':checked');
                dt.column(colIdx).visible(checked);
            });
            dt.columns.adjust().draw(false);
        };

        $('#columnPickerMenu input').on('change', function() {
            const state = {};
            $('#columnPickerMenu input').each(function() {
                state[$(this).data('column')] = $(this).is(':checked');
            });
            localStorage.setItem(storageKey, JSON.stringify(state));
            applyVisibility();
        });

        if (savedState) applyVisibility();
    });
</script>
@endsection
