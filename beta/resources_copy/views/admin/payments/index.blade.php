@extends('admin.layouts.app')

@section('title', 'Manage Payments')

@section('page-title', 'Payments Management')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb m-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Payments</li>
    </ol>
</nav>
@endsection

@section('page-actions')
<a href="{{ route('admin.payments.reports') }}" class="btn btn-info me-2">
    <i class="fas fa-chart-line me-1"></i> Reports
</a>
<a href="{{ route('admin.payments.export') }}" class="btn btn-primary">
    <i class="fas fa-download me-1"></i> Export
</a>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">All Payments</h5>

        <div class="search-filter">
            <form action="{{ route('admin.payments.index') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-md-2">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="all">All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                        <option value="refunded" {{ request('status') == 'refunded' ? 'selected' : '' }}>Refunded</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <input type="date" name="start_date" class="form-control" placeholder="Start Date" value="{{ request('start_date') }}">
                </div>

                <div class="col-md-2">
                    <input type="date" name="end_date" class="form-control" placeholder="End Date" value="{{ request('end_date') }}">
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>

                <div class="col-md-1">
                    <a href="{{ route('admin.payments.index') }}" class="btn btn-secondary w-100">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card-body">
        <!-- Advanced Filters (Collapsible) -->
        <div class="mb-3">
            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#advancedFilters" aria-expanded="false" aria-controls="advancedFilters">
                <i class="fas fa-filter me-1"></i> Advanced Filters
            </button>

            <div class="collapse mt-3" id="advancedFilters">
                <div class="card card-body">
                    <form action="{{ route('admin.payments.index') }}" method="GET" class="row g-3">
                        <!-- Pass current search parameters -->
                        @if(request('search'))
                        <input type="hidden" name="search" value="{{ request('search') }}">
                        @endif
                        @if(request('status') && request('status') != 'all')
                        <input type="hidden" name="status" value="{{ request('status') }}">
                        @endif
                        @if(request('start_date'))
                        <input type="hidden" name="start_date" value="{{ request('start_date') }}">
                        @endif
                        @if(request('end_date'))
                        <input type="hidden" name="end_date" value="{{ request('end_date') }}">
                        @endif

                        <div class="col-md-4">
                            <label for="payment_method" class="form-label">Payment Method</label>
                            <select name="payment_method" id="payment_method" class="form-select">
                                <option value="all">All Methods</option>
                                @foreach($paymentMethods as $method)
                                <option value="{{ $method }}" {{ request('payment_method') == $method ? 'selected' : '' }}>
                                {{ ucfirst($method) }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="provider_id" class="form-label">Provider</label>
                            <select name="provider_id" id="provider_id" class="form-select">
                                <option value="">All Providers</option>
                                @foreach($providers as $provider)
                                <option value="{{ $provider->id }}" {{ request('provider_id') == $provider->id ? 'selected' : '' }}>
                                {{ $provider->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="user_id" class="form-label">Customer</label>
                            <select name="user_id" id="user_id" class="form-select">
                                <option value="">All Customers</option>
                                @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ request('user_id') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Amount Range</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" name="min_amount" class="form-control" placeholder="Min" value="{{ request('min_amount') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" name="max_amount" class="form-control" placeholder="Max" value="{{ request('max_amount') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Transaction ID</th>
                    <th>Customer</th>
                    <th>Provider</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($payments as $payment)
                <tr>
                    <td>{{ $payment->id }}</td>
                    <td>
                        <span class="small text-muted">{{ $payment->transaction_id }}</span>
                    </td>
                    <td>
                        @if($payment->user)
                        <div class="d-flex align-items-center">
                            <div class="me-2">
                                <img src="{{ $payment->user->profile_picture ? asset('storage/' . $payment->user->profile_picture) : asset('admin/images/default-avatar.png') }}" alt="{{ $payment->user->name }}" class="avatar-img" width="30" height="30">
                            </div>
                            <div>{{ $payment->user->name }}</div>
                        </div>
                        @else
                        <span class="text-muted">N/A</span>
                        @endif
                    </td>
                    <td>
                        @if($payment->provider)
                        <div class="d-flex align-items-center">
                            <div class="me-2">
                                <img src="{{ $payment->provider->profile_picture ? asset('storage/' . $payment->provider->profile_picture) : asset('admin/images/default-avatar.png') }}" alt="{{ $payment->provider->name }}" class="avatar-img" width="30" height="30">
                            </div>
                            <div>{{ $payment->provider->name }}</div>
                        </div>
                        @else
                        <span class="text-muted">N/A</span>
                        @endif
                    </td>
                    <td>
                        <strong>{{ $payment->formatted_amount }}</strong>
                    </td>
                    <td>
                        {{ $payment->getPaymentMethodDisplayAttribute() }}
                    </td>
                    <td>
                        @if($payment->status == 'pending')
                        <span class="badge bg-warning">Pending</span>
                        @elseif($payment->status == 'paid')
                        <span class="badge bg-success">Paid</span>
                        @elseif($payment->status == 'failed')
                        <span class="badge bg-danger">Failed</span>
                        @elseif($payment->status == 'refunded')
                        <span class="badge bg-info">Refunded</span>
                        @endif
                    </td>
                    <td>{{ $payment->created_at->format('M d, Y H:i') }}</td>
                    <td>
                        <div class="d-flex">
                            <a href="{{ route('admin.payments.show', $payment->id) }}" class="btn btn-sm btn-info me-1" data-bs-toggle="tooltip" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>

                            <a href="{{ route('admin.payments.edit', $payment->id) }}" class="btn btn-sm btn-primary me-1" data-bs-toggle="tooltip" title="Edit Payment">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center">No payments found.</td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-end mt-3">
            {{ $payments->links() }}
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
</script>
@endsection
