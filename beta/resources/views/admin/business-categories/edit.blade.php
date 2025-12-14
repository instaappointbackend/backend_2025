@extends('admin.layouts.app')

@section('title', 'Edit Business Type')

@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.business-categories.index') }}">Business Types</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit</li>
        </ol>
    </nav>
@endsection

@section('page-actions')
    <div class="d-flex gap-2">
        <a href="{{ route('admin.business-categories.show', $businessCategory->id) }}" class="btn btn-info">
            <i class="fas fa-eye me-1"></i> View
        </a>
        <a href="{{ route('admin.business-categories.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Business Types
        </a>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Edit Business Type</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.business-categories.update', $businessCategory->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $businessCategory->name) }}" required>
                        @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4">{{ old('description', $businessCategory->description) }}</textarea>
                    @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                @if($businessCategory->image)
                    <div class="mb-3">
                        <label class="form-label">Current Image</label>
                        <div class="d-flex align-items-center">
                            <div class="border rounded p-2 me-3">
                                <img src="{{ asset('storage/' . $businessCategory->image) }}" alt="{{ $businessCategory->name }}" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: contain;">
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remove_image" id="remove_image" value="1">
                                <label class="form-check-label" for="remove_image">
                                    Remove current image
                                </label>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="mb-3">
                    <label for="image" class="form-label">{{ $businessCategory->image ? 'Change Image' : 'Image' }} {{ !$businessCategory->image ? '<span class="text-danger">*</span>' : '' }}</label>
                    <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" name="image" accept="image/*" {{ !$businessCategory->image ? 'required' : '' }}>
                    <small class="form-text text-muted">{{ $businessCategory->image ? 'Upload a new image to replace the current one. Leave blank to keep the current image.' : 'Upload an image for this business type.' }} Allowed formats: JPG, PNG, GIF.</small>
                    @error('image')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="preview-container mb-3" style="display: none;">
                    <label class="form-label">New Image Preview</label>
                    <div class="preview-content p-2 border rounded d-inline-block">
                        <img id="image-preview" src="#" alt="Preview" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: contain;">
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <button type="reset" class="btn btn-secondary me-md-2" id="reset-form">Reset</button>
                    <button type="submit" class="btn btn-primary">Update Business Type</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Image preview
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const previewContainer = document.querySelector('.preview-container');
            const imagePreview = document.getElementById('image-preview');

            if (!file) {
                previewContainer.style.display = 'none';
                return;
            }

            if (!file.type.startsWith('image/')) {
                alert('Please select an image file');
                this.value = '';
                previewContainer.style.display = 'none';
                return;
            }

            previewContainer.style.display = 'block';

            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });

        // Toggle image required when remove_image is checked
        const removeImageCheckbox = document.getElementById('remove_image');
        if (removeImageCheckbox) {
            removeImageCheckbox.addEventListener('change', function() {
                const imageInput = document.getElementById('image');
                if (this.checked) {
                    imageInput.required = true;
                } else {
                    imageInput.required = false;
                }
            });
        }

        // Reset preview when form is reset
        document.getElementById('reset-form').addEventListener('click', function() {
            document.querySelector('.preview-container').style.display = 'none';
        });
    </script>
@endsection
