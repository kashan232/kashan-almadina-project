@extends('admin_panel.layout.app')
@section('content')

<style>
    html, body {
        height: 100%;
        overflow-x: hidden !important;
        overflow-y: auto !important;
        padding-right: 0 !important;
        margin: 0;
    }

    body.modal-open {
        padding-right: 0 !important;
    }

    body::-webkit-scrollbar {
        width: 0px;
        background: transparent;
    }

    .main-content {
        min-height: calc(100vh - 60px);
        overflow-y: auto;
    }

    .container {
        padding-bottom: 10px;
        margin-bottom: 0;
    }

    .custom-table th {
        padding: 8px 6px !important;
        font-weight: 600;
        font-size: 14px;
    }

    .custom-table td {
        padding: 4px 6px !important;
        font-size: 13px;
        vertical-align: middle;
    }

    .custom-table .btn-sm {
        padding: 2px 6px;
        font-size: 12px;
    }

    .dataTables_wrapper {
        overflow-x: auto;
    }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="fw-bold text-dark">Category</h3>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal" id="reset">
                            Create
                        </button>
                    </div>

                    <div class="border mt-1 shadow rounded bg-white">
                        <div class="col-lg-12 m-auto">
                            <div class="table-responsive mt-4 mb-4">
                                <table id="default-datatable" class="table custom-table table-bordered table-hover text-center">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($category as $company)
                                            <tr>
                                                <td class="id">{{ $company->id }}</td>
                                                <td class="name">{{ $company->name }}</td>
                                                <td>
                                                    <button class="btn btn-primary btn-sm edit-btn"
                                                        data-url="{{ route('store.category') }}">
                                                        Edit
                                                    </button>
                                                    <button class="btn btn-danger btn-sm delete-btn"
                                                        data-url="{{ route('delete.category', $company->id) }}"
                                                        data-msg="Are you sure you want to delete this title"
                                                        data-method="get" onclick="logoutAndDeleteFunction(this)">
                                                        Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div> <!-- table-responsive -->
                        </div> <!-- col-lg-12 -->
                    </div> <!-- border -->
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal --}}
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="exampleModalLabel">Add Category</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form class="myform" action="{{ route('store.category') }}" method="POST">
                    @csrf
                    <input type="hidden" name="edit_id" id="id" />
                    <div class="mb-3">
                        <label for="name" class="form-label">Title</label>
                        <input type="text" name="name" class="form-control" id="name" />
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <input type="submit" class="btn btn-success save-btn" value="Save">
            </div>
            </form>
        </div>
    </div>
</div>

{{-- Scripts --}}
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{ asset('assets/js/mycode.js') }}"></script>

<script>
    $(document).on('submit', '.myform', function(e) {
        e.preventDefault();
        let formdata = new FormData(this);
        let url = $(this).attr('action');
        let method = $(this).attr('method');
        $(this).find(':submit').attr('disabled', true);
        myAjax(url, formdata, method);
    });

    $(document).on('click', '.edit-btn', function() {
        let tr = $(this).closest("tr");
        $('#id').val(tr.find(".id").text());
        $('#name').val(tr.find(".name").text());
        $("#exampleModal").modal("show");
    });

    $(document).ready(function() {
        $('#default-datatable').DataTable({
            pageLength: 10,
            lengthMenu: [5, 10, 25, 50, 100],
            order: [[0, 'desc']],
            language: {
                search: "Search Category:",
                lengthMenu: "Show _MENU_ entries"
            }
        });

        // Fix scroll/padding after modal close
        $(document).on('hidden.bs.modal', function () {
            $('body').css('padding-right', '0');
        });
    });
</script>

@endsection
