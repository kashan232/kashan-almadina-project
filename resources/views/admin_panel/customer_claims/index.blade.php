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
        background-color: #f8f9fa !important;
        color: #333 !important;
        font-weight: 600;
        vertical-align: middle;
        padding: 8px 10px !important;
        font-size: 12px;
        text-transform: uppercase;
    }
    
    #claimsTable tbody td {
        white-space: nowrap;
        vertical-align: middle;
        padding: 4px 10px !important;
        font-size: 12px;
        color: #333;
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

    .card {
        border-radius: 8px;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: none;
    }
    
    .item-detail-row {
        font-size: 10.5px;
        border-bottom: 1px dashed #eee;
        padding: 1px 0;
        line-height: 1.2;
    }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-3">
            
            <!-- Filters Section -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-2">
                            <form action="{{ route('customer-claims.index') }}" method="GET" class="row g-2 align-items-center">
                                <div class="col-md-3">
                                    <h6 class="mb-0 fw-bold text-dark ms-2"><i class="fa fa-handshake-o me-2 text-primary"></i>Customer Claims</h6>
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white border-end-0 small fw-bold text-muted">Range</span>
                                        <input type="date" name="start_date" class="form-control border-start-0" value="{{ request('start_date') }}">
                                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <select name="status" class="form-select form-select-sm select2">
                                        <option value="">All Status</option>
                                        <option value="Unposted" {{ request('status') == 'Unposted' ? 'selected' : '' }}>Unposted</option>
                                        <option value="Posted" {{ request('status') == 'Posted' ? 'selected' : '' }}>Posted</option>
                                    </select>
                                </div>
                                <div class="col-md-4 text-end">
                                    <div class="d-flex gap-1 justify-content-end align-items-center">
                                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">Filter</button>
                                        <a href="{{ route('customer-claims.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-2" title="Reset"><i class="fa fa-refresh"></i></a>
                                        <a class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm ms-2" href="{{ route('customer-claims.create') }}">
                                            <i class="fa fa-plus me-1"></i> Add Claim
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
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span class="fw-bold text-muted small text-uppercase">Claim Registry</span>
                            <div class="column-picker-dropdown">
                                <button class="btn btn-outline-secondary btn-sm px-3 rounded-pill" type="button" id="columnPickerBtn">
                                    <i class="fa fa-columns me-1"></i> Columns
                                </button>
                                <div class="column-picker-menu shadow" id="columnPickerMenu">
                                    <div class="p-2 border-bottom fw-bold small text-muted">Show/Hide Columns</div>
                                    <label class="column-picker-item"><input type="checkbox" data-column="1" checked> ID</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="2" checked> Type</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="3" checked> Inv#</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="4" checked> Date</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="5" checked> Party</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="6" checked> Product</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="7" checked> Claim Type</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="8" checked> Sales Price</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="9" checked> Replacement</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="10" checked> Fault</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="11" checked> Remarks</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="12" checked> Claim Income</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="13" checked> Status</label>
                                </div>
                            </div>
                        </div>

                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table id="claimsTable" class="table table-sm table-striped table-bordered w-100 mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Type</th>
                                            <th>Inv#</th>
                                            <th>Date</th>
                                            <th>Party</th>
                                            <th>Product</th>
                                            <th>Claim Type</th>
                                            <th class="text-end">Sales Price</th>
                                            <th>Replacement</th>
                                            <th>Fault</th>
                                            <th>Remarks</th>
                                            <th class="text-end">Claim Income</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center" style="min-width: 120px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($claims as $claim)
                                        <tr>
                                            <td>{{ $claim->id }}</td>
                                            <td class="text-center small">CLM</td>
                                            <td class="fw-bold text-success">{{ preg_replace('/[^0-9]/', '', $claim->claim_no) ?: '-' }}</td>
                                            <td class="small">{{ \Carbon\Carbon::parse($claim->claim_date)->format('d-M-Y') }}</td>
                                            <td>
                                                <span class="fw-semibold text-dark small">
                                                    @if($claim->party_type == 'vendor')
                                                        {{ $claim->party->name ?? 'N/A' }}
                                                    @else
                                                        {{ $claim->party->customer_name ?? 'N/A' }}
                                                    @endif
                                                </span>
                                            </td>
                                            <td class="small text-dark">{{ $claim->product->name ?? 'N/A' }}</td>
                                            <td class="small text-muted">{{ $claim->claim_type }}</td>
                                            <td class="text-end fw-bold">{{ number_format((float)$claim->sales_price, 0) }}</td>
                                            <td class="small text-dark">{{ $claim->replacementProduct->name ?? '-' }}</td>
                                            <td class="small text-muted">{{ Str::limit($claim->fault_found, 15) }}</td>
                                            <td class="small text-muted">{{ Str::limit($claim->remarks, 15) }}</td>
                                            <td class="text-end fw-bold">{{ number_format((float)$claim->claim_income, 0) }}</td>
                                            <td class="text-center">
                                                @if($claim->status === 'Posted')
                                                    <span class="badge bg-success rounded-pill px-3">Posted</span>
                                                @else
                                                    <span class="badge bg-warning text-dark rounded-pill px-3">Draft</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    <a href="{{ route('customer-claims.edit', $claim->id) }}" class="btn btn-outline-primary btn-xs px-2 py-0" title="View/Edit" style="height: 20px;">
                                                        <i class="fa {{ $claim->status == 'Posted' ? 'fa-eye' : 'fa-edit' }}"></i>
                                                    </a>
                                                    @if($claim->status !== 'Posted')
                                                        <form action="{{ route('customer-claims.post', $claim->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Post this claim?')">
                                                            @csrf
                                                            <button type="submit" class="btn btn-primary btn-xs px-2 py-0" title="Post now" style="font-size: 10px;">
                                                                <i class="fa fa-send"></i> Post
                                                            </button>
                                                        </form>
                                                    @endif
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
        $('.select2').select2({ width: '100%' });

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

        // Apply saved column visibility
        const savedState = localStorage.getItem(storageKey);
        if (savedState) {
            const columns = JSON.parse(savedState);
            $('#columnPickerMenu input').each(function() {
                const colIdx = parseInt($(this).data('column'));
                const checked = columns.hasOwnProperty(colIdx) ? columns[colIdx] : true;
                $(this).prop('checked', checked);
                dt.column(colIdx - 1).visible(checked);
            });
            dt.columns.adjust().draw(false);
        }

        // Handle Checkbox Change
        $('#columnPickerMenu input').on('change', function() {
            const colIdx = parseInt($(this).data('column'));
            const isChecked = $(this).is(':checked');
            
            dt.column(colIdx - 1).visible(isChecked);
            dt.columns.adjust().draw(false);
            
            const state = {};
            $('#columnPickerMenu input').each(function() {
                state[$(this).data('column')] = $(this).is(':checked');
            });
            localStorage.setItem(storageKey, JSON.stringify(state));
        });
    });
</script>
@endsection
