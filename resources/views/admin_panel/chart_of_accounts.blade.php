@extends('admin_panel.layout.app')

@section('content')
<div class="container mt-4">

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-between mb-3">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAccountModal">➕ Add New Account</button>
        <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#addHeadModal">➕ Add Head</button>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Account Code</th>
                    <th>Expense Head</th>
                    <th>Account Title</th>
                    <th>Type</th>
                    <th>Total Debit</th>
                    <th>Total Credit</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($accounts as $key => $account)
                    <tr>
                        <td>{{ $key+1 }}</td>
                        <td>{{ $account->account_code }}</td>
                        <td>{{ $account->head->name }}</td>
                        <td>{{ $account->title }}</td>
                        <td>{{ $account->type }}</td>
                        <td>{{ $account->total_debit }}</td>
                        <td>{{ $account->total_credit }}</td>
                        <td>
                            @if($account->status)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Add Account Modal -->
<div class="modal fade" id="addAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('coa.account.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Add New Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label>Select Head</label>
                    <select name="head_id" class="form-control" required>
                        <option value="">Select Head</option>
                        @foreach($heads as $head)
                            <option value="{{ $head->id }}">{{ $head->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label>Account Code</label>
                    <input type="text" name="account_code" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Account Title</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Type</label>
                    <select name="type" class="form-control" required>
                        <option value="Debit">Debit</option>
                        <option value="Credit">Credit</option>
                    </select>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" name="status" type="checkbox" checked>
                    <label class="form-check-label">Active</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-success">Add Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Head Modal -->
<div class="modal fade" id="addHeadModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('coa.head.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Add Head</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label>Head Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-secondary">Add Head</button>
            </div>
        </form>
    </div>
</div>

@endsection
