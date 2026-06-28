 @extends('admin_panel.layout.app')
 @section('content')
     
<style>
    .permissions-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.06);
        background-color: #ffffff;
    }
    .permissions-header {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        color: white;
        border-radius: 12px 12px 0 0 !important;
        padding: 20px 25px;
        border-bottom: none;
    }
    .table-permissions th {
        background-color: #f8f9fc;
        color: #4e73df;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.05em;
        border-bottom: 2px solid #e3e6f0 !important;
        padding: 15px;
    }
    .table-permissions td {
        vertical-align: middle;
        color: #5a5c69;
        border-bottom: 1px solid #e3e6f0;
        padding: 15px;
    }
    .table-permissions tbody tr:hover {
        background-color: #f1f3f9;
        transition: background-color 0.2s ease-in-out;
    }
    .permission-badge {
        background-color: #e8eaef;
        color: #3a3b45;
        font-weight: 600;
        padding: 8px 16px;
        border-radius: 30px;
        font-size: 14px;
        display: inline-block;
        box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        border: 1px solid #d1d3e2;
    }
    .permission-badge i {
        color: #1cc88a;
    }
    .dataTables_wrapper .dataTables_filter input {
        border-radius: 20px;
        border: 1px solid #d1d3e2;
        padding: 5px 15px;
        outline: none;
    }
    .dataTables_wrapper .dataTables_filter input:focus {
        border-color: #4e73df;
        box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
    }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid mt-4">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card permissions-card">
                        <div class="card-header permissions-header d-flex align-items-center">
                            <i class="fas fa-shield-alt fa-2x mr-3"></i>
                            <h3 class="mb-0 text-white" style="font-weight: 600;">System Permissions</h3>
                        </div>
                        <div class="card-body p-4">
                            <div class="table-responsive">
                                <table id="default-datatable" class="table table-hover table-permissions w-100">
                                    <thead>
                                        <tr>
                                            <th class="text-center" width="10%">ID</th>
                                            <th width="90%">Module Name</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($permissions as $permission)
                                            <tr>
                                                <td class="text-center fw-bold" style="color: #4e73df;">{{ $permission->id }}</td>
                                                <td>
                                                    <span class="permission-badge">
                                                        <i class="fas fa-check-circle mr-2"></i>{{ $permission->name }}
                                                    </span>
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

 
<!-- DataTable CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- DataTable JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script  src="{{ asset('assets/js/mycode.js') }}">  </script>

@if(session('success'))
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: "{{ session('success') }}",
            timer: 2000,
            showConfirmButton: false
        });
    </script>
@endif
<script>
    $(document).ready(function() {
        $('#default-datatable').DataTable({
            "pageLength": 10,
            "lengthMenu": [5, 10, 25, 50, 100],
            "order": [[0, 'desc']],
            "language": {
                "search": "Search Permissions:",
                "lengthMenu": "Show _MENU_ entries"
            }
        });
    });
</script>

 @endsection
