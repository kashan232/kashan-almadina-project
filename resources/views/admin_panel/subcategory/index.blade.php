@extends('admin_panel.layout.app')
@section('content')

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid pt-2">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-2 border-bottom d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold text-dark mb-0"><i class="fa fa-sitemap me-2 text-primary"></i>Sub Categories</h5>
                            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#exampleModal" id="reset">
                                <i class="fa fa-plus me-1"></i> Add Subcategory
                            </button>
                        </div>
                        <div class="card-body p-3">
                            <div class="table-responsive">
                                <table id="example" class="table table-sm table-hover table-bordered align-middle w-100 mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th style="width: 80px;" class="text-center">ID</th>
                                            <th>Subcategory Name</th>
                                            <th>Main Category</th>
                                            <th style="width: 150px;" class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($subcategory as $company)
                                        <tr>
                                            <td class="id text-center fw-bold text-muted">#{{ $company->id }}</td>
                                            <td class="name fw-bold text-dark">{{ $company->name }}</td>
                                            <td class="cat-name"><span class="badge bg-light text-primary border px-2 py-1">{{ $company->category->name ?? '-' }}</span></td>
                                            <td class="text-center">
                                                <button class="btn btn-outline-primary btn-xs px-2 edit-btn"
                                                    data-id="{{ $company->id }}"
                                                    data-name="{{ $company->name }}"
                                                    data-category-id="{{ $company->category_id }}">
                                                    <i class="fa fa-pencil me-1"></i> Edit
                                                </button>
                                                <button class="btn btn-outline-danger btn-xs px-2 delete-btn"
                                                    data-url="{{ route('delete.subcategory', $company->id) }}"
                                                    data-msg="Are you sure you want to delete this subcategory?"
                                                    data-method="get"
                                                    onclick="logoutAndDeleteFunction(this)">
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

<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content shadow-sm">
            <div class="modal-header bg-primary text-white py-2">
                <h5 class="modal-title fs-6 fw-bold" id="exampleModalLabel"><i class="fa fa-sitemap me-2"></i>Add / Edit Subcategory</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form class="myform" action="{{ route('store.subcategory') }}" method="POST" autocomplete="off">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="edit_id" id="edit_id" />
                    <div class="mb-3">
                        <label for="name" class="form-label fw-bold">Subcategory Title <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" id="name" placeholder="Enter Subcategory Name" required />
                    </div>
                    <div class="mb-3">
                        <label for="category_id" class="form-label fw-bold">Select Category <span class="text-danger">*</span></label>
                        <select name="category_id" id="category_id" class="form-select form-select-sm select2" required>
                            <option value="">Select Category...</option>
                            @foreach ($category as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success btn-sm rounded-pill px-4 save-btn"><i class="fa fa-check me-1"></i> Save Subcategory</button>
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
            $('#category_id').val('').trigger('change');
            $('.myform').find(':submit').attr('disabled', false);
        });

        $(document).on('click', '.edit-btn', function() {
            let id = $(this).data('id');
            let name = $(this).data('name');
            let catId = $(this).data('category-id');
            $('#edit_id').val(id);
            $('#name').val(name);
            $('#category_id').val(catId).trigger('change');
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