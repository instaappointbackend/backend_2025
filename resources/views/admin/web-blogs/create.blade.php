@extends('admin.layouts.app')

@section('title', 'Create Blog Post')

@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.web-blogs.index') }}">Web Blog Posts</a></li>
            <li class="breadcrumb-item active" aria-current="page">Create</li>
        </ol>
    </nav>
@endsection

@section('page-actions')
    <a href="{{ route('admin.web-blogs.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i> Back to Blog Posts
    </a>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Create Web Blog Post</h5>
        </div>
        <div class="card-body">
            @include('admin.web-blogs.createForm')
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
