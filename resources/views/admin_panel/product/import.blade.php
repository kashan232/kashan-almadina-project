@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-4">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                            <h5 class="card-title mb-0 fw-bold text-dark">
                                <i class="fa fa-file-excel-o text-success me-2"></i> Bulk Import Products
                            </h5>
                            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
                                <i class="fa fa-arrow-left me-1"></i> Back to Products
                            </a>
                        </div>
                        <div class="card-body p-4">

                            @if (session('success'))
                                <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
                                    <i class="fa fa-check-circle me-2"></i> {{ session('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            @if (session('error'))
                                <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
                                    <i class="fa fa-exclamation-triangle me-2"></i> {{ session('error') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            <!-- Instructions Step -->
                            <div class="mb-4 p-3 bg-light rounded-3 border">
                                <h6 class="fw-bold text-primary mb-2"><i class="fa fa-info-circle me-1"></i> Instructions:</h6>
                                <ol class="mb-0 ps-3 small text-muted">
                                    <li class="mb-1">Download the Excel template using the button below.</li>
                                    <li class="mb-1">Fill in the product details like Name, Category, Sub Category, Brand, Opening Stock, Purchase & Sale Prices.</li>
                                    <li class="mb-1">Dynamic columns are included for all your Warehouses to specify initial stock per warehouse.</li>
                                    <li>Upload the completed Excel file (.xlsx, .xls) to import all products in bulk.</li>
                                </ol>
                            </div>

                            <!-- Template Download Button -->
                            <div class="d-flex align-items-center justify-content-between p-3 mb-4 rounded-3 border" style="background-color: #f0fDF4; border-color: #BBF7D0 !important;">
                                <div>
                                    <h6 class="fw-bold mb-1 text-success"><i class="fa fa-download me-1"></i> Download Template</h6>
                                    <p class="mb-0 text-muted small">Includes all formatted columns & warehouse stock headers</p>
                                </div>
                                <a href="{{ route('products.import.template') }}" class="btn btn-success btn-sm px-4 rounded-pill fw-bold">
                                    <i class="fa fa-file-excel-o me-1"></i> Download Template
                                </a>
                            </div>

                            <!-- Upload Form -->
                            <form action="{{ route('products.import.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-4">
                                    <label class="form-label fw-bold text-dark">Select Excel File (.xlsx, .xls)</label>
                                    <input type="file" name="excel_file" class="form-control @error('excel_file') is-invalid @enderror" accept=".xlsx, .xls, .csv" required>
                                    @error('excel_file')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('products.index') }}" class="btn btn-light px-4 rounded-pill">Cancel</a>
                                    <button type="submit" class="btn btn-primary px-5 rounded-pill fw-bold">
                                        <i class="fa fa-upload me-1"></i> Upload & Import Products
                                    </button>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
