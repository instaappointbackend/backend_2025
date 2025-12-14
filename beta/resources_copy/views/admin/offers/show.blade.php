@extends('admin.layouts.app')

@section('title', 'View Global Offer')

@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.offers.index') }}">Global Offers</a></li>
            <li class="breadcrumb-item active" aria-current="page">View</li>
        </ol>
    </nav>
@endsection

@section('page-actions')
    <div class="d-flex gap-2">
        <a href="{{ route('admin.offers.edit', $offer->id) }}" class="btn btn-primary">
            <i class="fas fa-edit me-1"></i> Edit
        </a>
        <form action="{{ route('admin.offers.toggle-status', $offer->id) }}" method="POST" class="d-inline">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn {{ $offer->is_active ? 'btn-warning' : 'btn-success' }}">
                <i class="fas {{ $offer->is_active ? 'fa-toggle-off' : 'fa-toggle-on' }} me-1"></i>
                {{ $offer->is_active ? 'Deactivate' : 'Activate' }}
            </button>
        </form>
        <form action="{{ route('admin.offers.destroy', $offer->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this offer?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">
                <i class="fas fa-trash me-1"></i> Delete
            </button>
        </form>
        <a href="{{ route('admin.offers.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Offers
        </a>
    </div>
@endsection

@section('content')
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Global Offer Details</h5>
            <span class="badge {{ $offer->is_active ? 'bg-success' : 'bg-secondary' }}">
                {{ $offer->is_active ? 'Active' : 'Inactive' }}
            </span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-4">
                        <h4 class="text-primary mb-3">Offer Information</h4>
                        <div class="bg-light p-4 rounded">
                            <h3 class="mb-3">{{ $offer->title }}</h3>
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge bg-dark me-2">{{ $offer->coupon_code }}</span>
                                <span class="badge bg-primary">{{ $offer->discount_percentage }}% OFF</span>
                            </div>
                            <p class="mb-4">{{ $offer->description }}</p>

                            <div class="d-flex flex-wrap gap-4 mb-3">
                                <div>
                                    <h6 class="text-muted mb-2">Validity Period</h6>
                                    <p class="mb-0 d-flex align-items-center">
                                        <i class="fas fa-calendar-alt me-2 text-muted"></i>
                                        {{ $offer->start_date->format('M d, Y') }} - {{ $offer->end_date->format('M d, Y') }}
                                    </p>
                                </div>

                                <div>
                                    <h6 class="text-muted mb-2">Usage</h6>
                                    <p class="mb-0 d-flex align-items-center">
                                        <i class="fas fa-chart-bar me-2 text-muted"></i>
                                        {{ $offer->used_count }} uses
                                        @if($offer->usage_limit)
                                            <span class="ms-1 text-muted">(Limit: {{ $offer->usage_limit }})</span>
                                        @else
                                            <span class="ms-1 text-muted">(No limit)</span>
                                        @endif
                                    </p>
                                </div>
                            </div>

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

                                $isLimited = $offer->usage_limit !== null;
                                $isLimitReached = $isLimited && $offer->used_count >= $offer->usage_limit;
                            @endphp

                            <div class="alert {{ $offer->isValid() ? 'alert-success' : 'alert-warning' }} mt-3">
                                <div class="d-flex align-items-center">
                                    <i class="fas {{ $offer->isValid() ? 'fa-check-circle' : 'fa-exclamation-triangle' }} me-2"></i>
                                    <div>
                                        <strong>Offer Status:</strong>
                                        @if(!$offer->is_active)
                                            This offer is currently inactive.
                                        @elseif($today < $offer->start_date)
                                            This offer will be active starting {{ $offer->start_date->format('M d, Y') }}.
                                        @elseif($today > $offer->end_date)
                                            This offer expired on {{ $offer->end_date->format('M d, Y') }}.
                                        @elseif($isLimitReached)
                                            This offer has reached its usage limit.
                                        @else
                                            This offer is currently active and available for use.
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Details</h5>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="text-muted">ID:</span>
                                    <span class="fw-medium">{{ $offer->id }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="text-muted">Status:</span>
                                    <div>
                                        @if($offer->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                        <span class="badge {{ $statusClass }} ms-1">{{ $status }}</span>
                                    </div>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="text-muted">Type:</span>
                                    <span class="fw-medium">Admin (Global)</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="text-muted">Discount:</span>
                                    <span class="fw-medium">{{ $offer->discount_percentage }}%</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="text-muted">Coupon Code:</span>
                                    <span class="fw-medium">{{ $offer->coupon_code }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="text-muted">Start Date:</span>
                                    <span class="fw-medium">{{ $offer->start_date->format('M d, Y') }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="text-muted">End Date:</span>
                                    <span class="fw-medium">{{ $offer->end_date->format('M d, Y') }}</span>
                                </li>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Usage:</span>
                                        <span class="fw-medium">
                                            {{ $offer->used_count }}
                                            @if($offer->usage_limit)
                                                / {{ $offer->usage_limit }}
                                            @endif
                                        </span>
                                    </div>
                                    @if($offer->usage_limit)
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar {{ $isLimitReached ? 'bg-danger' : 'bg-primary' }}" style="width: {{ min(100, ($offer->used_count / $offer->usage_limit) * 100) }}%"></div>
                                        </div>
                                    @endif
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="text-muted">Created:</span>
                                    <span class="fw-medium">{{ $offer->created_at->format('M d, Y H:i A') }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="text-muted">Last Updated:</span>
                                    <span class="fw-medium">{{ $offer->updated_at->format('M d, Y H:i A') }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Customer Preview</h5>
                        </div>
                        <div class="card-body">
                            <div class="p-4 border rounded position-relative" style="background-color: #f9f9f9;">
                                @if(!$offer->isValid())
                                    <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background-color: rgba(0,0,0,0.1);">
                                        <div class="badge bg-danger p-2">NOT AVAILABLE</div>
                                    </div>
                                @endif
                                <div class="row">
                                    <div class="col-md-8">
                                        <h4 class="text-primary">{{ $offer->title }}</h4>
                                        <p>{{ $offer->description }}</p>
                                        <div class="d-flex align-items-center mt-3">
                                            <div class="me-3">
                                                <span class="d-block text-muted small">Use code:</span>
                                                <span class="badge bg-dark p-2">{{ $offer->coupon_code }}</span>
                                            </div>
                                            <div>
                                                <span class="d-block text-muted small">Valid until:</span>
                                                <span class="text-danger fw-medium">{{ $offer->end_date->format('M d, Y') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-center justify-content-center">
                                        <div class="text-center">
                                            <div class="display-4 text-danger fw-bold">{{ $offer->discount_percentage }}%</div>
                                            <div class="fw-medium">OFF</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-center mt-3 text-muted">
                                <small>This is how the offer will appear to customers</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection