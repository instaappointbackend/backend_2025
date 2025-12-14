@extends('admin.layouts.app')

@section('title', 'View Blog Post')

@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.blogs.index') }}">Web Blog Posts</a></li>
            <li class="breadcrumb-item active" aria-current="page">View</li>
        </ol>
    </nav>
@endsection

@section('page-actions')
    <div class="d-flex gap-2">
        <a href="{{ route('admin.web-blogs.edit', $blog->id) }}" class="btn btn-primary">
            <i class="fas fa-edit me-1"></i> Edit
        </a>
        <form action="{{ route('admin.web-blogs.destroy', $blog->id) }}" method="POST" class="d-inline"
            onsubmit="return confirm('Are you sure you want to delete this blog post?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">
                <i class="fas fa-trash me-1"></i> Delete
            </button>
        </form>
        <a href="{{ route('admin.web-blogs.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Blog Posts
        </a>
    </div>
@endsection

@section('content')
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Web Blog Post Details</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h2>{{ $blog->title }}</h2>

                    <div class="d-flex align-items-center text-muted mb-4">
                        <div class="me-3">
                            <i class="fas fa-user me-1"></i> {{ $blog->user->name }}
                        </div>
                        <div class="me-3">
                            <i class="fas fa-calendar-alt me-1"></i> {{ $blog->created_at->format('F d, Y') }}
                        </div>
                        <div>
                            @if ($blog->status == 'published')
                                <span class="badge bg-success">Published</span>
                            @else
                                <span class="badge bg-warning text-dark">Draft</span>
                            @endif
                        </div>
                    </div>
                    <div class="mb-4">
                        <h6 class="fw-bold">Banner Image</h6>
                        <div class="w-100 h-100" style="max-width: 400px; max-height: 300px; overflow: hidden;">


                            <img src="{{ asset('storage/' . $blog->image_path) }}"
                                class="img-fluid w-100 h-100 object-fit-cover">
                        </div>
                    </div>
                    <div class="mb-4">
                        <h6 class="fw-bold">Content</h6>
                        <div class="p-3 bg-light rounded">
                            {!! nl2br(e($blog->description)) !!}
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    {{-- <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Attachment</h6>
                        </div>
                        <div class="card-body">
                            @if ($blog->attachment)
                                @if ($blog->attachment_type == 'image')
                                    <div class="text-center">
                                        <img src="{{ asset('storage/' . $blog->attachment) }}" alt="{{ $blog->title }}"
                                            class="img-fluid rounded mb-2">
                                        <div class="mt-2">
                                            <a href="{{ asset('storage/' . $blog->attachment) }}"
                                                class="btn btn-sm btn-outline-primary" target="_blank">
                                                <i class="fas fa-external-link-alt me-1"></i> View Full Size
                                            </a>
                                        </div>
                                    </div>
                                @elseif($blog->attachment_type == 'video')
                                    <div class="text-center">
                                        <video controls class="img-fluid rounded mb-2" style="max-height: 300px;">
                                            <source src="{{ asset('storage/' . $blog->attachment) }}" type="video/mp4">
                                            Your browser does not support the video tag.
                                        </video>
                                        <div class="mt-2">
                                            <a href="{{ asset('storage/' . $blog->attachment) }}"
                                                class="btn btn-sm btn-outline-primary" download>
                                                <i class="fas fa-download me-1"></i> Download Video
                                            </a>
                                        </div>
                                    </div>
                                @else
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-file-pdf fa-3x text-danger me-3"></i>
                                        <div>
                                            <p class="mb-1">Document Attached</p>
                                            <a href="{{ asset('storage/' . $blog->attachment) }}"
                                                class="btn btn-sm btn-outline-primary" target="_blank">
                                                <i class="fas fa-download me-1"></i> Download
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            @else
                                <div class="text-center py-4">
                                    <i class="fas fa-file-upload fa-3x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">No attachment available</p>
                                </div>
                            @endif
                        </div>
                    </div> --}}

                    <div class="card mt-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Details</h6>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="text-muted">ID:</span>
                                    <span class="fw-medium">{{ $blog->id }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="text-muted">Created:</span>
                                    <span class="fw-medium">{{ $blog->created_at->format('M d, Y H:i A') }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="text-muted">Last Updated:</span>
                                    <span class="fw-medium">{{ $blog->updated_at->format('M d, Y H:i A') }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="text-muted">Status:</span>
                                    <span class="fw-medium">
                                        @if ($blog->status == 'published')
                                            <span class="text-success">Published</span>
                                        @else
                                            <span class="text-warning">Draft</span>
                                        @endif
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="text-muted">Author:</span>
                                    <span class="fw-medium">{{ $blog->user->name }}</span>
                                </li>
                                @if ($blog->attachment)
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span class="text-muted">Attachment Type:</span>
                                        <span class="fw-medium">
                                            @if ($blog->attachment_type == 'image')
                                                <i class="fas fa-image text-info me-1"></i> Image
                                            @elseif($blog->attachment_type == 'video')
                                                <i class="fas fa-video text-success me-1"></i> Video
                                            @else
                                                <i class="fas fa-file text-danger me-1"></i> Document
                                            @endif
                                        </span>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <h6><strong>Image</strong></h6>
                </div>
                <div class="col-md-6">
                    <h6> <strong> Image Description </strong></h6>

                </div>
            </div>
            <div class="row">
                @foreach ($blog->images as $image)
                    <div class="w-100 h-100 m-2" style="max-width: 400px; max-height: 300px; overflow: hidden;">


                        <img src="{{ asset('storage/' . $image->image_path) }}"
                            class="img-fluid w-100 h-100 object-fit-cover">
                    </div>
                    <div class="col-md-6 m-2">

                        <div>{{ $image->image_description }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Additional JavaScript if needed
    </script>
@endsection
