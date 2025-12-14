@extends('admin.layouts.app')

@section('title', 'Edit Blog Post')

@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.web-blogs.index') }}">Blog Posts</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit</li>
        </ol>
    </nav>
@endsection

@section('page-actions')
    <div class="d-flex gap-2">
        <a href="{{ route('admin.web-blogs.show', $blog->id) }}" class="btn btn-info">
            <i class="fas fa-eye me-1"></i> View
        </a>
        <a href="{{ route('admin.web-blogs.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Blog Posts
        </a>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Edit Blog Post</h5>
        </div>
        <div class="card-body">
            @include('admin.web-blogs.editForm')
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function addImageField() {
            const container = document.getElementById('image-upload-group');
            console.log('here', container);
            const html = `
                <div class="image-row mb-4">
                    <div class="mb-2">
                        <input type="file" name="images[]" class="form-control">
                    </div>
                    <div>
                        <textarea name="image_descriptions[]" class="form-control" rows="3" placeholder="Image Description"></textarea>
                    </div>
                </div>
                `;
            container.insertAdjacentHTML('beforeend', html);
        }
    </script>
@endsection
