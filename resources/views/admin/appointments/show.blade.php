@extends('admin.layouts.app')

@section('title', 'Appointment Details')

@section('page-title', 'Appointment Details')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb m-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.appointments.index') }}">Appointments</a></li>
        <li class="breadcrumb-item active" aria-current="page">Details</li>
    </ol>
</nav>
@endsection

@section('page-actions')
<a href="{{ route('admin.appointments.calendar') }}" class="btn btn-info me-2">
    <i class="fas fa-calendar-alt me-1"></i> Calendar View
</a>
<a href="{{ route('admin.appointments.index') }}" class="btn btn-secondary me-2">
    <i class="fas fa-list me-1"></i> List View
</a>
<!--<a href="{{ route('admin.appointments.edit', $appointment->id) }}" class="btn btn-primary me-2">-->
<!--    <i class="fas fa-edit me-1"></i> Edit-->
<!--</a>-->
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        <!-- Appointment Details Card -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Appointment #{{ $appointment->id }}</h5>
                <div>
                    @if($appointment->status == 'pending')
                    <span class="badge bg-warning">Pending</span>
                    @elseif($appointment->status == 'confirmed')
                    <span class="badge bg-primary">Confirmed</span>
                    @elseif($appointment->status == 'completed')
                    <span class="badge bg-success">Completed</span>
                    @elseif($appointment->status == 'cancelled')
                    <span class="badge bg-danger">Cancelled</span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <h6 class="text-muted mb-1">Service</h6>
                        <p class="fs-5 fw-bold">
                            @if(!empty($appointment->service->name))
                            {{ $appointment->service->name }}
                            <span class="badge bg-info">Regular Service</span>
                            @elseif(!empty($appointment->comboService->name))
                            {{ $appointment->comboService->name }}
                            <span class="badge bg-info">Combo Service</span>
                            @else
                            N/A
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6 mb-4">
                        <h6 class="text-muted mb-1">Date & Time</h6>
                        <p class="fs-5">{{ $appointment->date->format('M d, Y') }}</p>
                        <p>{{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($appointment->end_time)->format('g:i A') }}</p>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <h6 class="text-muted mb-2">Customer</h6>
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <img src="{{ $appointment->client->profile_picture ? asset('storage/' . $appointment->client->profile_picture) : asset('admin/images/default-avatar.png') }}"
                                     alt="{{ $appointment->client->name }}" class="rounded-circle" width="50" height="50">
                            </div>
                            <div>
                                <p class="fs-5 fw-bold mb-0">{{ $appointment->client->name }}</p>
                                <p class="text-muted mb-0">{{ $appointment->client->email }}</p>
                                @if($appointment->client->phone)
                                <p class="mb-0">{{ $appointment->client->phone }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <h6 class="text-muted mb-2">Vendor</h6>
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <img src="{{ $appointment->user->profile_picture ? asset('storage/' . $appointment->user->profile_picture) : asset('admin/images/default-avatar.png') }}"
                                     alt="{{ $appointment->user->name }}" class="rounded-circle" width="50" height="50">
                            </div>
                            <div>
                                <p class="fs-5 fw-bold mb-0">{{ $appointment->user->name }}</p>
                                <p class="text-muted mb-0">{{ $appointment->user->email }}</p>
                                @if($appointment->user->phone)
                                <p class="mb-0">{{ $appointment->user->phone }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">Visit Type</h6>
                        <p class="badge bg-light text-dark">
                            {{ ($appointment->visit_type ?? 'office') == 'home' ? 'Home Visit' : 'Office Visit' }}
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">Duration</h6>
                        <p>{{ $appointment->getDurationInMinutesAttribute() }} minutes</p>
                    </div>
                </div>

                @if(!empty($appointment->service->description) || (!empty($appointment->comboService) && !empty($appointment->comboService->description)))
                <div class="mb-4">
                    <h6 class="text-muted mb-2">Service Description</h6>
                    <p>{{ !empty($appointment->service->description) ? $appointment->service->description : $appointment->comboService->description }}</p>
                </div>
                <hr>
                @endif

                @if($appointment->notes)
                <div class="mb-4">
                    <h6 class="text-muted mb-2">Appointment Notes</h6>
                    <p class="p-3 bg-light rounded">{{ $appointment->notes }}</p>
                </div>
                <hr>
                @endif

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <h6 class="text-muted mb-2">Created</h6>
                        <p>{{ $appointment->created_at->format('M d, Y g:i A') }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <h6 class="text-muted mb-2">Last Updated</h6>
                        <p>{{ $appointment->updated_at->format('M d, Y g:i A') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service Details Card (if not a combo service) -->
        @if($appointment->service)
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Service Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <h6 class="text-muted mb-1">Duration</h6>
                        <p>{{ $appointment->service->duration }} minutes</p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <h6 class="text-muted mb-1">Price</h6>
                        <p>₹{{ number_format($appointment->service->price, 2) }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <h6 class="text-muted mb-1">Category</h6>
                        <p>{{ $appointment->service->category->name ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Combo Service Details Card (if combo service) -->
        @if(!empty($appointment->comboService))
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Combo Service Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <h6 class="text-muted mb-1">Total Duration</h6>
                        <p>{{ $appointment->comboService->total_duration }} minutes</p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <h6 class="text-muted mb-1">Original Price</h6>
                        <p>₹{{ number_format($appointment->comboService->original_price, 2) }}</p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <h6 class="text-muted mb-1">Discounted Price</h6>
                        <p>₹{{ number_format($appointment->comboService->price, 2) }}</p>
                    </div>
                </div>

                <h6 class="text-muted mb-3 mt-2">Included Services</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-borderless">
                        <thead class="table-light">
                        <tr>
                            <th>Service</th>
                            <th>Duration</th>
                            <th>Price</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($appointment->comboService->services as $service)
                        <tr>
                            <td>{{ $service->name }}</td>
                            <td>{{ $service->duration }} minutes</td>
                            <td>₹{{ number_format($service->price, 2) }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        <!-- Status Actions Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    @if($appointment->status == 'pending')
                    <form action="{{ route('admin.appointments.status', ['appointment' => $appointment->id, 'status' => 'confirmed']) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-check me-1"></i> Confirm Appointment
                        </button>
                    </form>
                    @endif

                    @if($appointment->status == 'confirmed')
                    <form action="{{ route('admin.appointments.status', ['appointment' => $appointment->id, 'status' => 'completed']) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-success w-100 mb-2">
                            <i class="fas fa-check-double me-1"></i> Mark as Completed
                        </button>
                    </form>
                    @endif

                    @if($appointment->status == 'pending' || $appointment->status == 'confirmed')
                    <form action="{{ route('admin.appointments.status', ['appointment' => $appointment->id, 'status' => 'cancelled']) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-danger w-100 mb-2">
                            <i class="fas fa-times me-1"></i> Cancel Appointment
                        </button>
                    </form>
                    @endif
<!---->
<!--                    <a href="{{ route('admin.appointments.edit', $appointment->id) }}" class="btn btn-outline-primary w-100 mb-2">-->
<!--                        <i class="fas fa-edit me-1"></i> Edit Appointment-->
<!--                    </a>-->

                    <form action="{{ route('admin.appointments.destroy', $appointment->id) }}" method="POST" class="delete-form">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100" data-confirm="Are you sure you want to delete this appointment?">
                            <i class="fas fa-trash me-1"></i> Delete Appointment
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Payment Details Card -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Payment Details</h5>
                <div>
                    @if($appointment->payment_status == 'paid')
                    <span class="badge bg-success">Paid</span>
                    @elseif($appointment->payment_status == 'pending')
                    <span class="badge bg-warning">Pending</span>
                    @elseif($appointment->payment_status == 'failed')
                    <span class="badge bg-danger">Failed</span>
                    @elseif($appointment->payment_status == 'refunded')
                    <span class="badge bg-info">Refunded</span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                @if($appointment->payment_amount)
                <div class="d-flex justify-content-between mb-2">
                    <span>Original Price:</span>
                    <span class="fw-bold">{{ $appointment->formatted_original_price ?? '₹'.number_format($appointment->original_price ?? 0, 2) }}</span>
                </div>

                @if($appointment->discount_amount && $appointment->discount_amount > 0)
                <div class="d-flex justify-content-between mb-2 text-danger">
                    <span>Discount:</span>
                    <span>-{{ $appointment->formatted_discount_amount ?? '₹'.number_format($appointment->discount_amount, 2) }}</span>
                </div>
                @endif

                @if($appointment->home_visit_fee && $appointment->home_visit_fee > 0)
                <div class="d-flex justify-content-between mb-2">
                    <span>Home Visit Fee:</span>
                    <span>{{ $appointment->formatted_home_visit_fee ?? '₹'.number_format($appointment->home_visit_fee, 2) }}</span>
                </div>
                @endif

                @if($appointment->additional_services_fee && $appointment->additional_services_fee > 0)
                <div class="d-flex justify-content-between mb-2">
                    <span>Additional Services:</span>
                    <span>{{ $appointment->formatted_additional_services_fee ?? '₹'.number_format($appointment->additional_services_fee, 2) }}</span>
                </div>
                @endif

                <div class="d-flex justify-content-between mb-2">
                    <span>Platform Fee:</span>
                    <span>{{ $appointment->formatted_platform_fees ?? '₹'.number_format($appointment->platform_fees ?? 8, 2) }}</span>
                </div>

                @if($appointment->gst && $appointment->gst > 0)
                <div class="d-flex justify-content-between mb-2">
                    <span>GST:</span>
                    <span>{{ $appointment->formatted_gst ?? '₹'.number_format($appointment->gst, 2) }}</span>
                </div>
                @endif

                <hr>

                <div class="d-flex justify-content-between fw-bold">
                    <span>Total Amount:</span>
                    <span class="fs-5">{{ $appointment->formatted_payment_amount ?? '₹'.number_format($appointment->payment_amount, 2) }}</span>
                </div>

                @if($appointment->payment_method)
                <div class="mt-3">
                    <h6 class="text-muted mb-2">Payment Method</h6>
                    <p>{{ ucfirst($appointment->payment_method) }} ({{ ucfirst($appointment->payment_mode ?? 'online') }})</p>
                </div>
                @endif

                @if($appointment->payment_id)
                <div class="mt-3">
                    <h6 class="text-muted mb-2">Transaction ID</h6>
                    <p class="mb-0">{{ $appointment->payment_id }}</p>
                </div>
                @endif

                @if($appointment->payment && $appointment->payment->created_at)
                <div class="mt-3">
                    <h6 class="text-muted mb-2">Payment Date</h6>
                    <p class="mb-0">{{ $appointment->payment->created_at->format('M d, Y g:i A') }}</p>
                </div>
                @endif

                @else
                <div class="text-center py-3">
                    <p class="text-muted mb-0">No payment information available</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Confirm delete
        document.querySelectorAll('[data-confirm]').forEach(function(element) {
            element.addEventListener('click', function(e) {
                if (!confirm(this.dataset.confirm)) {
                    e.preventDefault();
                    return false;
                }
            });
        });
    });
</script>
@endsection
