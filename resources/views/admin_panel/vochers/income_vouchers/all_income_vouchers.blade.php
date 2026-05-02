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
    
    #incomeTable thead th {
        white-space: nowrap;
        background-color: #f8f9fa !important;
        color: #333 !important;
        font-weight: 600;
        vertical-align: middle;
        padding: 8px 10px !important;
        font-size: 12px;
        text-transform: uppercase;
    }
    
    #incomeTable tbody td {
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

    /* Card styling */
    .card {
        border-radius: 8px;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: none;
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
                            <form action="{{ route('all-income-vochers') }}" method="GET" class="row g-2 align-items-center">
                                <div class="col-md-3">
                                    <h6 class="mb-0 fw-bold text-dark ms-2"><i class="fa fa-line-chart me-2 text-success"></i>Income Vouchers</h6>
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
                                        <a href="{{ route('all-income-vochers') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-2" title="Reset"><i class="fa fa-refresh"></i></a>
                                        <a class="btn btn-success btn-sm rounded-pill px-4 shadow-sm ms-2" href="{{ route('income-vochers') }}">
                                            <i class="fa fa-plus me-1"></i> Add Income
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
                            <span class="fw-bold text-muted small text-uppercase">Income Registry</span>
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
                                <table id="incomeTable" class="table table-sm table-striped table-bordered w-100 mb-0">
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
                                            <th class="text-center" style="min-width: 120px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($incomes as $item)
                                        <tr>
                                            <td class="text-center text-muted small">{{ $item->id }}</td>
                                            <td class="text-center small">IV</td>
                                            <td class="fw-bold text-success">{{ (int) preg_replace('/[^0-9]/', '', substr($item->ivid, strlen('IVID-'))) }}</td>
                                            <td class="small">{{ $item->entry_date ? \Carbon\Carbon::parse($item->entry_date)->format('d-M-Y') : '-' }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-success border-0 rounded-pill px-2" style="font-size: 9px;">{{ $item->type_label ?? '-' }}</span>
                                            </td>
                                            <td class="fw-semibold text-dark small">{{ $item->party_name ?? '-' }}</td>
                                            <td class="small text-muted">{{ Str::limit($item->remarks, 30) }}</td>
                                            <td class="text-end fw-bold">{{ number_format((float)$item->total_amount, 0) }}</td>
                                            <td class="text-center">
                                                @if($item->status === 'posted')
                                                    <span class="badge bg-success rounded-pill px-3">Posted</span>
                                                @else
                                                    <span class="badge bg-warning text-dark rounded-pill px-3">Unposted</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    @if($item->status === 'draft' || $item->status === 'Unposted' || $item->status === 'unposted')
                                                        <form action="{{ route('income.vochers.post', $item->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-primary btn-xs px-2 py-0" title="Post now" style="font-size: 10px;">
                                                                <i class="fa fa-send"></i> Post
                                                            </button>
                                                        </form>
                                                        
                                                        <a href="{{ route('income-vochers', $item->id) }}" class="btn btn-outline-warning btn-xs px-1 py-0" title="Edit" style="height: 20px;">
                                                            <i class="fa fa-edit text-dark"></i>
                                                        </a>

                                                        <form action="{{ route('income.vochers.cancel', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this unposted voucher?')">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="btn btn-outline-danger btn-xs px-1 py-0" title="Delete" style="height: 20px;">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                    
                                                    <a href="{{ route('incomeVoucher.print', $item->id) }}" target="_blank" class="btn btn-outline-dark btn-xs px-1 py-0" title="Print" style="height: 20px;">
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

        const storageKey = 'income_voucher_table_columns_v3';
        
        var dt = $('#incomeTable').DataTable({
            scrollX: true,
            autoWidth: false,
            pageLength: 25,
            order: [[0, 'desc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search income..."
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
