@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">

            <div class="row align-items-center mb-3">
                <div class="col-lg-6">
                    {{-- <h4>Vendor Ledger - {{ $vendor->name }}</h4> --}}
                    <h6>Complete Ledger Statement</h6>
                </div>
                <div class="col-lg-6 text-end">
                    <a href="{{ url()->previous() }}" class="btn btn-sm btn-secondary">← Back</a>
                </div>
            </div>


            <div class="card">
                <div class="card-body table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Vendor Name</th>
                                <th>Description</th>
                                <th>Opening Balance</th>
                                <th>Previous Balance</th>
                                <th>Closing Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ledgers as $ledger)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($ledger->date)->format('d-M-Y') }}</td>
                                <td>{{ $ledger->vendor->name ?? 'N/A' }}</td>
                                <td>{{ $ledger->description }}</td>
                                <td>{{ $ledger->opening_balance }}</td>
                                <td>{{ $ledger->previous_balance }}</td>
                                <td class="text-danger fw-bold">{{ $ledger->closing_balance }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>


                </div>
            </div>

        </div>
    </div>
</div>
@endsection