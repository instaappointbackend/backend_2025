<!-- resources/views/admin/reports/users.blade.php -->
@extends('admin.layouts.app')

@section('title', 'User Reports')

@section('page-title', 'User Reports')

@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">Reports</a></li>
            <li class="breadcrumb-item active" aria-current="page">Users</li>
        </ol>
    </nav>
@endsection

@section('page-actions')
    <a href="{{ route('admin.reports.users.export') }}?start_date={{ request('start_date') }}&end_date={{ request('end_date') }}&role={{ request('role') }}"
        class="btn btn-success me-2">
        <i class="fas fa-file-excel me-1"></i> Export to CSV
    </a>
@endsection

@section('content')
    <!-- Date Range Filter -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-1"></i>
            Filter Users
        </div>
        <div class="card-body">
            <form action="{{ route('admin.reports.users') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date"
                        value="{{ request('start_date', $startDate->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date"
                        value="{{ request('end_date', $endDate->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-select" id="role" name="role">
                        <option value="">All Roles</option>
                        <option value="vendor" {{ request('role') == 'vendor' ? 'selected' : '' }}>Vendors</option>
                        <option value="customer" {{ request('role') == 'customer' ? 'selected' : '' }}>Customers</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="h5 mb-0 font-weight-bold">{{ $totalUsers }}</div>
                            <div>Total Users</div>
                        </div>
                        <div>
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="h5 mb-0 font-weight-bold">{{ $vendorsCount }}</div>
                            <div>Vendors</div>
                        </div>
                        <div>
                            <i class="fas fa-store fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="h5 mb-0 font-weight-bold">{{ $customersCount }}</div>
                            <div>Customers</div>
                        </div>
                        <div>
                            <i class="fas fa-user-friends fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="h5 mb-0 font-weight-bold">{{ $kycCompletedCount }}</div>
                            <div>KYC Completed</div>
                        </div>
                        <div>
                            <i class="fas fa-id-card fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- User Registration Chart -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-line me-1"></i>
                    User Registration Trends
                </div>
                <div class="card-body">
                    <canvas id="userChart" height="80"></canvas>
                </div>
            </div>
        </div>

        <!-- Business Categories -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-store me-1"></i>
                    Top Business Categories
                </div>
                <div class="card-body">
                    <canvas id="businessCategoryChart" height="180"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            User Data
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Role</th>
                            <th>KYC Status</th>
                            <th>Status</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <img src="{{ $user->profile_picture ? asset('storage/' . $user->profile_picture) : asset('admin/images/default-avatar.png') }}"
                                                alt="{{ $user->name }}" class="avatar-img" width="30" height="30">
                                        </div>
                                        <div>{{ $user->name }}</div>
                                    </div>
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->mobile }}</td>
                                <td>{{ ucfirst($user->role) }}</td>
                                <td>
                                    @if ($user->is_kyc_completed)
                                        <span class="badge bg-success">Completed</span>
                                    @else
                                        <span class="badge bg-warning">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($user->status)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </td>
                                <td>{{ $user->created_at->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No users found in the selected date range.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- <div class="d-flex justify-content-end mt-3">
                {{ $users->appends(request()->except('page'))->links() }}
            </div> --}}
            @if ($users->hasPages())
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        Showing {{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }} of
                        {{ $users->total() }}
                        users
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        {{ $users->onEachSide(5)->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // User Registration Chart Data
        var userData = @json($userStats);

        // Parse the data for Chart.js
        var labels = userData.map(function(item) {
            return item.date;
        });

        var totalData = userData.map(function(item) {
            return item.total;
        });

        var vendorData = userData.map(function(item) {
            return item.vendors;
        });

        var customerData = userData.map(function(item) {
            return item.customers;
        });

        // Create the user registration chart
        var ctx = document.getElementById('userChart').getContext('2d');
        var userChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                        label: 'Total Users',
                        data: totalData,
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Vendors',
                        data: vendorData,
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Customers',
                        data: customerData,
                        backgroundColor: 'rgba(23, 162, 184, 0.1)',
                        borderColor: 'rgba(23, 162, 184, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'User Registration Trends',
                        font: {
                            size: 16
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    },
                    legend: {
                        position: 'top'
                    }
                },
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Business Categories Chart
        var businessCategories = @json($businessCategories);

        // Parse the data for Chart.js
        var categoryLabels = businessCategories.map(function(item) {
            return item.name;
        });

        var categoryData = businessCategories.map(function(item) {
            return item.total;
        });

        // Create colors array for pie chart
        var colors = [
            'rgba(0, 123, 255, 0.7)',
            'rgba(40, 167, 69, 0.7)',
            'rgba(23, 162, 184, 0.7)',
            'rgba(255, 193, 7, 0.7)',
            'rgba(220, 53, 69, 0.7)'
        ];

        // Create the business categories pie chart
        var ctxPie = document.getElementById('businessCategoryChart').getContext('2d');
        var businessCategoryChart = new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels: categoryLabels,
                datasets: [{
                    data: categoryData,
                    backgroundColor: colors,
                    borderWidth: 1
                }]
            },
            options: {
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var label = context.label || '';
                                var value = context.raw || 0;
                                var total = context.dataset.data.reduce((a, b) => a + b, 0);
                                var percentage = Math.round((value / total) * 100);
                                return label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    }
                },
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
@endsection
