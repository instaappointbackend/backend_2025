<!-- resources/views/admin/payments/edit.blade.php -->
@extends('admin.layouts.app')

@section('title', 'Edit Payment')

@section('page-title', 'Edit Payment #' . $payment->id)

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb m-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.payments.index') }}">Payments</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.payments.show', $payment->id) }}">Details</a></li>
        <li class="breadcrumb-item active" aria-current="page">Edit</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Edit Payment Information</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.payments.update', $payment->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Editing payment information should be done with caution as it may affect financial records and reporting.
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="transaction_id" class="form-label">Transaction ID</label>
                            <input type="text" class="form-control" id="transaction_id" name="transaction_id" value="{{ $payment->transaction_id }}">
                            <small class="text-muted">External payment gateway transaction ID</small>
                        </div>

                        <div class="col-md-6">
                            <label for="status" class="form-label">Payment Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="pending" {{ $payment->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="paid" {{ $payment->status == 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="failed" {{ $payment->status == 'failed' ? 'selected' : '' }}>Failed</option>
                                <option value="refunded" {{ $payment->status == 'refunded' ? 'selected' : '' }}>Refunded</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="payment_method" class="form-label">Payment Method</label>
                            <select class="form-select" id="payment_method" name="payment_method">
                                <option value="">Select Payment Method</option>
                                <option value="cash" {{ $payment->payment_method == 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="card" {{ $payment->payment_method == 'card' ? 'selected' : '' }}>Credit/Debit Card</option>
                                <option value="upi" {{ $payment->payment_method == 'upi' ? 'selected' : '' }}>UPI</option>
                                <option value="netbanking" {{ $payment->payment_method == 'netbanking' ? 'selected' : '' }}>Net Banking</option>
                                <option value="wallet" {{ $payment->payment_method == 'wallet' ? 'selected' : '' }}>Wallet</option>
                                <option value="phonepe" {{ $payment->payment_method == 'phonepe' ? 'selected' : '' }}>PhonePe</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="currency" class="form-label">Currency</label>
                            <input type="text" class="form-control" id="currency" name="currency" value="{{ $payment->currency ?? 'INR' }}" readonly>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="amount" class="form-label">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" class="form-control" id="amount" name="amount" value="{{ $payment->amount }}" readonly>
                            </div>
                            <small class="text-muted">To modify the amount, update the payment breakdown fields</small>
                        </div>

                        <div class="col-md-6">
                            <label for="created_at" class="form-label">Payment Date</label>
                            <input type="text" class="form-control" id="created_at" value="{{ $payment->created_at->format('Y-m-d H:i:s') }}" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        <small class="text-muted">Add notes about why this payment is being updated</small>
                    </div>

                    <div class="d-flex justify-content-end">
                        <a href="{{ route('admin.payments.show', $payment->id) }}" class="btn btn-secondary me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Payment Info Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Payment Information</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted">Payment ID</label>
                    <p>{{ $payment->id }}</p>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted">Transaction ID</label>
                    <p>{{ $payment->transaction_id }}</p>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted">Amount</label>
                    <p class="fw-bold">{{ $payment->formatted_amount }}</p>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted">Status</label>
                    <p>
                        @if($payment->status == 'pending')
                        <span class="badge bg-warning">Pending</span>
                        @elseif($payment->status == 'paid')
                        <span class="badge bg-success">Paid</span>
                        @elseif($payment->status == 'failed')
                        <span class="badge bg-danger">Failed</span>
                        @elseif($payment->status == 'refunded')
                        <span class="badge bg-info">Refunded</span>
                        @endif
                    </p>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted">Payment Method</label>
                    <p>{{ $payment->getPaymentMethodDisplayAttribute() }}</p>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted">Date</label>
                    <p>{{ $payment->created_at->format('M d, Y H:i:s') }}</p>
                </div>
            </div>
        </div>

        <!-- Associated Appointment Card -->
        @if($payment->appointment)
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Associated Appointment</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted">Appointment ID</label>
                    <p>{{ $payment->appointment->id }}</p>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted">Date & Time</label>
                    <p>{{ $payment->appointment->date->format('M d, Y') }} at
                        {{ Carbon\Carbon::parse($payment->appointment->start_time)->format('g:i A') }}</p>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted">Service</label>
                    <p>
                        @if($payment->appointment->service)
                        {{ $payment->appointment->service->name }}
                        @elseif($payment->appointment->comboService)
                        {{ $payment->appointment->comboService->name }}
                        @else
                        <span class="text-muted">N/A</span>
                        @endif
                    </p>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted">Status</label>
                    <p>
                        @if($payment->appointment->status == 'pending')
                        <span class="badge bg-warning">Pending</span>
                        @elseif($payment->appointment->status == 'confirmed')
                        <span class="badge bg-primary">Confirmed</span>
                        @elseif($payment->appointment->status == 'completed')
                        <span class="badge bg-success">Completed</span>
                        @elseif($payment->appointment->status == 'cancelled')
                        <span class="badge bg-danger">Cancelled</span>
                        @endif
                    </p>
                </div>

                <div class="d-grid">
                    <a href="{{ route('admin.appointments.show', $payment->appointment->id) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye me-1"></i> View Appointment
                    </a>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
