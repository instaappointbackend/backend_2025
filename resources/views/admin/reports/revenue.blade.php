<!-- resources/views/admin/reports/revenue.blade.php -->
@extends('admin.layouts.app')

@section('title', 'Revenue Reports')

@section('page-title', 'Revenue Reports')

@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">Reports</a></li>
            <li class="breadcrumb-item active" aria-current="page">Revenue</li>
        </ol>
    </nav>
@endsection

@section('page-actions')
    <a href="{{ route('admin.reports.revenue.export') }}?start_date={{ request('start_date') }}&end_date={{ request('end_date') }}&status={{ request('status') }}"
        class="btn btn-success me-2">
        <i class="fas fa-file-excel me-1"></i> Export to CSV
    </a>
@endsection

@section('content')
    <!-- Date Range Filter -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-1"></i>
            Filter Revenue Data
        </div>
        <div class="card-body">
            <form action="{{ route('admin.reports.revenue') }}" method="GET" class="row g-3">
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
                    <label for="status" class="form-label">Payment Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                        <option value="refunded" {{ request('status') == 'refunded' ? 'selected' : '' }}>Refunded</option>
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
                            <div class="h5 mb-0 font-weight-bold">₹{{ number_format($totalRevenue, 2) }}</div>
                            <div>Total Revenue</div>
                        </div>
                        <div>
                            <i class="fas fa-rupee-sign fa-2x"></i>
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
                            <div class="h5 mb-0 font-weight-bold">₹{{ number_format($platformRevenue, 2) }}</div>
                            <div>Platform Revenue</div>
                        </div>
                        <div>
                            <i class="fas fa-hand-holding-usd fa-2x"></i>
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
                            <div class="h5 mb-0 font-weight-bold">₹{{ number_format($vendorRevenue, 2) }}</div>
                            <div>Vendor Earnings</div>
                        </div>
                        <div>
                            <i class="fas fa-store fa-2x"></i>
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
                            <div class="h5 mb-0 font-weight-bold">{{ $totalTransactions }}</div>
                            <div>Total Transactions</div>
                        </div>
                        <div>
                            <i class="fas fa-exchange-alt fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Revenue Chart -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-line me-1"></i>
                    Revenue Trends
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="80"></canvas>
                </div>
            </div>
        </div>

        <!-- Payment Methods -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-credit-card me-1"></i>
                    Payment Methods
                </div>
                <div class="card-body">
                    <canvas id="paymentMethodChart" height="180"></canvas>
                </div>
                <div class="card-footer bg-white p-3">
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless mb-0">
                            <thead>
                                <tr>
                                    <th>Method</th>
                                    <th class="text-end">Transactions</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($paymentMethods as $method)
                                    <tr>
                                        <td>{{ ucfirst($method->payment_method) }}</td>
                                        <td class="text-end">{{ $method->count }}</td>
                                        <td class="text-end">₹{{ number_format($method->total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Payment Transactions
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Sr No</th>
                            <th>ID</th>
                            <th>Transaction ID</th>
                            <th>Customer</th>
                            <th>Vendor</th>
                            <th>Amount</th>
                            <th>Platform Fee</th>
                            <th>Vendor Earnings</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $index=>$payment)
                            <tr>
                                <td>{{ $payments->firstItem() + $index }}</td>
                                <td>{{ $payment->id }}</td>
                                <td>{{ $payment->transaction_id }}</td>
                                <td>{{ $payment->user->name ?? 'N/A' }}</td>
                                <td>{{ $payment->provider->name ?? 'N/A' }}</td>
                                <td>₹{{ number_format($payment->amount, 2) }}</td>
                                <td>₹{{ number_format($payment->platform_fee ?? 0, 2) }}</td>
                                <td>₹{{ number_format($payment->vendor_earnings ?? 0, 2) }}</td>
                                <td>{{ ucfirst($payment->payment_method) }}</td>
                                <td>
                                    @if ($payment->status == 'paid')
                                        <span class="badge bg-success">Paid</span>
                                    @elseif($payment->status == 'pending')
                                        <span class="badge bg-warning">Pending</span>
                                    @elseif($payment->status == 'failed')
                                        <span class="badge bg-danger">Failed</span>
                                    @elseif($payment->status == 'refunded')
                                        <span class="badge bg-info">Refunded</span>
                                    @endif
                                </td>
                                <td>{{ $payment->created_at->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center">No payment transactions found in the selected date
                                    range.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- <div class="d-flex justify-content-end mt-3">
                {{ $payments->appends(request()->except('page'))->links() }}
            </div> --}}

            @if ($payments->hasPages())
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        Showing {{ $payments->firstItem() ?? 0 }} to {{ $payments->lastItem() ?? 0 }} of
                        {{ $payments->total() }}
                        payments
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        {{ $payments->onEachSide(5)->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Revenue Chart Data
        var revenueData = @json($revenueStats);

        // Parse the data for Chart.js
        var labels = revenueData.map(function(item) {
            return item.date;
        });

        var totalData = revenueData.map(function(item) {
            return item.total;
        });

        var adminData = revenueData.map(function(item) {
            return item.admin;
        });

        var vendorData = revenueData.map(function(item) {
            return item.vendor;
        });

        // Create the revenue chart
        var ctx = document.getElementById('revenueChart').getContext('2d');
        var revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                        label: 'Total Revenue',
                        data: totalData,
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Platform Revenue',
                        data: adminData,
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Vendor Earnings',
                        data: vendorData,
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
                            callback: function(value) {
                                return '₹' + value;
                            }
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Revenue Trends',
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

        // Payment Methods Chart
        var paymentMethods = @json($paymentMethods);

        // Parse the data for Chart.js
        var methodLabels = paymentMethods.map(function(item) {
            return item.payment_method.charAt(0).toUpperCase() + item.payment_method.slice(1);
        });

        var methodCounts = paymentMethods.map(function(item) {
            return item.count;
        });

        // Create colors array for pie chart
        var colors = [
            'rgba(0, 123, 255, 0.7)',
            'rgba(40, 167, 69, 0.7)',
            'rgba(23, 162, 184, 0.7)',
            'rgba(255, 193, 7, 0.7)',
            'rgba(220, 53, 69, 0.7)',
            'rgba(108, 117, 125, 0.7)'
        ];

        // Create the payment methods pie chart
        var ctxPie = document.getElementById('paymentMethodChart').getContext('2d');
        var paymentMethodChart = new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: methodLabels,
                datasets: [{
                    data: methodCounts,
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
                                return label + ': ' + value + ' transactions (' + percentage + '%)';
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
