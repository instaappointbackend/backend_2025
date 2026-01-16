<!-- resources/views/admin/reports/appointments.blade.php -->
@extends('admin.layouts.app')

@section('title', 'Appointment Reports')

@section('page-title', 'Appointment Reports')

@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">Reports</a></li>
            <li class="breadcrumb-item active" aria-current="page">Appointments</li>
        </ol>
    </nav>
@endsection

@section('page-actions')
    <a href="{{ route('admin.reports.appointments.export') }}?start_date={{ request('start_date') }}&end_date={{ request('end_date') }}&status={{ request('status') }}"
        class="btn btn-success me-2">
        <i class="fas fa-file-excel me-1"></i> Export to CSV
    </a>
@endsection

@section('content')
    <!-- Date Range Filter -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-1"></i>
            Filter Appointments
        </div>
        <div class="card-body">
            <form action="{{ route('admin.reports.appointments') }}" method="GET" class="row g-3">
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
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed
                        </option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed
                        </option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled
                        </option>
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
                            <div class="h5 mb-0 font-weight-bold">{{ $totalAppointments }}</div>
                            <div>Total Appointments</div>
                        </div>
                        <div>
                            <i class="fas fa-calendar fa-2x"></i>
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
                            <div class="h5 mb-0 font-weight-bold">{{ $completedAppointments }}</div>
                            <div>Completed</div>
                        </div>
                        <div>
                            <i class="fas fa-check-circle fa-2x"></i>
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
                            <div class="h5 mb-0 font-weight-bold">{{ $pendingAppointments }}</div>
                            <div>Pending</div>
                        </div>
                        <div>
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="h5 mb-0 font-weight-bold">{{ $cancelledAppointments }}</div>
                            <div>Cancelled</div>
                        </div>
                        <div>
                            <i class="fas fa-times-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-chart-line me-1"></i>
            Appointment Trends
        </div>
        <div class="card-body">
            <canvas id="appointmentChart" height="80"></canvas>
        </div>
    </div>

    <!-- Appointments Table -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Appointment Data
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Service</th>
                            <th>Customer</th>
                            <th>Vendor</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($appointments as $appointment)
                            <tr>
                                <td>{{ $appointment->id }}</td>
                                <td>{{ !empty($appointment->service->name) ? $appointment->service->name : $appointment->comboService->name ?? 'N/A' }}
                                </td>
                                <td>{{ $appointment->client->name ?? 'N/A' }}</td>
                                <td>{{ $appointment->user->name ?? 'N/A' }}</td>
                                <td>{{ $appointment->date ? $appointment->date->format('M d, Y') . ', ' . \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') . ' - ' . \Carbon\Carbon::parse($appointment->end_time)->format('g:i A') : 'N/A' }}
                                </td>
                                <td>
                                    @if ($appointment->status == 'pending')
                                        <span class="badge bg-warning">Pending</span>
                                    @elseif($appointment->status == 'confirmed')
                                        <span class="badge bg-primary">Confirmed</span>
                                    @elseif($appointment->status == 'completed')
                                        <span class="badge bg-success">Completed</span>
                                    @elseif($appointment->status == 'cancelled')
                                        <span class="badge bg-danger">Cancelled</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($appointment->payment_status == 'paid')
                                        <span class="badge bg-success">Paid</span>
                                        <span class="ms-1">₹{{ number_format($appointment->payment_amount, 2) }}</span>
                                    @elseif($appointment->payment_status == 'pending')
                                        <span class="badge bg-warning">Pending</span>
                                    @elseif($appointment->payment_status == 'failed')
                                        <span class="badge bg-danger">Failed</span>
                                    @elseif($appointment->payment_status == 'refunded')
                                        <span class="badge bg-info">Refunded</span>
                                    @endif
                                </td>
                                <td>{{ $appointment->created_at->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No appointments found in the selected date range.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- <div class="d-flex justify-content-end mt-3">
                {{ $appointments->appends(request()->except('page'))->links() }}
            </div> --}}

            @if ($appointments->hasPages())
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        Showing {{ $appointments->firstItem() ?? 0 }} to {{ $appointments->lastItem() ?? 0 }} of
                        {{ $appointments->total() }}
                        appointments
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        {{ $appointments->onEachSide(5)->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Chart data from PHP
        var appointmentData = @json($appointmentStats);

        // Parse the data for Chart.js
        var labels = appointmentData.map(function(item) {
            return item.date;
        });

        var totalData = appointmentData.map(function(item) {
            return item.total;
        });

        var completedData = appointmentData.map(function(item) {
            return item.completed;
        });

        var cancelledData = appointmentData.map(function(item) {
            return item.cancelled;
        });

        // Create the chart
        var ctx = document.getElementById('appointmentChart').getContext('2d');
        var appointmentChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                        label: 'Total',
                        data: totalData,
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Completed',
                        data: completedData,
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Cancelled',
                        data: cancelledData,
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        borderColor: 'rgba(220, 53, 69, 1)',
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
                        text: 'Appointment Trends',
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
    </script>
@endsection
