<!-- resources/views/admin/payouts/provider_earnings.blade.php -->
@extends('admin.layouts.app')

@section('title', 'Provider Earnings')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Provider Earnings: {{ $provider->name }}</h1>
        <div>
            <a href="{{ route('admin.payouts.index') }}" class="btn btn-sm btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Payouts
            </a>
            <button class="btn btn-sm btn-primary shadow-sm ml-2" data-toggle="modal" data-target="#createPayoutModal">
                <i class="fas fa-plus fa-sm text-white-50"></i> Create Manual Payout
            </button>
        </div>
    </div>

    <!-- Earnings Summary Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Earnings
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $earningsSummary['formatted_total_earnings'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-wallet fa-2x text-gray-300"></i>
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
                                Available Balance
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $earningsSummary['formatted_available_balance'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
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
                                Withdrawn Amount
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $earningsSummary['formatted_withdrawn_amount'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                                Pending Payouts
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $earningsSummary['formatted_pending_amount'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Bank Accounts Card -->
        <div class="col-xl-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Bank Accounts</h6>
                </div>
                <div class="card-body">
                    @if(count($bankAccounts) > 0)
                    <div class="table-responsive">
                        <table class="table table-borderless">
                            <thead>
                            <tr>
                                <th>Bank</th>
                                <th>Account</th>
                                <th>Default</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($bankAccounts as $account)
                            <tr>
                                <td>{{ $account->bank_name }}</td>
                                <td>
                                    {{ $account->account_holder_name }}<br>
                                    <small class="text-muted">{{ substr($account->account_number, 0, 4) }}...{{ substr($account->account_number, -4) }}</small>
                                </td>
                                <td>
                                    @if($account->is_default)
                                    <span class="badge badge-success">Default</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-3">
                        <p class="mb-0">No bank accounts added yet.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Payout History Card -->
        <div class="col-xl-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Payout History</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Transaction</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($payoutHistory as $payout)
                            <tr>
                                <td>{{ $payout->created_at->format('M d, Y') }}</td>
                                <td>{{ $payout->formatted_amount }}</td>
                                <td>
                                            <span class="badge {{ $payout->getStatusBadgeClassAttribute() }}">
                                                {{ $payout->human_status }}
                                            </span>
                                </td>
                                <td>
                                    @if($payout->status === 'completed')
                                    {{ $payout->transaction_id ?? 'N/A' }}
                                    @else
                                    -
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.payouts.show', $payout->id) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center">No payout history found</td>
                            </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Payments Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Recent Payments Received</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Service</th>
                        <th>Total</th>
                        <th>Platform Fee</th>
                        <th>Earnings</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($paymentHistory as $payment)
                    <tr>
                        <td>{{ $payment->created_at->format('M d, Y') }}</td>
                        <td>{{ $payment->user ? $payment->user->name : 'N/A' }}</td>
                        <td>
                            @if($payment->appointment)
                            @if($payment->appointment->service)
                            {{ $payment->appointment->service->name }}
                            @elseif($payment->appointment->comboService)
                            {{ $payment->appointment->comboService->name }}
                            @else
                            Unknown Service
                            @endif
                            @else
                            N/A
                            @endif
                        </td>
                        <td>{{ $payment->formatted_amount }}</td>
                        <td>{{ $payment->formatted_platform_fee ?? '₹0.00' }}</td>
                        <td>{{ $payment->formatted_vendor_earnings }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">No payment history found</td>
                    </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create Manual Payout Modal -->
<div class="modal fade" id="createPayoutModal" tabindex="-1" role="dialog" aria-labelledby="createPayoutModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.payouts.create_manual') }}" method="POST">
                @csrf
                <input type="hidden" name="user_id" value="{{ $provider->id }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="createPayoutModalLabel">Create Manual Payout</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Available Balance:</strong> {{ $earningsSummary['formatted_available_balance'] }}
                    </div>

                    <div class="form-group">
                        <label>Bank Account <span class="text-danger">*</span></label>
                        <select name="bank_account_id" class="form-control" required>
                            <option value="">Select Bank Account</option>
                            @foreach($bankAccounts as $account)
                            <option value="{{ $account->id }}" {{ $account->is_default ? 'selected' : '' }}>
                                {{ $account->bank_name }} - {{ substr($account->account_number, -4) }}
                                {{ $account->is_default ? '(Default)' : '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Amount (₹) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control"
                               min="1" max="{{ $earningsSummary['available_balance'] }}"
                               step="0.01" required>
                    </div>

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
                    <button type="submit" class="btn btn-success">Create Payout</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
