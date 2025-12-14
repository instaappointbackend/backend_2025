<!-- resources/views/admin/payouts/show.blade.php -->
@extends('admin.layouts.app')

@section('title', 'Payout Request Details')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Payout Request Details</h1>
        <div>
            <a href="{{ route('admin.payouts.index') }}" class="btn btn-sm btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List
            </a>
            @if(in_array($payoutRequest->status, ['pending', 'processing']))
            <div class="btn-group ml-2">
                <button type="button" class="btn btn-sm btn-primary dropdown-toggle shadow-sm" data-toggle="dropdown">
                    <i class="fas fa-cog fa-sm text-white-50"></i> Actions
                </button>
                <div class="dropdown-menu">
                    @if($payoutRequest->status === 'pending')
                    <form action="{{ route('admin.payouts.status', $payoutRequest->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="status" value="processing">
                        <button type="submit" class="dropdown-item">Mark as Processing</button>
                    </form>
                    @endif
                    <a class="dropdown-item" href="#" data-toggle="modal" data-target="#completeModal">Mark as Completed</a>
                    <a class="dropdown-item" href="#" data-toggle="modal" data-target="#rejectModal">Reject Payout</a>
                </div>
            </div>
            @endif
        </div>
    </div>

    <div class="row">
        <!-- Payout Request Details Card -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Payout Request #{{ $payoutRequest->id }}</h6>
                    <span class="badge {{ $payoutRequest->getStatusBadgeClassAttribute() }} px-3 py-2">
                        {{ $payoutRequest->human_status }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="font-weight-bold">Provider Information</h5>
                            @if($payoutRequest->user)
                            <p>
                                <a href="{{ route('admin.payouts.provider_earnings', $payoutRequest->user_id) }}">
                                    <strong>{{ $payoutRequest->user->name }}</strong>
                                </a><br>
                                {{ $payoutRequest->user->email }}<br>
                                {{ $payoutRequest->user->phone ?? 'No phone number' }}
                            </p>
                            @else
                            <p>Provider information not available</p>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <h5 class="font-weight-bold">Bank Account Details</h5>
                            @if($payoutRequest->bankAccount)
                            <p>
                                <strong>Account Holder:</strong> {{ $payoutRequest->bankAccount->account_holder_name }}<br>
                                <strong>Bank Name:</strong> {{ $payoutRequest->bankAccount->bank_name }}<br>
                                <strong>Account #:</strong> {{ $payoutRequest->bankAccount->account_number }}<br>
                                <strong>IFSC Code:</strong> {{ $payoutRequest->bankAccount->ifsc_code }}<br>
                                <strong>Account Type:</strong> {{ ucfirst($payoutRequest->bankAccount->account_type) }}
                            </p>
                            @else
                            <p>Bank account information not available</p>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="font-weight-bold">Payout Details</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th>Amount</th>
                                    <td><span class="text-lg font-weight-bold">{{ $payoutRequest->formatted_amount }}</span></td>
                                </tr>
                                <tr>
                                    <th>Request Date</th>
                                    <td>{{ $payoutRequest->created_at->format('M d, Y h:i A') }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <span class="badge {{ $payoutRequest->getStatusBadgeClassAttribute() }}">
                                            {{ $payoutRequest->human_status }}
                                        </span>
                                    </td>
                                </tr>
                                @if($payoutRequest->status === 'completed')
                                <tr>
                                    <th>Transaction ID</th>
                                    <td>{{ $payoutRequest->transaction_id ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Transaction Date</th>
                                    <td>{{ $payoutRequest->transaction_date ? $payoutRequest->transaction_date->format('M d, Y') : 'N/A' }}</td>
                                </tr>
                                @endif
                                @if($payoutRequest->status === 'rejected')
                                <tr>
                                    <th>Rejection Reason</th>
                                    <td>{{ $payoutRequest->rejection_reason ?? 'N/A' }}</td>
                                </tr>
                                @endif
                                @if($payoutRequest->status === 'cancelled')
                                <tr>
                                    <th>Cancellation Date</th>
                                    <td>{{ $payoutRequest->cancellation_date ? $payoutRequest->cancellation_date->format('M d, Y') : 'N/A' }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>

                        <div class="col-md-6">
                            <h5 class="font-weight-bold">Provider Earnings Summary</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th>Total Earnings</th>
                                    <td>{{ $earningsSummary['formatted_total_earnings'] }}</td>
                                </tr>
                                <tr>
                                    <th>Withdrawn Amount</th>
                                    <td>{{ $earningsSummary['formatted_withdrawn_amount'] }}</td>
                                </tr>
                                <tr>
                                    <th>Pending Payouts</th>
                                    <td>{{ $earningsSummary['formatted_pending_amount'] }}</td>
                                </tr>
                                <tr>
                                    <th>Available Balance</th>
                                    <td><strong>{{ $earningsSummary['formatted_available_balance'] }}</strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Timeline Card -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Payout Status Timeline</h6>
                </div>
                <div class="card-body">
                    <div class="timeline-container">
                        <div class="timeline-item active">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h5 class="timeline-title">Request Created</h5>
                                <p class="timeline-date">{{ $payoutRequest->created_at->format('M d, Y h:i A') }}</p>
                                <p>Payout request of {{ $payoutRequest->formatted_amount }} was created</p>
                            </div>
                        </div>

                        @if($payoutRequest->status !== 'pending')
                        <div class="timeline-item {{ in_array($payoutRequest->status, ['processing', 'completed', 'rejected']) ? 'active' : '' }}">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h5 class="timeline-title">Processing</h5>
                                <p class="timeline-date">{{ $payoutRequest->updated_at->format('M d, Y h:i A') }}</p>
                                <p>Payout request marked as processing</p>
                            </div>
                        </div>
                        @endif

                        @if($payoutRequest->status === 'completed')
                        <div class="timeline-item active">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h5 class="timeline-title">Completed</h5>
                                <p class="timeline-date">{{ $payoutRequest->transaction_date ? $payoutRequest->transaction_date->format('M d, Y') : 'N/A' }}</p>
                                <p>
                                    Payout completed successfully<br>
                                    <strong>Transaction ID:</strong> {{ $payoutRequest->transaction_id }}
                                </p>
                            </div>
                        </div>
                        @elseif($payoutRequest->status === 'rejected')
                        <div class="timeline-item active">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h5 class="timeline-title">Rejected</h5>
                                <p class="timeline-date">{{ $payoutRequest->updated_at->format('M d, Y h:i A') }}</p>
                                <p>
                                    Payout request was rejected<br>
                                    <strong>Reason:</strong> {{ $payoutRequest->rejection_reason }}
                                </p>
                            </div>
                        </div>
                        @elseif($payoutRequest->status === 'cancelled')
                        <div class="timeline-item active">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h5 class="timeline-title">Cancelled</h5>
                                <p class="timeline-date">{{ $payoutRequest->cancellation_date ? $payoutRequest->cancellation_date->format('M d, Y h:i A') : $payoutRequest->updated_at->format('M d, Y h:i A') }}</p>
                                <p>Payout request was cancelled by the provider</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Complete Modal -->
<div class="modal fade" id="completeModal" tabindex="-1" role="dialog" aria-labelledby="completeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.payouts.status', $payoutRequest->id) }}" method="POST">
                @csrf
                <input type="hidden" name="status" value="completed">
                <div class="modal-header">
                    <h5 class="modal-title" id="completeModalLabel">Complete Payout</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Transaction ID <span class="text-danger">*</span></label>
                        <input type="text" name="transaction_id" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Transaction Date <span class="text-danger">*</span></label>
                        <input type="date" name="transaction_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Complete Payout</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.payouts.status', $payoutRequest->id) }}" method="POST">
                @csrf
                <input type="hidden" name="status" value="rejected">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel">Reject Payout</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Payout</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('styles')
<style>
    .timeline-container {
        position: relative;
        padding-left: 20px;
    }

    .timeline-container::before {
        content: '';
        position: absolute;
        top: 0;
        bottom: 0;
        left: 9px;
        width: 2px;
        background: #e3e6f0;
    }

    .timeline-item {
        position: relative;
        margin-bottom: 25px;
        opacity: 0.5;
    }

    .timeline-item.active {
        opacity: 1;
    }

    .timeline-marker {
        position: absolute;
        top: 5px;
        left: -20px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #e3e6f0;
        border: 2px solid #fff;
    }

    .timeline-item.active .timeline-marker {
        background: #4e73df;
    }

    .timeline-content {
        padding-left: 15px;
    }

    .timeline-title {
        margin-top: 0;
        margin-bottom: 5px;
        font-size: 16px;
        font-weight: bold;
    }

    .timeline-date {
        font-size: 12px;
        color: #858796;
        margin-bottom: 8px;
    }
</style>
@endsection
