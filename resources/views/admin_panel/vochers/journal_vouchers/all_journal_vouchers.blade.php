@extends('admin_panel.layout.app')

@section('content')
<style>
    .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; margin-bottom: 1rem; }
    #journalTable thead th { white-space: nowrap; background-color: #f8f9fa; color: #333; font-weight: 600; vertical-align: middle; }
    #journalTable tbody td { white-space: nowrap; vertical-align: middle; }
    .column-picker-dropdown { position: relative; display: inline-block; }
    .column-picker-menu {
        position: absolute; top: 100%; right: 0; z-index: 1000; display: none; min-width: 200px;
        padding: 5px 0; margin: 2px 0 0; font-size: 14px; text-align: left; list-style: none;
        background-color: #fff; background-clip: padding-box; border: 1px solid rgba(0,0,0,.15);
        border-radius: 4px; box-shadow: 0 6px 12px rgba(0,0,0,.175); max-height: 400px; overflow-y: auto;
    }
    .column-picker-menu.show { display: block; }
    .column-picker-item { display: block; padding: 4px 15px; clear: both; font-weight: 400; line-height: 1.42857143; color: #333; white-space: nowrap; cursor: pointer; }
    .column-picker-item:hover { background-color: #f5f5f5; }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-4">
            
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="fa fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-3">
                            <form action="{{ route('all-journal-vochers') }}" method="GET" class="row g-3 align-items-end">
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
                                        <button type="submit" class="btn btn-success btn-sm px-4 rounded-pill shadow-sm">
                                            <i class="fa fa-filter me-1"></i> Filter
                                        </button>
                                        <a href="{{ route('all-journal-vochers') }}" class="btn btn-outline-secondary btn-sm px-4 rounded-pill">
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
                        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                            <h4 class="card-title mb-0 fw-bold text-dark"><i class="fa fa-book me-2 text-success"></i>Journal Vouchers</h4>
                            <div class="d-flex gap-2">
                                <div class="column-picker-dropdown">
                                    <button class="btn btn-outline-secondary btn-sm px-3 rounded-pill" type="button" id="columnPickerBtn">
                                        <i class="fa fa-columns me-1"></i> Columns
                                    </button>
                                    <div class="column-picker-menu shadow" id="columnPickerMenu">
                                        <div class="p-2 border-bottom fw-bold small text-muted">Show/Hide Columns</div>
                                        <label class="column-picker-item"><input type="checkbox" data-column="1" checked> ID</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="2" checked> Type</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="3" checked> Inv#</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="4" checked> Entry Date</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="5" checked> Header Party</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="6" checked> Details</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="7" checked> Dr / Cr Totals</label>
                                        <label class="column-picker-item"><input type="checkbox" data-column="8" checked> Status</label>
                                    </div>
                                </div>
                                <a class="btn btn-success btn-sm px-4 rounded-pill shadow-sm" href="{{ route('journal-vochers') }}">
                                    <i class="fa fa-plus me-1"></i> Add Journal Voucher
                                </a>
                            </div>
                        </div>

                        <div class="card-body p-3">
                            <div class="table-responsive">
                                <table id="journalTable" class="table table-hover table-striped table-bordered display w-100">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Type</th>
                                            <th>Inv#</th>
                                            <th>Entry Date</th>
                                            <th>Header Party</th>
                                            <th>Details (Rows)</th>
                                            <th class="text-end">Dr / Cr Totals</th>
                                            <th>Status</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($vouchers as $item)
                                        <tr>
                                            <td>{{ $item->id }}</td>
                                            <td>JV</td>
                                            <td class="fw-bold text-success">{{ (int) preg_replace('/[^0-9]/', '', substr($item->jvid, strlen('JV-'))) }}</td>
                                            <td>{{ $item->entry_date ? \Carbon\Carbon::parse($item->entry_date)->format('d-M-Y') : '-' }}</td>
                                            <td>
                                                <div class="small fw-bold text-muted text-uppercase" style="font-size: 0.7rem;">{{ $item->type_label }}</div>
                                                <div class="fw-bold text-dark">{{ $item->party_name }}</div>
                                                <div class="small text-muted">ID: {{ $item->party_code }}</div>
                                            </td>
                                            <td>
                                                <div style="font-size: 0.8rem; line-height: 1.2;">
                                                    {!! $item->accounts_detail !!}
                                                </div>
                                            </td>
                                            <td class="text-end fw-bold">
                                                <div class="text-primary">DR: {{ number_format($item->total_debit, 2) }}</div>
                                                <div class="text-danger">CR: {{ number_format($item->total_credit, 2) }}</div>
                                            </td>
                                            <td class="text-center">
                                                @if($item->status === 'posted')
                                                    <span class="badge bg-success px-3 rounded-pill">Posted</span>
                                                @else
                                                    <span class="badge bg-warning text-dark px-3 rounded-pill">Unposted</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    @if($item->status != 'posted')
                                                        <form action="{{ route('journal.vochers.post', $item->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill px-2" title="Post now">
                                                                <i class="fa fa-send"></i> Post
                                                            </button>
                                                        </form>
                                                        <a href="{{ route('journal-vochers', $item->id) }}" class="btn btn-outline-warning btn-sm rounded-circle" title="Edit">
                                                           <i class="fa fa-edit"></i>
                                                        </a>
                                                        <form action="{{ route('journal.vochers.cancel', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this voucher?')">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-circle" title="Delete">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                    <a href="{{ route('journalVoucher.print', $item->id) }}" target="_blank" class="btn btn-outline-dark btn-sm rounded-circle" title="Print">
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
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('#columnPickerBtn').on('click', function(e) { e.stopPropagation(); $('#columnPickerMenu').toggleClass('show'); });
    $(document).on('click', function(e) { if (!$(e.target).closest('.column-picker-dropdown').length) $('#columnPickerMenu').removeClass('show'); });

    var dt = $('#journalTable').DataTable({
        scrollX: true, autoWidth: false, pageLength: 25, order: [[0, 'desc']],
        columnDefs: [{ targets: [7], orderable: false }],
        language: { search: "_INPUT_", searchPlaceholder: "Search journals..." }
    });

    $('#columnPickerMenu input').on('change', function() {
        const colIdx = parseInt($(this).data('column'));
        dt.column(colIdx - 1).visible($(this).is(':checked'));
        dt.columns.adjust().draw();
    });
});
</script>
@endsection
