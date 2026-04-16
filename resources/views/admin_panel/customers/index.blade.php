@extends('admin_panel.layout.app')
@section('content')
@php $isAdmin = Auth::user()->roles->pluck('name')->contains('Admin') || Auth::user()->usertype == 'admin'; @endphp
<style>
    .btn-sm i.fa-toggle-on {
        color: green;
        font-size: 20px;
    }

    .btn-sm i.fa-toggle-off {
        color: gray;
        font-size: 20px;
    }

    /* Column Picker Styles added from product index */
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
</style>

<div class="container-fluid mt-4">
    <div class="card shadow-sm border-0">
        <div class="card-header d-flex justify-content-between align-items-center bg-light">
            <h5 class="mb-0">Customer List</h5>
            <div>
                <a href="{{ route('customers.inactive') }}" class="btn btn-sm btn-secondary">Inactive</a>
                <a href="{{ route('customers.ledger') }}" class="btn btn-sm btn-info">Ledger</a>
                <a href="{{ route('customer.payments') }}" class="btn btn-sm btn-primary">Payments</a>
                
                {{-- Column Picker --}}
                <div class="column-picker-dropdown">
                    <button class="btn btn-sm btn-outline-dark" type="button" id="columnPickerBtn">
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
                        {{-- Note: Indices shift based on Admin role --}}
                        @php $shift = $isAdmin ? 0 : -1; @endphp
                        <label class="column-picker-item"><input type="checkbox" data-column="{{ 7 + $shift }}" checked> Mobile</label>
                        <label class="column-picker-item"><input type="checkbox" data-column="{{ 8 + $shift }}" checked> Zone</label>
                        <label class="column-picker-item"><input type="checkbox" data-column="{{ 9 + $shift }}" checked> Opening</label>
                        <label class="column-picker-item"><input type="checkbox" data-column="{{ 10 + $shift }}" checked> Closing</label>
                        <label class="column-picker-item"><input type="checkbox" data-column="{{ 11 + $shift }}" checked> Filer</label>
                        <label class="column-picker-item"><input type="checkbox" data-column="{{ 12 + $shift }}" checked> Status</label>
                    </div>
                </div>

                <a href="{{ route('customers.create') }}" class="btn btn-sm btn-success">+ Add Customer</a>
            </div>
        </div>

        <div class="card-body">
            @if($isAdmin)
            <div class="row mb-3 align-items-end">
                <div class="col-md-4">
                    <form action="{{ route('customers.index') }}" method="GET" class="d-flex gap-2">
                        <div class="flex-grow-1">
                            <label class="form-label small fw-bold">Filter by User:</label>
                            <select name="created_by" class="form-control form-control-sm select2">
                                <option value="">All Users</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ request('created_by') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-sm btn-info mt-4">Filter</button>
                            @if(request('created_by'))
                                <a href="{{ route('customers.index') }}" class="btn btn-sm btn-secondary mt-4">Reset</a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
            @endif

            @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session()->has('error'))
            <div class="alert alert-danger">
                <strong>Error:</strong> {{ session('error') }}
            </div>
            @endif
            <div class="table-responsive">
                <table id="customerTable" class="display table table-bordered" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Customer ID</th>
                            <th>Type</th>
                            <th>Name</th>
                            <th>Groups</th>
                            @if($isAdmin)
                                <th>Created By</th>
                            @endif
                            <th>Mobile</th>
                            <th>Zone</th>
                            <th>Opening Balance</th>
                            <th>Closing Balance</th>
                            <th>Filer Type</th>
                            <th>Status</th>
                            <th width="160">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                        <tr>
                            <td>{{ $customer->id }}</td>
                            <td>{{ $customer->customer_id }}</td>
                            <td>
                                @if($customer->customer_type == 'Main Customer')
                                <span class="badge bg-success">Main Customer</span>
                                @elseif($customer->customer_type == 'Walking Customer')
                                <span class="badge bg-warning text-dark">Walking Customer</span>
                                @else
                                <span class="badge bg-secondary">{{ $customer->customer_type }}</span>
                                @endif
                            </td>
                            <td>{{ $customer->customer_name }}</td>
                            <td>
                                @if(!empty($customer->user_group_ids))
                                    @foreach($customer->user_group_ids as $groupId)
                                        <span class="badge bg-light text-dark border">
                                            {{ $userGroups[$groupId]->group_name ?? 'N/A' }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-muted small">No Group</span>
                                @endif
                            </td>
                            @if($isAdmin)
                                <td>{{ $customer->creator->name ?? 'System' }}</td>
                            @endif
                            <td>{{ $customer->mobile }}</td>
                            <td>{{ $customer->zone }}</td>
                            <!-- Display the Opening and Closing Balance -->
                            <td>
                                @if ($customer->customerLedger)
                                <span
                                    class="text-success fw-bold">{{ number_format($customer->customerLedger->opening_balance, 2) }}</span>
                                @else
                                N/A
                                @endif
                            </td>
                            <td>
                                @if ($customer->customerLedger)
                                <span
                                    class="text-success fw-bold">{{ number_format($customer->customerLedger->closing_balance, 2) }}</span>
                                @else
                                N/A
                                @endif
                            </td>
                            <td>{{ $customer->filer_type }}</td>
                            <td>
                                @if ($customer->status === 'active')
                                <span class="badge bg-success">Active</span>
                                @else
                                <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="d-flex">
                                <a href="{{ route('customers.edit', $customer->id) }}"
                                    class="btn btn-sm btn-warning">Edit</a>

                                <a href="{{ route('customers.toggleStatus', $customer->id) }}"
                                    class="btn btn-sm {{ $customer->status === 'active' ? 'btn-dark' : 'btn-secondary' }}"
                                    title="Toggle Status">
                                    <i
                                        class="fa-solid {{ $customer->status === 'active' ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                </a>

                                @if($customer->sales_count == 0)
                                <a href="{{ route('customers.destroy', $customer->id) }}"
                                    class="btn btn-sm btn-danger"
                                    onclick="return confirm('Are you sure you want to delete this customer?');">
                                    Delete
                                </a>
                                @else
                                <button class="btn btn-sm btn-secondary" disabled title="Cannot delete: customer has sales">
                                    Delete
                                </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        @php $totalCols = $isAdmin ? 13 : 12; @endphp
                        <tr>
                            <td colspan="{{ $totalCols }}" class="text-center text-muted">No customers found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
         // Initialize DataTables
         const table = $('#customerTable').DataTable({
             dom: 'Bfrtip',
             order: [[0, "desc"]],
             buttons: [
                 'copyHtml5',
                 'excelHtml5',
                 'csvHtml5',
                 'pdfHtml5',
                 'colvis'
             ]
         });

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
         const storageKey = 'customer_table_columns_v1';
         
         // Load initial state
         const savedState = localStorage.getItem(storageKey);
         if (savedState) {
             const columns = JSON.parse(savedState);
             $('#columnPickerMenu input').each(function() {
                 const colIdx = $(this).data('column');
                 if (columns.hasOwnProperty(colIdx)) {
                     const isChecked = columns[colIdx];
                     $(this).prop('checked', isChecked);
                     // Using DataTables API for column visibility
                     table.column(colIdx - 1).visible(isChecked);
                 }
             });
         }

         // Handle Checkbox Change
         $('#columnPickerMenu input').on('change', function() {
             const colIdx = $(this).data('column');
             const isChecked = $(this).is(':checked');
             
             // Using DataTables API for column visibility
             table.column(colIdx - 1).visible(isChecked);
             saveState();
         });

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