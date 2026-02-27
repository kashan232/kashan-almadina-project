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
    .column-hidden {
        display: none !important;
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
            <!-- Filter Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-3">
                            <form action="{{ route('Purchase.home') }}" method="GET" class="row g-3 align-items-end">
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
                                        <button type="submit" class="btn btn-primary btn-sm px-4 rounded-pill">
                                            <i class="fa fa-filter me-1"></i> Filter
                                        </button>
                                        <a href="{{ route('Purchase.home') }}" class="btn btn-outline-secondary btn-sm px-4 rounded-pill">
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
                    <div class="card border-0">
                        <div class="card-header d-flex justify-content-between align-items-center py-3">
                            <h4 class="card-title mb-0 fw-bold text-dark">Purchase Management</h4>
                            <div class="d-flex gap-2">
                                <!-- Column Picker Button -->
                                <div class="column-picker-dropdown">
                                    <button class="btn btn-outline-secondary btn-sm px-3 rounded-pill" type="button" id="columnPickerBtn">
                                        <i class="fa fa-columns me-1"></i> Columns
                                    </button>
                                    <div class="column-picker-menu shadow" id="columnPickerMenu">
                                        <div class="p-2 border-bottom fw-bold small text-muted">Show/Hide Columns</div>
                                        <label class="column-picker-item"><input type="checkbox" data-column="1" checked> ID</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="2" checked> Invoice</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="3" checked> Source</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="4" checked> Party Type</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="5" checked> Supplier</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="6" checked> Items</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="7" checked> Rate</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="8" checked> Qty</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="9" checked> Date</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="10" checked> Warehouse</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="11" checked> DC / Bilty</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="12" checked> Subtotal</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="13" checked> Disc</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="14" checked> WHT</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="15" checked> Net</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="16" checked> Status</label>
                                    </div>
                                </div>

                                <a class="btn btn-primary btn-sm px-4 rounded-pill" href="{{ route('add_purchase') }}">
                                    <i class="fa fa-plus me-1"></i> Add Purchase
                                </a>
                            </div>
                        </div>

                        <div class="card-body p-3">
                            <div class="table-responsive">
                                <table id="example" class="table table-striped table-bordered display w-100">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Invoice</th>
                                            <th>Source</th>
                                            <th>Party Type</th>
                                            <th>Supplier</th>
                                            <th>Items</th>
                                            <th>Rate</th>
                                            <th>Qty</th>
                                            <th>Date</th>
                                            <th>Warehouse</th>
                                            <th>DC / Bilty</th>
                                            <th>Subtotal</th>
                                            <th>Disc</th>
                                            <th>WHT</th>
                                            <th>Net</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($Purchase as $purchase)
                                        <tr>
                                            <td>{{ $purchase->id }}</td>
                                            <td class="fw-bold">{{ $purchase->invoice_no ?? '-' }}</td>
                                            <td class="text-center">
                                                @if($purchase->inward_id)
                                                    <span class="badge bg-info">Inward ({{ $purchase->inward_id }})</span>
                                                @else
                                                    <span class="badge bg-success">Direct</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @php
                                                    $pType = $purchase->purchasable_type;
                                                    $model = $purchase->purchasable;
                                                @endphp

                                                @if($pType)
                                                    @if(str_contains($pType, 'Vendor'))
                                                        <span class="badge bg-primary">Vendor</span>
                                                    @elseif(str_contains($pType, 'Customer'))
                                                        @if(optional($model)->customer_type == 'Walking Customer')
                                                            <span class="badge bg-secondary">Walking Customer</span>
                                                        @else
                                                            <span class="badge bg-warning text-dark">Customer</span>
                                                        @endif
                                                    @else
                                                        <span class="badge bg-secondary">{{ class_basename($pType) }}</span>
                                                    @endif
                                                @else
                                                    <span class="badge bg-secondary">N/A</span>
                                                @endif
                                            </td>
                                            <td>{{ $purchase->purchasable->name ?? ($purchase->purchasable->customer_name ?? ($purchase->vendor->name ?? 'N/A')) }}</td>
                                            
                                            <td class="small">
                                                @foreach($purchase->items as $item)
                                                    <div style="font-size: 11px; border-bottom: 1px dashed #eee; padding: 2px 0;">
                                                        {{ $item->product->name ?? 'Unknown' }}
                                                    </div>
                                                @endforeach
                                            </td>
                                            <td class="small text-end">
                                                @foreach($purchase->items as $item)
                                                    <div style="font-size: 11px; border-bottom: 1px dashed #eee; padding: 2px 0;">
                                                        {{ number_format($item->price, 0) }}
                                                    </div>
                                                @endforeach
                                            </td>
                                            <td class="small text-center">
                                                @foreach($purchase->items as $item)
                                                    <div style="font-size: 11px; border-bottom: 1px dashed #eee; padding: 2px 0;">
                                                        {{ $item->qty }}
                                                    </div>
                                                @endforeach
                                            </td>

                                            <td>{{ \Carbon\Carbon::parse($purchase->current_date)->format('d-M-Y') }}</td>
                                            <td>{{ $purchase->warehouse->warehouse_name ?? 'N/A' }}</td>
                                            
                                            <td>
                                                @if($purchase->dc) <div><small>DC:</small> {{ $purchase->dc }}</div> @endif
                                                @if($purchase->bilty_no) <div><small>Bilty:</small> {{ $purchase->bilty_no }}</div> @endif
                                            </td>

                                            <td class="text-end">{{ number_format($purchase->subtotal, 0) }}</td>
                                            <td class="text-end text-danger">{{ number_format($purchase->discount, 0) }}</td>
                                            <td class="text-end">{{ number_format($purchase->wht, 0) }}</td>
                                            <td class="text-end fw-bold">{{ number_format($purchase->net_amount, 0) }}</td>
                                            <td class="text-center">
                                                @if($purchase->status === 'Posted')
                                                    <span class="badge bg-success">Posted</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">Unposted</span>
                                                @endif
                                            </td>
                                             <td class="text-center">
                                                 <div class="d-flex gap-1 justify-content-center">
                                                     @if($purchase->status === 'Unposted')
                                                         <form action="{{ route('purchase.post', $purchase->id) }}" method="POST" class="d-inline">
                                                             @csrf
                                                             <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill px-2" title="Post now">
                                                                 <i class="fa fa-send"></i> Post
                                                             </button>
                                                         </form>
                                                         
                                                         <a href="{{ route('purchase.edit', $purchase->id) }}" class="btn btn-outline-warning btn-sm rounded-circle" title="Edit">
                                                            <i class="fa fa-edit"></i>
                                                         </a>

                                                         <form action="{{ route('purchase.destroy', $purchase->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this unposted purchase?')">
                                                             @csrf
                                                             @method('DELETE')
                                                             <button type="submit" class="btn btn-outline-danger btn-sm rounded-circle" title="Delete">
                                                                 <i class="fa fa-trash"></i>
                                                             </button>
                                                         </form>
                                                     @endif
                                                     
                                                     <a href="{{ route('purchase.invoice', $purchase->id) }}" class="btn btn-outline-dark btn-sm rounded-circle" title="Print Invoice">
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

        // Close menu when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.column-picker-dropdown').length) {
                $('#columnPickerMenu').removeClass('show');
            }
        });

        // Column Persistence with LocalStorage
        const storageKey = 'purchase_table_columns_v2';
        
        // Load initial state
        const savedState = localStorage.getItem(storageKey);
        if (savedState) {
            const columns = JSON.parse(savedState);
            $('#columnPickerMenu input').each(function() {
                const colIdx = $(this).data('column');
                if (columns.hasOwnProperty(colIdx)) {
                    $(this).prop('checked', columns[colIdx]);
                    toggleColumn(colIdx, columns[colIdx]);
                }
            });
        }

        // Handle Checkbox Change
        $('#columnPickerMenu input').on('change', function() {
            const colIdx = $(this).data('column');
            const isChecked = $(this).is(':checked');
            
            toggleColumn(colIdx, isChecked);
            saveState();
        });

        function toggleColumn(index, show) {
            const table = $('#example');
            // nth-child is 1-indexed
            const cells = table.find(`th:nth-child(${index}), td:nth-child(${index})`);
            if (show) {
                cells.removeClass('column-hidden');
            } else {
                cells.addClass('column-hidden');
            }
        }

        function saveState() {
            const state = {};
            $('#columnPickerMenu input').each(function() {
                state[$(this).data('column')] = $(this).is(':checked');
            });
            localStorage.setItem(storageKey, JSON.stringify(state));
        }

        // Initialize DataTable
        var dt = $('#example').DataTable({
            destroy: true,
            scrollX: true,
            autoWidth: false,
            pageLength: 25,
            order: [[0, 'desc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search purchases..."
            }
        });

        // Re-apply saved column visibility after DataTable init
        const savedState2 = localStorage.getItem(storageKey);
        if (savedState2) {
            const columns = JSON.parse(savedState2);
            $('#columnPickerMenu input').each(function() {
                const colIdx = parseInt($(this).data('column'));
                const checked = columns.hasOwnProperty(colIdx) ? columns[colIdx] : true;
                $(this).prop('checked', checked);
                // DataTable column index is 0-based, picker is 1-based
                dt.column(colIdx - 1).visible(checked);
            });
            dt.columns.adjust().draw(false);
        }

        // Override toggleColumn to use DataTable API
        toggleColumn = function(index, show) {
            dt.column(parseInt(index) - 1).visible(show);
            dt.columns.adjust().draw(false);
        };
    });

    $(document).on('submit', '.myform', function(e) {
        e.preventDefault();
        var formdata = new FormData(this);
        var url = $(this).attr('action');
        var method = $(this).attr('method');
        $(this).find(':submit').attr('disabled', true);
        myAjax(url, formdata, method);
    });
</script>
@endsection