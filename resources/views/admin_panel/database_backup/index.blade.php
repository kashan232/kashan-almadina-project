@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="container-fluid p-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-primary mb-1"><i class="fas fa-database me-2"></i> Database Backup Manager</h3>
                <p class="text-muted mb-0">Create, track, and download full system database SQL backups.</p>
            </div>
            <div>
                <form action="{{ route('database-backups.create') }}" method="POST" onsubmit="return confirmBackup(this);">
                    @csrf
                    <button type="submit" class="btn btn-success fw-bold px-4 py-2 shadow-sm" id="btnGenerateBackup">
                        <i class="fas fa-download me-2"></i> Create Backup Now
                    </button>
                </form>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 mb-4 shadow-sm" role="alert">
                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 mb-4 shadow-sm" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-bold text-dark"><i class="fas fa-history me-2 text-secondary"></i> Backup History & Logs</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-nowrap">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">#</th>
                                <th>Filename</th>
                                <th>Size</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Date & Time</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($backups as $index => $backup)
                                <tr>
                                    <td class="ps-4 fw-semibold text-secondary">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-file-code text-primary fa-lg me-3"></i>
                                            <div>
                                                <span class="fw-bold text-dark d-block">{{ $backup->filename }}</span>
                                                <small class="text-muted">{{ $backup->file_path }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-light text-dark border fw-semibold px-2 py-1">{{ $backup->formatted_size }}</span></td>
                                    <td>
                                        @if($backup->status == 'completed')
                                            <span class="badge bg-success-subtle text-success border border-success fw-bold px-3 py-1">Completed</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger fw-bold px-3 py-1">{{ ucfirst($backup->status) }}</span>
                                        @endif
                                    </td>
                                    <td><span class="fw-semibold text-secondary">{{ $backup->creator ? $backup->creator->name : 'System' }}</span></td>
                                    <td><span class="text-dark">{{ $backup->created_at->format('d M Y, h:i A') }}</span></td>
                                    <td class="text-end pe-4">
                                        <a href="{{ route('database-backups.download', $backup->id) }}" class="btn btn-sm btn-outline-primary me-2 px-3 fw-bold" title="Download SQL File">
                                            <i class="fas fa-download me-1"></i> Download
                                        </a>
                                        <form action="{{ route('database-backups.destroy', $backup->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this backup file?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger px-3 fw-bold" title="Delete Backup">
                                                <i class="fas fa-trash-alt me-1"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fas fa-database fa-3x mb-3 text-secondary d-block"></i>
                                        <h5>No database backups found</h5>
                                        <p class="mb-0">Click the <strong>Create Backup Now</strong> button above to generate your first backup.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmBackup(form) {
    var btn = document.getElementById('btnGenerateBackup');
    if(confirm('Are you sure you want to generate a new Database Backup?')) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Generating Backup...';
        return true;
    }
    return false;
}
</script>
@endsection
