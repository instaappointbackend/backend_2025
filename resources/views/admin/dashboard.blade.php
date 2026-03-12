<!-- resources/views/admin/dashboard.blade.php -->
@extends('admin.layouts.app')

@section('title', 'Admin Dashboard')

@section('page-title', 'Dashboard Overview')

@section('styles')
    <style>
        /* Enhanced Dashboard Styles */
        .stat-card {
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            overflow: hidden;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-card-body {
            padding: 1.5rem;
            position: relative;
            z-index: 1;
        }

        .stat-card-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            border-radius: 12px;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
        }

        .stat-card-title {
            color: #8898aa;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 0.75rem;
        }

        .stat-card-value {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #32325d;
        }

        .stat-card-trend {
            font-size: 0.875rem;
            font-weight: 500;
        }

        .trend-up {
            color: #2dce89;
        }

        .trend-down {
            color: #f5365c;
        }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-pending {
            background-color: rgba(251, 99, 64, 0.1);
            color: #fb6340;
        }

        .status-confirmed {
            background-color: rgba(94, 114, 228, 0.1);
            color: #5e72e4;
        }

        .status-completed {
            background-color: rgba(45, 206, 137, 0.1);
            color: #2dce89;
        }

        .status-cancelled {
            background-color: rgba(245, 54, 92, 0.1);
            color: #f5365c;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .activity-card {
            border-radius: 12px;
            overflow: hidden;
        }

        .activity-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            color: white;
        }

        .dashboard-card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            height: 100%;
        }

        .dashboard-card .card-header {
            background-color: transparent;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
        }

        .dashboard-card .card-body {
            padding: 1.5rem;
        }

        .stat-status-card {
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .stat-status-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-status-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            font-size: 1.75rem;
            color: white;
            margin-bottom: 1rem;
        }

        .appointment-table th {
            font-weight: 600;
            font-size: 0.825rem;
            text-transform: uppercase;
            color: #8898aa;
        }

        .appointment-table td {
            vertical-align: middle;
            padding: 1rem 0.75rem;
        }

        .btn-action {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .btn-action:hover {
            transform: translateY(-2px);
        }

        /* Visual accents and background shapes */
        .stat-card::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            z-index: 0;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            bottom: -30px;
            left: -30px;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.05);
            z-index: 0;
        }
    </style>
@endsection

@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb m-0">
            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
        </ol>
    </nav>
@endsection

@section('content')
    <!-- Welcome Banner -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card dashboard-card bg-primary text-white">
                <div class="card-body py-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="text-white mb-1">Welcome back, Admin!</h4>
                            <p class="mb-0 opacity-8">Here's what's happening with InstaAppoint today.</p>
                        </div>
                        <div class="d-none d-md-block">
                            <i class="fas fa-chart-line fa-3x opacity-6"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3 col-sm-6 mb-4">

            <div class="stat-card position-relative bg-white">
                <div class="stat-card-body">
                    <div class="stat-card-icon bg-primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <h6 class="stat-card-title">TOTAL USERS</h6>
                    <h2 class="stat-card-value">{{ $totalCustomers + $totalVendors }}</h2>
                    <div class="stat-card-trend trend-up">
                        <i class="fas fa-arrow-up me-1"></i> 12.5% from last month
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-4">
            <a href="{{ route('admin.users.index') }}" class="stat-card position-relative bg-white text-decoration-none"
                data-card="users">
                <div class="stat-card position-relative bg-white">
                    <div class="stat-card-body">
                        <div class="stat-card-icon bg-primary">
                            <i class="fas fa-user-cog"></i>
                        </div>
                        <h6 class="stat-card-title">TOTAL ADMIN USERS</h6>
                        <h2 class="stat-card-value">{{ $totalUsers }}</h2>
                        <div class="stat-card-trend trend-up">
                            <i class="fas fa-arrow-up me-1"></i> 12.5% from last month
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
    <!-- Stats Cards -->
    <div class="row">
        <div class="col-md-3 col-sm-6 mb-4">
            <a href="{{ route('admin.users.customers') }}" class="stat-card position-relative bg-white text-decoration-none"
                data-card="users">
                <div class="stat-card position-relative bg-white">
                    <div class="stat-card-body">
                        <div class="stat-card-icon bg-primary">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <h6 class="stat-card-title">TOTAL Customers</h6>
                        <h2 class="stat-card-value">{{ $totalCustomers }}</h2>
                        <div class="stat-card-trend trend-up">
                            <i class="fas fa-arrow-up me-1"></i> 12.5% from last month
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3 col-sm-6 mb-4">
            <a href="{{ route('admin.users.vendors') }}" class="stat-card position-relative bg-white text-decoration-none"
                data-card="vendors">
                <div class="stat-card position-relative bg-white">
                    <div class="stat-card-body">
                        <div class="stat-card-icon bg-success">
                            <i class="fas fa-store"></i>
                        </div>
                        <h6 class="stat-card-title">TOTAL VENDORS</h6>
                        <h2 class="stat-card-value">{{ $totalVendors }}</h2>
                        <div class="stat-card-trend trend-up">
                            <i class="fas fa-arrow-up me-1"></i> 8.2% from last month
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3 col-sm-6 mb-4">
            <a href="{{ route('admin.appointments.index') }}"
                class="stat-card position-relative bg-white text-decoration-none" data-card="appointments">
                <div class="stat-card position-relative bg-white">
                    <div class="stat-card-body">
                        <div class="stat-card-icon bg-warning">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h6 class="stat-card-title">TOTAL APPOINTMENTS</h6>
                        <h2 class="stat-card-value">{{ $totalAppointments }}</h2>
                        <div class="stat-card-trend trend-up">
                            <i class="fas fa-arrow-up me-1"></i> 15.3% from last month
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3 col-sm-6 mb-4">
            <a href="{{ route('admin.services.index') }}" class="stat-card position-relative bg-white text-decoration-none"
                data-card="services">
                <div class="stat-card position-relative bg-white">
                    <div class="stat-card-body">
                        <div class="stat-card-icon bg-info">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <h6 class="stat-card-title">TOTAL SERVICES</h6>
                        <h2 class="stat-card-value">{{ $totalServices }}</h2>
                        <div class="stat-card-trend trend-up">
                            <i class="fas fa-arrow-up me-1"></i> 5.7% from last month
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Appointment Status Cards -->
    <div class="row">
        <div class="col-md-3 col-sm-6 mb-4">
            <a href="{{ route('admin.appointments.index', ['status' => 'pending']) }}"
                class="card dashboard-card stat-status-card h-100 text-decoration-none" data-card="pending">
                <div class="card-body">
                    <div class="stat-status-icon" style="background-color: rgba(251, 99, 64, 0.1);">
                        <i class="fas fa-clock" style="color: #fb6340;"></i>
                    </div>
                    <h5 class="card-title">Pending</h5>
                    <h2 class="fw-bold mb-0">{{ $pendingAppointments }}</h2>
                    <p class="text-muted small mb-0">Awaiting confirmation</p>
                </div>
            </a>
        </div>

        <div class="col-md-3 col-sm-6 mb-4">
            <a href="{{ route('admin.appointments.index', ['status' => 'confirmed']) }}"
                class="card dashboard-card stat-status-card h-100 text-decoration-none" data-card="confirmed">
                <div class="card-body">
                    <div class="stat-status-icon" style="background-color: rgba(94, 114, 228, 0.1);">
                        <i class="fas fa-check-circle" style="color: #5e72e4;"></i>
                    </div>
                    <h5 class="card-title">Confirmed</h5>
                    <h2 class="fw-bold mb-0">{{ $confirmedAppointments }}</h2>
                    <p class="text-muted small mb-0">Ready to go</p>
                </div>
            </a>
        </div>

        <div class="col-md-3 col-sm-6 mb-4">
            <a href="{{ route('admin.appointments.index', ['status' => 'completed']) }}"
                class="card dashboard-card stat-status-card h-100 text-decoration-none" data-card="completed">
                <div class="card-body">
                    <div class="stat-status-icon" style="background-color: rgba(45, 206, 137, 0.1);">
                        <i class="fas fa-check-double" style="color: #2dce89;"></i>
                    </div>
                    <h5 class="card-title">Completed</h5>
                    <h2 class="fw-bold mb-0">{{ $completedAppointments }}</h2>
                    <p class="text-muted small mb-0">Successfully delivered</p>
                </div>
            </a>
        </div>

        <div class="col-md-3 col-sm-6 mb-4">
            <a href="{{ route('admin.appointments.index', ['status' => 'cancelled']) }}"
                class="card dashboard-card stat-status-card h-100 text-decoration-none" data-card="cancelled">
                <div class="card-body">
                    <div class="stat-status-icon" style="background-color: rgba(245, 54, 92, 0.1);">
                        <i class="fas fa-times-circle" style="color: #f5365c;"></i>
                    </div>
                    <h5 class="card-title">Cancelled</h5>
                    <h2 class="fw-bold mb-0">{{ $cancelledAppointments }}</h2>
                    <p class="text-muted small mb-0">Not proceeding</p>
                </div>
            </a>
        </div>
    </div>

    <!-- Refund Statistics Cards -->
    @if (hasPermission('refunds_view_refunds'))
        <div class="row">
            <div class="col-12 mb-3">
                <h5 class="text-muted">Refund Management</h5>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <a href="{{ route('admin.refunds.index') }}"
                    class="card dashboard-card stat-status-card h-100 text-decoration-none" data-card="total-refunds">
                    <div class="card-body">
                        <div class="stat-status-icon" style="background-color: rgba(156, 39, 176, 0.1);">
                            <i class="fas fa-undo-alt" style="color: #9c27b0;"></i>
                        </div>
                        <h5 class="card-title">Total Refunds</h5>
                        <h2 class="fw-bold mb-0">{{ $totalRefunds ?? 0 }}</h2>
                        <p class="text-muted small mb-0">All time refunds</p>
                    </div>
                </a>
            </div>

            <div class="col-md-3 col-sm-6 mb-4">
                <a href="{{ route('admin.refunds.index', ['status' => 'pending']) }}"
                    class="card dashboard-card stat-status-card h-100 text-decoration-none" data-card="pending-refunds">
                    <div class="card-body">
                        <div class="stat-status-icon" style="background-color: rgba(255, 193, 7, 0.1);">
                            <i class="fas fa-hourglass-half" style="color: #ffc107;"></i>
                        </div>
                        <h5 class="card-title">Pending Refunds</h5>
                        <h2 class="fw-bold mb-0">{{ $pendingRefunds ?? 0 }}</h2>
                        <p class="text-muted small mb-0">Awaiting processing</p>
                    </div>
                </a>
            </div>

            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card dashboard-card stat-status-card h-100">
                    <div class="card-body">
                        <div class="stat-status-icon" style="background-color: rgba(76, 175, 80, 0.1);">
                            <i class="fas fa-rupee-sign" style="color: #4caf50;"></i>
                        </div>
                        <h5 class="card-title">Refund Amount</h5>
                        <h2 class="fw-bold mb-0">₹{{ number_format($totalRefundAmount ?? 0, 0) }}</h2>
                        <p class="text-muted small mb-0">This month</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card dashboard-card stat-status-card h-100">
                    <div class="card-body">
                        <div class="stat-status-icon" style="background-color: rgba(33, 150, 243, 0.1);">
                            <i class="fas fa-percentage" style="color: #2196f3;"></i>
                        </div>
                        <h5 class="card-title">Success Rate</h5>
                        <h2 class="fw-bold mb-0">{{ number_format($refundSuccessRate ?? 0, 1) }}%</h2>
                        <p class="text-muted small mb-0">Processing success</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Charts -->
    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card dashboard-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Appointment Statistics</h5>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                            id="dropdownAppointmentStats" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-calendar-alt me-1"></i> Last 7 Days
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownAppointmentStats">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-calendar-week me-2"></i>Last 7
                                    Days</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-calendar-alt me-2"></i>Last 30
                                    Days</a></li>
                            <li><a class="dropdown-item" href="#"><i class="far fa-calendar-alt me-2"></i>Last 90
                                    Days</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="appointmentChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card dashboard-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">User Distribution</h5>
                    <span class="badge bg-primary">{{ $totalUsers }} Total</span>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="userRegistrationChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="row">
        <!-- Recent Appointments -->
        <div class="col-lg-8 mb-4">
            <div class="card dashboard-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Recent Appointments</h5>
                        <p class="text-muted mb-0 small">Latest booking activities</p>
                    </div>
                    <a href="{{ route('admin.appointments.index') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye me-1"></i> View All
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table appointment-table mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-3">ID</th>
                                    <th>SERVICE</th>
                                    <th>CUSTOMER</th>
                                    <th>VENDOR</th>
                                    <th>DATE & TIME</th>
                                    <th>STATUS</th>
                                    <th class="text-end pe-3">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody class="border-top-0">
                                @forelse($recentAppointments as $appointment)
                                    <tr>
                                        <td class="ps-3 fw-bold">{{ $appointment->id }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-tag text-primary me-2"></i>
                                                {{ !empty($appointment->service->name) ? $appointment->service->name : $appointment->comboService->name }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                {{-- <div class="avatar avatar-sm bg-light rounded-circle me-2 d-flex align-items-center justify-content-center">
                                                    <span class="text-dark">{{ substr($appointment->client->name, 0, 1) }}</span>
                                                </div> --}}
                                                {{ $appointment->client->name ?? '' }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                {{-- <div
                                                    class="avatar avatar-sm bg-primary text-white rounded-circle me-2 d-flex align-items-center justify-content-center">
                                                    <span>{{ substr($appointment->user->name, 0, 1) }}</span>
                                                </div> --}}
                                                {{ $appointment->user->name ?? '-' }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="far fa-calendar-alt text-muted me-2"></i>
                                                {{ $appointment->formatted_time }}
                                            </div>
                                        </td>
                                        <td>
                                            @if ($appointment->status == \App\Models\Appointment::STATUS_PENDING)
                                                <span class="status-badge status-pending">Pending</span>
                                            @elseif($appointment->status == \App\Models\Appointment::STATUS_CONFIRMED)
                                                <span class="status-badge status-confirmed">Confirmed</span>
                                            @elseif($appointment->status == \App\Models\Appointment::STATUS_COMPLETED)
                                                <span class="status-badge status-completed">Completed</span>
                                            @elseif($appointment->status == \App\Models\Appointment::STATUS_CANCELLED)
                                                <span class="status-badge status-cancelled">Cancelled</span>
                                            @endif
                                        </td>
                                        <td class="text-end pe-3">
                                            <a href="{{ route('admin.appointments.show', $appointment->id) }}"
                                                class="btn-action btn-info text-white">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <img src="{{ asset('images/no-data.svg') }}" alt="No Data"
                                                style="height: 120px; opacity: 0.5;">
                                            <p class="text-muted mt-3">No recent appointments found.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Refunds -->
        @if (hasPermission('refunds_view_refunds'))
            <div class="col-lg-4 mb-4">
                <div class="card dashboard-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Recent Refunds</h5>
                            <p class="text-muted mb-0 small">Latest refund requests</p>
                        </div>
                        <a href="{{ route('admin.refunds.index') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-eye me-1"></i> View All
                        </a>
                    </div>
                    <div class="card-body p-0">
                        @forelse($recentRefunds ?? [] as $refund)
                            <div class="d-flex align-items-center p-3 border-bottom">
                                <div
                                    class="activity-icon bg-{{ $refund->refund_status == 'pending' ? 'warning' : ($refund->refund_status == 'processed' ? 'success' : 'danger') }} me-3">
                                    <i class="fas fa-undo-alt"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">{{ $refund->refund_reference }}</h6>
                                            <p class="text-muted small mb-1">{{ $refund->user->name ?? 'N/A' }}</p>
                                            <p class="text-muted small mb-0">{{ $refund->created_at->diffForHumans() }}
                                            </p>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold text-success">
                                                ₹{{ number_format($refund->refund_amount, 0) }}</div>
                                            <span
                                                class="badge badge-{{ $refund->refund_status == 'pending' ? 'warning' : ($refund->refund_status == 'processed' ? 'success' : 'danger') }} small">
                                                {{ ucfirst($refund->refund_status) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <i class="fas fa-undo-alt fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No recent refunds found.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Enhanced Appointment Statistics Chart
            const appointmentCtx = document.getElementById('appointmentChart').getContext('2d');
            const appointmentData = @json($appointmentChartData);

            const appointmentChart = new Chart(appointmentCtx, {
                type: 'bar',
                data: {
                    labels: appointmentData.map(item => item.label),
                    datasets: [{
                            label: 'Pending',
                            data: appointmentData.map(item => item.pending),
                            backgroundColor: 'rgba(251, 99, 64, 0.7)',
                            borderColor: '#fb6340',
                            borderWidth: 1,
                            borderRadius: 4,
                            barPercentage: 0.6,
                            categoryPercentage: 0.7
                        },
                        {
                            label: 'Confirmed',
                            data: appointmentData.map(item => item.confirmed),
                            backgroundColor: 'rgba(94, 114, 228, 0.7)',
                            borderColor: '#5e72e4',
                            borderWidth: 1,
                            borderRadius: 4,
                            barPercentage: 0.6,
                            categoryPercentage: 0.7
                        },
                        {
                            label: 'Completed',
                            data: appointmentData.map(item => item.completed),
                            backgroundColor: 'rgba(45, 206, 137, 0.7)',
                            borderColor: '#2dce89',
                            borderWidth: 1,
                            borderRadius: 4,
                            barPercentage: 0.6,
                            categoryPercentage: 0.7
                        },
                        {
                            label: 'Cancelled',
                            data: appointmentData.map(item => item.cancelled),
                            backgroundColor: 'rgba(245, 54, 92, 0.7)',
                            borderColor: '#f5365c',
                            borderWidth: 1,
                            borderRadius: 4,
                            barPercentage: 0.6,
                            categoryPercentage: 0.7
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                font: {
                                    size: 11
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 11
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                boxWidth: 12,
                                padding: 15,
                                font: {
                                    size: 11
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.7)',
                            padding: 10,
                            bodyFont: {
                                size: 12
                            },
                            titleFont: {
                                size: 13
                            }
                        }
                    }
                }
            });

            // Enhanced User Registration Chart
            const userRegistrationCtx = document.getElementById('userRegistrationChart').getContext('2d');
            const userRegistrationData = @json($userRegistrationChartData);

            const userRegistrationChart = new Chart(userRegistrationCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Vendors', 'Customers'],
                    datasets: [{
                        data: [
                            userRegistrationData.reduce((sum, item) => sum + item.vendors, 0),
                            userRegistrationData.reduce((sum, item) => sum + item.customers, 0)
                        ],
                        backgroundColor: [
                            'rgba(94, 114, 228, 0.8)',
                            'rgba(45, 206, 137, 0.8)'
                        ],
                        borderColor: [
                            '#5e72e4',
                            '#2dce89'
                        ],
                        borderWidth: 2,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                padding: 15,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.7)',
                            padding: 10,
                            bodyFont: {
                                size: 12
                            },
                            titleFont: {
                                size: 13
                            },
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((acc, val) => acc + val,
                                        0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
@endsection
