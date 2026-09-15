@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-3">
            <div class="card shadow-sm border-0">
                <div class="card-header text-white d-flex justify-content-between align-items-center" style="background-color:#7bbcbe;">
                    <h5 class="mb-0 text-white"><i class="fa fa-file-excel-o me-2"></i>Import Stock Holds from Excel</h5>
                    <a href="{{ route('stock-hold-list') }}" class="btn btn-light btn-sm rounded-pill px-3">Back to List</a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="row g-4">
                        <div class="col-lg-5">
                            <div class="border rounded p-3 h-100 bg-light">
                                <h6 class="fw-bold text-dark mb-3"><i class="fa fa-download me-2 text-success"></i>Step 1 — Download Template</h6>
                                <p class="small text-muted mb-3">
                                    Download the Excel template and fill in the stock hold details for your customers/vendors.
                                </p>
                                <a href="{{ route('stock-holds.import.template') }}" class="btn btn-success rounded-pill px-4">
                                    <i class="fa fa-file-excel-o me-1"></i> Download Excel Template
                                </a>

                                <hr>

                                <h6 class="fw-bold text-dark mb-2">Template Columns</h6>
                                <ul class="small text-muted mb-0 ps-3">
                                    @foreach(\App\Services\StockHoldImportService::COLUMN_MAP as $label)
                                        <li>{{ $label }}</li>
                                    @endforeach
                                </ul>
                                <p class="small text-muted mt-3 mb-0">
                                    <strong>Note:</strong> Imported Stock Holds post automatically and reflect immediately in Party Hold & Release reports.
                                </p>
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-bold text-dark mb-3"><i class="fa fa-upload me-2 text-primary"></i>Step 2 — Upload Filled Excel</h6>
                                <form action="{{ route('stock-holds.import.store') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Select Excel File (.xlsx, .xls, .csv)</label>
                                        <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                                    </div>

                                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                                        <i class="fa fa-upload me-1"></i> Import Stock Holds
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
