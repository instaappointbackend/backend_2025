<!-- resources/views/admin/users/vendors.blade.php -->
@extends('admin.layouts.app')

@section('title', 'Manage Vendors')

@section('page-title', 'Vendors Management')

@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Vendors</li>
        </ol>
    </nav>
@endsection

@section('page-actions')
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
        <i class="fas fa-plus-circle me-1"></i> Add New Vendor
    </a>

    <a href="{{ route('admin.vendor.export') }}" class="btn btn-primary">
        <i class="fas fa-download me-1"></i> Export
    </a>
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Vendors</h5>

            <div class="search-filter">
                <form action="{{ route('admin.users.vendors') }}" method="GET" class="d-flex gap-2">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search vendors..."
                            value="{{ request('search') }}">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>

                    <select name="kyc_status" class="form-select" onchange="this.form.submit()">
                        <option value="">-- KYC Status --</option>
                        <option value="verified" {{ request('kyc_status') == 'verified' ? 'selected' : '' }}>Verified
                        </option>
                        <option value="pending" {{ request('kyc_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    </select>

                    <select name="status" class="form-select" onchange="this.form.submit()">

                        <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </form>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Sr No</th>
                            <th>ID</th>
                            <th>Category</th>
                            <th>Vendor</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>KYC Status</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vendors as $index=>$vendor)
                            <tr>
                                <td>{{ $vendors->firstItem() + $index }}</td>
                                <td>{{ $vendor->id }}</td>
                                <td>{{ $vendor?->businessCategory?->name }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <img src="{{ $vendor->profile_picture ? asset('storage/' . $vendor->profile_picture) : asset('admin/images/default-avatar.png') }}"
                                                alt="{{ $vendor->name }}" class="avatar-img" width="40" height="40">
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ $vendor->name }}</h6>
                                            @if (isset($vendor->businessDetail) && $vendor->businessDetail)
                                                <small
                                                    class="text-muted">{{ $vendor->businessDetail->business_name }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $vendor->email }}</td>
                                <td>{{ $vendor->mobile }}</td>
                                <td>
                                    @if ($vendor->is_kyc_completed)
                                        <span class="badge bg-success">Verified</span>
                                    @else
                                        <span class="badge bg-warning">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($vendor->status)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </td>
                                <td>{{ $vendor->created_at->format('M d, Y') }}</td>
                                <td>
                                    <div class="d-flex">
                                        <a href="{{ route('admin.users.show', $vendor->id) }}"
                                            class="btn btn-sm btn-info me-1" data-bs-toggle="tooltip" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.users.edit', $vendor->id) }}"
                                            class="btn btn-sm btn-primary me-1" data-bs-toggle="tooltip"
                                            title="Edit Vendor">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.users.destroy', $vendor->id) }}" method="POST"
                                            class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger"
                                                data-confirm="Are you sure you want to delete this vendor?"
                                                data-bs-toggle="tooltip" title="Delete Vendor">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        <a target="blank"
                                            href="{{ route('admin.services.index', ['vendor_id' => $vendor->id]) }}"
                                            class="btn btn-sm btn-primary me-1" data-bs-toggle="tooltip" title="Services">
                                            <i class="fas fa-concierge-bell"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No vendors found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>


            @if ($vendors->hasPages())
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        Showing {{ $vendors->firstItem() ?? 0 }} to {{ $vendors->lastItem() ?? 0 }} of
                        {{ $vendors->total() }}
                        users
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        {{ $vendors->onEachSide(5)->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
