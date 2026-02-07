@extends('admin.layouts.app')

@section('title', 'Blog Posts')

@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Blog Posts</li>
        </ol>
    </nav>
@endsection

@section('page-actions')
    <a href="{{ route('admin.web-blogs.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Create Blog Post
    </a>
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Web Blog Posts</h5>

            <div class="search-filter">
                <form action="{{ route('admin.web-blogs.index') }}" method="GET" class="d-flex gap-2">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search..."
                            value="{{ request('search') }}">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>

                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>Published
                        </option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    </select>

                    <select name="user_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All Authors</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="col-md-1">
                        <a href="{{ route('admin.web-blogs.index') }}" class="btn btn-secondary ml-2">
                            <i class="fas fa-sync"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="5%">Sr No</th>
                            <th width="5%">ID</th>
                            <th width="35%">Title</th>
                            <th width="35%">Sub Title</th>
                            <th width="35%">Slug</th>
                            <th width="15%">Author</th>
                            <th width="10%">Status</th>
                            <th width="15%">Created At</th>
                            <th width="10%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($blogs as $index=>$blog)
                            <tr>
                                <td>{{ $blogs->firstItem() + $index }}</td>
                                <td>{{ $blog->id }}</td>
                                <td>
                                    <a href="{{ route('admin.web-blogs.show', $blog->id) }}"
                                        class="fw-bold text-decoration-none">
                                        {{ $blog->title }}
                                    </a>
                                    <div class="text-muted small">
                                        {{ Str::limit(strip_tags($blog->description), 100) }}
                                    </div>
                                </td>

                                <td>{{ $blog->sub_title }}</td>
                                <td>{{ $blog->slug }}</td>
                                <td>
                                    @if ($blog->user)
                                        <div class="d-flex align-items-center">
                                            <div class="me-2">
                                                @if ($blog->user->profile_picture)
                                                    <img src="{{ asset('storage/' . $blog->user->profile_picture) }}"
                                                        alt="{{ $blog->user->name }}" class="rounded-circle" width="30"
                                                        height="30">
                                                @else
                                                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center"
                                                        style="width: 30px; height: 30px; color: white;">
                                                        {{ strtoupper(substr($blog->user->name, 0, 1)) }}
                                                    </div>
                                                @endif
                                            </div>
                                            <div>
                                                {{ $blog->user->name }}
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted">Unknown</span>
                                    @endif
                                </td>

                                <td>
                                    <span
                                        class="badge bg-{{ $blog->status == 'published' ? 'success' : 'warning text-dark' }}">
                                        {{ ucfirst($blog->status) }}
                                    </span>
                                </td>
                                <td>{{ $blog->created_at->format('M d, Y') }}</td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('admin.web-blogs.show', $blog->id) }}"
                                            class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.web-blogs.edit', $blog->id) }}"
                                            class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.web-blogs.destroy', $blog->id) }}" method="POST"
                                            onsubmit="return confirm('Are you sure you want to delete this blog post?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" data-bs-toggle="tooltip"
                                                title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No blog posts found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($blogs->hasPages())
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        Showing {{ $blogs->firstItem() ?? 0 }} to {{ $blogs->lastItem() ?? 0 }} of
                        {{ $blogs->total() }}
                        blogs
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        {{ $blogs->onEachSide(5)->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    </script>
@endsection
