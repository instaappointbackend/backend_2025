<!-- resources/views/admin/payouts/reports.blade.php -->
@extends('admin.layouts.app')

@section('title', 'Payout Reports')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Payout Reports</h1>
        <div>
            <a href="{{ route('admin.payouts.index') }}" class="btn btn-sm btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Payouts
            </a>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Date Range Filter</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.payouts.reports') }}" class="row">
                <div class="col-md-4 mb-3">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" value="{{ $startDate }}">
                </div>

                <div class="col-md-4 mb-3">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" value="{{ $endDate }}">
                </div>

                <div class="col-md-4 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Payout Requests
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalRequests }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Paid Amount
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₹{{ number_format($totalAmount, 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Pending Amount
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₹{{ number_format($pendingAmount, 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Processing Amount
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₹{{ number_format($processingAmount, 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-spinner fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Status Chart Card -->
        <div class="col-xl-6 col-lg-7 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Payouts by Status</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="statusChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <span class="mr-2">
                            <i class="fas fa-circle text-warning"></i> Pending ({{ $pendingCount }})
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-info"></i> Processing ({{ $processingCount }})
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-success"></i> Completed ({{ $completedCount }})
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-danger"></i> Rejected ({{ $rejectedCount }})
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-secondary"></i> Cancelled ({{ $cancelledCount }})
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daily Payouts Chart Card -->
        <div class="col-xl-6 col-lg-5 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Daily Payout Amounts</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="dailyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Monthly Payouts Chart Card -->
        <div class="col-xl-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Monthly Payout Amounts</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Providers Card -->
        <div class="col-xl-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Top Providers by Payout</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-bordered">
                            <thead>
                            <tr>
                                <th>Provider</th>
                                <th>Amount</th>
                                <th>Count</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($topProviders as $provider)
                            <tr>
                                <td>
                                    @if($provider->user)
                                    <a href="{{ route('admin.payouts.provider_earnings', $provider->user_id) }}">
                                        {{ $provider->user->name }}
                                    </a>
                                    @else
                                    Unknown
                                    @endif
                                </td>
                                <td>₹{{ number_format($provider->total_amount, 2) }}</td>
                                <td>{{ $provider->request_count }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center">No data available</td>
                            </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(document).ready(function() {
        // Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Processing', 'Completed', 'Rejected', 'Cancelled'],
                datasets: [{
                    data: [
                        {{ $pendingCount }},
                    {{ $processingCount }},
        {{ $completedCount }},
        {{ $rejectedCount }},
        {{ $cancelledCount }}
    ],
        backgroundColor: [
            '#f6c23e', // warning
            '#36b9cc', // info
            '#1cc88a', // success
            '#e74a3b', // danger
            '#858796'  // secondary
        ],
            hoverBackgroundColor: [
            '#e0b037',
            '#2ca6b9',
            '#17a673',
            '#d03b2d',
            '#717384'
        ],
            hoverBorderColor: "rgba(234, 236, 244, 1)",
    }],
    },
        options: {
            maintainAspectRatio: false,
                tooltips: {
                callbacks: {
                    label: function(tooltipItem, data) {
                        const dataset = data.datasets[tooltipItem.datasetIndex];
                        const currentValue = dataset.data[tooltipItem.index];
                        const total = dataset.data.reduce((acc, val) => acc + val, 0);
                        const percentage = Math.round((currentValue / total) * 100);
                        return `${data.labels[tooltipItem.index]}: ${currentValue} (${percentage}%)`;
                    }
                }
            },
            legend: {
                display: false
            },
            cutoutPercentage: 70,
        },
    });

        // Daily Chart
        const dailyCtx = document.getElementById('dailyChart').getContext('2d');
        const dailyChart = new Chart(dailyCtx, {
            type: 'bar',
            data: {
                labels: [
                @foreach($dailyStats as $stat)
        "{{ $stat['formatted_date'] }}",
        @endforeach
    ],
        datasets: [{
            label: 'Daily Payout Amount',
            data: [
            @foreach($dailyStats as $stat)
        {{ $stat['amount'] }},
    @endforeach
    ],
        backgroundColor: '#4e73df',
            borderColor: '#4e73df',
            borderWidth: 1
    }]
    },
        options: {
            maintainAspectRatio: false,
                scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        callback: function(value) {
                            return '₹' + value.toLocaleString();
                        }
                    }
                }]
            },
            tooltips: {
                callbacks: {
                    label: function(tooltipItem, data) {
                        return 'Amount: ₹' + tooltipItem.yLabel.toLocaleString();
                    }
                }
            }
        }
    });

        // Monthly Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyChart = new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: [
                @foreach($monthlyStats as $stat)
        "{{ $stat['month'] }}",
        @endforeach
    ],
        datasets: [{
            label: 'Monthly Payout Amount',
            data: [
            @foreach($monthlyStats as $stat)
        {{ $stat['amount'] }},
    @endforeach
    ],
        backgroundColor: 'rgba(78, 115, 223, 0.05)',
            borderColor: '#4e73df',
            pointRadius: 3,
            pointBackgroundColor: '#4e73df',
            pointBorderColor: '#4e73df',
            pointHoverRadius: 5,
            pointHoverBackgroundColor: '#4e73df',
            pointHoverBorderColor: '#4e73df',
            pointHitRadius: 10,
            pointBorderWidth: 2,
            fill: true
    }]
    },
        options: {
            maintainAspectRatio: false,
                scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        callback: function(value) {
                            return '₹' + value.toLocaleString();
                        }
                    }
                }]
            },
            tooltips: {
                callbacks: {
                    label: function(tooltipItem, data) {
                        return 'Amount: ₹' + tooltipItem.yLabel.toLocaleString();
                    }
                }
            }
        }
    });
    });
</script>
@endsection
