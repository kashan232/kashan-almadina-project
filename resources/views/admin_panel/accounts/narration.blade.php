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
        background-color: #f8f9fa !important;
        color: #333 !important;
        font-weight: 600;
        vertical-align: middle;
        padding: 8px 10px !important;
        font-size: 12px;
        text-transform: uppercase;
    }
    
    #narrationsTable tbody td {
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
        border: none;
    }
    
    .card-header {
        background-color: #fff;
        border-bottom: 1px solid #edf2f9;
    }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-3">
            
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
                <i class="fa fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            <!-- Header and Filters -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div class="d-flex align-items-center">
                                <h6 class="mb-0 fw-bold text-dark ms-2 me-4"><i class="fa fa-commenting-o me-2 text-primary"></i>Voucher Narrations</h6>
                                <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" id="addBtn">
                                    <i class="fa fa-plus-circle me-1"></i> Add Narration
                                </button>
                            </div>
                            
                            <form action="{{ route('coa.narration') }}" method="GET" class="d-flex gap-1 align-items-center">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white border-end-0 small fw-bold text-muted">Range</span>
                                    <input type="date" name="start_date" class="form-control border-start-0" value="{{ request('start_date') }}">
                                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Filter</button>
                                <a href="{{ route('coa.narration') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-2" title="Reset"><i class="fa fa-refresh"></i></a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span class="fw-bold text-muted small text-uppercase">Narrations Log</span>
                            <div class="column-picker-dropdown">
                                <button class="btn btn-outline-secondary btn-sm px-3 rounded-pill" type="button" id="columnPickerBtn">
                                    <i class="fa fa-columns me-1"></i> Columns
                                </button>
                                <div class="column-picker-menu shadow" id="columnPickerMenu">
                                    <div class="p-2 border-bottom fw-bold small text-muted">Show/Hide Columns</div>
                                    <label class="column-picker-item"><input type="checkbox" data-column="1" checked> #</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="2" checked> Voucher Type</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="3" checked> Narration Text</label>
                                    <label class="column-picker-item"><input type="checkbox" data-column="4" checked> Date</label>
                                </div>
                            </div>
                        </div>

                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table id="narrationsTable" class="table table-sm table-striped table-bordered w-100 mb-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Voucher Type</th>
                                            <th>Narration Text</th>
                                            <th class="text-center">Date</th>
                                            <th class="text-center" style="width: 100px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($narrations as $key => $row)
                                        <tr>
                                            <td class="text-center text-muted small">{{ $key+1 }}</td>
                                            <td><span class="small fw-semibold text-primary">{{ $row->expense_head }}</span></td>
                                            <td class="text-dark small">{{ $row->narration }}</td>
                                            <td class="text-center small text-muted">{{ $row->created_at->format('d-M-Y') }}</td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    <button class="btn btn-outline-warning btn-xs px-1 py-0 editBtn" 
                                                        data-id="{{ $row->id }}"
                                                        data-expense="{{ $row->expense_head }}"
                                                        data-narration="{{ $row->narration }}" title="Edit"
                                                        style="height: 20px;">
                                                        <i class="fa fa-edit text-dark"></i>
                                                    </button>
                                                    <form action="{{ route('narrations.destroy', $row->id) }}" method="POST" class="d-inline">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" onclick="return confirm('Delete this narration?')" class="btn btn-outline-danger btn-xs px-1 py-0" title="Delete" style="height: 20px;">
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
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title fw-bold" id="modalTitle"><i class="fa fa-plus-circle me-2"></i>Narration Setup</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <input type="hidden" name="id" id="narration_id">

                <div class="mb-2">
                    <label class="form-label small fw-bold">Select Voucher Head</label>
                    <select name="expense_head" id="expense_head" class="form-select form-select-sm" required>
                        <option value="" disabled selected>Choose Head...</option>
                        <option value="Receipts Voucher">Receipts Voucher</option>
                        <option value="Expense voucher">Expense voucher</option>
                        <option value="Income voucher">Income voucher</option>
                        <option value="Journal voucher">Journal voucher</option>
                        <option value="Payment voucher">Payment voucher</option>
                        <option value="Adjustment voucher">Adjustment voucher</option>
                    </select>
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-bold">Narration Text</label>
                    <textarea name="narration" id="narration" class="form-control form-control-sm" rows="3" placeholder="Enter narration text here..." required></textarea>
                </div>
            </div>
            <div class="modal-footer py-1">
                <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm" id="saveBtn">Save Narration</button>
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

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.column-picker-dropdown').length) {
            $('#columnPickerMenu').removeClass('show');
        }
    });

    const storageKey = 'narration_columns_v2';
    
    var dt = $('#narrationsTable').DataTable({
        scrollX: true,
        autoWidth: false,
        pageLength: 25,
        order: [[0, 'asc']],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search narrations..."
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
