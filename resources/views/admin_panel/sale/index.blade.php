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
    
    #saleListingTable thead th {
        white-space: nowrap;
        background-color: #f8f9fa;
        color: #333;
        font-weight: 600;
        vertical-align: middle;
        font-size: 13px;
    }
    
    #saleListingTable tbody td {
        white-space: nowrap;
        vertical-align: middle;
        font-size: 13px;
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
        min-width: 220px;
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
        max-height: 450px;
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

    .badge-unposted {
        background-color: #ffc107;
        color: #000;
    }
    .badge-posted {
        background-color: #198754;
        color: #fff;
    }
    
    .item-detail-row {
        font-size: 11px;
        border-bottom: 1px dashed #eee;
        padding: 2px 0;
    }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-4">
            
            <!-- Filters Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-3">
                            <form action="{{ route('sale.index') }}" method="GET" class="row g-3 align-items-end">
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
                                        <a href="{{ route('sale.index') }}" class="btn btn-outline-secondary btn-sm px-4 rounded-pill">
                                            <i class="fa fa-refresh me-1"></i> Reset
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0">
                <div class="card-header d-flex justify-content-between align-items-center py-3">
                    <h4 class="mb-0 fw-bold">Sales & Bookings Management</h4>
                    <div class="d-flex gap-2">
                        <!-- Column Picker Button -->
                        <div class="column-picker-dropdown">
                            <button class="btn btn-outline-secondary btn-sm px-3 rounded-pill" type="button" id="columnPickerBtn">
                                <i class="fa fa-columns me-1"></i> Columns
                            </button>
                            <div class="column-picker-menu shadow" id="columnPickerMenu">
                                <div class="p-2 border-bottom fw-bold small text-muted">Show/Hide Columns</div>
                                <label class="column-picker-item"><input type="checkbox" data-column="1" checked> #</label>
                                <label class="column-picker-item"><input type="checkbox" data-column="2" checked> Invoice#</label>
                                <label class="column-picker-item"><input type="checkbox" data-column="3" checked> Manual Inv</label>
                                <label class="column-picker-item"><input type="checkbox" data-column="4" checked> Party Type</label>
                                <label class="column-picker-item"><input type="checkbox" data-column="5" checked> Customer/Vendor</label>
                                <label class="column-picker-item"><input type="checkbox" data-column="6" checked> Items</label>
                                <label class="column-picker-item"><input type="checkbox" data-column="7" checked> Rate</label>
                                <label class="column-picker-item"><input type="checkbox" data-column="8" checked> Qty</label>
                                <label class="column-picker-item"><input type="checkbox" data-column="9" checked> Sale Price</label>
                                <label class="column-picker-item"><input type="checkbox" data-column="10" checked> Line Total</label>
                                <label class="column-picker-item"><input type="checkbox" data-column="11" checked> Net Total</label>
                                <label class="column-picker-item"><input type="checkbox" data-column="12" checked> Disc</label>
                                <label class="column-picker-item"><input type="checkbox" data-column="13" checked> Prev Bal</label>
                                <label class="column-picker-item"><input type="checkbox" data-column="14" checked> Receipts</label>
                                <label class="column-picker-item"><input type="checkbox" data-column="15" checked> Payable</label>
                                <label class="column-picker-item"><input type="checkbox" data-column="16" checked> Date</label>
                                <label class="column-picker-item"><input type="checkbox" data-column="17" checked> Status</label>
                            </div>
                        </div>

                        <a class="btn btn-primary btn-sm px-4 rounded-pill" href="{{ route('sale.add') }}">
                            <i class="fa fa-plus me-1"></i> Add Sale
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="saleListingTable" class="table table-striped table-bordered display w-100">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Invoice#</th>
                                    <th>Manual Inv</th>
                                    <th>Party Type</th>
                                    <th>Customer/Vendor</th>
                                    <th>Items</th>
                                    <th>Rate</th>
                                    <th>Qty</th>
                                    <th>Sale Price</th>
                                    <th>Line Total</th>
                                    <th class="text-end">Net Total</th>
                                    <th class="text-end">Disc</th>
                                    <th class="text-end">Prev Bal</th>
                                    <th class="text-end">Receipts</th>
                                    <th class="text-end text-primary">Payable</th>
                                    <th>Date</th>
                                    <th class="text-center">Status</th>
                                    <th style="width: 120px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sales as $key => $sale)
                                <tr>
                                    <td>{{ $key+1 }}</td>
                                    <td class="fw-bold text-primary">{{ $sale->invoice_no }}</td>
                                    <td>{{ $sale->manual_invoice ?? '-' }}</td>
                                    <td>
                                        @if($sale->p_type === 'vendor')
                                            <span class="badge bg-info">Vendor</span>
                                        @elseif($sale->p_type === 'customer')
                                            <span class="badge bg-primary">Customer</span>
                                        @elseif($sale->p_type === 'walking')
                                            <span class="badge bg-secondary">Walk-in</span>
                                        @else
                                            <span class="badge bg-light text-dark">{{ ucfirst($sale->p_type ?? 'N/A') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($sale->p_type === 'vendor')
                                            <span class="fw-bold">{{ $sale->vendor->name ?? 'N/A' }}</span>
                                        @else
                                            <span class="fw-bold">{{ $sale->customer->customer_name ?? 'N/A' }}</span>
                                        @endif
                                    </td>
                                    
                                    {{-- Items Detailed Columns --}}
                                    <td class="small">
                                        @foreach($sale->items as $item)
                                            <div class="item-detail-row">
                                                {{ $item->product->name ?? 'Product #'.$item->product_id }}
                                            </div>
                                        @endforeach
                                    </td>
                                    <td class="small text-end">
                                        @foreach($sale->items as $item)
                                            <div class="item-detail-row">
                                                {{ number_format($item->retail_price ?? 0, 0) }}
                                            </div>
                                        @endforeach
                                    </td>
                                    <td class="small text-center">
                                        @foreach($sale->items as $item)
                                            <div class="item-detail-row">
                                                {{ number_format($item->sales_qty ?? 0, 0) }}
                                            </div>
                                        @endforeach
                                    </td>
                                    <td class="small text-end">
                                        @foreach($sale->items as $item)
                                            <div class="item-detail-row">
                                                {{ number_format($item->sales_price ?? 0, 0) }}
                                            </div>
                                        @endforeach
                                    </td>
                                    <td class="small text-end">
                                        @foreach($sale->items as $item)
                                            <div class="item-detail-row fw-semibold">
                                                {{ number_format($item->amount ?? 0, 0) }}
                                            </div>
                                        @endforeach
                                    </td>

                                    {{-- Financial Summary --}}
                                    <td class="text-end">{{ number_format($sale->sub_total2 ?? ($sale->items->sum('amount') ?? 0), 0) }}</td>
                                    <td class="text-end text-danger">{{ number_format($sale->discount_amount ?? 0, 0) }}</td>
                                    <td class="text-end text-warning">{{ number_format($sale->previous_balance ?? 0, 0) }}</td>
                                    <td class="text-end text-success">
                                        {{ number_format(($sale->receipt1 ?? 0) + ($sale->receipt2 ?? 0), 0) }}
                                    </td>
                                    <td class="text-end fw-bold text-primary">{{ number_format($sale->total_balance, 0) }}</td>
                                    
                                    <td data-order="{{ $sale->created_at }}">
                                        {{ \Carbon\Carbon::parse($sale->created_at)->format('d-M-Y') }}
                                    </td>
                                    <td class="text-center">
                                        @if($sale->entry_status === 'Posted')
                                            <span class="badge badge-posted px-2 py-1 shadow-sm">Posted</span>
                                        @else
                                            <span class="badge badge-unposted px-2 py-1 shadow-sm">Unposted</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 justify-content-center">
                                            @if($sale->entry_status === 'Unposted')
                                                <form action="{{ route('sale.post', $sale->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill px-2" title="Post now">
                                                        <i class="fa fa-send"></i> Post
                                                    </button>
                                                </form>

                                                <a class="btn btn-outline-warning btn-sm rounded-circle" href="{{ route('editBooking.index', $sale->id) }}" title="Edit">
                                                    <i class="fa fa-edit"></i>
                                                </a>

                                                <form action="{{ route('sale.destroy', $sale->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this unposted booking?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-circle" title="Delete">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>

                                                <a class="btn btn-outline-dark btn-sm rounded-circle" href="{{ route('booking.print', $sale->id) }}" title="Print Preview">
                                                   <i class="fa fa-file-text-o"></i>
                                               </a>
                                            @else
                                                <a class="btn btn-outline-dark btn-sm rounded-circle" href="{{ route('sale.invoice', $sale->id) }}" title="Invoice">
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
        const storageKey = 'sale_table_columns_v1';
        
        function saveState() {
            const state = {};
            $('#columnPickerMenu input').each(function() {
                state[$(this).data('column')] = $(this).is(':checked');
            });
            localStorage.setItem(storageKey, JSON.stringify(state));
        }

        // Initialize DataTable
        var dt = $('#saleListingTable').DataTable({
            "order": [[1, 'desc']], // Default sort by Invoice# Descending
            "pageLength": 25,
            "scrollX": true,
            "autoWidth": false,
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search sales..."
            },
            "columnDefs": [
                { "orderable": false, "targets": [5, 6, 7, 8, 9, 17] } // Disable sort for details and actions
            ],
            dom: 'Bfrtip',
            buttons: [
                'copyHtml5',
                'excelHtml5',
                'csvHtml5',
                {
                    extend: 'pdfHtml5',
                    orientation: 'landscape',
                    pageSize: 'A4'
                }
            ]
        });

        // Handle Checkbox Change
        $('#columnPickerMenu input').on('change', function() {
            const colIdx = parseInt($(this).data('column'));
            const isChecked = $(this).is(':checked');
            
            // DataTable column index is 0-based, picker is 1-based
            dt.column(colIdx - 1).visible(isChecked);
            dt.columns.adjust().draw(false);
            saveState();
        });

        // Load initial state
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
    });
</script>
@endsection
