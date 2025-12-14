<!-- resources/views/admin/payments/show.blade.php -->
@extends('admin.layouts.app')

@section('title', 'Payment Details')

@section('page-title', 'Payment Details')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb m-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.payments.index') }}">Payments</a></li>
        <li class="breadcrumb-item active" aria-current="page">Details</li>
    </ol>
</nav>
@endsection

@section('page-actions')
<a href="{{ route('admin.payments.edit', $payment->id) }}" class="btn btn-primary me-2">
    <i class="fas fa-edit me-1"></i> Edit Payment
</a>
<a href="{{ route('admin.payments.index') }}" class="btn btn-secondary">
    <i class="fas fa-arrow-left me-1"></i> Back to List
</a>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        <!-- Payment Details Card -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Payment #{{ $payment->id }}</h5>
                <div>
                    @if($payment->status == 'pending')
                    <span class="badge bg-warning">Pending</span>
                    @elseif($payment->status == 'paid')
                    <span class="badge bg-success">Paid</span>
                    @elseif($payment->status == 'failed')
                    <span class="badge bg-danger">Failed</span>
                    @elseif($payment->status == 'refunded')
                    <span class="badge bg-info">Refunded</span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <h6 class="text-muted mb-1">Transaction ID</h6>
                        <p class="fs-5 fw-bold">{{ $payment->transaction_id }}</p>
                    </div>
                    <div class="col-md-6 mb-4">
                        <h6 class="text-muted mb-1">Payment Method</h6>
                        <p class="fs-5">{{ $payment->getPaymentMethodDisplayAttribute() }}</p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <h6 class="text-muted mb-1">Amount</h6>
                        <p class="fs-5 fw-bold">{{ $payment->formatted_amount }}</p>
                    </div>
                    <div class="col-md-6 mb-4">
                        <h6 class="text-muted mb-1">Payment Date</h6>
                        <p class="fs-5">{{ $payment->created_at->format('M d, Y H:i:s') }}</p>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <h6 class="text-muted mb-2">Customer</h6>
                        @if($payment->user)
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <img src="{{ $payment->user->profile_picture ? asset('storage/' . $payment->user->profile_picture) : asset('admin/images/default-avatar.png') }}" alt="{{ $payment->user->name }}" class="rounded-circle" width="50" height="50">
                            </div>
                            <div>
                                <p class="fs-5 fw-bold mb-0">{{ $payment->user->name }}</p>
                                <p class="text-muted mb-0">{{ $payment->user->email }}</p>
                                @if($payment->user->phone)
                                <p class="mb-0">{{ $payment->user->phone }}</p>
                                @endif
                            </div>
                        </div>
                        @else
                        <p class="text-muted">Customer information not available</p>
                        @endif
                    </div>
                    <div class="col-md-6 mb-4">
                        <h6 class="text-muted mb-2">Provider</h6>
                        @if($payment->provider)
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <img src="{{ $payment->provider->profile_picture ? asset('storage/' . $payment->provider->profile_picture) : asset('admin/images/default-avatar.png') }}" alt="{{ $payment->provider->name }}" class="rounded-circle" width="50" height="50">
                            </div>
                            <div>
                                <p class="fs-5 fw-bold mb-0">{{ $payment->provider->name }}</p>
                                <p class="text-muted mb-0">{{ $payment->provider->email }}</p>
                                @if($payment->provider->phone)
                                <p class="mb-0">{{ $payment->provider->phone }}</p>
                                @endif
                            </div>
                        </div>
                        @else
                        <p class="text-muted">Provider information not available</p>
                        @endif
                    </div>
                </div>

                <hr>

                <!-- Appointment Information -->
                @if($payment->appointment)
                <div class="mb-4">
                    <h6 class="text-muted mb-2">Appointment Information</h6>
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h5 class="card-title mb-1">Appointment #{{ $payment->appointment->id }}</h5>
                                    <p class="card-subtitle text-muted">
                                        {{ $payment->appointment->date->format('M d, Y') }} at
                                        {{ Carbon\Carbon::parse($payment->appointment->start_time)->format('g:i A') }} -
                                        {{ Carbon\Carbon::parse($payment->appointment->end_time)->format('g:i A') }}
                                    </p>
                                </div>
                                <span class="badge bg-{{ $payment->appointment->status == 'pending' ? 'warning' : ($payment->appointment->status == 'confirmed' ? 'primary' : ($payment->appointment->status == 'completed' ? 'success' : 'danger')) }}">
                                        {{ ucfirst($payment->appointment->status) }}
                                    </span>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <h6 class="mb-1">Service</h6>
                                    <p class="mb-0">
                                        @if($payment->appointment->service)
                                        {{ $payment->appointment->service->name }}
                                        <span class="badge bg-secondary">Regular Service</span>
                                        @elseif($payment->appointment->comboService)
                                        {{ $payment->appointment->comboService->name }}
                                        <span class="badge bg-secondary">Combo Service</span>
                                        @else
                                        <span class="text-muted">N/A</span>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="text-end">
                                <a href="{{ route('admin.appointments.show', $payment->appointment->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye me-1"></i> View Appointment
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Payment Breakdown -->
                <div class="mb-4">
                    <h6 class="text-muted mb-2">Payment Breakdown</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tbody>
                            <tr>
                                <th>Original Price</th>
                                <td class="text-end">{{ $payment->formatted_original_price ?? '₹' . number_format($payment->original_price ?? 0, 2) }}</td>
                            </tr>

                            @if($payment->discount_amount && $payment->discount_amount > 0)
                            <tr>
                                <th>Discount</th>
                                <td class="text-end text-danger">-{{ $payment->formatted_discount_amount ?? '₹' . number_format($payment->discount_amount, 2) }}</td>
                            </tr>
                            @endif

                            @if($payment->home_visit_fee && $payment->home_visit_fee > 0)
                            <tr>
                                <th>Home Visit Fee</th>
                                <td class="text-end">{{ $payment->formatted_home_visit_fee ?? '₹' . number_format($payment->home_visit_fee, 2) }}</td>
                            </tr>
                            @endif

                            @if($payment->additional_services_fee && $payment->additional_services_fee > 0)
                            <tr>
                                <th>Additional Services</th>
                                <td class="text-end">{{ $payment->formatted_additional_services_fee ?? '₹' . number_format($payment->additional_services_fee, 2) }}</td>
                            </tr>
                            @endif

                            <tr>
                                <th>Platform Fee</th>
                                <td class="text-end">{{ $payment->formatted_platform_fee ?? '₹' . number_format($payment->platform_fee ?? 8, 2) }}</td>
                            </tr>

                            <tr>
                                <th>Other Charges</th>
                                <td class="text-end">{{ $payment->formatted_other_charges ?? '₹' . number_format($payment->other_charges ?? 0, 2) }}</td>
                            </tr>

                            <tr>
                                <th>GST</th>
                                <td class="text-end">{{ $payment->formatted_gst_amount ?? '₹' . number_format($payment->gst_amount ?? 0, 2) }}</td>
                            </tr>

                            <tr class="table-light">
                                <th>Total Amount</th>
                                <td class="text-end fw-bold">{{ $payment->formatted_amount }}</td>
                            </tr>

                            <tr class="table-success">
                                <th>Provider Earnings</th>
                                <td class="text-end">{{ $payment->formatted_vendor_earnings ?? '₹' . number_format($payment->vendor_earnings ?? $payment->booking_price ?? 0, 2) }}</td>
                            </tr>

                            <tr class="table-primary">
                                <th>Admin Earnings</th>
                                <td class="text-end">{{ $payment->formatted_admin_earnings ?? '₹' . number_format($payment->admin_earnings ?? (($payment->platform_fee ?? 8) + ($payment->other_charges ?? 0) + ($payment->gst_amount ?? 0)), 2) }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Additional Information -->
                @if($payment->coupon_code || $payment->offer_title || $payment->additional_notes)
                <div class="mb-4">
                    <h6 class="text-muted mb-2">Additional Information</h6>
                    <div class="row">
                        @if($payment->coupon_code)
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Coupon Code</label>
                                <p class="form-control-static">{{ $payment->coupon_code }}</p>
                            </div>
                        </div>
                        @endif

                        @if($payment->offer_title)
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Offer Applied</label>
                                <p class="form-control-static">{{ $payment->offer_title }}</p>
                            </div>
                        </div>
                        @endif

                        @if($payment->additional_notes)
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <p class="form-control-static">{{ $payment->additional_notes }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Payment Timeline -->
                @if(is_array($payment->payment_details) || is_object($payment->payment_details))
                <div class="mb-4">
                    <h6 class="text-muted mb-2">Payment Timeline</h6>
                    <ul class="timeline">
                        <li class="timeline-item">
                            <span class="timeline-point bg-info"></span>
                            <div class="timeline-event">
                                <div class="timeline-header">
                                    <h6 class="mb-0">Payment Created</h6>
                                    <small class="text-muted">{{ $payment->created_at->format('M d, Y H:i:s') }}</small>
                                </div>
                                <div class="timeline-content">
                                    <p>Payment created with status: <span class="badge bg-secondary">{{ ucfirst($payment->status) }}</span></p>
                                </div>
                            </div>
                        </li>

                        @if(isset($payment->payment_details['status_update']))
                        <li class="timeline-item">
                            <span class="timeline-point bg-warning"></span>
                            <div class="timeline-event">
                                <div class="timeline-header">
                                    <h6 class="mb-0">Status Updated</h6>
                                    <small class="text-muted">{{ \Carbon\Carbon::parse($payment->payment_details['status_update']['updated_at'])->format('M d, Y H:i:s') }}</small>
                                </div>
                                <div class="timeline-content">
                                    <p>Status changed from <span class="badge bg-secondary">{{ ucfirst($payment->payment_details['status_update']['previous_status']) }}</span> to
                                        <span class="badge bg-secondary">{{ ucfirst($payment->payment_details['status_update']['new_status']) }}</span></p>

                                    @if(isset($payment->payment_details['status_update']['notes']))
                                    <p class="text-muted">Notes: {{ $payment->payment_details['status_update']['notes'] }}</p>
                                    @endif
                                </div>
                            </div>
                        </li>
                        @endif

                        @if(isset($payment->payment_details['refund']))
                        <li class="timeline-item">
                            <span class="timeline-point bg-danger"></span>
                            <div class="timeline-event">
                                <div class="timeline-header">
                                    <h6 class="mb-0">Payment Refunded</h6>
                                    <small class="text-muted">{{ \Carbon\Carbon::parse($payment->payment_details['refund']['initiated_at'])->format('M d, Y H:i:s') }}</small>
                                </div>
                                <div class="timeline-content">
                                    <p>Refund ID: {{ $payment->payment_details['refund']['refund_id'] }}</p>
                                    <p>Amount: ₹{{ number_format($payment->payment_details['refund']['amount'], 2) }}</p>
                                    <p>Reason: {{ $payment->payment_details['refund']['reason'] }}</p>
                                </div>
                            </div>
                        </li>
                        @endif
                    </ul>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Actions Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.payments.edit', $payment->id) }}" class="btn btn-primary">
                        <i class="fas fa-edit me-1"></i> Edit Payment
                    </a>

                    @if($payment->status === 'paid')
                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#refundModal">
                        <i class="fas fa-undo me-1"></i> Refund Payment
                    </button>
                    @endif

                    @if($payment->appointment)
                    <a href="{{ route('admin.appointments.show', $payment->appointment->id) }}" class="btn btn-info">
                        <i class="fas fa-calendar me-1"></i> View Appointment
                    </a>
                    @endif

                    <a href="{{ route('admin.payments.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to List
                    </a>
                </div>
            </div>
        </div>

        <!-- Status Card -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Status</h5>
                <div>
                    @if($payment->status == 'pending')
                    <span class="badge bg-warning">Pending</span>
                    @elseif($payment->status == 'paid')
                    <span class="badge bg-success">Paid</span>
                    @elseif($payment->status == 'failed')
                    <span class="badge bg-danger">Failed</span>
                    @elseif($payment->status == 'refunded')
                    <span class="badge bg-info">Refunded</span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.payments.update', $payment->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="status" class="form-label">Update Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="pending" {{ $payment->status == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="paid" {{ $payment->status == 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="failed" {{ $payment->status == 'failed' ? 'selected' : '' }}>Failed</option>
                            <option value="refunded" {{ $payment->status == 'refunded' ? 'selected' : '' }}>Refunded</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Status Update Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Reason for status change"></textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Refund Modal -->
<div class="modal fade" id="refundModal" tabindex="-1" aria-labelledby="refundModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.payments.refund', $payment->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="refundModalLabel">Refund Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Refunding this payment will change the payment status to 'Refunded'. This action cannot be undone.
                    </div>

                    <div class="mb-3">
                        <label for="refund_amount" class="form-label">Refund Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" step="0.01" class="form-control" id="refund_amount" name="refund_amount"
                                   value="{{ $payment->amount }}" max="{{ $payment->amount }}" required>
                        </div>
                        <small class="text-muted">Maximum refund amount: {{ $payment->formatted_amount }}</small>
                    </div>

                    <div class="mb-3">
                        <label for="refund_reason" class="form-label">Refund Reason</label>
                        <textarea class="form-control" id="refund_reason" name="refund_reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Process Refund</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('styles')
<style>
    .timeline {
        margin: 0;
        padding: 0;
        list-style: none;
        position: relative;
    }

    .timeline::before {
        content: '';
        position: absolute;
        top: 0;
        left: 16px;
        height: 100%;
        width: 2px;
        background: #dee2e6;
        z-index: 1;
    }

    .timeline-item {
        position: relative;
        padding-left: 40px;
        padding-bottom: 20px;
    }

    .timeline-item:last-child {
        padding-bottom: 0;
    }

    .timeline-point {
        position: absolute;
        left: 9px;
        top: 0;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        z-index: 2;
    }

    .timeline-event {
        position: relative;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 4px;
    }

    .timeline-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .timeline-content {
        margin-bottom: 0;
    }
</style>
@endsection
