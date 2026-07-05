@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-3">
            <div class="card shadow-sm border-0">
                <div class="card-header text-white d-flex justify-content-between align-items-center" style="background-color:#7bbcbe;">
                    <h5 class="mb-0 text-white"><i class="fa fa-file-excel-o me-2"></i>Import Customers from Excel</h5>
                    <a href="{{ route('customers.index') }}" class="btn btn-light btn-sm rounded-pill px-3">Back to List</a>
                </div>
                <div class="card-body">
                    @if(session('import_success'))
                        <div class="alert alert-success">
                            {{ session('import_success') }}
                        </div>
                    @endif

                    @if(session('import_errors'))
                        <div class="alert alert-warning">
                            <strong>Import completed with notes:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach(session('import_errors') as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
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
                                    Download the Excel template and share it with your client. They should fill customer data in the same format as the Add Customer form.
                                </p>
                                <a href="{{ route('customers.import.template') }}" class="btn btn-success rounded-pill px-4">
                                    <i class="fa fa-file-excel-o me-1"></i> Download Excel Template
                                </a>

                                <hr>

                                <h6 class="fw-bold text-dark mb-2">Template Columns</h6>
                                <ul class="small text-muted mb-0 ps-3">
                                    @foreach(\App\Services\CustomerImportService::COLUMN_MAP as $label)
                                        <li>{{ $label }}</li>
                                    @endforeach
                                </ul>
                                <p class="small text-muted mt-3 mb-0">
                                    <strong>Note:</strong> Template includes 2 sample rows — Row 2 (Main Customer) and Row 3 (Walking Customer). Delete them before import.
                                </p>
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-bold text-dark mb-3"><i class="fa fa-upload me-2 text-primary"></i>Step 2 — Upload Filled Excel</h6>
                                <form action="{{ route('customers.import.store') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Select Excel File (.xlsx, .xls, .csv)</label>
                                        <input type="file" name="import_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                                    </div>

                                    @if(!$isAdmin)
                                        <div class="alert alert-info small py-2">
                                            Your assigned user groups will be applied automatically to imported customers.
                                        </div>
                                    @endif

                                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                                        <i class="fa fa-upload me-1"></i> Import Customers
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
