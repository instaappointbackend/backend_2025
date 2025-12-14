@extends('admin.layouts.app')

@section('title', 'View Policy Page')

@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.pages.index') }}">Policy Pages</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $page->title }}</li>
        </ol>
    </nav>
@endsection

@section('page-actions')
    <div class="btn-group">
        <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-primary">
            <i class="fas fa-edit me-1"></i> Edit
        </a>
        <a href="{{ route('admin.pages.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Page Content</h5>
                    <div>
                        @if($page->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <h2 class="mb-4">{{ $page->title }}</h2>
                    
                    <div class="content-preview border rounded p-3 mb-3">
                        {!! $page->content !!}
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-primary">
                            <i class="fas fa-edit me-1"></i> Edit Content
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Page Details</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="fw-bold">URL Slug</h6>
                        <div class="input-group">
                            <span class="input-group-text text-muted">{{ url('/') }}/</span>
                            <input type="text" class="form-control" value="{{ $page->slug }}" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('{{ url('/') }}/{{ $page->slug }}')">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="fw-bold">SEO Information</h6>
                        <table class="table table-sm">
                            <tr>
                                <th>Meta Title</th>
                                <td>{{ $page->meta_title ?: $page->title }}</td>
                            </tr>
                            <tr>
                                <th>Meta Description</th>
                                <td>{{ $page->meta_description ?: 'Not set' }}</td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="fw-bold">Last Updated</h6>
                        <p class="mb-0">
                            @if($page->last_updated_at)
                                {{ $page->last_updated_at->format('F d, Y \a\t H:i') }}
                            @else
                                Never
                            @endif
                        </p>
                    </div>
                    
                    <hr>
                    
                    <form action="{{ route('admin.pages.destroy', $page) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this page? This action cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <div class="d-grid">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash me-1"></i> Delete Page
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Preview</h5>
                </div>
                <div class="card-body">
                    <p>See how this page looks on the front-end:</p>
                    <div class="d-grid">
                        <a href="{{ url('/') . '/' . $page->slug }}" class="btn btn-outline-primary" target="_blank">
                            <i class="fas fa-external-link-alt me-1"></i> View Live Page
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            alert('URL copied to clipboard');
        }, function() {
            alert('Failed to copy URL');
        });
    }
</script>
@endsection