<!-- resources/views/admin/reports/index.blade.php -->
@extends('admin.layouts.app')

@section('title', 'Admin Reports Dashboard')

@section('page-title', 'Reports Dashboard')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb m-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Reports</li>
    </ol>
</nav>
@endsection

@section('page-actions')
<div class="dropdown">
    <button class="btn btn-primary dropdown-toggle" type="button" id="reportsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fas fa-chart-bar me-1"></i> View Reports
    </button>
    <ul class="dropdown-menu" aria-labelledby="reportsDropdown">
        <li><a class="dropdown-item" href="{{ route('admin.reports.appointments') }}">Appointments</a></li>
        <li><a class="dropdown-item" href="{{ route('admin.reports.users') }}">Users</a></li>
        <li><a class="dropdown-item" href="{{ route('admin.reports.revenue') }}">Revenue</a></li>
        <li><a class="dropdown-item" href="{{ route('admin.reports.payouts') }}">Payouts</a></li>
    </ul>
</div>
@endsection

@section('content')
<!-- Overview Cards -->
<div class="row">
    <div class="col-md-3">
        <div class="card bg-primary text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">₹{{ number_format($totalRevenue, 2) }}</h5>
                        <div class="small">Total Revenue</div>
                    </div>
                    <div class="fa-3x">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-white stretched-link" href="{{ route('admin.reports.revenue') }}">View Revenue Reports</a>
                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">{{ number_format($totalAppointments) }}</h5>
                        <div class="small">Total Appointments</div>
                    </div>
                    <div class="fa-3x">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-white stretched-link" href="{{ route('admin.reports.appointments') }}">View Appointment Reports</a>
                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">{{ number_format($totalUsers) }}</h5>
                        <div class="small">Total Users</div>
                    </div>
                    <div class="fa-3x">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-white stretched-link" href="{{ route('admin.reports.users') }}">View User Reports</a>
                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">{{ number_format($totalPayouts) }}</h5>
                        <div class="small">Total Payout Requests</div>
                    </div>
                    <div class="fa-3x">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-white stretched-link" href="{{ route('admin.reports.payouts') }}">View Payout Reports</a>
                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Area -->
<div class="row">
    <!-- Recent Appointments -->
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-calendar-alt me-1"></i>
                    Recent Appointments
                </div>
                <a href="{{ route('admin.reports.appointments') }}" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Vendor</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($recentAppointments as $appointment)
                        <tr>
                            <td>{{ $appointment->id }}</td>
                            <td>{{ $appointment->client->name ?? 'N/A' }}</td>
                            <td>{{ $appointment->user->name ?? 'N/A' }}</td>
                            <td>{{ $appointment->date ? $appointment->date->format('M d, Y') : 'N/A' }}</td>
                            <td>
                                @if($appointment->status == 'pending')
                                <span class="badge bg-warning">Pending</span>
                                @elseif($appointment->status == 'confirmed')
                                <span class="badge bg-primary">Confirmed</span>
                                @elseif($appointment->status == 'completed')
                                <span class="badge bg-success">Completed</span>
                                @elseif($appointment->status == 'cancelled')
                                <span class="badge bg-danger">Cancelled</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">No recent appointments found.</td>
                        </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Payouts -->
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-money-bill-wave me-1"></i>
                    Recent Payout Requests
                </div>
                <a href="{{ route('admin.reports.payouts') }}" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Vendor</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($recentPayouts as $payout)
                        <tr>
                            <td>{{ $payout->id }}</td>
                            <td>{{ $payout->user->name ?? 'N/A' }}</td>
                            <td>₹{{ number_format($payout->amount, 2) }}</td>
                            <td>
                                @if($payout->status == 'pending')
                                <span class="badge bg-warning">Pending</span>
                                @elseif($payout->status == 'processing')
                                <span class="badge bg-info">Processing</span>
                                @elseif($payout->status == 'completed')
                                <span class="badge bg-success">Completed</span>
                                @elseif($payout->status == 'rejected')
                                <span class="badge bg-danger">Rejected</span>
                                @elseif($payout->status == 'cancelled')
                                <span class="badge bg-secondary">Cancelled</span>
                                @endif
                            </td>
                            <td>{{ $payout->created_at->format('M d, Y') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">No recent payout requests found.</td>
                        </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Links -->
<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-link me-1"></i>
                Quick Access Reports
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3 mb-3">
                        <a href="{{ route('admin.reports.appointments') }}" class="btn btn-lg btn-outline-primary w-100 py-4">
                            <i class="fas fa-calendar-alt fa-2x mb-2"></i><br>
                            Appointment Reports
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="{{ route('admin.reports.users') }}" class="btn btn-lg btn-outline-info w-100 py-4">
                            <i class="fas fa-users fa-2x mb-2"></i><br>
                            User Reports
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="{{ route('admin.reports.revenue') }}" class="btn btn-lg btn-outline-success w-100 py-4">
                            <i class="fas fa-chart-line fa-2x mb-2"></i><br>
                            Revenue Reports
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="{{ route('admin.reports.payouts') }}" class="btn btn-lg btn-outline-warning w-100 py-4">
                            <i class="fas fa-money-bill-wave fa-2x mb-2"></i><br>
                            Payout Reports
                        </a>
                    </div>
                </div>
            </div>
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
