@extends('admin_panel.layout.app')
@section('content')

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-2">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-2 border-bottom d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold text-dark mb-0"><i class="fa fa-bookmark me-2 text-primary"></i>Brands</h5>
                            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#exampleModal" id="reset">
                                <i class="fa fa-plus me-1"></i> Add Brand
                            </button>
                        </div>
                        <div class="card-body p-3">
                            <div class="table-responsive">
                                <table id="example" class="table table-sm table-hover table-bordered align-middle w-100 mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th style="width: 80px;" class="text-center">ID</th>
                                            <th>Brand Name</th>
                                            <th style="width: 150px;" class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($Brand as $company)
                                        <tr>
                                            <td class="id text-center fw-bold text-muted">#{{ $company->id }}</td>
                                            <td class="name fw-bold text-dark">{{ $company->name }}</td>
                                            <td class="text-center">
                                                <button class="btn btn-outline-primary btn-xs px-2 edit-btn"
                                                    data-id="{{ $company->id }}"
                                                    data-name="{{ $company->name }}">
                                                    <i class="fa fa-pencil me-1"></i> Edit
                                                </button>
                                                <button class="btn btn-outline-danger btn-xs px-2 delete-btn"
                                                    data-url="{{ route('delete.Brand', $company->id) }}"
                                                    data-msg="Are you sure you want to delete this brand?"
                                                    data-method="get" onclick="logoutAndDeleteFunction(this)">
                                                    <i class="fa fa-trash me-1"></i> Delete
                                                </button>
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

{{-- Modal --}}
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content shadow-sm">
            <div class="modal-header bg-primary text-white py-2">
                <h5 class="modal-title fs-6 fw-bold" id="exampleModalLabel"><i class="fa fa-bookmark me-2"></i>Add / Edit Brand</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form class="myform" action="{{ route('store.Brand') }}" method="POST" autocomplete="off">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="edit_id" id="edit_id" />
                    <div class="mb-3">
                        <label for="name" class="form-label fw-bold">Brand Title <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" id="name" placeholder="Enter Brand Name" required />
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success btn-sm rounded-pill px-4 save-btn"><i class="fa fa-check me-1"></i> Save Brand</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $(document).on('click', '#reset', function() {
            $('#edit_id').val('');
            $('#name').val('');
            $('.myform').find(':submit').attr('disabled', false);
        });

        $(document).on('click', '.edit-btn', function() {
            let id = $(this).data('id');
            let name = $(this).data('name');
            $('#edit_id').val(id);
            $('#name').val(name);
            $('.myform').find(':submit').attr('disabled', false);
            $("#exampleModal").modal("show");
        });

        $(document).on('submit', '.myform', function(e) {
            e.preventDefault();
            let formdata = new FormData(this);
            let url = $(this).attr('action');
            let method = $(this).attr('method') || 'POST';
            let $submitBtn = $(this).find(':submit');
            $submitBtn.attr('disabled', true);
            
            myAjax(url, formdata, method, function(res) {
                $submitBtn.attr('disabled', false);
            });
        });
    });
</script>
@endsection
