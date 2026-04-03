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
    
    #accountsTable thead th {
        white-space: nowrap;
        background-color: #f8f9fa;
        color: #333;
        font-weight: 600;
        vertical-align: middle;
    }
    
    #accountsTable tbody td {
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

    .badge-soft-success {
        background-color: #e1f6e1;
        color: #28a745;
    }
    .badge-soft-danger {
        background-color: #fdeaea;
        color: #dc3545;
    }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-4">
            
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            @endif

            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-3">
                            <div class="d-flex flex-wrap gap-2">
                                <button class="btn btn-primary btn-sm px-4 rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#addAccountModal">
                                    <i class="fa fa-plus-circle me-1"></i> Add Account
                                </button>
                                <button class="btn btn-outline-secondary btn-sm px-4 rounded-pill" data-bs-toggle="modal" data-bs-target="#addHeadModal">
                                    <i class="fa fa-folder-plus me-1"></i> Add Head
                                </button>
                                <button class="btn btn-outline-info btn-sm px-4 rounded-pill" data-bs-toggle="modal" data-bs-target="#listHeadsModal">
                                    <i class="fa fa-list-ul me-1"></i> List Heads
                                </button>
                                <a href="{{ route('purcahse-account-allocation') }}" class="btn btn-outline-danger btn-sm px-4 rounded-pill">
                                    <i class="fa fa-history me-1"></i> View History
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card border-0">
                        <div class="card-header d-flex justify-content-between align-items-center py-3">
                            <h4 class="card-title mb-0 fw-bold text-dark">Chart of Accounts</h4>
                            <div class="d-flex gap-2">
                                <div class="column-picker-dropdown">
                                    <button class="btn btn-outline-secondary btn-sm px-3 rounded-pill" type="button" id="columnPickerBtn">
                                        <i class="fa fa-columns me-1"></i> Columns
                                    </button>
                                    <div class="column-picker-menu shadow" id="columnPickerMenu">
                                        <div class="p-2 border-bottom fw-bold small text-muted">Show/Hide Columns</div>
                                        <label class="column-picker-item"><input type="checkbox" data-column="1" checked> #</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="2" checked> Account Code</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="3" checked> Head</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="4" checked> Account Title</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="5" checked> Balance</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="6" checked> Status</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-body p-3">
                            <div class="table-responsive">
                                <table id="accountsTable" class="table table-striped table-bordered display w-100">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Account Code</th>
                                            <th>Expense Head</th>
                                            <th>Account Title</th>
                                            <th class="text-end">Closing Balance</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($accounts as $key => $account)
                                        <tr>
                                            <td>{{ $key+1 }}</td>
                                            <td class="fw-bold">{{ $account->account_code }}</td>
                                            <td><span class="badge bg-light text-dark border">{{ $account->head->name }}</span></td>
                                            <td>{{ $account->title }}</td>
                                            <td class="text-end fw-bold text-danger">{{ number_format($account->opening_balance, 2) }}</td>
                                            <td class="text-center">
                                                @if($account->status)
                                                    <span class="badge badge-soft-success text-dark rounded-pill px-3">Active</span>
                                                @else
                                                    <span class="badge badge-soft-danger text-dark  rounded-pill px-3">Inactive</span>
                                                @endif
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

{{-- MODALS --}}

<!-- Add Account Modal -->
<div class="modal fade" id="addAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('coa.account.store') }}" method="POST" class="modal-content border-0 shadow">
            @csrf
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fa fa-plus-circle me-2"></i>Add New Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-bold">Select Head</label>
                    <select name="head_id" class="form-select" required>
                        <option value="">Select Head</option>
                        @foreach($heads as $head)
                        <option value="{{ $head->id }}">{{ $head->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Account Code</label>
                    <input type="text" name="account_code" class="form-control" placeholder="Auto-generated" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Account Title</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Cash in Hand" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Opening Balance</label>
                    <div class="input-group">
                        <span class="input-group-text">₨</span>
                        <input type="number" step="0.01" name="opening_balance" class="form-control text-end" value="0.00">
                    </div>
                </div>
                <div class="form-check form-switch mt-3">
                    <input class="form-check-input" name="status" type="checkbox" value="on" id="accStatus" checked>
                    <label class="form-check-label fw-bold" for="accStatus">Active Status</label>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary btn-sm px-4 rounded-pill" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm px-4 rounded-pill shadow-sm">Save Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Head Modal -->
<div class="modal fade" id="addHeadModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('coa.head.store') }}" method="POST" class="modal-content border-0 shadow">
            @csrf
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title"><i class="fa fa-folder-plus me-2"></i>Add Account Head</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-bold">Head Code</label>
                    <input type="text" class="form-control bg-light" value="{{ $nextHeadId ?? '' }}" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Head Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Assets" required>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-outline-secondary btn-sm px-4 rounded-pill" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-secondary btn-sm px-4 rounded-pill shadow-sm">Create Head</button>
            </div>
        </form>
    </div>
</div>

<!-- List of Heads Modal -->
<div class="modal fade" id="listHeadsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fa fa-list-ul me-2"></i>Available Account Heads</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="table-responsive">
                    <table id="headsTable" class="table table-hover table-bordered w-100">
                        <thead class="bg-light">
                            <tr>
                                <th style="width: 15%;">Head ID</th>
                                <th>Name</th>
                                <th style="width: 20%;" class="text-center">Status</th>
                                <th style="width: 25%;">Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($heads as $head)
                            <tr>
                                <td class="fw-bold">{{ $head->id }}</td>
                                <td>{{ $head->name }}</td>
                                <td class="text-center">
                                    @if($head->status)
                                        <span class="badge badge-soft-success rounded-pill px-3">Active</span>
                                    @else
                                        <span class="badge badge-soft-danger rounded-pill px-3">Inactive</span>
                                    @endif
                                </td>
                                <td>{{ $head->created_at ? $head->created_at->format('d-M-Y') : '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
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

        // Column Persistence
        const storageKey = 'coa_table_columns_v1';
        const savedState = localStorage.getItem(storageKey);
        
        var dt = $('#accountsTable').DataTable({
            scrollX: true,
            autoWidth: false,
            pageLength: 25,
            order: [[0, 'asc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search accounts..."
            },
            dom: '<"d-flex justify-content-between align-items-center mb-3"lf>rtip'
        });

        if (savedState) {
            const columns = JSON.parse(savedState);
            $('#columnPickerMenu input').each(function() {
                const colIdx = parseInt($(this).data('column'));
                const checked = columns.hasOwnProperty(colIdx) ? columns[colIdx] : true;
                $(this).prop('checked', checked);
                dt.column(colIdx - 1).visible(checked);
            });
        }

        // Handle Checkbox Change
        $('#columnPickerMenu input').on('change', function() {
            const colIdx = $(this).data('column');
            const isChecked = $(this).is(':checked');
            dt.column(parseInt(colIdx) - 1).visible(isChecked);
            
            const state = {};
            $('#columnPickerMenu input').each(function() {
                state[$(this).data('column')] = $(this).is(':checked');
            });
            localStorage.setItem(storageKey, JSON.stringify(state));
            dt.columns.adjust().draw(false);
        });

        // Initialize DataTable for Heads Table inside Modal
        $('#headsTable').DataTable({
            pageLength: 10,
            language: {
                search: "",
                searchPlaceholder: "Search heads..."
            }
        });

        // Auto-generate Account Code on Head selection
        const headSelect = $('select[name="head_id"]');
        const codeInput = $('input[name="account_code"]');
        
        headSelect.on('change', function() {
            const headId = $(this).val();
            if (headId) {
                const url = "{{ url('/coa/next-account-code') }}/" + headId;
                fetch(url)
                    .then(response => response.json())
                    .then(data => codeInput.val(data.code))
                    .catch(error => console.error('Error fetching code:', error));
            } else {
                codeInput.val('');
            }
        });
    });
</script>
@endsection