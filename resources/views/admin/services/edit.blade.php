 
<!-- resources/views/admin/services/edit.blade.php -->
@extends('admin.layouts.app')

@section('title', 'Edit Service')

@section('page-title', 'Edit Service')

@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.services.index') }}">Services</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit</li>
        </ol>
    </nav>
@endsection

@section('page-actions')
    <a href="{{ route('admin.services.show', $service->id) }}" class="btn btn-info me-2">
        <i class="fas fa-eye me-1"></i> View Service
    </a>
    <a href="{{ route('admin.services.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i> Back to Services
    </a>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Edit Service Information</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.services.update', $service->id) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Service Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $service->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label for="user_id" class="form-label">Vendor <span class="text-danger">*</span></label>
                        <select class="form-select @error('user_id') is-invalid @enderror" id="user_id" name="user_id" required>
                            <option value="">Select Vendor</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" {{ old('user_id', $service->user_id) == $vendor->id ? 'selected' : '' }}>
                                    {{ $vendor->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4" required>{{ old('description', $service->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="duration" class="form-label">Duration (minutes) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('duration') is-invalid @enderror" id="duration" name="duration" value="{{ old('duration', $service->duration) }}" min="5" required>
                        <div class="form-text" id="formatted-duration">
                            {{ floor($service->duration / 60) > 0 ? floor($service->duration / 60) . ' hour' . (floor($service->duration / 60) > 1 ? 's' : '') : '' }}
                            {{ $service->duration % 60 > 0 ? ($service->duration % 60) . ' minute' . ($service->duration % 60 > 1 ? 's' : '') : '' }}
                        </div>
                        @error('duration')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label for="price" class="form-label">Price (INR) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price', $service->price) }}" min="0" step="0.01" required>
                            @error('price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ old('is_active', $service->is_active) == '1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                    <div class="form-text">If checked, this service will be available for booking.</div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted">Created At</label>
                        <p>{{ $service->created_at->format('M d, Y h:i A') }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">Last Updated</label>
                        <p>{{ $service->updated_at->format('M d, Y h:i A') }}</p>
                    </div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <button type="reset" class="btn btn-secondary me-md-2">Reset</button>
                    <button type="submit" class="btn btn-primary">Update Service</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Danger Zone -->
    <div class="card mt-4 border-danger">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0">Danger Zone</h5>
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1">Delete Service</h6>
                    <p class="text-muted mb-0">Once deleted, this service will be permanently removed.</p>
                </div>
                <div>
                    <form action="{{ route('admin.services.destroy', $service->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this service?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i> Delete Service
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    // Format price as currency
    document.getElementById('price').addEventListener('blur', function(e) {
        const value = parseFloat(this.value);
        if (!isNaN(value)) {
            this.value = value.toFixed(2);
        }
    });
    
    // Convert duration to hours and minutes
    document.getElementById('duration').addEventListener('blur', function(e) {
        const duration = parseInt(this.value);
        if (!isNaN(duration) && duration > 0) {
            const hours = Math.floor(duration / 60);
            const minutes = duration % 60;
            let durationText = '';
            
            if (hours > 0) {
                durationText += hours + ' hour' + (hours > 1 ? 's' : '');
                if (minutes > 0) {
                    durationText += ' ';
                }
            }
            
            if (minutes > 0) {
                durationText += minutes + ' minute' + (minutes > 1 ? 's' : '');
            }
            
            // Update the formatted duration display
            document.getElementById('formatted-duration').textContent = durationText;
        }
    });
</script>
@endsection