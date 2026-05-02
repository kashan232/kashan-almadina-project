@extends('admin_panel.layout.app')

@section('content')
@php $isAdmin = Auth::user()->roles->pluck('name')->contains('Admin') || Auth::user()->usertype == 'admin'; @endphp
<style>
    /* Table Responsive & Scroll Enhancements */
    .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 1rem;
    }
    
    #customerTable thead th {
        white-space: nowrap;
        background-color: #f8f9fa !important;
        color: #333 !important;
        font-weight: 600;
        vertical-align: middle;
        padding: 8px 10px !important;
        font-size: 12px;
        text-transform: uppercase;
    }
    
    #customerTable tbody td {
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

    .card {
        border-radius: 8px;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: none;
    }
    
    .badge-status-active {
        background-color: #198754;
        color: #fff;
    }
    .badge-status-inactive {
        background-color: #6c757d;
        color: #fff;
    }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-3">
            
            <!-- Filters & Actions Section -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-2">
                            <form action="{{ route('customers.index') }}" method="GET" class="row g-2 align-items-center">
                                <div class="col-md-3">
                                    <h6 class="mb-0 fw-bold text-dark ms-2"><i class="fa fa-users me-2 text-primary"></i>Customer List</h6>
                                </div>
                                @if($isAdmin)
                                <div class="col-md-3">
                                    <select name="created_by" class="form-select form-select-sm select2">
                                        <option value="">All Users (Created By)</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ request('created_by') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif
                                <div class="col-md-{{ $isAdmin ? '6' : '9' }} text-end">
                                    <div class="d-flex gap-1 justify-content-end align-items-center">
                                        @if($isAdmin)
                                            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">Filter</button>
                                            <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-2" title="Reset"><i class="fa fa-refresh"></i></a>
                                        @endif
                                        <a href="{{ route('customers.inactive') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 ms-2">Inactive</a>
                                        <a href="{{ route('customers.ledger') }}" class="btn btn-info btn-sm rounded-pill px-3 text-white">Ledger</a>
                                        <a href="{{ route('customer.payments') }}" class="btn btn-primary btn-sm rounded-pill px-3">Payments</a>
                                        <a href="{{ route('customers.create') }}" class="btn btn-success btn-sm rounded-pill px-3 ms-2 shadow-sm">+ Add Customer</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="fw-bold text-muted small text-uppercase">Customer Registry</span>
                    <div class="column-picker-dropdown">
                        <button class="btn btn-outline-secondary btn-sm px-3 rounded-pill" type="button" id="columnPickerBtn">
                            <i class="fa fa-columns me-1"></i> Columns
                        </button>
                        <div class="column-picker-menu shadow" id="columnPickerMenu">
                            <div class="p-2 border-bottom fw-bold small text-muted">Show/Hide Columns</div>
                            <label class="column-picker-item"><input type="checkbox" data-column="1" checked> DB ID</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="2" checked> ID</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="3" checked> Type</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="4" checked> Name</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="5" checked> Groups</label>
                            @if($isAdmin)
                                <label class="column-picker-item"><input type="checkbox" data-column="6" checked> Created By</label>
                            @endif
                            @php $shift = $isAdmin ? 0 : -1; @endphp
                            <label class="column-picker-item"><input type="checkbox" data-column="{{ 7 + $shift }}" checked> Mobile</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="{{ 8 + $shift }}" checked> Zone</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="{{ 9 + $shift }}" checked> Opening</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="{{ 10 + $shift }}" checked> Closing</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="{{ 11 + $shift }}" checked> Filer</label>
                            <label class="column-picker-item"><input type="checkbox" data-column="{{ 12 + $shift }}" checked> Status</label>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="customerTable" class="table table-sm table-striped table-bordered w-100 mb-0">
                            <thead>
                                <tr>
                                    <th>DB ID</th>
                                    <th>ID</th>
                                    <th>Type</th>
                                    <th>Name</th>
                                    <th>Groups</th>
                                    @if($isAdmin)
                                        <th>Created By</th>
                                    @endif
                                    <th>Mobile</th>
                                    <th>Zone</th>
                                    <th class="text-end">Opening</th>
                                    <th class="text-end">Closing</th>
                                    <th>Filer</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center" style="min-width: 120px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($customers as $customer)
                                <tr>
                                    <td class="text-muted">{{ $customer->id }}</td>
                                    <td class="fw-bold text-primary">{{ $customer->customer_id }}</td>
                                    <td>
                                        @if($customer->customer_type == 'Main Customer')
                                            <span class="badge bg-success-subtle text-success border border-success px-2 py-0" style="font-size: 10px;">Main</span>
                                        @elseif($customer->customer_type == 'Walking Customer')
                                            <span class="badge bg-warning-subtle text-warning border border-warning px-2 py-0" style="font-size: 10px;">Walking</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary px-2 py-0" style="font-size: 10px;">{{ $customer->customer_type }}</span>
                                        @endif
                                    </td>
                                    <td class="fw-bold text-dark">{{ $customer->customer_name }}</td>
                                    <td>
                                        @if(!empty($customer->user_group_ids))
                                            @foreach($customer->user_group_ids as $groupId)
                                                <span class="badge bg-light text-dark border px-1" style="font-size: 9px;">
                                                    {{ $userGroups[$groupId]->group_name ?? 'N/A' }}
                                                </span>
                                            @endforeach
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    @if($isAdmin)
                                        <td class="small">{{ $customer->creator->name ?? 'System' }}</td>
                                    @endif
                                    <td class="small">{{ $customer->mobile }}</td>
                                    <td class="small">{{ $customer->zone }}</td>
                                    <td class="text-end text-success fw-bold">
                                        {{ $customer->customerLedger ? number_format($customer->customerLedger->opening_balance, 0) : '0' }}
                                    </td>
                                    <td class="text-end text-primary fw-bold">
                                        {{ $customer->customerLedger ? number_format($customer->customerLedger->closing_balance, 0) : '0' }}
                                    </td>
                                    <td class="small">{{ $customer->filer_type }}</td>
                                    <td class="text-center">
                                        @if ($customer->status === 'active')
                                            <span class="badge bg-success rounded-pill px-3">Active</span>
                                        @else
                                            <span class="badge bg-secondary rounded-pill px-3">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-outline-warning btn-xs px-1 py-0" title="Edit" style="height: 20px;">
                                                <i class="fa fa-edit text-dark"></i>
                                            </a>

                                            <a href="{{ route('customers.toggleStatus', $customer->id) }}" class="btn btn-xs {{ $customer->status === 'active' ? 'btn-outline-dark' : 'btn-outline-secondary' }} px-1 py-0" title="Toggle Status" style="height: 20px;">
                                                <i class="fa {{ $customer->status === 'active' ? 'fa-toggle-on text-success' : 'fa-toggle-off' }}"></i>
                                            </a>

                                            @if($customer->sales_count == 0)
                                                <a href="{{ route('customers.destroy', $customer->id) }}" class="btn btn-outline-danger btn-xs px-1 py-0" style="height: 20px;" onclick="return confirm('Delete customer?');">
                                                    <i class="fa fa-trash"></i>
                                                </a>
                                            @else
                                                <button class="btn btn-outline-secondary btn-xs px-1 py-0" disabled title="Has Sales" style="height: 20px;">
                                                    <i class="fa fa-trash"></i>
                                                </button>
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
        $('.select2').select2({ width: '100%' });

        var dt = $('#customerTable').DataTable({
            dom: 'Bfrtip',
            order: [[0, "desc"]],
            pageLength: 25,
            scrollX: true,
            autoWidth: false,
            buttons: [
                'copyHtml5', 'excelHtml5', 'csvHtml5'
            ]
        });

        $('#columnPickerBtn').on('click', function(e) {
            e.stopPropagation();
            $('#columnPickerMenu').toggleClass('show');
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('.column-picker-dropdown').length) {
                $('#columnPickerMenu').removeClass('show');
            }
        });

        const storageKey = 'customer_table_cols_v1';
        
        const savedState = localStorage.getItem(storageKey);
        if (savedState) {
            const columns = JSON.parse(savedState);
            $('#columnPickerMenu input').each(function() {
                const colIdx = parseInt($(this).data('column'));
                if (columns.hasOwnProperty(colIdx)) {
                    const isChecked = columns[colIdx];
                    $(this).prop('checked', isChecked);
                    dt.column(colIdx - 1).visible(isChecked);
                }
            });
        }

        $('#columnPickerMenu input').on('change', function() {
            const colIdx = parseInt($(this).data('column'));
            const isChecked = $(this).is(':checked');
            dt.column(colIdx - 1).visible(isChecked);
            
            const state = {};
            $('#columnPickerMenu input').each(function() {
                state[$(this).data('column')] = $(this).is(':checked');
            });
            localStorage.setItem(storageKey, JSON.stringify(state));
        });
    });
</script>
@endsection