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
    
    #purchaseTable thead th {
        white-space: nowrap;
        background-color: #f8f9fa;
        color: #333;
        font-weight: 600;
        vertical-align: middle;
        padding: 6px 10px !important;
        font-size: 13px;
    }
    
    #purchaseTable tbody td {
        white-space: nowrap;
        vertical-align: middle;
        padding: 3px 10px !important;
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
    .column-hidden {
        display: none !important;
    }

    /* Card styling */
    .card {
        border-radius: 8px;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
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
                            <form action="{{ route('Purchase.home') }}" method="GET" class="row g-2 align-items-end">
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
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 border-bottom">
                            <h4 class="card-title mb-0 fw-bold text-dark"><i class="fa fa-shopping-cart me-2 text-primary"></i>Purchase Management</h4>
                            <div class="d-flex gap-2">
                                <!-- Column Picker Button -->
                                <div class="column-picker-dropdown">
                                    <button class="btn btn-outline-secondary btn-sm px-3 rounded-pill" type="button" id="columnPickerBtn">
                                        <i class="fa fa-columns me-1"></i> Columns
                                    </button>
                                    <div class="column-picker-menu shadow" id="columnPickerMenu">
                                        <div class="p-2 border-bottom fw-bold small text-muted">Show/Hide Columns</div>
                                        <label class="column-picker-item"><input type="checkbox" data-column="1" checked> ID</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="2" checked> Type</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="3" checked> Inv#</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="4" checked> Source</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="5" checked> Party Type</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="6" checked> Supplier</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="7" checked> Items</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="8" checked> Rate</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="9" checked> Qty</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="10" checked> Date</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="11" checked> Warehouse</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="12" checked> DC / Bilty</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="13" checked> Subtotal</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="14" checked> Disc</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="15" checked> WHT</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="16" checked> Net</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="17" checked> Status</label>
                                    </div>
                                </div>

                                <a class="btn btn-primary btn-sm px-4 rounded-pill shadow-sm" href="{{ route('add_purchase') }}">
                                    <i class="fa fa-plus me-1"></i> Add Purchase
                                </a>
                            </div>
                        </div>

                        <div class="card-body p-0 bg-white">
                            <div class="table-responsive">
                                <table id="purchaseTable" class="table table-sm table-striped table-bordered w-100 mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Type</th>
                                            <th>Inv#</th>
                                            <th>Source</th>
                                            <th>Party Type</th>
                                            <th>Supplier</th>
                                            <th>Items</th>
                                            <th>Rate</th>
                                            <th>Qty</th>
                                            <th>Date</th>
                                            <th>Warehouse</th>
                                            <th>DC / Bilty</th>
                                            <th class="text-end">Subtotal</th>
                                            <th class="text-end">Disc</th>
                                            <th class="text-end">WHT</th>
                                            <th class="text-end">Net</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($Purchase as $purchase)
                                        <tr>
                                            <td>{{ $purchase->id }}</td>
                                            <td>PJ</td>
                                            <td class="fw-bold text-primary">{{ (int) preg_replace('/[^0-9]/', '', substr($purchase->invoice_no, strlen('PUR-'))) }}</td>
                                            <td class="text-center">
                                                @if($purchase->inward_id)
                                                    <span class="badge bg-info px-2">Inward ({{ $purchase->inward_id }})</span>
                                                @else
                                                    <span class="badge bg-success px-2">Direct</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @php
                                                    $pType = $purchase->purchasable_type;
                                                    $model = $purchase->purchasable;
                                                @endphp

                                                @if($pType)
                                                    @if(str_contains($pType, 'Vendor'))
                                                        <span class="badge bg-primary px-2">Vendor</span>
                                                    @elseif(str_contains($pType, 'Customer'))
                                                        @if(optional($model)->customer_type == 'Walking Customer')
                                                            <span class="badge bg-secondary px-2">Walkin</span>
                                                        @else
                                                            <span class="badge bg-warning text-dark px-2">Customer</span>
                                                        @endif
                                                    @else
                                                        <span class="badge bg-secondary px-2">{{ class_basename($pType) }}</span>
                                                    @endif
                                                @else
                                                    <span class="badge bg-secondary px-2">N/A</span>
                                                @endif
                                            </td>
                                            <td class="fw-bold text-dark">{{ $purchase->purchasable->name ?? ($purchase->purchasable->customer_name ?? ($purchase->vendor->name ?? 'N/A')) }}</td>
                                            
                                            <td class="small py-1">
                                                @foreach($purchase->items as $item)
                                                    <div style="font-size: 10.5px; border-bottom: 1px dashed #f0f0f0; padding: 1px 0; line-height: 1.2;">
                                                        {{ $item->product->name ?? 'Unknown' }}
                                                    </div>
                                                @endforeach
                                            </td>
                                            <td class="small text-end fw-bold py-1">
                                                @foreach($purchase->items as $item)
                                                    <div style="font-size: 10.5px; border-bottom: 1px dashed #f0f0f0; padding: 1px 0; line-height: 1.2;">
                                                        {{ number_format($item->price, 0) }}
                                                    </div>
                                                @endforeach
                                            </td>
                                            <td class="small text-center fw-bold text-muted py-1">
                                                @foreach($purchase->items as $item)
                                                    <div style="font-size: 10.5px; border-bottom: 1px dashed #f0f0f0; padding: 1px 0; line-height: 1.2;">
                                                        {{ (float)$item->qty }}
                                                    </div>
                                                @endforeach
                                            </td>

                                            <td>{{ \Carbon\Carbon::parse($purchase->current_date)->format('d-M-Y') }}</td>
                                            <td>
                                                @if($purchase->warehouse_id == 0 || !$purchase->warehouse)
                                                    <span class="text-primary fw-bold px-1">🏠 Shop Stock</span>
                                                @else
                                                    <span class="text-muted fw-bold">📦 {{ $purchase->warehouse->warehouse_name }}</span>
                                                @endif
                                            </td>
                                            
                                            <td>
                                                @if($purchase->dc) <div><small class="text-muted">DC:</small> {{ $purchase->dc }}</div> @endif
                                                @if($purchase->bilty_no) <div><small class="text-muted">Bilty:</small> {{ $purchase->bilty_no }}</div> @endif
                                            </td>

                                            <td class="text-end fw-bold">{{ number_format($purchase->subtotal, 0) }}</td>
                                            <td class="text-end text-danger fw-bold">{{ number_format($purchase->discount, 0) }}</td>
                                            <td class="text-end fw-bold">{{ number_format($purchase->wht, 0) }}</td>
                                            <td class="text-end fw-bold text-success">{{ number_format($purchase->net_amount, 0) }}</td>
                                            <td class="text-center">
                                                @if($purchase->status === 'Posted')
                                                    <span class="badge bg-success rounded-pill px-3">Posted</span>
                                                @else
                                                    <span class="badge bg-warning text-dark rounded-pill px-3">Unposted</span>
                                                @endif
                                            </td>
                                             <td class="text-center">
                                                 <div class="d-flex gap-1 justify-content-center">
                                                     @if($purchase->status === 'Unposted')
                                                         <form action="{{ route('purchase.post', $purchase->id) }}" method="POST" class="d-inline">
                                                             @csrf
                                                             <button type="submit" class="btn btn-primary btn-xs px-2 py-0" title="Post now" style="font-size: 10px;">
                                                                 <i class="fa fa-send"></i> Post
                                                             </button>
                                                         </form>
                                                         
                                                         <a href="{{ route('purchase.edit', $purchase->id) }}" class="btn btn-outline-warning btn-xs px-1 py-0" title="Edit">
                                                            <i class="fa fa-edit"></i>
                                                         </a>

                                                         <form action="{{ route('purchase.destroy', $purchase->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this unposted purchase?')">
                                                             @csrf
                                                             @method('DELETE')
                                                             <button type="submit" class="btn btn-outline-danger btn-xs px-1 py-0" title="Delete">
                                                                 <i class="fa fa-trash"></i>
                                                             </button>
                                                         </form>
                                                     @endif
                                                     
                                                     <a href="{{ route('purchase.invoice', $purchase->id) }}" class="btn btn-outline-dark btn-xs px-1 py-0" title="Print Invoice">
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

        const storageKey = 'purchase_table_columns_v3';
        
        // Initialize DataTable
        var dt = $('#purchaseTable').DataTable({
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