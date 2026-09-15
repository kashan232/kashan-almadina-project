@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="container-fluid p-3">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-calendar-day me-2"></i> Daily Activity Report</h5>
                    </div>
                    <div class="card-body p-4">
                        <form action="{{ route('reports.daily-activity.preview') }}" method="POST" id="reportForm" target="_blank">
                            @csrf

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-muted">From Date</label>
                                    <input type="date" name="from_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-muted">To Date</label>
                                    <input type="date" name="to_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">
                                    <i class="fas fa-eye me-1"></i> Preview Report
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
