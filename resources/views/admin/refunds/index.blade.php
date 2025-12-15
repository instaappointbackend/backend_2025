@extends('admin.layouts.app')

@section('title', 'Refund Management')

@section('content')
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Refund Management</h1>
            <div>
                <a href="{{ route('admin.refunds.export') }}" class="btn btn-sm btn-success shadow-sm">
                    <i class="fas fa-download fa-sm text-white-50"></i> Export Data
                </a>
            </div>
        </div>

        <!-- Filters Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Filters</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.refunds.index') }}" class="row">
                    <!-- Status Filter -->
                    <div class="col-md-3 mb-3">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="processed" {{ request('status') == 'processed' ? 'selected' : '' }}>Processed
                            </option>
                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled
                            </option>
                        </select>
                    </div>

                    <!-- Type Filter -->
                    <div class="col-md-3 mb-3">
                        <label for="type">Refund Type</label>
                        <select id="type" name="type" class="form-control">
                            <option value="">All Types</option>
                            <option value="full_refund" {{ request('type') == 'full_refund' ? 'selected' : '' }}>Full Refund
                            </option>
                            <option value="partial_refund" {{ request('type') == 'partial_refund' ? 'selected' : '' }}>
                                Partial Refund</option>
                            <option value="no_refund" {{ request('type') == 'no_refund' ? 'selected' : '' }}>No Refund
                            </option>
                        </select>
                    </div>

                    <!-- Date Range Filters -->
                    <div class="col-md-3 mb-3">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" class="form-control"
                            value="{{ request('start_date') }}">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" class="form-control"
                            value="{{ request('end_date') }}">
                    </div>

                    <!-- Search Filter -->
                    <div class="col-md-6 mb-3">
                        <label for="search">Search (Customer/Provider/Reference)</label>
                        <input type="text" id="search" name="search" class="form-control" placeholder="Search..."
                            value="{{ request('search') }}">
                    </div>

                    <!-- Filter Button -->
                    <div class="col-md-3 mb-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a href="{{ route('admin.refunds.index') }}" class="btn btn-secondary ml-2">
                            <i class="fas fa-sync"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Refunds Table -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Refunds List</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="refundsTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Reference</th>
                                <th>Customer</th>
                                <th>Provider</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($refunds as $refund)
                                <tr>
                                    <td>{{ $refund->id }}</td>
                                    <td>
                                        <span class="badge badge-info">{{ $refund->refund_reference }}</span>
                                    </td>
                                    <td>
                                        @if ($refund->user)
                                            <div class="d-flex align-items-center">
                                                <div class="mr-2">
                                                    <img src="{{ $refund->user->profile_picture ? asset('storage/' . $refund->user->profile_picture) : asset('admin/images/default-avatar.png') }}"
                                                        alt="{{ $refund->user->name }}" class="avatar-img" width="30"
                                                        height="30">
                                                </div>
                                                <div>
                                                    <div class="small font-weight-bold">{{ $refund->user->name }}</div>
                                                    <div class="small text-gray-500">{{ $refund->user->email }}</div>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($refund->provider)
                                            <div class="d-flex align-items-center">
                                                <div class="mr-2">
                                                    <img src="{{ $refund->provider->profile_picture ? asset('storage/' . $refund->provider->profile_picture) : asset('admin/images/default-avatar.png') }}"
                                                        alt="{{ $refund->provider->name }}" class="avatar-img"
                                                        width="30" height="30">
                                                </div>
                                                <div>
                                                    <div class="small font-weight-bold">{{ $refund->provider->name }}</div>
                                                    <div class="small text-gray-500">{{ $refund->provider->email }}</div>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($refund->refund_type == 'full_refund')
                                            <span class="badge badge-success">Full Refund</span>
                                        @elseif($refund->refund_type == 'partial_refund')
                                            <span class="badge badge-warning">Partial Refund</span>
                                        @elseif($refund->refund_type == 'no_refund')
                                            <span class="badge badge-danger">No Refund</span>
                                        @else
                                            <span class="badge badge-secondary">{{ $refund->refund_type }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="font-weight-bold text-success">
                                            ₹{{ number_format($refund->refund_amount, 2) }}</div>
                                        <div class="small text-gray-500">
                                            Vendor: ₹{{ number_format($refund->vendor_amount, 2) }}<br>
                                            Admin: ₹{{ number_format($refund->admin_amount, 2) }}
                                        </div>
                                    </td>
                                    <td>
                                        @if ($refund->refund_status == 'pending')
                                            <span class="badge badge-warning">Pending</span>
                                        @elseif($refund->refund_status == 'processed')
                                            <span class="badge badge-success">Processed</span>
                                        @elseif($refund->refund_status == 'failed')
                                            <span class="badge badge-danger">Failed</span>
                                        @elseif($refund->refund_status == 'cancelled')
                                            <span class="badge badge-secondary">Cancelled</span>
                                        @endif
                                    </td>
                                    <td>{{ $refund->created_at->format('M d, Y H:i') }}</td>
                                    <td>
                                        <div class="d-flex">
                                            <a href="{{ route('admin.refunds.show', $refund->id) }}"
                                                class="btn btn-sm btn-info mr-1" data-toggle="tooltip"
                                                title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            @if ($refund->refund_status == 'pending')
                                                <form action="{{ route('admin.refunds.process', $refund->id) }}"
                                                    method="POST" class="d-inline"
                                                    onsubmit="return confirm('Are you sure you want to process this refund?')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success mr-1"
                                                        data-toggle="tooltip" title="Process Refund">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            <a href="{{ route('admin.refunds.receipt', $refund->id) }}"
                                                class="btn btn-sm btn-primary" data-toggle="tooltip"
                                                title="Download Receipt" target="_blank">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center">No refunds found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $refunds->onEachSide(5)->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Initialize tooltips
        $(function() {
            $('[data-toggle="tooltip"]').tooltip()
        })
    </script>
@endsection
