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
                                <i class="fa fa-file-excel-o text-success me-2"></i> Bulk Import Warehouse Stock
                            </h5>
                            <a href="{{ route('warehouse_stocks.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
                                <i class="fa fa-arrow-left me-1"></i> Back to Warehouse Stock
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
                                    <li class="mb-1">Export your current stock data using the Export Excel button below.</li>
                                    <li class="mb-1">Update the quantities for Shop Stock and Warehouses in the exported Excel spreadsheet.</li>
                                    <li class="mb-1">Do not alter the Product ID or Warehouse column headers so that system matches products accurately.</li>
                                    <li>Upload the completed file to set and update current stock balances across all locations.</li>
                                </ol>
                            </div>

                            <!-- Export Download Button -->
                            <div class="d-flex align-items-center justify-content-between p-3 mb-4 rounded-3 border" style="background-color: #f0fDF4; border-color: #BBF7D0 !important;">
                                <div>
                                    <h6 class="fw-bold mb-1 text-success"><i class="fa fa-download me-1"></i> Export Current Stock Excel</h6>
                                    <p class="mb-0 text-muted small">Includes all 204 products with Shop Stock & dynamic Warehouse columns</p>
                                </div>
                                <a href="{{ route('warehouse_stocks.export') }}" class="btn btn-success btn-sm px-4 rounded-pill fw-bold">
                                    <i class="fa fa-file-excel-o me-1"></i> Export Current Stock
                                </a>
                            </div>

                            <!-- Upload Form -->
                            <form action="{{ route('warehouse_stocks.import.process') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-4">
                                    <label class="form-label fw-bold text-dark">Select Stock Excel File (.xlsx, .xls, .csv)</label>
                                    <input type="file" name="excel_file" class="form-control @error('excel_file') is-invalid @enderror" accept=".xlsx, .xls, .csv" required>
                                    @error('excel_file')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('warehouse_stocks.index') }}" class="btn btn-light px-4 rounded-pill">Cancel</a>
                                    <button type="submit" class="btn btn-primary px-5 rounded-pill fw-bold">
                                        <i class="fa fa-upload me-1"></i> Upload & Import Stock
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
