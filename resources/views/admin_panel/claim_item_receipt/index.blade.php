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
    
    #receiptTable thead th {
        white-space: nowrap;
        background-color: #f8f9fa !important;
        color: #333 !important;
        font-weight: 600;
        vertical-align: middle;
        padding: 8px 10px !important;
        font-size: 12px;
        text-transform: uppercase;
    }
    
    #receiptTable tbody td {
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
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-3">
            
            <!-- Filters Section -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-2">
                            <form action="{{ route('claim-item-receipt.index') }}" method="GET" class="row g-2 align-items-center">
                                <div class="col-md-3">
                                    <h6 class="mb-0 fw-bold text-dark ms-2"><i class="fa fa-file-text-o me-2 text-primary"></i>Claim Item Receipt</h6>
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white border-end-0 small fw-bold text-muted">Range</span>
                                        <input type="date" name="start_date" class="form-control border-start-0" value="{{ request('start_date') }}">
                                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                     <select name="claim_type" class="form-select form-select-sm select2">
                                         <option value="">All Claim Types</option>
                                         <option value="receipt" {{ request('claim_type') == 'receipt' ? 'selected' : '' }}>Item Receipt</option>
                                         <option value="credit" {{ request('claim_type') == 'credit' ? 'selected' : '' }}>Credit Note</option>
                                     </select>
                                 </div>
                                 <div class="col-md-2">
                                     <select name="status" class="form-select form-select-sm select2">
                                         <option value="">All Status</option>
                                         <option value="Draft" {{ request('status') == 'Draft' ? 'selected' : '' }}>Draft</option>
                                         <option value="Posted" {{ request('status') == 'Posted' ? 'selected' : '' }}>Posted</option>
                                     </select>
                                 </div>
                                 <div class="col-md-2 text-end">
                                     <div class="d-flex gap-1 justify-content-end align-items-center">
                                         <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">Filter</button>
                                         <a href="{{ route('claim-item-receipt.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-2" title="Reset"><i class="fa fa-refresh"></i></a>
                                         <a class="btn btn-success btn-sm rounded-pill px-4 shadow-sm ms-2" href="{{ route('claim-item-receipt.create') }}">
                                             <i class="fa fa-plus me-1"></i> Add Receipt
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
                            <span class="fw-bold text-muted small text-uppercase">Receipt Registry</span>
                                <div class="column-picker-dropdown">
                                    <button class="btn btn-outline-secondary btn-sm px-3 rounded-pill" type="button" id="columnPickerBtn">
                                        <i class="fa fa-columns me-1"></i> Columns
                                    </button>
                                    <div class="column-picker-menu shadow" id="columnPickerMenu">
                                        <div class="p-2 border-bottom fw-bold small text-muted">Show/Hide Columns</div>
                                        <label class="column-picker-item"><input type="checkbox" data-column="1" checked> Type</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="2" checked> Claim Type</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="3" checked> Inv#</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="4" checked> Inv Date</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="5" checked> Entry Date</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="6" checked> Entry Time</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="7" checked> Party / Supplier</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="8" checked> From (Cr)</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="9" checked> To (Dr)</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="10" checked> Total Qty</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="11" checked> Net Amount</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="12" checked> WHT Tax</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="13" checked> Remarks</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="14" checked> Created By</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="15" checked> Status</label>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table id="receiptTable" class="table table-sm table-striped table-bordered w-100 mb-0">
                                        <thead>
                                            <tr>
                                                <th>Type</th>
                                                <th>Claim Type</th>
                                                <th>Inv#</th>
                                                <th>Inv Date</th>
                                                <th>Entry Date</th>
                                                <th>Entry Time</th>
                                                <th>Party / Supplier</th>
                                                <th>From (Cr)</th>
                                                <th>To (Dr)</th>
                                                <th class="text-center">Total Qty</th>
                                                <th class="text-end">Net Amount</th>
                                                <th class="text-end">WHT Tax</th>
                                                <th>Remarks</th>
                                                <th>Created By</th>
                                                <th class="text-center">Status</th>
                                                <th class="text-center" style="min-width: 120px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($vouchers as $v)
                                            <tr>
                                                <td class="text-muted small text-center fw-bold">CLR</td>
                                                <td class="small">
                                                    @if($v->doc_type == 'credit')
                                                        <span class="badge bg-light text-danger border">Credit Note</span>
                                                    @else
                                                        <span class="badge bg-light text-primary border">Item Receipt</span>
                                                    @endif
                                                </td>
                                                <td class="fw-bold text-primary text-center">
                                                    {{ $v->voucher_no }}
                                                </td>
                                                <td class="small">{{ \Carbon\Carbon::parse($v->date)->format('d-M-Y') }}</td>
                                                <td class="small">{{ \Carbon\Carbon::parse($v->created_at)->format('d-M-Y') }}</td>
                                                <td class="small text-muted">{{ \Carbon\Carbon::parse($v->created_at)->format('h:i A') }}</td>
                                                <td>
                                                    <small class="text-muted d-block" style="font-size:9px;">{{ ucfirst($v->party_type) }}</small>
                                                    <span class="fw-semibold text-dark small">
                                                        @if($v->party_type == 'vendor')
                                                            {{ $v->vendor->name ?? 'N/A' }}
                                                        @else
                                                            {{ $v->customer->customer_name ?? 'N/A' }}
                                                        @endif
                                                    </span>
                                                </td>
                                                @if($v->doc_type == 'credit')
                                                    <td class="small"><span class="text-danger"><i class="fa fa-minus-circle"></i></span> {{ $v->fromWarehouse->warehouse_name ?? 'Shop' }}</td>
                                                    <td class="small text-center text-muted">-</td>
                                                @else
                                                    <td class="small"><span class="text-danger"><i class="fa fa-minus-circle"></i></span> {{ $v->fromWarehouse->warehouse_name ?? 'Shop' }}</td>
                                                    <td class="small"><span class="text-success"><i class="fa fa-plus-circle"></i></span> {{ $v->toWarehouse->warehouse_name ?? 'Shop' }}</td>
                                                @endif
                                                <td class="text-center small fw-bold">{{ $v->items->sum('quantity') }}</td>
                                                <td class="text-end small fw-bold text-dark">
                                                    @if($v->doc_type == 'credit')
                                                        {{ number_format($v->net_total ?? 0, 2) }}
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="text-end small">
                                                    @if($v->doc_type == 'credit' && isset($v->wht_amount))
                                                        {{ number_format($v->wht_amount, 2) }} ({{ $v->wht_percent ?? 0 }}%)
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="small text-truncate" style="max-width: 150px;" title="{{ $v->remarks }}">{{ $v->remarks ?? '-' }}</td>
                                                <td>
                                                    @if($v->creator)
                                                        <span class="text-dark small">{{ $v->creator->name }}</span>
                                                    @else
                                                        <span class="text-muted small">System</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if($v->status == 'Posted')
                                                        <span class="badge bg-success rounded-pill px-3">Posted</span>
                                                    @else
                                                        <span class="badge bg-warning text-dark rounded-pill px-3">Draft</span>
                                                    @endif
                                                </td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    @if($v->status != 'Posted')
                                                        @if($v->doc_type == 'credit')
                                                            <form action="{{ route('claim-credit-note.post', $v->id) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                <button type="submit" class="btn btn-primary btn-xs px-2 py-0" onclick="return confirm('Post this Credit Note?')" title="Post now" style="font-size: 10px;">
                                                                    <i class="fa fa-send"></i> Post
                                                                </button>
                                                            </form>
                                                            <a href="{{ route('claim-credit-note.edit', $v->id) }}" class="btn btn-outline-warning btn-xs px-1 py-0" title="Edit" style="height: 20px;">
                                                                <i class="fa fa-edit text-dark"></i>
                                                            </a>
                                                        @else
                                                            <form action="{{ route('claim-item-receipt.post', $v->id) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                <button type="submit" class="btn btn-primary btn-xs px-2 py-0" onclick="return confirm('Post this receipt?')" title="Post now" style="font-size: 10px;">
                                                                    <i class="fa fa-send"></i> Post
                                                                </button>
                                                            </form>
                                                            <a href="{{ route('claim-item-receipt.edit', $v->id) }}" class="btn btn-outline-warning btn-xs px-1 py-0" title="Edit" style="height: 20px;">
                                                                <i class="fa fa-edit text-dark"></i>
                                                            </a>
                                                        @endif
                                                    @endif

                                                    @if($v->doc_type == 'credit')
                                                        <a href="{{ route('claim-credit-note.view', $v->id) }}" class="btn btn-outline-info btn-xs px-1 py-0" title="View" style="height: 20px;">
                                                            <i class="fa fa-eye"></i>
                                                        </a>
                                                        <a href="{{ route('claim-credit-note.print', $v->id) }}" target="_blank" class="btn btn-outline-dark btn-xs px-1 py-0" title="Print" style="height: 20px;">
                                                            <i class="fa fa-print"></i>
                                                        </a>
                                                    @else
                                                        <a href="{{ route('claim-item-receipt.view', $v->id) }}" class="btn btn-outline-info btn-xs px-1 py-0" title="View" style="height: 20px;">
                                                            <i class="fa fa-eye"></i>
                                                        </a>
                                                        <a href="{{ route('claim-item-receipt.print', $v->id) }}" target="_blank" class="btn btn-outline-dark btn-xs px-1 py-0" title="Print" style="height: 20px;">
                                                            <i class="fa fa-print"></i>
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

        const storageKey = 'claim_receipt_table_cols_v2';
        
        var dt = $('#receiptTable').DataTable({
            scrollX: true,
            autoWidth: false,
            pageLength: 25,
            order: [[0, 'desc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search receipts..."
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
