@extends('admin.layouts.app')

@section('title', 'Customers Management')

@section('content')
    <div class="container-fluid px-4">
        <h1 class="mt-4">Customers Management</h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.users.customers') }}">Users</a></li>
            <li class="breadcrumb-item active">Customers</li>
        </ol>

        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-users me-1"></i>
                        All Customers
                    </div>
                    <a href="{{ route('admin.deletedCustomer.export') }}" class="btn btn-primary">
                        <i class="fas fa-download me-1"></i> Export
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <form action="{{ route('admin.users.customers') }}" method="GET" class="row g-3">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control"
                                placeholder="Search by name, email or mobile" value="{{ request('search') }}">
                        </div>
                        <!--<div class="col-md-3">
                                                                            <select name="status" class="form-select">
                                                                                <option value="">All Status</option>
                                                                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                                                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                                                            </select>
                                                                        </div>-->
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Sr No</th>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email/Mobile</th>
                                <th>Joined Date</th>
                                <th>Appointments</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customers as $index=>$customer)
                                <tr>
                                    <td>{{ $customers->firstItem() + $index }}</td>
                                    <td>{{ $customer->id }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if ($customer->profile_picture)
                                                <img src="{{ asset('storage/' . $customer->profile_picture) }}"
                                                    alt="{{ $customer->name }}" class="rounded-circle me-2" width="40"
                                                    height="40">
                                            @else
                                                <div class="bg-info rounded-circle me-2 d-flex align-items-center justify-content-center"
                                                    style="width: 40px; height: 40px; color: white;">
                                                    {{ strtoupper(substr($customer->name, 0, 1)) }}
                                                </div>
                                            @endif
                                            {{ $customer->name }}
                                        </div>
                                    </td>
                                    <td>
                                        {{ $customer->email }}<br>
                                        <small>{{ $customer->mobile }}</small>
                                    </td>
                                    <td>{{ $customer->created_at->format('M d, Y') }}</td>
                                    <td>
                                        @if (isset($customer->clientAppointments))
                                            {{ $customer->clientAppointments->count() }}
                                        @else
                                            0
                                        @endif
                                    </td>
                                    <td>
                                        <form action="{{ route('admin.users.toggle-status', $customer) }}" method="POST"
                                            class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                class="btn btn-sm btn-{{ $customer->status ? 'success' : 'danger' }}">
                                                {{ $customer->status ? 'Active' : 'Inactive' }}
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('admin.users.show', $customer) }}"
                                                class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.users.edit', $customer) }}"
                                                class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('admin.users.destroy', $customer) }}" method="POST"
                                                class="d-inline"
                                                onsubmit="return confirm('Are you sure you want to delete this customer?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No customers found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($customers->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            Showing {{ $customers->firstItem() ?? 0 }} to {{ $customers->lastItem() ?? 0 }} of
                            {{ $customers->total() }}
                            customers
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            {{ $customers->withQueryString()->onEachSide(5)->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
