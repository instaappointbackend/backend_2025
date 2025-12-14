@extends('admin.layouts.app')

@section('title', 'View Business Category')

@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.business-categories.index') }}">Business categories</a></li>
            <li class="breadcrumb-item active" aria-current="page">View</li>
        </ol>
    </nav>
@endsection

@section('page-actions')
    <div class="d-flex gap-2">
        <a href="{{ route('admin.business-categories.edit', $businessCategory->id) }}" class="btn btn-primary">
            <i class="fas fa-edit me-1"></i> Edit
        </a>
        <form action="{{ route('admin.business-categories.destroy', $businessCategory->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this business Category? This action cannot be undone.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">
                <i class="fas fa-trash me-1"></i> Delete
            </button>
        </form>
        <a href="{{ route('admin.business-categories.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Business categories
        </a>
    </div>
@endsection

@section('content')
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Business Category Details</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="d-flex align-items-center mb-4">
                        @if($businessCategory->image)
                            <div class="me-3">
                                <img src="{{ $businessCategory->image }}" alt="{{ $businessCategory->name }}" class="img-thumbnail" style="width: 80px; height: 80px; object-fit: contain;">
                            </div>
                        @endif
                        <div>
                            <h2 class="mb-1">{{ $businessCategory->name }}</h2>
                            <div class="text-muted">
                                <i class="fas fa-calendar-alt me-1"></i> Created:
                                @if($businessCategory->created_at)
                                    {{ $businessCategory->created_at->format('F d, Y') }}
                                @else
                                    <span>—</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($businessCategory->description)
                        <div class="mb-4">
                            <h6 class="fw-bold">Description</h6>
                            <div class="p-3 bg-light rounded">
                                {!! nl2br(e($businessCategory->description)) !!}
                            </div>
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-12 mb-4">
                            <div class="card">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Associated Vendors ({{ $businessCategory->users->count() }})</h6>
                                    <span class="badge bg-primary">{{ $businessCategory->users->count() }} total</span>
                                </div>
                                @if($businessCategory->users->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Created</th>
                                                <th></th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($businessCategory->users as $user)
                                                <tr>
                                                    <td>{{ $user->id }}</td>
                                                    <td>{{ $user->name }}</td>
                                                    <td>{{ $user->email }}</td>
                                                    <td>
                                                        @if($user->created_at)
                                                            {{ $user->created_at->format('M d, Y') }}
                                                        @else
                                                            <span class="text-muted">—</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if(Route::has('admin.users.show'))
                                                            <a href="{{ route('admin.users.show', $user->id) }}" class="btn btn-sm btn-info">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="card-footer bg-white text-center">
                                        @if(Route::has('admin.users.index'))
                                            <a href="{{ route('admin.users.index', ['business_category_id' => $businessCategory->id]) }}" class="text-decoration-none">
                                                View All Associated Vendors <i class="fas fa-arrow-right ms-1"></i>
                                            </a>
                                        @endif
                                    </div>
                                @else
                                    <div class="card-body text-center py-4">
                                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                        <p class="mb-0">No vendors associated with this business Category yet.</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="col-12 mb-4">
                            <div class="card">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">KYC Documents ({{ $businessCategory->kycDocuments->count() }})</h6>
                                    <span class="badge bg-primary">{{ $businessCategory->kycDocuments->count() }} total</span>
                                </div>
                                @if($businessCategory->kycDocuments->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Document Name</th>
                                                <th>Required</th>
                                                <th>Created</th>
                                                <th></th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($businessCategory->kycDocuments as $document)
                                                <tr>
                                                    <td>{{ $document->id }}</td>
                                                    <td>{{ $document->name }}</td>
                                                    <td>
                                                        @if($document->is_required)
                                                            <span class="badge bg-success">Required</span>
                                                        @else
                                                            <span class="badge bg-secondary">Optional</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($document->created_at)
                                                            {{ $document->created_at->format('M d, Y') }}
                                                        @else
                                                            <span class="text-muted">—</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if(Route::has('admin.kyc-documents.show'))
                                                            <a href="{{ route('admin.kyc-documents.show', $document->id) }}" class="btn btn-sm btn-info">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="card-footer bg-white text-center">
                                        @if(Route::has('admin.kyc-documents.index'))
                                            <a href="{{ route('admin.kyc-documents.index', ['business_category_id' => $businessCategory->id]) }}" class="text-decoration-none">
                                                View All KYC Documents <i class="fas fa-arrow-right ms-1"></i>
                                            </a>
                                        @endif
                                    </div>
                                @else
                                    <div class="card-body text-center py-4">
                                        <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                        <p class="mb-0">No KYC documents associated with this business Category yet.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Details</h6>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="text-muted">ID:</span>
                                    <span class="fw-medium">{{ $businessCategory->id }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="text-muted">Created:</span>
                                    <span class="fw-medium">
                                        @if($businessCategory->created_at)
                                            {{ $businessCategory->created_at->format('M d, Y H:i A') }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="text-muted">Last Updated:</span>
                                    <span class="fw-medium">
                                        @if($businessCategory->updated_at)
                                            {{ $businessCategory->updated_at->format('M d, Y H:i A') }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="text-muted">Total Vendors:</span>
                                    <span class="fw-medium">{{ $businessCategory->users->count() }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span class="text-muted">KYC Documents:</span>
                                    <span class="fw-medium">{{ $businessCategory->kycDocuments->count() }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    @if($businessCategory->image)
                        <div class="card mt-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Image</h6>
                            </div>
                            <div class="card-body text-center">
                                <img src="{{ $businessCategory->image }}" alt="{{ $businessCategory->name }}" class="img-fluid rounded" style="max-height: 200px;">
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
