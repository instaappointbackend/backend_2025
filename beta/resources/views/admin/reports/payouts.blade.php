<!-- resources/views/admin/reports/payouts.blade.php -->
@extends('admin.layouts.app')

@section('title', 'Payout Reports')

@section('page-title', 'Payout Reports')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb m-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">Reports</a></li>
        <li class="breadcrumb-item active" aria-current="page">Payouts</li>
    </ol>
</nav>
@endsection

@section('page-actions')
<a href="{{ route('admin.reports.payouts.export') }}?start_date={{ request('start_date') }}&end_date={{ request('end_date') }}&status={{ request('status') }}" class="btn btn-success me-2">
    <i class="fas fa-file-excel me-1"></i> Export to CSV
</a>
@endsection

@section('content')
<!-- Date Range Filter -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-filter me-1"></i>
        Filter Payout Data
    </div>
    <div class="card-body">
        <form action="{{ route('admin.reports.payouts') }}" method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="{{ request('start_date', $startDate->format('Y-m-d')) }}">
            </div>
            <div class="col-md-4">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="{{ request('end_date', $endDate->format('Y-m-d')) }}">
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Payout Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
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
                        <div class="h5 mb-0 font-weight-bold">₹{{ number_format($totalPayouts, 2) }}</div>
                        <div>Total Payouts</div>
                    </div>
                    <div>
                        <i class="fas fa-money-bill-wave fa-2x"></i>
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
                        <div class="h5 mb-0 font-weight-bold">₹{{ number_format($completedPayouts, 2) }}</div>
                        <div>Completed Payouts</div>
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
                        <div class="h5 mb-0 font-weight-bold">₹{{ number_format($pendingPayouts, 2) }}</div>
                        <div>Pending Payouts</div>
                    </div>
                    <div>
                        <i class="fas fa-hourglass-half fa-2x"></i>
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
                        <div class="h5 mb-0 font-weight-bold">{{ $payoutRequests }}</div>
                        <div>Total Requests</div>
                    </div>
                    <div>
                        <i class="fas fa-file-invoice-dollar fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Payout Chart -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-chart-line me-1"></i>
                Payout Trends
            </div>
            <div class="card-body">
                <canvas id="payoutChart" height="80"></canvas>
            </div>
        </div>
    </div>

    <!-- Top Vendors -->
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-trophy me-1"></i>
                Top Vendors by Payout
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                        <thead>
                        <tr>
                            <th>Vendor</th>
                            <th class="text-end">Total Payout</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($topVendors as $vendor)
                        <tr>
                            <td>{{ $vendor->name }}</td>
                            <td class="text-end">₹{{ number_format($vendor->total, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="text-center">No vendors found in the selected date range.</td>
                        </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payouts Table -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-table me-1"></i>
        Payout Requests
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Vendor</th>
                    <th>Bank Account</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Transaction ID</th>
                    <th>Transaction Date</th>
                    <th>Requested On</th>
                </tr>
                </thead>
                <tbody>
                @forelse($payouts as $payout)
                <tr>
                    <td>{{ $payout->id }}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="me-2">
                                <img src="{{ $payout->user && $payout->user->profile_picture ? asset('storage/' . $payout->user->profile_picture) : asset('admin/images/default-avatar.png') }}" alt="{{ $payout->user ? $payout->user->name : 'N/A' }}" class="avatar-img" width="30" height="30">
                            </div>
                            <div>{{ $payout->user ? $payout->user->name : 'N/A' }}</div>
                        </div>
                    </td>
                    <td>
                        @if($payout->bankAccount)
                        {{ $payout->bankAccount->bank_name }} - {{ substr_replace($payout->bankAccount->account_number, 'XXXX', 0, -4) }}
                        @else
                        N/A
                        @endif
                    </td>
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
                    <td>{{ $payout->transaction_id ?? 'N/A' }}</td>
                    <td>{{ $payout->transaction_date ? $payout->transaction_date->format('M d, Y') : 'N/A' }}</td>
                    <td>{{ $payout->created_at->format('M d, Y') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center">No payout requests found in the selected date range.</td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-end mt-3">
            {{ $payouts->appends(request()->except('page'))->links() }}
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Payout Chart Data
    var payoutData = @json($payoutStats);

    // Parse the data for Chart.js
    var labels = payoutData.map(function(item) {
        return item.date;
    });

    var totalData = payoutData.map(function(item) {
        return item.total;
    });

    var completedData = payoutData.map(function(item) {
        return item.completed;
    });

    var pendingData = payoutData.map(function(item) {
        return item.pending;
    });

    // Create the payout chart
    var ctx = document.getElementById('payoutChart').getContext('2d');
    var payoutChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Total Payouts',
                    data: totalData,
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderColor: 'rgba(0, 123, 255, 1)',
                    borderWidth: 2,
                    tension: a0.3,
                    fill: true
                },
                {
                    label: 'Completed Payouts',
                    data: completedData,
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Pending Payouts',
                    data: pendingData,
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    borderColor: 'rgba(255, 193, 7, 1)',
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
                        callback: function(value) {
                            return '₹' + value;
                        }
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Payout Trends',
                    font: {
                        size: 16
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += '₹' + context.raw;
                            return label;
                        }
                    }
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
