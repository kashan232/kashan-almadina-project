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
                            <form action="{{ route('all-Payment-vochers') }}" method="GET" class="row g-3 align-items-end">
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
                                        <button type="submit" class="btn btn-primary btn-sm px-4 rounded-pill">
                                            <i class="fa fa-filter me-1"></i> Filter
                                        </button>
                                        <a href="{{ route('all-Payment-vochers') }}" class="btn btn-outline-secondary btn-sm px-4 rounded-pill">
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
                            <h4 class="card-title mb-0 fw-bold text-dark">Payment Vouchers</h4>
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
                                        <label class="column-picker-item"><input type="checkbox" data-column="3" checked> Receipt Date</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="4" checked> Entry Date</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="5" checked> Type</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="6" checked> Party</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="7" checked> Reference No</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="8" checked> Remarks</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="9" checked> Total Amount</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="10" checked> Created At</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="11" checked> Status</label>
                                    </div>
                                </div>

                                <a class="btn btn-primary btn-sm px-4 rounded-pill" href="{{ route('Payment-vochers') }}">
                                    <i class="fa fa-plus me-1"></i> Add Payment Voucher
                                </a>
                            </div>
                        </div>

                        <div class="card-body p-3">
                            <div class="table-responsive">
                                <table id="example" class="table table-striped table-bordered display w-100">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Voucher No</th>
                                            <th>Receipt Date</th>
                                            <th>Entry Date</th>
                                            <th>Type</th>
                                            <th>Party</th>
                                            <th>Reference No</th>
                                            <th>Remarks</th>
                                            <th>Total Amount</th>
                                            <th>Created At</th>
                                            <th>Status</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($receipts as $item)
                                        @php
                                            $refs = json_decode($item->reference_no, true);
                                            $reference = is_array($refs) ? implode(', ', $refs) : $item->reference_no;
                                        @endphp
                                        <tr>
                                            <td>{{ $item->id }}</td>
                                            <td class="fw-bold">{{ $item->pvid }}</td>
                                            <td>{{ $item->receipt_date ? \Carbon\Carbon::parse($item->receipt_date)->format('d-M-Y') : '-' }}</td>
                                            <td>{{ $item->entry_date ? \Carbon\Carbon::parse($item->entry_date)->format('d-M-Y') : '-' }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary">{{ $item->type_label ?? '-' }}</span>
                                            </td>
                                            <td>{{ $item->party_name ?? '-' }}</td>
                                            <td>{{ $reference }}</td>
                                            <td>{{ \Illuminate\Support\Str::limit($item->remarks, 30) }}</td>
                                            <td class="text-end fw-bold">{{ number_format((float)$item->total_amount, 2) }}</td>
                                            <td>{{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('d-M-Y g:i A') : '-' }}</td>
                                            <td class="text-center">
                                                @if($item->status === 'posted')
                                                    <span class="badge bg-success">Posted</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">Unposted</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    @if($item->status === 'draft' || $item->status === 'unposted' || empty($item->status))
                                                        <form action="{{ route('Payment.vochers.post', $item->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill px-2" title="Post now">
                                                                <i class="fa fa-send"></i> Post
                                                            </button>
                                                        </form>
                                                        
                                                        <a href="{{ route('Payment-vochers', $item->id) }}" class="btn btn-outline-warning btn-sm rounded-circle" title="Edit">
                                                           <i class="fa fa-edit"></i>
                                                        </a>

                                                        <form action="{{ route('Payment.vochers.cancel', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this unposted voucher?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-circle" title="Delete">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                    
                                                    @if($item->status === 'posted')
                                                    <form action="{{ route('Payment.vochers.unpost', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Unpost this voucher?')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-2" title="Unpost">
                                                            <i class="fa fa-undo"></i> Unpost
                                                        </button>
                                                    </form>
                                                    @endif

                                                    <a href="{{ route('PaymentVoucher.print', $item->id) }}" target="_blank" class="btn btn-outline-dark btn-sm rounded-circle" title="Print">
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
        const storageKey = 'payment_voucher_table_columns_v1';
        
        // Load initial state
        const savedState = localStorage.getItem(storageKey);
        if (savedState) {
            const columns = JSON.parse(savedState);
            $('#columnPickerMenu input').each(function() {
                const colIdx = $(this).data('column');
                if (columns.hasOwnProperty(colIdx)) {
                    $(this).prop('checked', columns[colIdx]);
                }
            });
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
                searchPlaceholder: "Search vouchers..."
            }
        });

        // Handle Checkbox Change
        $('#columnPickerMenu input').on('change', function() {
            const colIdx = parseInt($(this).data('column'));
            const isChecked = $(this).is(':checked');
            
            dt.column(colIdx - 1).visible(isChecked);
            dt.columns.adjust().draw(false);
            saveState();
        });

        // Re-apply saved column visibility after DataTable init
        if (savedState) {
            const columns = JSON.parse(savedState);
            const currentInputs = $('#columnPickerMenu input');
            currentInputs.each(function() {
                const colIdx = parseInt($(this).data('column'));
                const checked = columns.hasOwnProperty(colIdx) ? columns[colIdx] : true;
                $(this).prop('checked', checked);
                dt.column(colIdx - 1).visible(checked);
            });
            dt.columns.adjust().draw(false);
        }

        function saveState() {
            const state = {};
            $('#columnPickerMenu input').each(function() {
                state[$(this).data('column')] = $(this).is(':checked');
            });
            localStorage.setItem(storageKey, JSON.stringify(state));
        }
    });

</script>
@endsection