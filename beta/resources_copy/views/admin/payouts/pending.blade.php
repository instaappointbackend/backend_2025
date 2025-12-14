<!-- resources/views/admin/payouts/pending.blade.php -->
@extends('admin.layouts.app')

@section('title', 'Pending Payouts')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Pending Payouts by Provider</h1>
        <div>
            <a href="{{ route('admin.payouts.index') }}" class="btn btn-sm btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Payouts
            </a>
        </div>
    </div>

    <!-- Pending Payouts Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Providers with Pending Payouts</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="pendingPayoutsTable" width="100%" cellspacing="0">
                    <thead>
                    <tr>
                        <th>Provider</th>
                        <th>Pending Amount</th>
                        <th>Pending Requests</th>
                        <th>Total Earnings</th>
                        <th>Available Balance</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($vendors as $vendor)
                    <tr>
                        <td>
                            {{ $vendor->name }}<br>
                            <small class="text-muted">{{ $vendor->email }}</small>
                        </td>
                        <td class="font-weight-bold">₹{{ number_format($vendor->pending_payout_amount, 2) }}</td>
                        <td>{{ $vendor->pending_payout_count }}</td>
                        <td>{{ $vendor->earnings_data['formatted_total_earnings'] }}</td>
                        <td>{{ $vendor->earnings_data['formatted_available_balance'] }}</td>
                        <td>
                            <a href="{{ route('admin.payouts.provider_earnings', $vendor->id) }}"
                               class="btn btn-sm btn-primary">
                                View Details
                            </a>
                            <button type="button" class="btn btn-sm btn-success process-batch-btn"
                                    data-toggle="modal"
                                    data-target="#processBatchModal"
                                    data-vendor-id="{{ $vendor->id }}"
                                    data-vendor-name="{{ $vendor->name }}"
                                    data-pending-amount="{{ $vendor->pending_payout_amount }}">
                                Process All
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">No pending payouts found</td>
                    </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Process Batch Modal -->
<div class="modal fade" id="processBatchModal" tabindex="-1" role="dialog" aria-labelledby="processBatchModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.payouts.batch') }}" method="POST" id="batchProcessForm">
                @csrf
                <input type="hidden" name="action" value="mark_completed">
                <div class="modal-header">
                    <h5 class="modal-title" id="processBatchModalLabel">Process All Pending Payouts</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="vendor-info mb-3"></div>

                    <div class="form-group">
                        <label>Transaction ID Prefix <span class="text-danger">*</span></label>
                        <input type="text" name="transaction_prefix" class="form-control"
                               value="BATCH_{{ date('Ymd') }}_" required>
                        <small class="form-text text-muted">
                            A unique ID will be generated for each payout using this prefix.
                        </small>
                    </div>

                    <div class="form-group">
                        <label>Transaction Date <span class="text-danger">*</span></label>
                        <input type="date" name="transaction_date" class="form-control"
                               value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> This action will mark all pending payout requests for this provider as completed.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Process All Payouts</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle modal show event for process all button
        const processBtns = document.querySelectorAll('.process-batch-btn');
        processBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const vendorId = this.getAttribute('data-vendor-id');
                const vendorName = this.getAttribute('data-vendor-name');
                const pendingAmount = parseFloat(this.getAttribute('data-pending-amount'));

                const modal = document.getElementById('processBatchModal');
                const vendorInfoDiv = modal.querySelector('.vendor-info');
                vendorInfoDiv.innerHTML = `
                    <strong>Provider:</strong> ${vendorName}<br>
                    <strong>Pending Amount:</strong> ₹${pendingAmount.toFixed(2)}
                `;

                // Reset form and clear any previous alerts
                document.getElementById('batchProcessForm').reset();
                modal.querySelectorAll('.alert').forEach(alert => alert.remove());

                // Make an AJAX request to get the pending payouts
                fetch(`{{ route('admin.api.get_pending_payouts') }}?vendor_id=${vendorId}`)
                    .then(response => response.json())
                    .then(data => {
                        // Clear any existing hidden fields
                        document.querySelectorAll('#batchProcessForm input[name^="payout_ids"]').forEach(input => input.remove());
                        document.querySelectorAll('#batchProcessForm input[name^="transaction_details"]').forEach(input => input.remove());

                        // Add each payout ID as a hidden field
                        if (data.payouts && data.payouts.length > 0) {
                            const prefix = document.querySelector('input[name="transaction_prefix"]').value;

                            data.payouts.forEach(payout => {
                                const idInput = document.createElement('input');
                                idInput.type = 'hidden';
                                idInput.name = 'payout_ids[]';
                                idInput.value = payout.id;
                                document.getElementById('batchProcessForm').appendChild(idInput);

                                // Add transaction ID for each payout
                                const txnInput = document.createElement('input');
                                txnInput.type = 'hidden';
                                txnInput.name = `transaction_details[${payout.id}]`;
                                txnInput.value = `${prefix}${payout.id}`;
                                document.getElementById('batchProcessForm').appendChild(txnInput);
                            });
                        } else {
                            // Show a message if no payouts found
                            const alertDiv = document.createElement('div');
                            alertDiv.className = 'alert alert-warning';
                            alertDiv.textContent = 'No pending payouts found for this provider.';
                            modal.querySelector('.modal-body').prepend(alertDiv);
                        }
                    })
                    .catch(error => {
                        console.error("Error fetching payouts:", error);
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-danger';
                        alertDiv.textContent = 'Error fetching pending payouts. Please try again.';
                        modal.querySelector('.modal-body').prepend(alertDiv);
                    });
            });
        });

        // Update transaction IDs when prefix changes
        const prefixInput = document.querySelector('input[name="transaction_prefix"]');
        if (prefixInput) {
            prefixInput.addEventListener('input', function() {
                const prefix = this.value;

                // Update all transaction details inputs
                document.querySelectorAll('input[name^="transaction_details["]').forEach(input => {
                    const payoutId = input.name.match(/\[(\d+)\]/)[1];
                    input.value = `${prefix}${payoutId}`;
                });
            });
        }

        // Initialize DataTable if available
        if (typeof $.fn.DataTable !== 'undefined') {
            $('#pendingPayoutsTable').DataTable({
                "order": [[ 1, "desc" ]],
                "pageLength": 25
            });
        }
    });
</script>
@endsection
