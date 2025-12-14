@extends('admin.layouts.app')

@section('title', 'Create Blog Post')

@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.blogs.index') }}">Blog Posts</a></li>
            <li class="breadcrumb-item active" aria-current="page">Create</li>
        </ol>
    </nav>
@endsection

@section('page-actions')
    <a href="{{ route('admin.blogs.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i> Back to Blog Posts
    </a>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Create New Blog Post</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.blogs.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="row mb-3">
                    <div class="col-md-8">
                        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-4">
                        <label for="user_id" class="form-label">Author <span class="text-danger">*</span></label>
                        <select class="form-select @error('user_id') is-invalid @enderror" id="user_id" name="user_id" required>
                            <option value="">Select Author</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="content" class="form-label">Content <span class="text-danger">*</span></label>
                    <textarea class="form-control @error('content') is-invalid @enderror" id="content" name="content" rows="10" required>{{ old('content') }}</textarea>
                    @error('content')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Tip: You can use Markdown formatting for rich text.</div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="attachment" class="form-label">Attachment</label>
                        <input type="file" class="form-control @error('attachment') is-invalid @enderror" id="attachment" name="attachment">
                        <div class="form-text">Supported formats: JPG, JPEG, PNG, PDF, MP4, WEBM, MOV. Max size: 10MB.</div>
                        @error('attachment')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                            <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="published" {{ old('status') == 'published' ? 'selected' : '' }}>Published</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="preview-container mb-3" style="display: none;">
                    <label class="form-label">Attachment Preview</label>
                    <div class="preview-content p-2 border rounded">
                        <img id="image-preview" src="#" alt="Preview" class="img-fluid" style="max-height: 200px; display: none;">
                        <video id="video-preview" controls class="img-fluid" style="max-height: 200px; display: none;">
                            <source src="" type="">
                            Your browser does not support the video tag.
                        </video>
                        <div id="document-preview" class="d-flex align-items-center" style="display: none;">
                            <i class="fas fa-file-pdf fa-3x text-danger me-2"></i>
                            <span id="document-name">Document</span>
                        </div>
                    </div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <button type="reset" class="btn btn-secondary me-md-2">Reset</button>
                    <button type="submit" class="btn btn-primary">Create Blog Post</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    // Initialize rich text editor if you have one
    // For example: CKEDITOR.replace('content');
    
    // Attachment preview
    document.getElementById('attachment').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const previewContainer = document.querySelector('.preview-container');
        const imagePreview = document.getElementById('image-preview');
        const videoPreview = document.getElementById('video-preview');
        const documentPreview = document.getElementById('document-preview');
        const documentName = document.getElementById('document-name');
        
        if (!file) {
            previewContainer.style.display = 'none';
            return;
        }
        
        previewContainer.style.display = 'block';
        
        // Hide all preview elements initially
        imagePreview.style.display = 'none';
        videoPreview.style.display = 'none';
        documentPreview.style.display = 'none';
        
        if (file.type.startsWith('image/')) {
            // Show image preview
            imagePreview.style.display = 'block';
            
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        } else if (file.type.startsWith('video/')) {
            // Show video preview
            videoPreview.style.display = 'block';
            
            const videoSource = videoPreview.querySelector('source');
            videoSource.src = URL.createObjectURL(file);
            videoSource.type = file.type;
            videoPreview.load();
        } else {
            // Show document preview
            documentPreview.style.display = 'flex';
            documentName.textContent = file.name;
            
            // Change icon based on file type
            const icon = documentPreview.querySelector('i');
            if (file.type === 'application/pdf') {
                icon.className = 'fas fa-file-pdf fa-3x text-danger me-2';
            } else {
                icon.className = 'fas fa-file-alt fa-3x text-primary me-2';
            }
        }
    });
</script>
@endsection