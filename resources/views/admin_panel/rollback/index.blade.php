@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="container-fluid p-4">
        
        <div class="row justify-content-center mt-5">
            <div class="col-lg-4 col-md-6">
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="card-header bg-danger text-white py-3 text-center">
                        <h4 class="mb-0 fw-bold">Rollback Postings</h4>
                    </div>
                    <div class="card-body p-4">
                        
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 mb-4" role="alert">
                                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 mb-4" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        <form action="{{ route('rollback.process') }}" method="POST">
                            @csrf
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold text-secondary">Select Module</label>
                                <select name="module" class="form-select border-2" required>
                                    <option value="" selected disabled>Choose Module</option>
                                    @foreach($modules as $key => $name)
                                        <option value="{{ $key }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold text-secondary">Invoice / Document Number</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-2 border-end-0"><i class="fas fa-hashtag"></i></span>
                                    <input type="text" name="invoice_no" class="form-control border-2 border-start-0" placeholder="Enter Invoice Number" required>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-danger fw-bold py-2 rounded-3 shadow-sm">
                                    Process Rollback
                                </button>
                                <a href="{{ url()->previous() }}" class="btn btn-light fw-bold py-2 rounded-3 border-0">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
    .form-control:focus, .form-select:focus {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.1);
    }
    .card {
        transition: transform 0.3s ease;
    }
    .card:hover {
        transform: translateY(-5px);
    }
</style>
@endsection
