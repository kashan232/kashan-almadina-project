@extends('admin_panel.layout.app')
@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container">
            <div class="card shadow-sm mt-4">
                <div class="card-header text-white bg-warning">
                    <h4 class="mb-0 text-dark">Edit Vendor</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('vendor.update', $vendor->id) }}" method="POST">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label><strong>Vendor Name:</strong></label>
                                <input type="text" class="form-control" name="name" value="{{ old('name', $vendor->name) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label><strong>Phone Number:</strong></label>
                                <input type="text" class="form-control" name="phone" value="{{ old('phone', $vendor->phone) }}">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label><strong>Opening Balance:</strong></label>
                                <input type="number" class="form-control" name="opening_balance" value="{{ old('opening_balance', $vendor->opening_balance) }}" step="0.01">
                            </div>
                            <div class="col-md-6">
                                <label><strong>Assigned User Groups:</strong></label>
                                @if($isAdmin)
                                    <select name="user_group_ids[]" class="form-control select2" multiple>
                                        @foreach($userGroups as $group)
                                            <option value="{{ $group->id }}" {{ in_array($group->id, $vendor->user_group_ids ?? []) ? 'selected' : '' }}>
                                                {{ $group->group_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <div class="form-control bg-light" style="height: auto; min-height: 38px;">
                                        @php $myGroups = Auth::user()->userGroups; @endphp
                                        @if($myGroups->count() > 0)
                                            @foreach($myGroups as $group)
                                                <span class="badge bg-info text-dark">{{ $group->group_name }}</span>
                                                <input type="hidden" name="user_group_ids[]" value="{{ $group->id }}">
                                            @endforeach
                                        @else
                                            <span class="text-muted">No Groups Assigned to You</span>
                                        @endif
                                    </div>
                                    <small class="text-muted">Your groups are automatically assigned to this vendor.</small>
                                @endif
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-12">
                                <label><strong>Address:</strong></label>
                                <textarea rows="3" class="form-control" name="address">{{ old('address', $vendor->address) }}</textarea>
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn btn-primary px-5">Update Vendor</button>
                            <a href="{{ url('vendor') }}" class="btn btn-secondary px-5">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('.select2').select2({
            width: '100%',
            placeholder: 'Select Groups'
        });
    });
</script>
@endsection
