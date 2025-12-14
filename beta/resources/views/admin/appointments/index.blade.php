<!-- resources/views/admin/appointments/index.blade.php -->
@extends('admin.layouts.app')

@section('title', 'Manage Appointments')

@section('page-title', 'Appointments Management')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb m-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Appointments</li>
    </ol>
</nav>
@endsection

@section('page-actions')
<a href="{{ route('admin.appointments.calendar') }}" class="btn btn-info me-2">
    <i class="fas fa-calendar-alt me-1"></i> Calendar View
</a>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">All Appointments</h5>

        <div class="search-filter">
            <form action="{{ route('admin.appointments.index') }}" method="GET" class="row g-3" id="filterForm">
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <input type="date" name="start_date" class="form-control" placeholder="Start Date" value="{{ request('start_date') }}">
                </div>

                <div class="col-md-2">
                    <input type="date" name="end_date" class="form-control" placeholder="End Date" value="{{ request('end_date') }}">
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>

                <div class="col-md-1">
                    <a href="{{ route('admin.appointments.index') }}" class="btn btn-secondary w-100" title="Reset Filters">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card-body">
        <!-- Advanced Filters (Collapsible) -->
        <div class="mb-3">
            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#advancedFilters" aria-expanded="false" aria-controls="advancedFilters">
                <i class="fas fa-filter me-1"></i> Advanced Filters
            </button>

            <div class="collapse mt-3" id="advancedFilters">
                <div class="card card-body">
                    <form action="{{ route('admin.appointments.index') }}" method="GET" class="row g-3">
                        <!-- Pass current search parameters -->
                        @if(request('search'))
                        <input type="hidden" name="search" value="{{ request('search') }}">
                        @endif
                        @if(request('status'))
                        <input type="hidden" name="status" value="{{ request('status') }}">
                        @endif
                        @if(request('start_date'))
                        <input type="hidden" name="start_date" value="{{ request('start_date') }}">
                        @endif
                        @if(request('end_date'))
                        <input type="hidden" name="end_date" value="{{ request('end_date') }}">
                        @endif

                        <div class="col-md-4">
                            <label for="vendor_id" class="form-label">Vendor</label>
                            <select name="vendor_id" id="vendor_id" class="form-select">
                                <option value="">All Vendors</option>
                                @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" {{ request('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                {{ $vendor->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="client_id" class="form-label">Customer</label>
                            <select name="client_id" id="client_id" class="form-select">
                                <option value="">All Customers</option>
                                @foreach($clients as $client)
                                <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                                {{ $client->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="service_id" class="form-label">Service</label>
                            <select name="service_id" id="service_id" class="form-select">
                                <option value="">All Services</option>
                                @foreach($services as $service)
                                <option value="{{ $service->id }}" {{ request('service_id') == $service->id ? 'selected' : '' }}>
                                {{ $service->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Results Info -->
        @if(request()->anyFilled(['search', 'status', 'start_date', 'end_date', 'vendor_id', 'client_id', 'service_id']))
        <div class="alert alert-info">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>Active Filters:</strong>
                    @if(request('search'))
                    <span class="badge bg-primary me-1">Search: {{ request('search') }}</span>
                    @endif
                    @if(request('status'))
                    <span class="badge bg-primary me-1">Status: {{ ucfirst(request('status')) }}</span>
                    @endif
                    @if(request('start_date'))
                    <span class="badge bg-primary me-1">From: {{ \Carbon\Carbon::parse(request('start_date'))->format('M d, Y') }}</span>
                    @endif
                    @if(request('end_date'))
                    <span class="badge bg-primary me-1">To: {{ \Carbon\Carbon::parse(request('end_date'))->format('M d, Y') }}</span>
                    @endif
                    @if(request('vendor_id'))
                    <span class="badge bg-primary me-1">Vendor: {{ $vendors->find(request('vendor_id'))->name ?? 'Unknown' }}</span>
                    @endif
                    @if(request('client_id'))
                    <span class="badge bg-primary me-1">Customer: {{ $clients->find(request('client_id'))->name ?? 'Unknown' }}</span>
                    @endif
                    @if(request('service_id'))
                    <span class="badge bg-primary me-1">Service: {{ $services->find(request('service_id'))->name ?? 'Unknown' }}</span>
                    @endif
                </div>
                <small class="text-muted">{{ $appointments->total() }} result(s) found</small>
            </div>
        </div>
        @endif

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Service</th>
                    <th>Customer</th>
                    <th>Vendor</th>
                    <th>Date & Time</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($appointments as $appointment)
                <tr>
                    <td>{{ $appointment->id }}</td>
                    <td>{{ !empty($appointment->service->name) ? $appointment->service->name : $appointment->comboService->name }}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="me-2">
                                <img src="{{ $appointment->client->profile_picture ? asset('storage/' . $appointment->client->profile_picture) : asset('admin/images/default-avatar.png') }}" alt="{{ $appointment->client->name }}" class="avatar-img" width="30" height="30">
                            </div>
                            <div>{{ $appointment->client->name }}</div>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="me-2">
                                <img src="{{ $appointment->user->profile_picture ? asset('storage/' . $appointment->user->profile_picture) : asset('admin/images/default-avatar.png') }}" alt="{{ $appointment->user->name }}" class="avatar-img" width="30" height="30">
                            </div>
                            <div>{{ $appointment->user->name }}</div>
                        </div>
                    </td>
                    <td>
                        @if(isset($appointment->formatted_time))
                        {{ $appointment->formatted_time }}
                        @else
                        {{ $appointment->date->format('M d, Y') }},
                        {{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }} -
                        {{ \Carbon\Carbon::parse($appointment->end_time)->format('g:i A') }}
                        @endif
                    </td>
                    <td>
                        @if($appointment->status == 'pending')
                        <span class="badge bg-warning">Pending</span>
                        @elseif($appointment->status == 'confirmed')
                        <span class="badge bg-primary">Confirmed</span>
                        @elseif($appointment->status == 'completed')
                        <span class="badge bg-success">Completed</span>
                        @elseif($appointment->status == 'cancelled')
                        <span class="badge bg-danger">Cancelled</span>
                        @endif
                    </td>
                    <td>{{ $appointment->created_at->format('M d, Y') }}</td>
                    <td>
                        <div class="d-flex">
                            <a href="{{ route('admin.appointments.show', $appointment->id) }}" class="btn btn-sm btn-info me-1" data-bs-toggle="tooltip" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>

                            <form action="{{ route('admin.appointments.destroy', $appointment->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" data-confirm="Are you sure you want to delete this appointment?" data-bs-toggle="tooltip" title="Delete Appointment">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No appointments found.</p>
                        @if(request()->anyFilled(['search', 'status', 'start_date', 'end_date', 'vendor_id', 'client_id', 'service_id']))
                        <a href="{{ route('admin.appointments.index') }}" class="btn btn-sm btn-outline-primary">Clear Filters</a>
                        @endif
                    </td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($appointments->hasPages())
        <div class="d-flex justify-content-end mt-3">
            {{ $appointments->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })

        // Confirm delete
        document.querySelectorAll('[data-confirm]').forEach(function(element) {
            element.addEventListener('click', function(e) {
                if (!confirm(this.dataset.confirm)) {
                    e.preventDefault();
                    return false;
                }
            });
        });

        // Auto-submit status filter when changed (optional)
        const statusSelect = document.querySelector('select[name="status"]');
        if (statusSelect) {
            statusSelect.addEventListener('change', function() {
                // Uncomment the line below if you want auto-submit on status change
                // this.form.submit();
            });
        }

        // Validate date range
        const startDateInput = document.querySelector('input[name="start_date"]');
        const endDateInput = document.querySelector('input[name="end_date"]');

        if (startDateInput && endDateInput) {
            function validateDateRange() {
                const startDate = startDateInput.value;
                const endDate = endDateInput.value;

                if (startDate && endDate && startDate > endDate) {
                    endDateInput.setCustomValidity('End date must be after start date');
                } else {
                    endDateInput.setCustomValidity('');
                }
            }

            startDateInput.addEventListener('change', validateDateRange);
            endDateInput.addEventListener('change', validateDateRange);
        }
    });
</script>
@endsection
