@extends('admin_panel.layout.app')

@section('content')
<style>
    /* Ultra-High Density Design System */
    .main-content-inner { background: #f4f7fa; min-height: 100vh; }
    
    /* Table Density */
    #expenseVoucherTable { font-size: 11px !important; border-collapse: separate !important; border-spacing: 0; width: 100% !important; }
    #expenseVoucherTable thead th { 
        padding: 2px 10px !important; 
        font-size: 11px !important; 
        height: 20px !important;
        line-height: 1.2 !important;
        background: #fff !important;
        color: #444 !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        border-bottom: 2px solid #ebedef !important;
        vertical-align: middle !important;
    }
    
    /* DataTables Sorting Arrow Fix */
    table.dataTable thead .sorting:before, table.dataTable thead .sorting:after,
    table.dataTable thead .sorting_asc:before, table.dataTable thead .sorting_asc:after,
    table.dataTable thead .sorting_desc:before, table.dataTable thead .sorting_desc:after {
        bottom: 2px !important;
        font-size: 0.7rem !important;
        opacity: 0.3;
    }

    #expenseVoucherTable tbody td { 
        padding: 4px 10px !important; 
        vertical-align: middle !important; 
        border-bottom: 1px solid #f0f2f5 !important;
        white-space: nowrap;
    }
    #expenseVoucherTable tbody tr:hover { background-color: #f8f9ff !important; }

    /* Compact Buttons */
    .btn-xs { padding: 1px 5px; font-size: 10px; line-height: 1.2; border-radius: 3px; }
    .btn-mini { padding: 0px 4px; font-size: 9px; height: 18px; display: inline-flex; align-items: center; justify-content: center; }
    
    /* DataTables Export Buttons styling */
    .dt-buttons { margin-bottom: 0px !important; }
    .dt-button { 
        padding: 2px 10px !important; 
        font-size: 10px !important; 
        border-radius: 4px !important; 
        background: #fff !important;
        border: 1px solid #dee2e6 !important;
        box-shadow: none !important;
        transition: all 0.2s;
    }
    .dt-button:hover { background: #f8f9fa !important; border-color: #adb5bd !important; }

    /* Filter Bar Compact */
    .form-control-sm, .form-select-sm { font-size: 11px !important; height: calc(1.5em + 0.5rem + 2px) !important; padding: 0.25rem 0.5rem !important; }
    
    /* Column Picker Styles */
    .column-picker-dropdown { position: relative; display: inline-block; }
    .column-picker-menu {
        position: absolute;
        top: 100%;
        right: 0;
        z-index: 10000;
        display: none;
        min-width: 220px;
        padding: 8px 0;
        margin-top: 5px;
        font-size: 13px;
        text-align: left;
        list-style: none;
        background-color: #fff;
        background-clip: padding-box;
        border: 1px solid rgba(0,0,0,.1);
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        max-height: 400px;
        overflow-y: auto;
    }
    .column-picker-menu.show { display: block; }
    .column-picker-item {
        display: flex;
        align-items: center;
        padding: 6px 16px;
        clear: both;
        font-weight: 400;
        line-height: 1.5;
        color: #444;
        white-space: nowrap;
        cursor: pointer;
        transition: background 0.2s;
    }
    .column-picker-item:hover { background-color: #f8f9fa; color: #000; }
    .column-picker-item input { margin-right: 12px; cursor: pointer; width: 16px; height: 16px; }

    .card { border-radius: 8px; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); border: none; margin-bottom: 0.5rem; }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-1">
            
            <!-- Filters Section -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-2" style="overflow: visible;">
                            <form action="{{ route('all-expense-vochers') }}" method="GET" class="row g-2 align-items-center">
                                <div class="col-md-3">
                                    <h6 class="mb-0 fw-bold text-dark ms-2"><i class="fa fa-money me-2 text-danger"></i>Expenses</h6>
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
                                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Unposted</option>
                                        <option value="posted" {{ request('status') == 'posted' ? 'selected' : '' }}>Posted</option>
                                    </select>
                                </div>
                                <div class="col-md-4 text-end">
                                    <div class="d-flex gap-1 justify-content-end align-items-center">
                                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">Filter</button>
                                        <a href="{{ route('all-expense-vochers') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-2" title="Reset"><i class="fa fa-refresh"></i></a>
                                        <a class="btn btn-danger btn-sm rounded-pill px-4 shadow-sm ms-2" href="{{ route('expense-vochers') }}">
                                            <i class="fa fa-plus me-1"></i> Add Expense
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="fw-bold text-muted small text-uppercase"><i class="fa fa-list me-1"></i> Expenses Registry</span>
                    <div class="column-picker-dropdown">
                        <button class="btn btn-outline-secondary btn-sm px-3 rounded-pill" type="button" id="columnPickerBtn">
                            <i class="fa fa-columns me-1"></i> Columns
                        </button>
                        <div class="column-picker-menu shadow" id="columnPickerMenu">
                            <div class="p-2 border-bottom fw-bold small text-muted">Show/Hide Columns</div>
                            <label class="column-picker-item"><input type="checkbox" data-column="1" checked> ID</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="2" checked> Type</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="3" checked> Inv#</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="4" checked> Entry Date</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="5" checked> Type Label</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="6" checked> Party</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="7" checked> Remarks</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="8" checked> Total Amount</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="9" checked> Status</label>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="expenseVoucherTable" class="table table-sm table-striped table-bordered w-100 mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Type</th>
                                    <th>Inv#</th>
                                    <th>Entry Date</th>
                                    <th>Party Type</th>
                                    <th>Party</th>
                                    <th>Remarks</th>
                                    <th class="text-end">Total Amount</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center" style="min-width: 140px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($expenses as $item)
                                <tr>
                                    <td class="text-muted small">{{ $item->id }}</td>
                                    <td class="small">EV</td>
                                    <td class="fw-bold text-danger">{{ $item->evid }}</td>
                                    <td class="small">{{ $item->entry_date ? \Carbon\Carbon::parse($item->entry_date)->format('d-M-Y') : '-' }}</td>
                                    <td>
                                        <span class="badge bg-light text-danger border px-2 py-0" style="font-size: 9px;">{{ $item->type_label ?? '-' }}</span>
                                    </td>
                                    <td class="fw-bold text-dark small">{{ Str::limit($item->party_name ?? '-', 25) }}</td>
                                    <td class="small text-muted">{{ Str::limit($item->remarks, 30) }}</td>
                                    <td class="text-end fw-bold text-dark">{{ number_format((float)$item->total_amount, 0) }}</td>
                                    <td class="text-center">
                                        @if($item->status === 'posted')
                                            <span class="badge bg-success rounded-pill px-3" style="font-size: 9px;">Posted</span>
                                        @else
                                            <span class="badge bg-warning text-dark rounded-pill px-3" style="font-size: 9px;">Unposted</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            @if($item->status === 'draft' || $item->status === 'Unposted')
                                                <form action="{{ route('Expense.vochers.post', $item->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-primary btn-mini px-2" title="Post now">
                                                        <i class="fa fa-send me-1"></i> Post
                                                    </button>
                                                </form>
                                                
                                                <a href="{{ route('expense-vochers', $item->id) }}" class="btn btn-outline-warning btn-mini" title="Edit">
                                                    <i class="fa fa-pencil text-dark"></i>
                                                </a>

                                                <form action="{{ route('Expense.vochers.cancel', $item->id) }}" method="POST" class="d-inline delete-form">
                                                    @csrf @method('DELETE')
                                                    <button type="button" class="btn btn-outline-danger btn-mini delete-btn" title="Delete">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            
                                            <a href="{{ route('ExpenseVoucher.print', $item->id) }}" target="_blank" class="btn btn-outline-dark btn-mini" title="Print">
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
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('.select2').select2({ width: '100%' });

        // Column Picker Logic
        $('#columnPickerBtn').on('click', function(e) {
            e.stopPropagation();
            $('#columnPickerMenu').toggleClass('show');
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('.column-picker-dropdown').length) {
                $('#columnPickerMenu').removeClass('show');
            }
        });

        const storageKey = 'expense_voucher_cols_v2';
        
        var dt = $('#expenseVoucherTable').DataTable({
            "destroy": true, // Fix reinitialisation error
            "scrollX": true,
            "autoWidth": false,
            "pageLength": 25,
            "order": [[0, 'desc']],
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search expenses..."
            },
            dom: 'Bfrtip',
            buttons: [
                'copyHtml5', 'excelHtml5', 'csvHtml5'
            ]
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

        // Confirmation handlers
        $(document).on('click', '.delete-btn', function() {
            var form = $(this).closest('form');
            Swal.fire({
                title: 'Delete Voucher?',
                text: 'This unposted record will be removed permanently.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete!'
            }).then((result) => { if (result.isConfirmed) form.submit(); });
        });
    });
</script>
@endsection