 @extends('admin_panel.layout.app')
 @section('content')
     
<div class="main-content">
    <div class="main-content-inner">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <h3 class="mb-0 fw-bold text-dark">Users</h3>
                        <button type="button" class="btn btn-primary shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#exampleModal"
                            id="reset-form"><i class="fa-solid fa-user-plus me-1"></i> Create User</button>
                    </div>

                    @if($canManageLockdown ?? false)
                    @php $lockdownOn = (bool) ($loginLockdownActive ?? false); @endphp
                    <div id="loginLockdownCard" class="lockdown-panel {{ $lockdownOn ? 'is-active' : 'is-inactive' }}">
                        <div class="lockdown-panel-left">
                            <div class="lockdown-icon-wrap" id="lockdownIconWrap">
                                <i class="fa-solid {{ $lockdownOn ? 'fa-shield-halved' : 'fa-unlock-keyhole' }}" id="lockdownIcon"></i>
                            </div>
                            <div class="lockdown-text">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <h5 class="lockdown-title mb-0">Login Lockdown</h5>
                                    <span class="lockdown-status-pill" id="lockdownStatusPill">
                                        <span class="lockdown-pulse-dot"></span>
                                        <span id="lockdownStatusLabel">{{ $lockdownOn ? 'ACTIVE' : 'INACTIVE' }}</span>
                                    </span>
                                </div>
                                <p class="lockdown-desc mb-0" id="lockdownStatusText">
                                    {{ $lockdownOn
                                        ? 'All users are blocked from login. Only admin can access the system (e.g. price update).'
                                        : 'All users can login normally. Turn ON before price update to block logins.' }}
                                </p>
                            </div>
                        </div>
                        <div class="lockdown-panel-right">
                            <span class="lockdown-toggle-label" id="lockdownToggleLabel">{{ $lockdownOn ? 'ON' : 'OFF' }}</span>
                            <label class="lockdown-switch" for="loginLockdownToggle" title="Toggle login lockdown">
                                <input type="checkbox" id="loginLockdownToggle" {{ $lockdownOn ? 'checked' : '' }}>
                                <span class="lockdown-slider"></span>
                            </label>
                        </div>
                    </div>
                    @endif

        <div class="border mt-1 shadow rounded users-table-wrap">
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
        /* Login Lockdown Panel */
        .lockdown-panel {
            border-radius: 14px;
            padding: 18px 22px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            border: 1px solid transparent;
            box-shadow: 0 4px 18px rgba(15, 23, 42, 0.08);
            transition: all 0.35s ease;
            position: relative;
            overflow: hidden;
        }
        .lockdown-panel::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 5px;
            border-radius: 14px 0 0 14px;
        }
        .lockdown-panel.is-inactive {
            background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 55%, #f8fafc 100%);
            border-color: #bbf7d0;
        }
        .lockdown-panel.is-inactive::before {
            background: linear-gradient(180deg, #22c55e, #16a34a);
        }
        .lockdown-panel.is-active {
            background: linear-gradient(135deg, #fff7ed 0%, #ffffff 55%, #fef2f2 100%);
            border-color: #fed7aa;
            box-shadow: 0 4px 22px rgba(234, 88, 12, 0.12);
        }
        .lockdown-panel.is-active::before {
            background: linear-gradient(180deg, #f97316, #dc2626);
        }
        .lockdown-panel-left {
            display: flex;
            align-items: center;
            gap: 16px;
            flex: 1;
            min-width: 260px;
        }
        .lockdown-icon-wrap {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }
        .lockdown-panel.is-inactive .lockdown-icon-wrap {
            background: rgba(34, 197, 94, 0.12);
            color: #16a34a;
            box-shadow: inset 0 0 0 1px rgba(34, 197, 94, 0.2);
        }
        .lockdown-panel.is-active .lockdown-icon-wrap {
            background: rgba(249, 115, 22, 0.14);
            color: #ea580c;
            box-shadow: inset 0 0 0 1px rgba(249, 115, 22, 0.25);
        }
        .lockdown-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.02em;
        }
        .lockdown-desc {
            font-size: 0.82rem;
            color: #64748b;
            margin-top: 4px;
            line-height: 1.45;
            max-width: 620px;
        }
        .lockdown-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.06em;
            padding: 4px 10px;
            border-radius: 999px;
            transition: all 0.3s ease;
        }
        .lockdown-panel.is-inactive .lockdown-status-pill {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }
        .lockdown-panel.is-active .lockdown-status-pill {
            background: #ffedd5;
            color: #c2410c;
            border: 1px solid #fdba74;
        }
        .lockdown-pulse-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .lockdown-panel.is-inactive .lockdown-pulse-dot {
            background: #22c55e;
        }
        .lockdown-panel.is-active .lockdown-pulse-dot {
            background: #f97316;
            animation: lockdownPulse 1.4s ease-in-out infinite;
        }
        @keyframes lockdownPulse {
            0%, 100% { opacity: 1; transform: scale(1); box-shadow: 0 0 0 0 rgba(249, 115, 22, 0.5); }
            50% { opacity: 0.7; transform: scale(1.15); box-shadow: 0 0 0 6px rgba(249, 115, 22, 0); }
        }
        .lockdown-panel-right {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }
        .lockdown-toggle-label {
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            min-width: 28px;
            text-align: center;
            transition: color 0.3s ease;
        }
        .lockdown-panel.is-inactive .lockdown-toggle-label { color: #16a34a; }
        .lockdown-panel.is-active .lockdown-toggle-label { color: #ea580c; }
        .lockdown-switch {
            position: relative;
            display: inline-block;
            width: 58px;
            height: 32px;
            margin: 0;
            cursor: pointer;
        }
        .lockdown-switch input {
            opacity: 0;
            width: 0;
            height: 0;
            position: absolute;
        }
        .lockdown-slider {
            position: absolute;
            inset: 0;
            border-radius: 999px;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.12);
        }
        .lockdown-panel.is-inactive .lockdown-slider {
            background: linear-gradient(180deg, #86efac, #22c55e);
        }
        .lockdown-panel.is-active .lockdown-slider {
            background: linear-gradient(180deg, #fdba74, #ea580c);
        }
        .lockdown-slider::before {
            content: '';
            position: absolute;
            height: 26px;
            width: 26px;
            left: 3px;
            bottom: 3px;
            background: #fff;
            border-radius: 50%;
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        .lockdown-switch input:checked + .lockdown-slider::before {
            transform: translateX(26px);
        }
        .lockdown-switch input:disabled + .lockdown-slider {
            opacity: 0.65;
            cursor: not-allowed;
        }
        .users-table-wrap {
            background-color: white;
        }

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

        $('#loginLockdownToggle').on('change', function () {
            var toggle = $(this);
            var active = toggle.is(':checked');
            var actionText = active ? 'activate login lockdown' : 'deactivate login lockdown';
            var detailText = active
                ? 'All non-admin users will be logged out and cannot login until you turn this off.'
                : 'All users will be able to login again.';

            Swal.fire({
                title: active ? 'Activate Login Lockdown?' : 'Deactivate Login Lockdown?',
                html: 'Are you sure you want to <strong>' + actionText + '</strong>?<br><br>' + detailText,
                icon: active ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonText: active ? 'Yes, activate' : 'Yes, deactivate',
                cancelButtonText: 'Cancel',
                confirmButtonColor: active ? '#ea580c' : '#16a34a',
            }).then(function (result) {
                if (!result.isConfirmed) {
                    toggle.prop('checked', !active);
                    return;
                }

                toggle.prop('disabled', true);

                $.ajax({
                    url: @json(route('users.toggle-login-lockdown')),
                    method: 'POST',
                    data: {
                        _token: @json(csrf_token()),
                        active: active ? 1 : 0
                    },
                    success: function (res) {
                        updateLockdownUI(!!res.active);
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated',
                            text: res.message,
                            timer: 2500,
                            showConfirmButton: false
                        });
                    },
                    error: function (xhr) {
                        toggle.prop('checked', !active);
                        var msg = xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'Could not update login lockdown.';
                        Swal.fire({ icon: 'error', title: 'Error', text: msg });
                    },
                    complete: function () {
                        toggle.prop('disabled', false);
                    }
                });
            });
        });

        function updateLockdownUI(isActive) {
            var $card = $('#loginLockdownCard');
            $card.toggleClass('is-active', isActive).toggleClass('is-inactive', !isActive);
            $('#loginLockdownToggle').prop('checked', isActive);
            $('#lockdownStatusLabel').text(isActive ? 'ACTIVE' : 'INACTIVE');
            $('#lockdownToggleLabel').text(isActive ? 'ON' : 'OFF');
            $('#lockdownIcon')
                .removeClass('fa-shield-halved fa-unlock-keyhole')
                .addClass(isActive ? 'fa-shield-halved' : 'fa-unlock-keyhole');
            $('#lockdownStatusText').text(isActive
                ? 'All users are blocked from login. Only admin can access the system (e.g. price update).'
                : 'All users can login normally. Turn ON before price update to block logins.');
        }
    });
</script>


 @endsection
