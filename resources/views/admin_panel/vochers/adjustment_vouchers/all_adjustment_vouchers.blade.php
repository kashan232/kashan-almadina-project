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
    
    #adjustmentTable thead th {
        white-space: nowrap;
        background-color: #f8f9fa;
        color: #333;
        font-weight: 600;
        vertical-align: middle;
    }
    
    #adjustmentTable tbody td {
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
    .column-picker-menu.show { display: block; }
    .column-picker-item {
        display: block;
        padding: 4px 15px;
        clear: both;
        font-weight: 400;
        line-height: 1.42857143;
        color: #333;
        white-space: nowrap;
        cursor: pointer;
    }
    .column-picker-item:hover { background-color: #f5f5f5; }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-4">
            
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="fa fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="fa fa-exclamation-circle me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            <!-- Filter Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-3">
                            <form action="{{ route('all-adjustment-vochers') }}" method="GET" class="row g-3 align-items-end">
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
                                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Unposted</option>
                                        <option value="posted" {{ request('status') == 'posted' ? 'selected' : '' }}>Posted</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-success btn-sm px-4 rounded-pill shadow-sm">
                                            <i class="fa fa-filter me-1"></i> Filter
                                        </button>
                                        <a href="{{ route('all-adjustment-vochers') }}" class="btn btn-outline-secondary btn-sm px-4 rounded-pill">
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
                        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                            <h4 class="card-title mb-0 fw-bold text-dark"><i class="fa fa-adjust me-2 text-success"></i>Adjustment Vouchers</h4>
                            <div class="d-flex gap-2">
                                <!-- Column Picker Button -->
                                <div class="column-picker-dropdown">
                                    <button class="btn btn-outline-secondary btn-sm px-3 rounded-pill" type="button" id="columnPickerBtn">
                                        <i class="fa fa-columns me-1"></i> Columns
                                    </button>
                                    <div class="column-picker-menu shadow" id="columnPickerMenu">
                                        <div class="p-2 border-bottom fw-bold small text-muted">Show/Hide Columns</div>
                                        <label class="column-picker-item"><input type="checkbox" data-column="1" checked> ID</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="2" checked> Voucher No</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="3" checked> Entry Date</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="4" checked> Source Party</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="5" checked> Destination Accounts</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="6" checked> Remarks</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="7" checked> Total Amount</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="8" checked> Created At</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="9" checked> Status</label>
                                    </div>
                                </div>
                                <a class="btn btn-success btn-sm px-4 rounded-pill shadow-sm" href="{{ route('adjustment-vochers') }}">
                                    <i class="fa fa-plus me-1"></i> Add Adjustment Voucher
                                </a>
                            </div>
                        </div>

                        <div class="card-body p-3">
                            <div class="table-responsive">
                                <table id="adjustmentTable" class="table table-hover table-striped table-bordered display w-100">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Voucher No</th>
                                            <th>Entry Date</th>
                                            <th>Source Party (Side 1)</th>
                                            <th>Destination Accounts (Side 2)</th>
                                            <th>Remarks</th>
                                            <th class="text-end">Total Amount</th>
                                            <th>Created At</th>
                                            <th>Status</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($vouchers as $item)
                                        <tr>
                                            <td>{{ $item->id }}</td>
                                            <td class="fw-bold text-success">{{ $item->avid }}</td>
                                            <td>{{ $item->entry_date ? \Carbon\Carbon::parse($item->entry_date)->format('d-M-Y') : '-' }}</td>
                                            <td>
                                                <div class="small fw-bold text-muted text-uppercase" style="font-size: 0.7rem;">{{ $item->type_label }}</div>
                                                <div class="fw-bold text-dark">{{ $item->party_name }}</div>
                                                <div class="small text-muted">ID: {{ $item->party_code }}</div>
                                            </td>
                                            <td>
                                                <div style="font-size: 0.85rem; line-height: 1.2;">
                                                    {!! $item->accounts_detail !!}
                                                </div>
                                            </td>
                                            <td>{{ \Illuminate\Support\Str::limit($item->remarks, 30) }}</td>
                                            <td class="text-end fw-bold text-primary">{{ number_format((float)$item->total_amount, 2) }}</td>
                                            <td>{{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('d-M-Y g:i A') : '-' }}</td>
                                            <td class="text-center">
                                                @if($item->status === 'posted')
                                                    <span class="badge bg-success px-3 rounded-pill">Posted</span>
                                                @else
                                                    <span class="badge bg-warning text-dark px-3 rounded-pill">Unposted</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    @if($item->status === 'draft' || $item->status === 'Unposted' || $item->status === 'unposted')
                                                        <form action="{{ route('adjustment.vochers.post', $item->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill px-2" title="Post now">
                                                                <i class="fa fa-send"></i> Post
                                                            </button>
                                                        </form>
                                                        
                                                        <a href="{{ route('adjustment-vochers', $item->id) }}" class="btn btn-outline-warning btn-sm rounded-circle" title="Edit">
                                                           <i class="fa fa-edit"></i>
                                                        </a>

                                                        <form action="{{ route('adjustment.vochers.cancel', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this unposted voucher?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-circle" title="Delete">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    @else
                                                        <!-- Posted vouchers cannot be modified or unposted -->
                                                    @endif

                                                    
                                                    <a href="{{ route('adjustmentVoucher.print', $item->id) }}" target="_blank" class="btn btn-outline-dark btn-sm rounded-circle" title="Print">
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
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    // Toggle Column Picker
    $('#columnPickerBtn').on('click', function(e) {
        e.stopPropagation();
        $('#columnPickerMenu').toggleClass('show');
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.column-picker-dropdown').length) {
            $('#columnPickerMenu').removeClass('show');
        }
    });

    const storageKey = 'adjustment_voucher_table_columns_v1';
    const savedState = localStorage.getItem(storageKey);
    
    var dt = $('#adjustmentTable').DataTable({
        scrollX: true,
        autoWidth: false,
        pageLength: 25,
        order: [[0, 'desc']],
        columnDefs: [
            { targets: [9], orderable: false }
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search adjustments..."
        }
    });

    // Initialize checkboxes and column visibility
    if (savedState) {
        const columns = JSON.parse(savedState);
        $('#columnPickerMenu input').each(function() {
            const colIdx = parseInt($(this).data('column'));
            const isVisible = columns.hasOwnProperty(colIdx) ? columns[colIdx] : true;
            $(this).prop('checked', isVisible);
            dt.column(colIdx - 1).visible(isVisible);
        });
    }

    // Handle column toggle
    $('#columnPickerMenu input').on('change', function() {
        const colIdx = parseInt($(this).data('column'));
        const isChecked = $(this).is(':checked');
        
        dt.column(colIdx - 1).visible(isChecked);
        dt.columns.adjust().draw();

        // Save state
        const state = {};
        $('#columnPickerMenu input').each(function() {
            state[$(this).data('column')] = $(this).is(':checked');
        });
        localStorage.setItem(storageKey, JSON.stringify(state));
    });
});
</script>
@endsection
