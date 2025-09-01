@extends('admin_panel.layout.app')
@section('content')

<div class="container-fluid mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
            <h5>Sub Customer Ledger</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered text-center datatable">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Sub Customer</th>
                        <th>Description</th>
                        <th>Debit</th>
                        <th>Credit</th>
                        <th>Previous Balance</th>
                        <th>Closing Balance</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalClosing = 0; @endphp
                    @foreach($ledgers as $key => $ledger)
                        @php $totalClosing += $ledger->closing_balance; @endphp
                        <tr>
                            <td>{{ $key+1 }}</td>
                            <td>{{ $ledger->subCustomer->customer_name ?? 'N/A' }}</td>
                            <td>{{ $ledger->description ?? '-' }}</td>
                            <td>{{ number_format($ledger->debit,2) }}</td>
                            <td>{{ number_format($ledger->credit,2) }}</td>
                            <td>{{ number_format($ledger->previous_balance,2) }}</td>
                            <td class="{{ $ledger->closing_balance <0 ? 'text-danger':'text-success' }}">
                                {{ number_format($ledger->closing_balance,2) }}
                            </td>
                            <td>{{ $ledger->created_at->format('d-m-Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$('.datatable').DataTable({ responsive:true, pageLength:25, order:[[0,"asc"]] });
</script>
@endpush
