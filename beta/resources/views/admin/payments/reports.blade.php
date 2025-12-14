<!-- resources/views/admin/payments/reports.blade.php -->
@extends('admin.layouts.app')

@section('title', 'Payment Reports')

@section('page-title', 'Payment Reports & Analytics')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb m-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.payments.index') }}">Payments</a></li>
        <li class="breadcrumb-item active" aria-current="page">Reports</li>
    </ol>
</nav>
@endsection

@section('page-actions')
<a href="{{ route('admin.payments.export') }}?start_date={{ $startDate }}&end_date={{ $endDate }}" class="btn btn-primary">
    <i class="fas fa-download me-1"></i> Export Data
</a>
@endsection

@section('content')
<div class="row mb-4">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Filter Reports</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.payments.reports') }}" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $startDate }}">
                    </div>

                    <div class="col-md-4">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $endDate }}">
                    </div>

                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">Apply Filter</button>
                        <a href="{{ route('admin.payments.reports') }}" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1">Total Revenue</h5>
                        <p class="text-muted mb-0">{{ \Carbon\Carbon::parse($startDate)->format('M d') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</p>
                    </div>
                    <div class="avatar avatar-lg bg-primary-soft">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <h2 class="mb-0">₹{{ number_format($totalAmount, 2) }}</h2>
                    <p class="text-muted mb-0">{{ $totalPayments }} payments</p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1">Admin Earnings</h5>
                        <p class="text-muted mb-0">Fees + GST</p>
                    </div>
                    <div class="avatar avatar-lg bg-success-soft">
                        <i class="fas fa-building"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <h2 class="mb-0">₹{{ number_format($adminEarnings, 2) }}</h2>
                    @if($totalAmount>0)
                    <p class="text-muted mb-0">{{ number_format(($adminEarnings / $totalAmount) * 100, 1) }}% of total</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1">Provider Earnings</h5>
                        <p class="text-muted mb-0">Vendor payouts</p>
                    </div>
                    <div class="avatar avatar-lg bg-info-soft">
                        <i class="fas fa-user-tie"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <h2 class="mb-0">₹{{ number_format($vendorEarnings, 2) }}</h2>
                    @if($totalAmount>0)
                    <p class="text-muted mb-0">{{ number_format(($vendorEarnings / $totalAmount) * 100, 1) }}% of total</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1">Average Order</h5>
                        <p class="text-muted mb-0">Per transaction</p>
                    </div>
                    <div class="avatar avatar-lg bg-warning-soft">
                        <i class="fas fa-calculator"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <h2 class="mb-0">₹{{ number_format($paidCount > 0 ? $totalAmount / $paidCount : 0, 2) }}</h2>
                    <p class="text-muted mb-0">{{ $paidCount }} successful payments</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts and Analytics -->
<div class="row mb-4">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Daily Revenue</h5>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="300"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Payment Status</h5>
            </div>
            <div class="card-body">
                <canvas id="statusChart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Payment Methods</h5>
            </div>
            <div class="card-body">
                <canvas id="methodChart" height="300"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Revenue Breakdown</h5>
            </div>
            <div class="card-body">
                <canvas id="breakdownChart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Top Vendors -->
<div class="row mb-4">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Top Vendors by Earnings</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th>Vendor</th>
                            <th>Transactions</th>
                            <th>Total Earnings</th>
                            <th>Average Order</th>
                            <th class="text-end">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($topVendors as $vendor)
                        <tr>
                            <td>
                                @if($vendor->provider)
                                <div class="d-flex align-items-center">
                                    <div class="me-2">
                                        <img src="{{ $vendor->provider->profile_picture ? asset('storage/' . $vendor->provider->profile_picture) : asset('admin/images/default-avatar.png') }}" alt="{{ $vendor->provider->name }}" class="avatar-img rounded-circle" width="40" height="40">
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $vendor->provider->name }}</h6>
                                        <small class="text-muted">{{ $vendor->provider->email }}</small>
                                    </div>
                                </div>
                                @else
                                <span class="text-muted">Unknown Vendor</span>
                                @endif
                            </td>
                            <td>{{ $vendor->transactions }}</td>
                            <td>₹{{ number_format($vendor->earnings, 2) }}</td>
                            <td>₹{{ number_format($vendor->transactions > 0 ? $vendor->earnings / $vendor->transactions : 0, 2) }}</td>
                            <td class="text-end">
                                @if($vendor->provider)
                                <a href="{{ route('admin.payments.index') }}?provider_id={{ $vendor->provider_id }}&start_date={{ $startDate }}&end_date={{ $endDate }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye me-1"></i> View Payments
                                </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">No vendor data available for this period.</td>
                        </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Method Details -->
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Payment Method Details</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th>Payment Method</th>
                            <th>Transactions</th>
                            <th>Total</th>
                            <th>Average</th>
                            <th>Percentage</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($paymentMethods as $method)
                        <tr>
                            <td>{{ (new App\Models\Payment(['payment_method' => $method->payment_method]))->getPaymentMethodDisplayAttribute() }}</td>
                            <td>{{ $method->count }}</td>
                            <td>₹{{ number_format($method->total, 2) }}</td>
                            <td>₹{{ number_format($method->count > 0 ? $method->total / $method->count : 0, 2) }}</td>
                            <td>{{ number_format(($method->total / $totalAmount) * 100, 1) }}%</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">No payment method data available for this period.</td>
                        </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const primaryColor = '#5e72e4';
        const warningColor = '#fb6340';
        const successColor = '#2dce89';
        const dangerColor = '#f5365c';
        const infoColor = '#11cdef';

        // Daily Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode(array_column($dailyStats, 'formatted_date')) !!},
        datasets: [{
            label: 'Daily Revenue',
            data: {!! json_encode(array_column($dailyStats, 'amount')) !!},
        fill: {
            target: 'origin',
                above: 'rgba(94, 114, 228, 0.1)',
        },
        borderColor: primaryColor,
            borderWidth: 2,
            tension: 0.4,
            pointBackgroundColor: primaryColor,
            pointRadius: 3
    }]
    },
        options: {
            responsive: true,
                maintainAspectRatio: false,
                plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '₹' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                        ticks: {
                        callback: function(value) {
                            return '₹' + value;
                        }
                    }
                }
            }
        }
    });

        // Payment Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Paid', 'Pending', 'Failed', 'Refunded'],
                datasets: [{
                    data: [{{ $paidCount }}, {{ $pendingCount }}, {{ $failedCount }}, {{ $refundedCount }}],
        backgroundColor: [
            successColor,
            warningColor,
            dangerColor,
            infoColor
        ],
            borderWidth: 0
    }]
    },
        options: {
            responsive: true,
                maintainAspectRatio: false,
                plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                            const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            },
            cutout: '70%'
        }
    });

        // Payment Methods Chart
        const methodCtx = document.getElementById('methodChart').getContext('2d');
        new Chart(methodCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($paymentMethods->pluck('payment_method')->map(function($method) {
            return (new App\Models\Payment(['payment_method' => $method]))->getPaymentMethodDisplayAttribute();
        })->toArray()) !!},
        datasets: [{
            label: 'Number of Transactions',
            data: {!! json_encode($paymentMethods->pluck('count')->toArray()) !!},
        backgroundColor: primaryColor,
            borderWidth: 0,
            borderRadius: 4
    }]
    },
        options: {
            responsive: true,
                maintainAspectRatio: false,
                plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

        // Revenue Breakdown Chart
        const breakdownCtx = document.getElementById('breakdownChart').getContext('2d');
        new Chart(breakdownCtx, {
            type: 'pie',
            data: {
                labels: ['Vendor Earnings', 'Platform Fees', 'Other Charges', 'GST'],
                datasets: [{
                    data: [
                        {{ $vendorEarnings }},
                    {{ $platformFees }},
        {{ $otherCharges }},
        {{ $gstAmount }}
    ],
        backgroundColor: [
            successColor,
            primaryColor,
            warningColor,
            infoColor
        ],
            borderWidth: 0
    }]
    },
        options: {
            responsive: true,
                maintainAspectRatio: false,
                plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                            const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                            return `${label}: ₹${value.toFixed(2)} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
    });
</script>
@endsection

@section('styles')
<style>
    .avatar {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background-color: #f8f9fa;
    }

    .avatar i {
        font-size: 20px;
        color: #5e72e4;
    }

    .bg-primary-soft {
        background-color: rgba(94, 114, 228, 0.1);
    }

    .bg-success-soft {
        background-color: rgba(45, 206, 137, 0.1);
    }

    .bg-info-soft {
        background-color: rgba(17, 205, 239, 0.1);
    }

    .bg-warning-soft {
        background-color: rgba(251, 99, 64, 0.1);
    }
</style>
@endsection
