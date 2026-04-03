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
    
    #narrationsTable thead th {
        white-space: nowrap;
        background-color: #f8f9fa;
        color: #333;
        font-weight: 600;
        vertical-align: middle;
    }
    
    #narrationsTable tbody td {
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
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-4">
            
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <i class="fa fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            <!-- Filter Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-3">
                            <form action="{{ route('coa.narration') }}" method="GET" class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted">Start Date</label>
                                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted">End Date</label>
                                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-sm px-4 rounded-pill">
                                            <i class="fa fa-filter me-1"></i> Filter
                                        </button>
                                        <a href="{{ route('coa.narration') }}" class="btn btn-outline-secondary btn-sm px-4 rounded-pill">
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
                        <div class="card-header py-3">
                            <h4 class="card-title mb-0 fw-bold text-dark">Voucher Narrations Log</h4>
                            <div class="d-flex gap-2">
                                <div class="column-picker-dropdown">
                                    <button class="btn btn-outline-secondary btn-sm px-3 rounded-pill" type="button" id="columnPickerBtn">
                                        <i class="fa fa-columns me-1"></i> Columns
                                    </button>
                                    <div class="column-picker-menu shadow" id="columnPickerMenu">
                                        <div class="p-2 border-bottom fw-bold small text-muted">Show/Hide Columns</div>
                                        <label class="column-picker-item"><input type="checkbox" data-column="1" checked> #</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="2" checked> Voucher Type</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="3" checked> Narration</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="4" checked> Date</label>
                                    </div>
                                </div>
                                <button class="btn btn-primary btn-sm px-4 rounded-pill shadow-sm" id="addBtn">
                                    <i class="fa fa-plus-circle me-1"></i> Add Narration
                                </button>
                            </div>
                        </div>

                        <div class="card-body p-3">
                            <div class="table-responsive">
                                <table id="narrationsTable" class="table table-striped table-bordered display w-100">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Voucher Type</th>
                                            <th>Narration Text</th>
                                            <th class="text-center">Date</th>
                                            <th class="text-center" style="width: 100px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($narrations as $key => $row)
                                        <tr>
                                            <td>{{ $key+1 }}</td>
                                            <td><span class="badge bg-light text-dark border">{{ $row->expense_head }}</span></td>
                                            <td>{{ $row->narration }}</td>
                                            <td class="text-center">{{ $row->created_at->format('d-M-Y') }}</td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    <button class="btn btn-outline-warning btn-sm rounded-circle editBtn" 
                                                        data-id="{{ $row->id }}"
                                                        data-expense="{{ $row->expense_head }}"
                                                        data-narration="{{ $row->narration }}" title="Edit">
                                                        <i class="fa fa-edit"></i>
                                                    </button>
                                                    <form action="{{ route('narrations.destroy', $row->id) }}" method="POST" class="d-inline">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" onclick="return confirm('Delete this narration?')" class="btn btn-outline-danger btn-sm rounded-circle" title="Delete">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
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

<!-- Add/Edit Modal -->
<div class="modal fade" id="narrationModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('narrations.store') }}" method="POST" id="narrationForm" class="modal-content border-0 shadow">
            @csrf
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitle"><i class="fa fa-plus-circle me-2"></i>Add Narration</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="id" id="narration_id">

                <div class="mb-3">
                    <label class="form-label fw-bold">Select Voucher Head</label>
                    <select name="expense_head" id="expense_head" class="form-select" required>
                        <option value="" disabled selected>Choose Head...</option>
                        <option value="Receipts Voucher">Receipts Voucher</option>
                        <option value="Expense voucher">Expense voucher</option>
                        <option value="Journal voucher">Journal voucher</option>
                        <option value="Payment voucher">Payment voucher</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Narration Text</label>
                    <textarea name="narration" id="narration" class="form-control" rows="4" placeholder="Enter narration text here..." required></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary btn-sm px-4 rounded-pill" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm px-4 rounded-pill shadow-sm" id="saveBtn">Save Narration</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function(){
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
    const storageKey = 'narration_columns_v1';
    const savedState = localStorage.getItem(storageKey);

    var dt = $('#narrationsTable').DataTable({
        scrollX: true,
        autoWidth: false,
        pageLength: 25,
        order: [[0, 'asc']],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search narrations..."
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

    // Add button click
    $('#addBtn').click(function(){
        $('#modalTitle').html('<i class="fa fa-plus-circle me-2"></i>Add Narration');
        $('#narrationForm')[0].reset();
        $('#narration_id').val('');
        $('#narrationModal').modal('show');
    });

    // Edit button click
    $(document).on('click', '.editBtn', function(){
        $('#modalTitle').html('<i class="fa fa-edit me-2"></i>Edit Narration');
        $('#narration_id').val($(this).data('id'));
        $('#expense_head').val($(this).data('expense'));
        $('#narration').val($(this).data('narration'));
        $('#narrationModal').modal('show');
    });
});
</script>
@endsection
