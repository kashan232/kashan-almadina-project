@extends('admin_panel.layout.app')

@section('content')
<div class="container-fluid mt-4">

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
 <div class="row align-items-center mb-3">
        <div class="col-lg-6">
            <h6>Purchase Account Allocaations</h6>
        </div>
        <div class="col-lg-6 text-end">
            <a href="{{ url()->previous() }}" class="btn btn-sm btn-secondary">← Back</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Invoice No</th>
                    <th>Account Head</th>
                    <th>Account</th>
                    <th>Amount</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($histories as $index => $history)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $history->purchase->invoice_no ?? '-' }}</td>
                    <td>{{ $history->head->name ?? '-' }}</td>
                    <td>{{ $history->account->title ?? '-' }}</td>
                    <td>{{ number_format($history->amount, 2) }}</td>
                    <td>{{ $history->created_at->format('Y-m-d') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

    </div>
</div>

@endsection