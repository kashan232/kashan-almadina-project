@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-3">
            <div class="card shadow-sm border-0">
                <div class="card-header text-white d-flex justify-content-between align-items-center" style="background-color: #0d6efd;">
                    <h5 class="mb-0 text-white"><i class="fa fa-file-excel-o me-2"></i>Import Customer Claims from Excel</h5>
                    <a href="{{ route('customer-claims.index') }}" class="btn btn-light btn-sm rounded-pill px-3 fw-bold">
                        <i class="fa fa-arrow-left me-1"></i> Back to Claims List
                    </a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fa fa-check-circle me-1"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('warning'))
                        <div class="alert alert-warning alert-dismissible fade show">
                            <i class="fa fa-exclamation-triangle me-1"></i> {{ session('warning') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fa fa-times-circle me-1"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('import_errors') && count(session('import_errors')) > 0)
                        <div class="alert alert-danger">
                            <h6 class="fw-bold mb-2"><i class="fa fa-list-ul me-1"></i> Skipped Rows Details:</h6>
                            <ul class="mb-0 small" style="max-height: 200px; overflow-y: auto;">
                                @foreach(session('import_errors') as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (isset($errors) && $errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="row g-4">
                        <!-- Step 1: Download Template -->
                        <div class="col-lg-5">
                            <div class="border rounded p-3 h-100 bg-light">
                                <h6 class="fw-bold text-dark mb-2">
                                    <i class="fa fa-download me-2 text-success"></i>Step 1 — Download Excel Template
                                </h6>
                                <p class="small text-muted mb-3">
                                    Download the ready-to-use template pre-filled with sample claim rows (Item Return, Credit Note, and Claim Hold).
                                </p>
                                <a href="{{ route('customer-claims.import.template') }}" class="btn btn-success rounded-pill px-4 shadow-sm fw-bold">
                                    <i class="fa fa-file-excel-o me-1"></i> Download Excel Template
                                </a>

                                <hr>

                                <h6 class="fw-bold text-dark mb-2 small text-uppercase">Template Columns Guide</h6>
                                <ul class="small text-muted mb-3 ps-3">
                                    <li><strong>Claim Type:</strong> <code>Item Return</code>, <code>Credit Note</code>, or <code>Claim Hold</code></li>
                                    <li><strong>Claim Date:</strong> <code>YYYY-MM-DD</code> (e.g. {{ date('Y-m-d') }})</li>
                                    <li><strong>Party Type:</strong> <code>Customer</code>, <code>Walking</code>, or <code>Vendor</code></li>
                                    <li><strong>Customer / Party Name:</strong> Customer name or ID as in system</li>
                                    <li><strong>Claimed Product Name:</strong> Battery/Product name or ID</li>
                                    <li><strong>Claim Warehouse:</strong> Name of claim warehouse (e.g. <code>Claim Hyd</code>)</li>
                                    <li><strong>Original Warehouse / Shop:</strong> <code>Shop</code> or Warehouse name</li>
                                    <li><strong>Warranty Card No:</strong> Optional card number</li>
                                    <li><strong>Mfg Date / Code:</strong> Optional manufacturing code (e.g. <code>08/25</code>)</li>
                                    <li><strong>Fault Found:</strong> Optional issue (e.g. <code>Cell Dead</code>)</li>
                                    <li><strong>Claim Amount / Fee:</strong> Optional charges (default: <code>0</code>)</li>
                                    <li><strong>Replacement Product:</strong> Only required for <code>Credit Note</code></li>
                                    <li><strong>Replacement From Warehouse:</strong> Usually <code>Shop</code></li>
                                </ul>

                                <div class="p-2 bg-white rounded border small text-muted">
                                    <i class="fa fa-info-circle text-primary me-1"></i>
                                    <strong>Notice:</strong> Claim Numbers (e.g. <code>CLM-00123</code>) will be auto-generated sequentially for each row upon upload.
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Upload Excel File -->
                        <div class="col-lg-7">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-bold text-dark mb-3">
                                    <i class="fa fa-upload me-2 text-primary"></i>Step 2 — Upload Filled Excel File
                                </h6>
                                <form action="{{ route('customer-claims.import.process') }}" method="POST" enctype="multipart/form-data" id="importForm">
                                    @csrf

                                    <div class="mb-3">
                                        <label class="form-label fw-bold small">Select Excel File (.xlsx, .xls, .csv) <span class="text-danger">*</span></label>
                                        <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                                        <div class="form-text small">Maximum file size: 10MB.</div>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-bold small">Import As Status <span class="text-danger">*</span></label>
                                        <div class="d-flex gap-3 mt-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="target_status" id="statusPosted" value="Posted" checked>
                                                <label class="form-check-label fw-semibold" for="statusPosted">
                                                    <span class="badge bg-success me-1">Posted</span> Auto-post claims & update stock / ledgers immediately (Recommended)
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="target_status" id="statusDraft" value="Draft">
                                                <label class="form-check-label fw-semibold" for="statusDraft">
                                                    <span class="badge bg-secondary me-1">Draft</span> Save as Unposted (Review before posting)
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary rounded-pill px-5 shadow-sm fw-bold" id="submitBtn">
                                        <i class="fa fa-upload me-1"></i> Start Import
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

<script>
    document.getElementById('importForm').addEventListener('submit', function() {
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i> Importing Claims, please wait...';
    });
</script>
@endsection
