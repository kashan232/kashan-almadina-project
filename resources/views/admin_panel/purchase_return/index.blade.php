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
    
    #example thead th {
        white-space: nowrap;
        background-color: #f8f9fa;
        color: #333;
        font-weight: 600;
        vertical-align: middle;
    }
    
    #example tbody td {
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

    /* Card styling */
    .card {
        border-radius: 8px;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    
    .card-header {
        background-color: #fff;
        border-bottom: 1px solid #edf2f9;
    }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-4">

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
                    <i class="fa fa-check-circle me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0" role="alert">
                    <i class="fa fa-times-circle me-1"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Filter Section -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-3">
                            <form action="{{ route('purchase.return.home') }}" method="GET" class="row g-2 align-items-end">
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
                                        <option value="Unposted" {{ request('status') == 'Unposted' ? 'selected' : '' }}>Unposted</option>
                                        <option value="Posted" {{ request('status') == 'Posted' ? 'selected' : '' }}>Posted</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-sm px-4 rounded-pill shadow-sm">
                                            <i class="fa fa-filter me-1"></i> Filter
                                        </button>
                                        <a href="{{ route('purchase.return.home') }}" class="btn btn-outline-secondary btn-sm px-4 rounded-pill">
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
                            <h4 class="card-title mb-0 fw-bold text-dark"><i class="fa fa-undo me-2 text-danger"></i>Purchase Return Management</h4>
                            <div class="d-flex gap-2">
                                <div class="column-picker-dropdown">
                                    <button class="btn btn-outline-secondary btn-sm px-3 rounded-pill" type="button" id="columnPickerBtn">
                                        <i class="fa fa-columns me-1"></i> Columns
                                    </button>
                                    <div class="column-picker-menu shadow" id="columnPickerMenu">
                                        <div class="p-2 border-bottom fw-bold small text-muted">Show/Hide Columns</div>
                                        <label class="column-picker-item"><input type="checkbox" data-column="0" checked> ID</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="1" checked> Type</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="2" checked> Inv#</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="3" checked> Orig. Purchase</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="4" checked> Supplier</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="5" checked> Items</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="6" checked> Qty</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="7" checked> Date</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="8" checked> Net Amount</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="9" checked> Status</label>
                                    </div>
                                </div>
                                <a class="btn btn-danger btn-sm px-4 rounded-pill shadow-sm" href="{{ route('purchase.return.add') }}">
                                    <i class="fa fa-plus me-1"></i> Add Return
                                </a>
                            </div>
                        </div>

                        <div class="card-body p-3 bg-white">
                            <div class="table-responsive">
                                <table id="example" class="table table-sm table-striped table-bordered display nowrap w-100">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Type</th>
                                            <th>Inv#</th>
                                            <th>Original Purchase</th>
                                            <th>Supplier</th>
                                            <th>Items</th>
                                            <th class="text-center">Qty</th>
                                            <th>Date</th>
                                            <th class="text-end">Net Amount</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($PurchaseReturns as $ret)
                                        <tr>
                                            <td>{{ $ret->id }}</td>
                                            <td>PRJ</td>
                                            <td class="fw-bold text-primary">{{ (int) preg_replace('/[^0-9]/', '', substr($ret->invoice_no, strlen('PUR-RET-'))) }}</td>
                                            <td><span class="badge bg-outline-secondary text-dark border">{{ $ret->purchase->invoice_no ?? 'N/A' }}</span></td>
                                            <td class="fw-bold text-dark">{{ $ret->purchasable->name ?? ($ret->purchasable->customer_name ?? 'N/A') }}</td>
                                            <td class="small">
                                                @foreach($ret->items as $item)
                                                    <div style="font-size: 11px; border-bottom: 1px dashed #eee; padding: 2px 0;">
                                                        {{ $item->product->name ?? 'Unknown' }}
                                                    </div>
                                                @endforeach
                                            </td>
                                            <td class="small text-center fw-bold text-muted">
                                                @foreach($ret->items as $item)
                                                    <div style="font-size: 11px; border-bottom: 1px dashed #eee; padding: 2px 0;">
                                                        {{ (float)$item->qty }}
                                                    </div>
                                                @endforeach
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($ret->current_date)->format('d-M-Y') }}</td>
                                            <td class="text-end fw-bold text-danger">{{ number_format($ret->net_amount, 0) }}</td>
                                            <td class="text-center">
                                                @if($ret->status === 'Posted')
                                                    <span class="badge bg-success rounded-pill px-3">Posted</span>
                                                @else
                                                    <span class="badge bg-warning text-dark rounded-pill px-3">Unposted</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    @if($ret->status === 'Unposted')
                                                        <form action="{{ route('purchase.return.post', $ret->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-primary btn-xs px-2 py-0" title="Post now" style="font-size: 10px;">
                                                                <i class="fa fa-send"></i> Post
                                                            </button>
                                                        </form>
                                                        
                                                        <a href="{{ route('purchase.return.edit', $ret->id) }}" class="btn btn-outline-warning btn-xs px-1 py-0" title="Edit">
                                                            <i class="fa fa-edit"></i>
                                                        </a>

                                                        <form action="{{ route('purchase.return.destroy', $ret->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this unposted return?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-outline-danger btn-xs px-1 py-0" title="Delete">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                    
                                                    <a href="{{ route('purchase.return.print', $ret->id) }}" target="_blank" class="btn btn-outline-dark btn-xs px-1 py-0" title="Print Return">
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
        var dt = $('#example').DataTable({
            destroy: true,
            scrollX: true,
            autoWidth: false,
            pageLength: 25,
            order: [[0, 'desc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search returns..."
            }
        });

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

        const storageKey = 'purchase_return_table_columns_v1';
        const savedState = localStorage.getItem(storageKey);

        if (savedState) {
            const columns = JSON.parse(savedState);
            $('#columnPickerMenu input').each(function() {
                const colIdx = parseInt($(this).data('column'));
                const checked = columns.hasOwnProperty(colIdx) ? columns[colIdx] : true;
                $(this).prop('checked', checked);
                dt.column(colIdx).visible(checked);
            });
        }

        $('#columnPickerMenu input').on('change', function() {
            const colIdx = $(this).data('column');
            const isChecked = $(this).is(':checked');
            dt.column(parseInt(colIdx)).visible(isChecked);
            
            const state = {};
            $('#columnPickerMenu input').each(function() {
                state[$(this).data('column')] = $(this).is(':checked');
            });
            localStorage.setItem(storageKey, JSON.stringify(state));
            dt.columns.adjust().draw(false);
        });
    });
</script>
@endsection
