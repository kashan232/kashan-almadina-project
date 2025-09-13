@extends('admin_panel.layout.app')

@section('content')

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">

           <div class="page-header row">
                <div class="page-title col-lg-6">
                    <h4>Customer Ledger</h4>
                    <h6>View Customer Balances</h6>
                </div>
                <div class="text-end col-lg-6">
                   <a href="{{ url('/customers') }}" class="btn btn-danger">Back</a>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <table class="table table-bordered datanew">
                        <thead class="table-light text-center">
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Opening Balance</th>
                                <th>Previous Balance</th>
                                <th>Closing Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $totalClosing = 0;
                            @endphp
                            @foreach($CustomerLedgers as $key => $ledger)
                            @php
                                $opening = $ledger->opening_balance;
                                $previous = $ledger->previous_balance;
                                $closing = $ledger->closing_balance;
                                $totalClosing += $closing;
                            @endphp
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>{{ $ledger->created_at->format('d-m-Y') }}</td>
                                <td>{{ $ledger->customer->customer_name ?? 'N/A' }}</td>
                                <td class="{{ $opening < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($opening, 2) }}
                                </td>
                                <td>{{ number_format($previous, 2) }}</td>
                                <td class="{{ $closing < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($closing, 2) }}
                                </td>
                            </tr>
                            @endforeach

                            {{--  Total Row
                            <tr class="table-secondary">
                                <td colspan="6" class="text-end"><strong>Total Closing Balance:</strong></td>
                                <td colspan="2"><strong>{{ number_format($totalClosing, 2) }}</strong></td>
                            </tr>  --}}
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    $('.datanew').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[ 0, "asc" ]]
    });
</script>
@endpush
