@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <div class="row m-0 p-0">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">Inward Gatepasses</h4>
                            <a class="btn btn-primary" href="{{ route('add_inwardgatepass') }}">Add Inward Gatepass</a>

                        </div>
                        <div class="card-body">
                            <div class="col-lg-12">
                                <div class="table-responsive">
                                    <table id="example" class="display" style="width:100%">
                                        <thead class="text-center" style="background:#add8e6;">
                                            <tr>
                                                <th style="text-align: center">Invoice#</th>
                                                <th style="text-align: center">Branch</th>
                                                <th style="text-align: center">Warehouse</th>
                                                <th style="text-align: center">Vendor</th>
                                                <th style="text-align: center">Date</th>
                                                <th style="text-align: center">Transport</th>
                                                <th style="text-align: center">Bilty No</th>
                                                <th style="text-align: center">Note</th>
                                                <th style="text-align: center">Status</th> <!-- Added Status Column -->
                                                <th style="text-align: center">Action</th> <!-- Added Action Column -->
                                            </tr>
                                        </thead>
                                        <tbody class="text-center">
                                            @foreach ($gatepasses as $gp)
                                            <tr>
                                                <td>{{ $gp->invoice_no }}</td>
                                                <td>{{ $gp->branch->name ?? 'N/A' }}</td>
                                                <td>{{ $gp->warehouse->warehouse_name ?? 'N/A' }}</td>
                                                <td>{{ $gp->vendor->name ?? 'N/A' }}</td>
                                                <td>{{ $gp->gatepass_date }}</td>
                                                <td>{{ $gp->transport_name ?? 'N/A' }}</td>
                                                <td>{{ $gp->gatepass_no ?? 'N/A' }}</td>
                                                <td>{{ $gp->remarks ?? 'N/A' }}</td>
                                                <td>
                                                    @if ($gp->status == 'pending')
                                                    <span class="badge bg-warning">Pending</span>
                                                    @elseif($gp->status == 'linked')
                                                    <span class="badge bg-success">Linked</span>
                                                    @elseif($gp->status == 'cancelled')
                                                    <span class="badge bg-danger">Cancelled</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ route('InwardGatepass.show', $gp->id) }}" class="btn btn-sm btn-info mb-1">View</a>

                                                    @if ($gp->status == 'pending')
                                                    <a href="{{ route('add_bill', $gp->id) }}" class="btn btn-sm btn-info mb-1">Add Bill</a>
                                                    @elseif($gp->status == 'linked')
                                                    <a href="{{ route('InwardGatepass.show', $gp->id) }}" class="btn btn-sm btn-success mb-1">View Bill</a>
                                                    @endif

                                                    <a href="{{ route('InwardGatepass.edit', $gp->id) }}" class="btn btn-sm mb-1" style="background:#add8e6">Edit</a>

                                                    <form action="{{ route('InwardGatepass.destroy', $gp->id) }}" method="POST" class="d-inline delete-form">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="btn btn-danger btn-sm delete-btn mb-1">Delete</button>
                                                    </form>
                                                </td>

                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Delete confirm
    $(document).on('click', '.delete-btn', function(e) {
        e.preventDefault();
        let form = $(this).closest('form');

        Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to delete this gatepass!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    // Success alert after delete
    @if(session('success'))
    Swal.fire({
        title: 'Deleted!',
        text: "{{ session('success') }}",
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
    });
    @endif
</script>
@endsection