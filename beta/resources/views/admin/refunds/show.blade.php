@extends('admin.layouts.app')

@section('title', 'Refund Details')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Refund Details</h1>
        <div>
            <a href="{{ route('admin.refunds.index') }}" class="btn btn-sm btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to List
            </a>
            <a href="{{ route('admin.refunds.receipt', $refund->id) }}" class="btn btn-sm btn-primary shadow-sm" target="_blank">
                <i class="fas fa-download fa-sm text-white-50"></i> Download Receipt
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Refund Information -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Refund Information</h6>
                    <div class="dropdown no-arrow">
                        @if($refund->refund_status == 'pending')
                        <form action="{{ route('admin.refunds.process', $refund->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to process this refund?')">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success">
                                <i class="fas fa-check"></i> Process Refund
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Reference ID:</strong></td>
                                    <td><span class="badge badge-info">{{ $refund->refund_reference }}</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        @if($refund->refund_status == 'pending')
                                        <span class="badge badge-warning">Pending</span>
                                        @elseif($refund->refund_status == 'processed')
                                        <span class="badge badge-success">Processed</span>
                                        @elseif($refund->refund_status == 'failed')
                                        <span class="badge badge-danger">Failed</span>
                                        @elseif($refund->refund_status == 'cancelled')
                                        <span class="badge badge-secondary">Cancelled</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Refund Type:</strong></td>
                                    <td>
                                        @if($refund->refund_type == 'full_refund')
                                        <span class="badge badge-success">Full Refund</span>
                                        @elseif($refund->refund_type == 'partial_refund')
                                        <span class="badge badge-warning">Partial Refund</span>
                                        @elseif($refund->refund_type == 'no_refund')
                                        <span class="badge badge-danger">No Refund</span>
                                        @else
                                        <span class="badge badge-secondary">{{ $refund->refund_type }}</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Reason:</strong></td>
                                    <td>{{ $refund->refund_reason }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Cancellation Hours:</strong></td>
                                    <td>{{ number_format($refund->cancellation_time_hours, 2) }} hours before appointment</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Created:</strong></td>
                                    <td>{{ $refund->created_at->format('M d, Y H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Processed:</strong></td>
                                    <td>{{ $refund->processed_at ? $refund->processed_at->format('M d, Y H:i:s') : 'Not Processed' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Appointment ID:</strong></td>
                                    <td>
                                        @if($refund->appointment)
                                        <a href="{{ route('admin.appointments.show', $refund->appointment->id) }}" class="text-primary">
                                            #{{ $refund->appointment->id }}
                                        </a>
                                        @else
                                        N/A
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Payment ID:</strong></td>
                                    <td>
                                        @if($refund->payment)
                                        <a href="{{ route('admin.payments.show', $refund->payment->id) }}" class="text-primary">
                                            #{{ $refund->payment->id }}
                                        </a>
                                        @else
                                        N/A
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Amount Breakdown -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Amount Breakdown</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>Description</th>
                                    <th class="text-right">Amount (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Original Amount</td>
                                    <td class="text-right">{{ number_format($refund->original_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Service Charges</td>
                                    <td class="text-right">{{ number_format($refund->service_charges, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Platform Fee</td>
                                    <td class="text-right">{{ number_format($refund->platform_fee, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Other Charges</td>
                                    <td class="text-right">{{ number_format($refund->other_charges, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>GST Amount</td>
                                    <td class="text-right">{{ number_format($refund->gst_amount, 2) }}</td>
                                </tr>
                                <tr class="table-success">
                                    <td><strong>Refund to Customer</strong></td>
                                    <td class="text-right"><strong>{{ number_format($refund->refund_amount, 2) }}</strong></td>
                                </tr>
                                <tr class="table-info">
                                    <td><strong>Vendor Amount</strong></td>
                                    <td class="text-right"><strong>{{ number_format($refund->vendor_amount, 2) }}</strong></td>
                                </tr>
                                <tr class="table-warning">
                                    <td><strong>Admin Amount</strong></td>
                                    <td class="text-right"><strong>{{ number_format($refund->admin_amount, 2) }}</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Refund Details -->
            @if($refund->refund_details)
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Processing Details</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        @if(isset($refund->refund_details['policy_applied']))
                        <div class="col-12 mb-3">
                            <strong>Policy Applied:</strong>
                            <p class="text-muted">{{ $refund->refund_details['policy_applied'] }}</p>
                        </div>
                        @endif

                        @if(isset($refund->refund_details['processed_by_admin']))
                        <div class="col-md-6">
                            <strong>Processed By:</strong>
                            <p class="text-muted">{{ $refund->refund_details['processed_by_admin_name'] ?? 'Admin' }}</p>
                        </div>
                        @endif

                        @if(isset($refund->refund_details['gateway_response']))
                        <div class="col-md-6">
                            <strong>Gateway Response:</strong>
                            <p class="text-muted">{{ $refund->refund_details['gateway_response'] }}</p>
                        </div>
                        @endif

                        @if(isset($refund->refund_details['gateway_reference']))
                        <div class="col-md-6">
                            <strong>Gateway Reference:</strong>
                            <p class="text-muted">{{ $refund->refund_details['gateway_reference'] }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Customer & Provider Information -->
        <div class="col-lg-4">
            <!-- Customer Information -->
            @if($refund->user)
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Customer Information</h6>
                </div>
                <div class="card-body text-center">
                    <img src="{{ $refund->user->profile_picture ? asset('storage/' . $refund->user->profile_picture) : asset('admin/images/default-avatar.png') }}" 
                         alt="{{ $refund->user->name }}" 
                         class="rounded-circle mb-3" 
                         width="80" height="80">
                    <h5 class="card-title">{{ $refund->user->name }}</h5>
                    <p class="card-text text-muted">{{ $refund->user->email }}</p>
                    @if($refund->user->mobile)
                    <p class="card-text"><i class="fas fa-phone"></i> {{ $refund->user->mobile }}</p>
                    @endif
                    <a href="{{ route('admin.users.show', $refund->user->id) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-user"></i> View Profile
                    </a>
                </div>
            </div>
            @endif

            <!-- Provider Information -->
            @if($refund->provider)
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Provider Information</h6>
                </div>
                <div class="card-body text-center">
                    <img src="{{ $refund->provider->profile_picture ? asset('storage/' . $refund->provider->profile_picture) : asset('admin/images/default-avatar.png') }}" 
                         alt="{{ $refund->provider->name }}" 
                         class="rounded-circle mb-3" 
                         width="80" height="80">
                    <h5 class="card-title">{{ $refund->provider->name }}</h5>
                    <p class="card-text text-muted">{{ $refund->provider->email }}</p>
                    @if($refund->provider->mobile)
                    <p class="card-text"><i class="fas fa-phone"></i> {{ $refund->provider->mobile }}</p>
                    @endif
                    <a href="{{ route('admin.users.show', $refund->provider->id) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-user"></i> View Profile
                    </a>
                </div>
            </div>
            @endif

            <!-- Appointment Information -->
            @if($refund->appointment)
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Appointment Details</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <td><strong>Date:</strong></td>
                            <td>{{ $refund->appointment->date->format('M d, Y') }}</td>
                        </tr>
                        <tr>
                            <td><strong>Time:</strong></td>
                            <td>{{ $refund->appointment->start_time }} - {{ $refund->appointment->end_time }}</td>
                        </tr>
                        <tr>
                            <td><strong>Status:</strong></td>
                            <td>
                                <span class="badge badge-{{ $refund->appointment->status == 'cancelled' ? 'danger' : 'info' }}">
                                    {{ ucfirst($refund->appointment->status) }}
                                </span>
                            </td>
                        </tr>
                        @if($refund->appointment->service)
                        <tr>
                            <td><strong>Service:</strong></td>
                            <td>{{ $refund->appointment->service->name }}</td>
                        </tr>
                        @endif
                    </table>
                    <a href="{{ route('admin.appointments.show', $refund->appointment->id) }}" class="btn btn-sm btn-primary btn-block">
                        <i class="fas fa-calendar"></i> View Appointment
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Any additional JavaScript for the refund details page
    $(document).ready(function() {
        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>
@endsection