@extends('admin.layouts.app')

@section('title', 'Edit Newsletter Subscriber')

@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.newsletters.index') }}">Newsletter Subscribers</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit Subscriber</li>
        </ol>
    </nav>
@endsection

@section('page-actions')
    <a href="{{ route('admin.newsletters.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i> Back to Subscribers
    </a>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Edit Subscriber</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.newsletters.update', $newsletter) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $newsletter->email) }}" required>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $newsletter->name) }}">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                        <option value="subscribed" {{ old('status', $newsletter->status) == 'subscribed' ? 'selected' : '' }}>Subscribed</option>
                        <option value="unsubscribed" {{ old('status', $newsletter->status) == 'unsubscribed' ? 'selected' : '' }}>Unsubscribed</option>
                        <option value="pending" {{ old('status', $newsletter->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Subscribed Date</label>
                        <input type="text" class="form-control" value="{{ $newsletter->subscribed_at ? $newsletter->subscribed_at->format('F d, Y, h:i A') : 'N/A' }}" readonly disabled>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Unsubscribed Date</label>
                        <input type="text" class="form-control" value="{{ $newsletter->unsubscribed_at ? $newsletter->unsubscribed_at->format('F d, Y, h:i A') : 'N/A' }}" readonly disabled>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Source</label>
                        <input type="text" class="form-control" value="{{ $newsletter->source ?? 'Website' }}" readonly disabled>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">IP Address</label>
                        <input type="text" class="form-control" value="{{ $newsletter->ip_address ?? 'N/A' }}" readonly disabled>
                    </div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <button type="button" class="btn btn-danger me-md-2" onclick="confirmDelete('{{ $newsletter->id }}')">Delete</button>
                    <button type="button" class="btn btn-secondary me-md-2" onclick="window.location.href='{{ route('admin.newsletters.index') }}'">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Subscriber</button>
                </div>
                
                <form id="delete-form-{{ $newsletter->id }}" 
                      action="{{ route('admin.newsletters.destroy', $newsletter) }}" 
                      method="POST" 
                      style="display: none;">
                    @csrf
                    @method('DELETE')
                </form>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    function confirmDelete(newsletterId) {
        if (confirm('Are you sure you want to delete this subscriber? This action cannot be undone.')) {
            document.getElementById('delete-form-' + newsletterId).submit();
        }
    }
</script>
@endsection