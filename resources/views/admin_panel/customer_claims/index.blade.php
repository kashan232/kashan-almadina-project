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
                                        <label class="column-picker-item"><input type="checkbox" data-column="1" checked> Type</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="2" checked> Inv#</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="3" checked> Date</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="4" checked> Type</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="5" checked> Party Type</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="6" checked> Party Name</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="7" checked> Item</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="8" checked> MFG Date</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="9" checked> Sales Price</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="10" checked> Serial / Card</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="11" checked> Bill Date</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="12" checked> Deliver From</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="13" checked> Claim WH</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="14" checked> Income</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="15" checked> Fault Found</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="16" checked> Remarks</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="17" checked> Replace Item</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="18" checked> Replace Price</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="19" checked> Replace From</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="20" checked> Status</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="21" checked> Created By</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="22" checked> Created At</label>
                                    </div>
                                </div>
                                <a class="btn btn-primary btn-sm px-4 rounded-pill shadow-sm" href="{{ route('customer-claims.create') }}">
                                    <i class="fa fa-plus me-1"></i> New Claim
                                </a>
                            </div>
                        </div>

                        <div class="card-body p-3 bg-white">
                            <div class="table-responsive">
                                <table id="claimsTable" class="table table-sm table-striped table-bordered display nowrap w-100">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Type</th>
                                            <th>Inv#</th>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Party Type</th>
                                            <th>Party Name</th>
                                            <th>Item</th>
                                            <th>MFG Date</th>
                                            <th>Sales Price</th>
                                            <th>Serial / Card</th>
                                            <th>Bill Date</th>
                                            <th>Deliver From</th>
                                            <th>Claim WH</th>
                                            <th class="text-end">Income</th>
                                            <th>Fault Found</th>
                                            <th>Remarks</th>
                                            <th>Replace Item</th>
                                            <th>Replace Price</th>
                                            <th>Replace From</th>
                                            <th class="text-center">Status</th>
                                            <th>Created By</th>
                                            <th>Created At</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($claims as $claim)
                                        <tr>
                                            <td>CLM</td>
                                            <td class="fw-bold text-primary">{{ (int) preg_replace('/[^0-9]/', '', substr($claim->claim_no, strlen('CLM-'))) }}</td>
                                            <td>{{ \Carbon\Carbon::parse($claim->claim_date)->format('d-M-Y') }}</td>
                                            <td>
                                                @if($claim->claim_type === 'item_return')
                                                    <span class="badge bg-info px-2">Item Return</span>
                                                @elseif($claim->claim_type === 'credit_note')
                                                    <span class="badge bg-primary px-2">Credit Note</span>
                                                @else
                                                    <span class="badge bg-secondary px-2">Hold</span>
                                                @endif
                                            </td>
                                            <td class="text-uppercase small fw-bold text-muted">{{ $claim->party_type }}</td>
                                            <td class="fw-bold text-dark">{{ $claim->party->customer_name ?? ($claim->party->name ?? 'N/A') }}</td>
                                            <td>{{ $claim->product->name ?? 'N/A' }}</td>
                                            <td>{{ $claim->mfg_date ?: '-' }}</td>
                                            <td class="text-end fw-bold">{{ number_format($claim->sales_price, 2) }}</td>
                                            <td>{{ $claim->card_no ?: '-' }}</td>
                                            <td>{{ $claim->bill_date ? \Carbon\Carbon::parse($claim->bill_date)->format('d-M-Y') : '-' }}</td>
                                            <td>{{ $claim->original_warehouse_id == 0 ? 'Shop' : ($claim->originalWarehouse->warehouse_name ?? 'N/A') }}</td>
                                            <td>{{ $claim->warehouse->warehouse_name ?? 'N/A' }}</td>
                                            <td class="text-end fw-bold text-success">{{ number_format($claim->claim_income, 2) }}</td>
                                            <td><small>{{ Str::limit($claim->fault_found, 30) ?: '-' }}</small></td>
                                            <td><small>{{ Str::limit($claim->remarks, 30) ?: '-' }}</small></td>
                                            <td>{{ $claim->replacementProduct->name ?? '-' }}</td>
                                            <td class="text-end fw-bold text-info">{{ $claim->replacement_sales_price ? number_format($claim->replacement_sales_price, 2) : '-' }}</td>
                                            <td>{{ $claim->replacement_from_warehouse_id == 0 ? 'Shop' : ($claim->replacementFromWarehouse->warehouse_name ?? '-') }}</td>
                                            <td class="text-center">
                                                @if($claim->status === 'Posted')
                                                    <span class="badge bg-success rounded-pill px-3">Posted</span>
                                                @else
                                                    <span class="badge bg-warning text-dark rounded-pill px-3">Draft</span>
                                                @endif
                                            </td>
                                            <td>{{ $claim->creator->name ?? 'System' }}</td>
                                            <td>{{ $claim->created_at->format('d-M-Y H:i') }}</td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                     @if($claim->status === 'Draft')
                                                        <form action="{{ route('customer-claims.post', $claim->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-primary btn-xs px-2 py-0" title="Post Now" style="font-size: 10px;">
                                                                <i class="fa fa-send"></i> Post
                                                            </button>
                                                        </form>
                                                         <a href="{{ route('customer-claims.edit', $claim->id) }}" class="btn btn-outline-warning btn-xs px-1 py-0" title="Edit">
                                                             <i class="fa fa-edit"></i>
                                                         </a>
                                                     @endif
                                                    <a href="#" class="btn btn-outline-dark btn-xs px-1 py-0" title="Print">
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

        const storageKey = 'claims_table_cols_v4';
        const savedState = localStorage.getItem(storageKey);
        
        if (savedState) {
            const colSettings = JSON.parse(savedState);
            $('#columnPickerMenu input').each(function() {
                const colNum = $(this).data('column');
                if (colSettings.hasOwnProperty(colNum)) {
                    $(this).prop('checked', colSettings[colNum]);
                }
            });
        }

        // Initialize DataTable with initial visibility
        const columnDefs = [];
        $('#columnPickerMenu input').each(function() {
            const colIdx = parseInt($(this).data('column')) - 1;
            columnDefs.push({
                targets: [colIdx],
                visible: $(this).is(':checked')
            });
        });
        columnDefs.push({ targets: [21], visible: true, orderable: false });

        var dt = $('#claimsTable').DataTable({
            scrollX: true,
            autoWidth: false,
            pageLength: 25,
            order: [[0, 'desc']],
            columnDefs: columnDefs,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search claims..."
            },
            initComplete: function() {
                this.api().columns.adjust();
            }
        });

        // Handle dynamically changing visibility
        $('#columnPickerMenu input').on('change', function() {
            const colIdx = parseInt($(this).data('column')) - 1;
            dt.column(colIdx).visible($(this).is(':checked'));
            dt.columns.adjust();

            const newState = {};
            $('#columnPickerMenu input').each(function() {
                newState[$(this).data('column')] = $(this).is(':checked');
            });
            localStorage.setItem(storageKey, JSON.stringify(newState));
        });
    });
</script>
@endsection
