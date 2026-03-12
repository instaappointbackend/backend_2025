@extends('admin.layouts.app')

@section('title', 'Business Types')

@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Business Types</li>
        </ol>
    </nav>
@endsection

@section('page-actions')
    <a href="{{ route('admin.business-categories.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Add New Business Type
    </a>
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Business Types</h5>
            <div class="search-filter">
                <form action="{{ route('admin.business-categories.index') }}" method="GET" class="d-flex gap-2">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Search business types..." name="search"
                            value="{{ request('search') }}">
                        <button class="btn btn-outline-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    @if (request('search'))
                        <a href="{{ route('admin.business-categories.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    @endif
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
                            <th width="10%">Image</th>
                            <th width="20%">Name</th>
                            <th width="35%">Description</th>
                            <th width="15%">Created At</th>
                            <th width="15%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($businessCategories as $index=>$businessCategory)
                            <tr>
                                <td>{{ $businessCategories->firstItem() + $index }}</td>
                                <td>{{ $businessCategory->id }}</td>
                                <td>
                                    @if ($businessCategory->image && Storage::disk('public')->exists($businessCategory->image))
                                        <img src="{{ asset('storage/' . $businessCategory->image) }}"
                                            alt="{{ $businessCategory->name }}" class="img-thumbnail"
                                            style="max-width: 50px; max-height: 50px; object-fit: cover;">
                                    @else
                                        <span class="badge bg-secondary">No Image</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.business-categories.show', $businessCategory->id) }}"
                                        class="fw-bold text-decoration-none">
                                        {{ $businessCategory->name }}
                                    </a>
                                </td>
                                <td>
                                    <div class="text-muted">
                                        {{ Str::limit($businessCategory->description, 100) }}
                                    </div>
                                </td>
                                <td>
                                    @if ($businessCategory->created_at)
                                        {{ $businessCategory->created_at->format('M d, Y') }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('admin.business-categories.show', $businessCategory->id) }}"
                                            class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.business-categories.edit', $businessCategory->id) }}"
                                            class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form
                                            action="{{ route('admin.business-categories.destroy', $businessCategory->id) }}"
                                            method="POST" class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to delete this business type? This action cannot be undone.');">
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
                                <td colspan="6" class="text-center py-4">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="fas fa-building fa-3x text-muted mb-3"></i>
                                        <p class="mb-1">No business types found</p>
                                        @if (request('search'))
                                            <a href="{{ route('admin.business-categories.index') }}"
                                                class="btn btn-sm btn-outline-secondary mt-2">
                                                Clear filters
                                            </a>
                                        @else
                                            <a href="{{ route('admin.business-categories.create') }}"
                                                class="btn btn-sm btn-primary mt-2">
                                                <i class="fas fa-plus me-1"></i> Add New Business Type
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
                @if ($businessCategories->hasPages())
                    <div class="pagination-container">
                        <div class="d-flex justify-content-center">
                            <nav>
                                <ul class="pagination mb-0">
                                    {{-- Previous Page Link --}}
                                    @if ($businessCategories->onFirstPage())
                                        <li class="page-item disabled">
                                            <span class="page-link">&laquo;</span>
                                        </li>
                                    @else
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $businessCategories->previousPageUrl() }}"
                                                rel="prev">&laquo;</a>
                                        </li>
                                    @endif

                                    {{-- Pagination Elements --}}
                                    @foreach ($businessCategories->getUrlRange(1, $businessCategories->lastPage()) as $page => $url)
                                        @if ($page == $businessCategories->currentPage())
                                            <li class="page-item active">
                                                <span class="page-link">{{ $page }}</span>
                                            </li>
                                        @else
                                            <li class="page-item">
                                                <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                            </li>
                                        @endif
                                    @endforeach

                                    {{-- Next Page Link --}}
                                    @if ($businessCategories->hasMorePages())
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $businessCategories->nextPageUrl() }}"
                                                rel="next">&raquo;</a>
                                        </li>
                                    @else
                                        <li class="page-item disabled">
                                            <span class="page-link">&raquo;</span>
                                        </li>
                                    @endif
                                </ul>
                            </nav>
                        </div>
                        <div class="text-center mt-2 text-muted small">
                            Showing {{ $businessCategories->firstItem() ?? 0 }} to
                            {{ $businessCategories->lastItem() ?? 0 }} of {{ $businessCategories->total() }} entries
                        </div>
                    </div>
                @endif
            </div>
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
