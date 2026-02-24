@extends('admin.layouts.app')

@section('title', 'Global Offers Management')

@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Global Offers</li>
        </ol>
    </nav>
@endsection

@section('page-actions')
    <a href="{{ route('admin.offers.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Add New Offer
    </a>
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Global Offers</h5>
            <div class="search-filter">
                <form action="{{ route('admin.offers.index') }}" method="GET" class="d-flex gap-2">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Search offers..." name="search"
                            value="{{ request('search') }}">
                        <button class="btn btn-outline-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <select class="form-select" name="status" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    <select class="form-select" name="validity" onchange="this.form.submit()">
                        <option value="">All Validity</option>
                        <option value="current" {{ request('validity') == 'current' ? 'selected' : '' }}>Current</option>
                        <option value="upcoming" {{ request('validity') == 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                        <option value="expired" {{ request('validity') == 'expired' ? 'selected' : '' }}>Expired</option>
                    </select>
                    @if (request('search') || request('status') || request('validity'))
                        <a href="{{ route('admin.offers.index') }}" class="btn btn-outline-secondary">
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
                            <th width="5%">ID</th>
                            <th width="20%">Title</th>
                            <th width="15%">Coupon Code</th>
                            <th width="10%">Discount</th>
                            <th width="15%">Validity</th>
                            <th width="10%">Status</th>
                            <th width="10%">Usage</th>
                            <th width="15%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($adminOffers as $offer)
                            <tr>
                                <td>{{ $offer->id }}</td>
                                <td>
                                    <a href="{{ route('admin.offers.show', $offer->id) }}"
                                        class="fw-bold text-decoration-none">
                                        {{ Str::limit($offer->title, 40) }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-dark">{{ $offer->coupon_code }}</span>
                                </td>
                                <td>
                                    @if ($offer->discount_type === 'percentage')
                                        <span class="badge bg-primary">
                                            {{ number_format($offer->discount_percentage, 2) }}%
                                        </span>
                                    @elseif ($offer->discount_type === 'fixed')
                                        <span class="badge bg-success">
                                            Rs {{ number_format($offer->discount_fixed, 2) }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <small>
                                        {{ $offer->start_date->format('M d, Y') }} -
                                        {{ $offer->end_date->format('M d, Y') }}
                                    </small>
                                    @php
                                        $today = now();
                                        if ($today < $offer->start_date) {
                                            $status = 'Upcoming';
                                            $statusClass = 'bg-info';
                                        } elseif ($today > $offer->end_date) {
                                            $status = 'Expired';
                                            $statusClass = 'bg-secondary';
                                        } else {
                                            $status = 'Current';
                                            $statusClass = 'bg-success';
                                        }
                                    @endphp
                                    <br>
                                    <span class="badge {{ $statusClass }}">{{ $status }}</span>
                                </td>
                                <td>
                                    @if ($offer->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($offer->usage_limit)
                                        {{ $offer->used_count }}/{{ $offer->usage_limit }}
                                        <div class="progress mt-1" style="height: 5px;">
                                            <div class="progress-bar bg-primary"
                                                style="width: {{ ($offer->used_count / $offer->usage_limit) * 100 }}%">
                                            </div>
                                        </div>
                                    @else
                                        {{ $offer->used_count }}/∞
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('admin.offers.show', $offer->id) }}" class="btn btn-sm btn-info"
                                            data-bs-toggle="tooltip" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.offers.edit', $offer->id) }}"
                                            class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if (!$offer->new_user_only)
                                            <form action="{{ route('admin.offers.destroy', $offer->id) }}" method="POST"
                                                class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger"
                                                    data-bs-toggle="tooltip" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif

                                        {{-- @if (!$offer->new_user_only) --}}
                                        <form action="{{ route('admin.offers.toggle-status', $offer->id) }}" method="POST"
                                            class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                class="btn btn-sm {{ $offer->is_active ? 'btn-warning' : 'btn-success' }}"
                                                data-bs-toggle="tooltip"
                                                title="{{ $offer->is_active ? 'Deactivate' : 'Activate' }}">
                                                <i
                                                    class="fas {{ $offer->is_active ? 'fa-toggle-off' : 'fa-toggle-on' }}"></i>
                                            </button>
                                        </form>
                                        {{-- @endif --}}
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="fas fa-tag fa-3x text-muted mb-3"></i>
                                        <p class="mb-1">No global offers found</p>
                                        @if (request('search') || request('status') || request('validity'))
                                            <a href="{{ route('admin.offers.index') }}"
                                                class="btn btn-sm btn-outline-secondary mt-2">
                                                Clear filters
                                            </a>
                                        @else
                                            <a href="{{ route('admin.offers.create') }}"
                                                class="btn btn-sm btn-primary mt-2">
                                                <i class="fas fa-plus me-1"></i> Add New Offer
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
                @if ($adminOffers->hasPages())
                    <div class="pagination-container">
                        <div class="d-flex justify-content-center">
                            <nav>
                                <ul class="pagination mb-0">
                                    {{-- Previous Page Link --}}
                                    @if ($adminOffers->onFirstPage())
                                        <li class="page-item disabled">
                                            <span class="page-link">&laquo;</span>
                                        </li>
                                    @else
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $adminOffers->previousPageUrl() }}"
                                                rel="prev">&laquo;</a>
                                        </li>
                                    @endif

                                    {{-- Pagination Elements --}}
                                    @foreach ($adminOffers->getUrlRange(1, $adminOffers->lastPage()) as $page => $url)
                                        @if ($page == $adminOffers->currentPage())
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
                                    @if ($adminOffers->hasMorePages())
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $adminOffers->nextPageUrl() }}"
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
                            Showing {{ $adminOffers->firstItem() ?? 0 }} to {{ $adminOffers->lastItem() ?? 0 }} of
                            {{ $adminOffers->total() }} entries
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
