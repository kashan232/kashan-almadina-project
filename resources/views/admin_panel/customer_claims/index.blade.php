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
                                    <label class="column-picker-item"><input type="checkbox" data-column="1" checked> BTR#</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="2" checked> Date</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="3" checked> Customer</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="4" checked> Items</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="5" checked> Total Amount</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="6" checked> Status</label>
                                </div>
                            </div>
                        </div>

                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table id="claimsTable" class="table table-sm table-striped table-bordered w-100 mb-0">
                                    <thead>
                                        <tr>
                                            <th>BTR#</th>
                                            <th>Date</th>
                                            <th>Customer</th>
                                            <th>Items</th>
                                            <th class="text-end">Total Amount</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center" style="min-width: 120px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($claims as $claim)
                                        <tr>
                                            <td class="fw-bold text-primary">{{ $claim->btr_no }}</td>
                                            <td class="small">{{ \Carbon\Carbon::parse($claim->date)->format('d-M-Y') }}</td>
                                            <td>
                                                <span class="fw-semibold text-dark small">{{ $claim->customer->customer_name ?? 'N/A' }}</span>
                                            </td>
                                            <td class="py-1">
                                                @foreach($claim->items as $item)
                                                    <div class="item-detail-row">
                                                        {{ $item->product->name ?? 'Product' }}
                                                        <span class="text-primary fw-bold ms-1">({{ (float)$item->qty }})</span>
                                                    </div>
                                                @endforeach
                                            </td>
                                            <td class="text-end fw-bold">{{ number_format($claim->total_amount, 0) }}</td>
                                            <td class="text-center">
                                                @if($claim->status === 'Posted')
                                                    <span class="badge bg-success rounded-pill px-3">Posted</span>
                                                @else
                                                    <span class="badge bg-warning text-dark rounded-pill px-3">Unposted</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    @if($claim->status === 'Unposted')
                                                        <form action="{{ route('customer-claims.post', $claim->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-primary btn-xs px-2 py-0" title="Post now" style="font-size: 10px;">
                                                                <i class="fa fa-send"></i> Post
                                                            </button>
                                                        </form>
                                                        
                                                        <a href="{{ route('customer-claims.edit', $claim->id) }}" class="btn btn-outline-warning btn-xs px-1 py-0" title="Edit" style="height: 20px;">
                                                            <i class="fa fa-edit text-dark"></i>
                                                        </a>
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
