@extends('admin_panel.layout.app')

@section('content')
<div class="container-fluid py-4">
    <div class="bg-white border shadow-sm mx-auto p-4 rounded-3" style="max-width: 98%;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 fw-bold"><i class="fa fa-shield me-2 text-primary"></i>Customer Claims</h4>
            <a href="{{ route('customer-claims.create') }}" class="btn btn-primary rounded-pill px-4 shadow-sm">
                <i class="fa fa-plus me-1"></i> New Claim
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle border">
                <thead class="bg-light">
                    <tr>
                        <th class="text-center">Claim No</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Party Name</th>
                        <th>Item</th>
                        <th class="text-end">Amount</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="text-muted small">
                        <td colspan="8" class="text-center py-5">
                            <i class="fa fa-info-circle me-1"></i> No claims recorded yet. Click "New Claim" to get started.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
