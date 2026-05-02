 @extends('admin_panel.layout.app')
 @section('content')
     
<div class="main-content">
    <div class="main-content-inner">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3>Users</h3>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal"
                            id="reset-form">Create</button>
                    </div>
        <div class="border mt-1 shadow rounded " style="background-color: white;">
            <div class="col-lg-12 m-auto">
   <div class="table-responsive mt-5 mb-5 ">
    <table id="default-datatable" class="table">
        <thead class="text-center">
            <tr>
                <th class="text-center">Id</th>
                <th class="text-center">Name</th>
                <th class="text-center">Email</th>
                <th class="text-center">Roles</th>
                <th class="text-center">Action</th>
                <th class="text-center d-none">Action</th>
            </tr>
        </thead>
        <tbody class="text-center">
                @foreach ($users as $key => $user)
                        <tr>
                            {{-- <span class="d-none" id="edit-id">{{ $user->id }}</span> --}}
                            <td class="d-none">
                                <input type="hidden" class="edit-id" value="{{ $user->id }}">
                            </td>
                            <th scope="row" class="id">{{ $user->id }}</th>
                            <td class="name">{{ $user->name }}</td>
                            <td class="email">{{ $user->email }}</td>
                            <td>
                                {{-- @foreach ($user->getRoleNames() as $role)
                                    <span class="badge bg-primary">{{ $role }}</span>
                                @endforeach --}}

                                {{-- <form action="{{ route('users.update.roles', $user->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')

                                    <h5 class="mb-3">Assign Roles to {{ $user->name }}</h5>
                                    @forelse ($allRoles as $key1 => $role)
                                        <div class="form-check d-flex" style="align-items:baseline !important;margin-left:140px !important;">
                                            <input class="form-check-input"
                                                type="checkbox"
                                                name="roles[]"
                                                value="{{ $role->name }}"
                                                {{ $user->hasRole($role->name) ? 'checked' : '' }}><br>
                                            <label class="form-check-label m-0 p-0">
                                                {{ $role->name }}
                                            </label>
                                        </div>
                                    @empty

                                    @endforelse

                                    <button type="submit" class="btn btn-warning mt-3 btn-sm p-1">Update Roles</button>
                                </form> --}}
                                @forelse ($user->getRoleNames() as $role)
                                    <span class="badge bg-success fw-bold p-2 text-white mb-2">{{ $role }}</span>
                                @empty
                                    <span class="badge bg-danger fw-bold p-2 text-white">No Role Assigned</span>
                                @endforelse
                            </td>       
                            <td>
                                <button class="btn btn-info btn-sm edit-role-btn p-1">
                                    Edit Roles
                                </button>
                                <button class="btn btn-primary btn-sm edit-btn p-1"
                                    data-url="{{ route('users.store') }}">
                                    Edit
                                </button>
                                <a href="{{ route('users.delete', $user->id) }}" class="btn btn-danger btn-sm delete-btn p-1"
                                data-url="{{ route('users.delete', $user->id) }}"
                                data-msg="Are you sure you want to delete this Role"
                                data-method="DELETE"
                                onclick="confirmedBox(this, event)">
                                Delete
                                </a>
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
    </div>

    <style>
        /* Custom Checkbox Design */
        .role-selection-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 10px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        .custom-role-check {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: none;
        }
        .custom-role-check:hover {
            border-color: #0d6efd;
            background-color: #f0f7ff;
        }
        .custom-role-check.active {
            background-color: #e7f1ff;
            border-color: #0d6efd;
        }
        .custom-role-check input {
            width: 18px;
            height: 18px;
            margin-right: 10px;
            cursor: pointer;
        }
        .custom-role-check label {
            margin: 0;
            font-weight: 500;
            font-size: 13px;
            color: #333;
            cursor: pointer;
        }
        .role-modal-header-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 10px;
            display: block;
            font-size: 14px;
        }
    </style>

    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Users</h5>
                </div>
                <div class="modal-body">
                    <form class="myform" action="{{ route('users.store') }}" method="POST">
                        @csrf
                            <input type="hidden" name="edit_id" id="id" />
                        <div class="mb-3">
                            <label for="title" class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" id="name" />
                        </div>
                        <div class="mb-3">
                            <label for="title" class="form-label">Email</label>
                            <input type="text" name="email" class="form-control" id="email" />
                        </div>
                        <div class="mb-3">
                            <label for="title" class="form-label">Pasword</label>
                            <input type="text" name="password" class="form-control" id="password" />
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <input type="submit" class="btn btn-primary save-btn">
                </div>
                </form>
            </div>
        </div>
    </div> 
    <div class="modal fade" id="edit-role-modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Update Roles</h5>
                </div>
                <form class="" action="{{ route('users.update.roles') }}" method="POST">
                    <div class="modal-body">
                            @csrf
                            <input type="hidden" name="edit_id" id="edit-role-id" />
                            <div class="mb-3">
                                <label for="title" class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" id="role-modal-name" readonly />
                            </div>
                            <div class="mb-3">
                                <label for="title" class="form-label">Email</label>
                                <input type="text" name="email" class="form-control" id="role-modal-email" readonly />
                            </div>
                                <label class="role-modal-header-label">Available Roles</label>
                            <div id="role-checkbox-container" class="role-selection-grid">
                                <!-- Checkboxes will be injected via JS here -->
                            </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <input type="submit" class="btn btn-primary save-btn">
                    </div>
                </form>
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
 <script>
    $(document).on('submit', '.myform', function(e) {
        e.preventDefault();
        var formdata = new FormData(this);
        url = $(this).attr('action');
        method = $(this).attr('method');
        $(this).find(':submit').attr('disabled', true);
        myAjax(url, formdata, method);
    });
    $(document).on('click', '.edit-btn', function () {

        var tr = $(this).closest("tr");
        var id = tr.find(".edit-id").val();
        // alert(id+"hit");
        var name = tr.find(".name").text();
        var email = tr.find(".email").text();
        $('#id').val(id);
        $('#name').val(name)
        $('#email').val(email)
        $("#exampleModal").modal("show")

    });
   

    function confirmedBox(element, event) {
        event.preventDefault(); // Stop immediate redirect

        const message = element.getAttribute('data-msg') || 'Are you sure?';
        const url = element.getAttribute('href');

        Swal.fire({
            title: 'Confirm Deletion',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6'
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirect manually after confirmation
                window.location.href = url;
            }
        });
    }

     $(document).on('click', '#reset-form', function () {
        // alert("sd");
        // Manually clear inputs
        $('#id').val('');
        $('#name').val('');
        $('#email').val('');
        $('#password').val('');
        $("#exampleModal").modal("show")
    });
    const allRoles = @json($allRoles);
    // update role
    $(document).on('click', '.edit-role-btn', function () {
        var tr = $(this).closest("tr");
        var id = tr.find(".edit-id").val();
        var name = tr.find(".name").text();
        var email = tr.find(".email").text();

        // get assigned roles from badges
        let assignedRoles = [];
        tr.find('td:eq(3) .badge').each(function () {
            assignedRoles.push($(this).text().trim());
        });

        // inject user info
        $('#role-modal-name').val(name);
        $('#role-modal-email').val(email);
        // alert(id);
        $('#edit-role-id').val(id);

        // Extract assigned role names from badges
        // var assignedRoles = [];
        // tr.find('td:nth-child(4) .badge').each(function () {
        //     assignedRoles.push($(this).text().trim());
        // });

        
        // clear previous checkboxes
        $('#role-checkbox-container').html('');

        // allRoles must be available in JS
        allRoles.forEach(function (role) {
            let isChecked = assignedRoles.includes(role.name) ? 'checked' : '';
            let activeClass = isChecked ? 'active' : '';
            $('#role-checkbox-container').append(`
                <div class="custom-role-check ${activeClass}">
                    <input type="checkbox" name="roles[]" id="role_${role.id}" value="${role.name}" ${isChecked}>
                    <label for="role_${role.id}">${role.name}</label>
                </div>
            `);
        });

        // Toggle active class on click
        $(document).on('change', '.custom-role-check input', function() {
            if($(this).is(':checked')) {
                $(this).closest('.custom-role-check').addClass('active');
            } else {
                $(this).closest('.custom-role-check').removeClass('active');
            }
        });

        $("#edit-role-modal").modal("show");
    });

</script>
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
                "search": "Search Users:",
                "lengthMenu": "Show _MENU_ entries"
            }
        });
    });
</script>


 @endsection
