<!-- resources/views/admin/payouts/index.blade.php -->
@extends('admin.layouts.app')

@section('title', 'Payout Requests')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Payout Requests</h1>
        <div>
            <a href="{{ route('admin.payouts.pending') }}" class="btn btn-sm btn-info shadow-sm">
                <i class="fas fa-list fa-sm text-white-50"></i> Pending Payouts
            </a>
            <a href="{{ route('admin.payouts.reports') }}" class="btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-chart-bar fa-sm text-white-50"></i> View Reports
            </a>
            <a href="{{ route('admin.payouts.export') }}" class="btn btn-sm btn-success shadow-sm ml-2">
                <i class="fas fa-download fa-sm text-white-50"></i> Export Data
            </a>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filters</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.payouts.index') }}" class="row">
                <!-- Status Filter -->
                <div class="col-md-3 mb-3">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Statuses</option>
                        @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>
                        {{ $label }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Date Range Filters -->
                <div class="col-md-3 mb-3">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>

                <div class="col-md-3 mb-3">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>

                <!-- Provider Filter -->
                <div class="col-md-3 mb-3">
                    <label for="user_id">Provider</label>
                    <select id="user_id" name="user_id" class="form-control">
                        <option value="">All Providers</option>
                        @foreach($providers as $provider)
                        <option value="{{ $provider->id }}" {{ request('user_id') == $provider->id ? 'selected' : '' }}>
                        {{ $provider->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Amount Range Filters -->
                <div class="col-md-3 mb-3">
                    <label for="min_amount">Min Amount (₹)</label>
                    <input type="number" id="min_amount" name="min_amount" class="form-control" value="{{ request('min_amount') }}">
                </div>

                <div class="col-md-3 mb-3">
                    <label for="max_amount">Max Amount (₹)</label>
                    <input type="number" id="max_amount" name="max_amount" class="form-control" value="{{ request('max_amount') }}">
                </div>

                <!-- Filter Button -->
                <div class="col-md-3 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <a href="{{ route('admin.payouts.index') }}" class="btn btn-secondary ml-2">
                        <i class="fas fa-sync"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Batch Actions Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Batch Actions</h6>
        </div>
        <div class="card-body">
            <form id="batch-action-form" method="POST" action="{{ route('admin.payouts.batch') }}">
                @csrf
                <div class="row">
                    <div class="col-md-4">
                        <select id="batch-action" name="action" class="form-control">
                            <option value="">Select Action</option>
                            <option value="mark_processing">Mark as Processing</option>
                            <option value="mark_completed">Mark as Completed</option>
                            <option value="mark_rejected">Mark as Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-4" id="rejection-reason-container" style="display:none;">
                        <input type="text" name="rejection_reason" class="form-control" placeholder="Enter rejection reason">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary" id="apply-batch-action" disabled>
                            Apply to Selected
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Payout Requests Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Payout Requests</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="payoutTable" width="100%" cellspacing="0">
                    <thead>
                    <tr>
                        <th width="40">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="select-all">
                                <label class="custom-control-label" for="select-all"></label>
                            </div>
                        </th>
                        <th>ID</th>
                        <th>Provider</th>
                        <th>Amount</th>
                        <th>Bank Account</th>
                        <th>Status</th>
                        <th>Request Date</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($payoutRequests as $payout)
                    <tr>
                        <td>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input payout-checkbox"
                                       id="payout-{{ $payout->id }}"
                                       value="{{ $payout->id }}"
                                       data-status="{{ $payout->status }}"
                                       {{ !in_array($payout->status, ['pending', 'processing']) ? 'disabled' : '' }}>
                                <label class="custom-control-label" for="payout-{{ $payout->id }}"></label>
                            </div>
                        </td>
                        <td>{{ $payout->id }}</td>
                        <td>
                            @if($payout->user)
                            <a href="{{ route('admin.payouts.provider_earnings', $payout->user_id) }}">
                                {{ $payout->user->name }}
                            </a>
                            @else
                            Unknown
                            @endif
                        </td>
                        <td>{{ $payout->formatted_amount }}</td>
                        <td>
                            @if($payout->bankAccount)
                            {{ $payout->bankAccount->bank_name }} - {{ substr($payout->bankAccount->account_number, -4) }}
                            @else
                            N/A
                            @endif
                        </td>
                        <td>
                                    <span class="badge {{ $payout->getStatusBadgeClassAttribute() }}">
                                        {{ $payout->human_status }}
                                    </span>
                        </td>
                        <td>{{ $payout->created_at->format('M d, Y h:i A') }}</td>
                        <td>
                            <a href="{{ route('admin.payouts.show', $payout->id) }}" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if(in_array($payout->status, ['pending', 'processing']))
                            <div class="btn-group">
                                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Action
                                </button>
                                <div class="dropdown-menu">
                                    @if($payout->status === 'pending')
                                    <form action="{{ route('admin.payouts.status', $payout->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="status" value="processing">
                                        <button type="submit" class="dropdown-item">Mark as Processing</button>
                                    </form>
                                    @endif
                                    <a class="dropdown-item" href="#" data-toggle="modal" data-target="#completeModal{{ $payout->id }}">
                                        Mark as Completed
                                    </a>
                                    <a class="dropdown-item" href="#" data-toggle="modal" data-target="#rejectModal{{ $payout->id }}">
                                        Reject Payout
                                    </a>
                                </div>
                            </div>

                            <!-- Complete Modal -->
                            <div class="modal fade" id="completeModal{{ $payout->id }}" tabindex="-1" role="dialog" aria-labelledby="completeModalLabel{{ $payout->id }}" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <form action="{{ route('admin.payouts.status', $payout->id) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="status" value="completed">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="completeModalLabel{{ $payout->id }}">Complete Payout</h5>
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
                            <div class="modal fade" id="rejectModal{{ $payout->id }}" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel{{ $payout->id }}" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <form action="{{ route('admin.payouts.status', $payout->id) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="status" value="rejected">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="rejectModalLabel{{ $payout->id }}">Reject Payout</h5>
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
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center">No payout requests found</td>
                    </tr>
                    @endforelse
                    </tbody>
                </table>

                <div class="mt-3">
                    {{ $payoutRequests->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Select all checkboxes
        document.getElementById('select-all')?.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.payout-checkbox:not(:disabled)');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBatchActionButton();
        });

        // Individual checkbox change
        document.querySelectorAll('.payout-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', updateBatchActionButton);
        });

        // Batch action change
        document.getElementById('batch-action')?.addEventListener('change', function() {
            const rejectionContainer = document.getElementById('rejection-reason-container');
            if (this.value === 'mark_rejected') {
                rejectionContainer.style.display = 'block';
            } else {
                rejectionContainer.style.display = 'none';
            }
            updateBatchActionButton();
        });

        // Batch action form submission
        const batchForm = document.getElementById('batch-action-form');
        if (batchForm) {
            batchForm.addEventListener('submit', function(e) {
                const action = document.getElementById('batch-action').value;
                if (!action) {
                    e.preventDefault();
                    alert('Please select an action.');
                    return false;
                }

                if (action === 'mark_rejected') {
                    const reason = document.querySelector('input[name="rejection_reason"]').value.trim();
                    if (!reason) {
                        e.preventDefault();
                        alert('Please enter a rejection reason.');
                        return false;
                    }
                }

                // Add the selected payout IDs to the form
                const checkedBoxes = document.querySelectorAll('.payout-checkbox:checked');
                checkedBoxes.forEach(checkbox => {
                    const payoutId = checkbox.value;
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'payout_ids[]';
                    hiddenInput.value = payoutId;
                    batchForm.appendChild(hiddenInput);

                    // For completed action, add transaction details
                    if (action === 'mark_completed') {
                        const transactionInput = document.createElement('input');
                        transactionInput.type = 'hidden';
                        transactionInput.name = 'transaction_details[' + payoutId + ']';
                        transactionInput.value = 'BATCH_TXN_' + Date.now() + '_' + payoutId;
                        batchForm.appendChild(transactionInput);
                    }
                });
            });
        }

        // Update batch action button state
        function updateBatchActionButton() {
            const checkedBoxes = document.querySelectorAll('.payout-checkbox:checked');
            const actionSelected = document.getElementById('batch-action')?.value;
            const applyButton = document.getElementById('apply-batch-action');

            if (applyButton) {
                applyButton.disabled = !(checkedBoxes.length > 0 && actionSelected);
            }
        }
    });
</script>
@endsection
