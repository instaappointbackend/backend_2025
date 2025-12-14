 
<!-- resources/views/admin/services/show.blade.php -->
@extends('admin.layouts.app')

@section('title', 'Service Details')

@section('page-title', 'Service Details')

@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.services.index') }}">Services</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $service->name }}</li>
        </ol>
    </nav>
@endsection

@section('page-actions')
    <a href="{{ route('admin.services.edit', $service->id) }}" class="btn btn-primary me-2">
        <i class="fas fa-edit me-1"></i> Edit Service
    </a>
    <a href="{{ route('admin.services.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i> Back to Services
    </a>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Service Information</h5>
                </div>
                <div class="card-body">
                    <h4 class="mb-3">{{ $service->name }}</h4>
                    
                    <div class="d-flex mb-4">
                        <span class="badge bg-{{ $service->is_active ? 'success' : 'danger' }} me-2">
                            {{ $service->is_active ? 'Active' : 'Inactive' }}
                        </span>
                        <span class="badge bg-info me-2">
                            <i class="fas fa-clock me-1"></i> 
                            {{ floor($service->duration / 60) > 0 ? floor($service->duration / 60) . ' hour' . (floor($service->duration / 60) > 1 ? 's' : '') : '' }}
                            {{ $service->duration % 60 > 0 ? ($service->duration % 60) . ' minute' . ($service->duration % 60 > 1 ? 's' : '') : '' }}
                        </span>
                        <span class="badge bg-primary">
                            <i class="fas fa-rupee-sign me-1"></i> {{ number_format($service->price, 2) }}
                        </span>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="text-muted mb-2">Description</h6>
                        <p>{{ $service->description }}</p>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Vendor</h6>
                            <div class="d-flex align-items-center">
                                @if($service->user && $service->user->profile_picture)
                                    <img src="{{ asset('storage/' . $service->user->profile_picture) }}" 
                                         alt="{{ $service->user->name }}" 
                                         class="rounded-circle me-2" 
                                         width="40" height="40">
                                @else
                                    <div class="bg-primary rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                         style="width: 40px; height: 40px; color: white;">
                                        {{ $service->user ? strtoupper(substr($service->user->name, 0, 1)) : 'U' }}
                                    </div>
                                @endif
                                <div>
                                    <p class="mb-0 fw-bold">
                                        @if($service->user)
                                            <a href="{{ route('admin.users.show', $service->user->id) }}">{{ $service->user->name }}</a>
                                        @else
                                            Unknown Vendor
                                        @endif
                                    </p>
                                    <small class="text-muted">
                                        {{ $service->user ? $service->user->email : '' }}
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Business Type</h6>
                            <p>{{ $service->user && $service->user->businessCategory ? $service->user->businessCategory->name : 'N/A' }}</p>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Created At</h6>
                            <p>{{ $service->created_at->format('M d, Y h:i A') }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Last Updated</h6>
                            <p>{{ $service->updated_at->format('M d, Y h:i A') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Service Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="text-muted mb-0">Bookings</h6>
                            @if(isset($stats['total_bookings']))
                                <span class="badge bg-primary">{{ $stats['total_bookings'] ?? 0 }}</span>
                            @else
                                <span class="badge bg-secondary">N/A</span>
                            @endif
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar" role="progressbar" style="width: {{ isset($stats['total_bookings']) && $stats['total_bookings'] > 0 ? '100' : '0' }}%"></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="text-muted mb-0">Completed</h6>
                            @if(isset($stats['completed_bookings']))
                                <span class="badge bg-success">{{ $stats['completed_bookings'] ?? 0 }}</span>
                            @else
                                <span class="badge bg-secondary">N/A</span>
                            @endif
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: {{ isset($stats['total_bookings']) && $stats['total_bookings'] > 0 ? ($stats['completed_bookings'] / $stats['total_bookings'] * 100) : '0' }}%"></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="text-muted mb-0">Cancelled</h6>
                            @if(isset($stats['cancelled_bookings']))
                                <span class="badge bg-danger">{{ $stats['cancelled_bookings'] ?? 0 }}</span>
                            @else
                                <span class="badge bg-secondary">N/A</span>
                            @endif
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-danger" role="progressbar" 
                                 style="width: {{ isset($stats['total_bookings']) && $stats['total_bookings'] > 0 ? ($stats['cancelled_bookings'] / $stats['total_bookings'] * 100) : '0' }}%"></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="text-muted mb-0">Revenue</h6>
                            @if(isset($stats['total_revenue']))
                                <span class="badge bg-success">₹{{ number_format($stats['total_revenue'] ?? 0, 2) }}</span>
                            @else
                                <span class="badge bg-secondary">N/A</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.services.edit', $service->id) }}" class="btn btn-primary">
                            <i class="fas fa-edit me-1"></i> Edit Service
                        </a>
                        
                        <form action="{{ route('admin.services.update', $service->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="is_active" value="{{ $service->is_active ? '0' : '1' }}">
                            <button type="submit" class="btn btn-{{ $service->is_active ? 'warning' : 'success' }} w-100">
                                <i class="fas fa-{{ $service->is_active ? 'ban' : 'check-circle' }} me-1"></i> 
                                {{ $service->is_active ? 'Deactivate' : 'Activate' }} Service
                            </button>
                        </form>
                        
                        <form action="{{ route('admin.services.destroy', $service->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this service?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="fas fa-trash me-1"></i> Delete Service
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Bookings -->
    @if(isset($recent_bookings) && count($recent_bookings) > 0)
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Recent Bookings</h5>
            <a href="{{ route('admin.appointments.index', ['service_id' => $service->id]) }}" class="btn btn-sm btn-primary">
                View All Bookings
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recent_bookings as $booking)
                        <tr>
                            <td>{{ $booking->id }}</td>
                            <td>{{ $booking->client ? $booking->client->name : 'N/A' }}</td>
                            <td>{{ $booking->date->format('M d, Y') }}</td>
                            <td>{{ $booking->start_time->format('h:i A') }}</td>
                            <td>
                                <span class="badge bg-{{ 
                                    $booking->status == 'confirmed' ? 'success' :
                                    ($booking->status == 'pending' ? 'warning' :
                                    ($booking->status == 'cancelled' ? 'danger' : 'info'))
                                }}">
                                    {{ ucfirst($booking->status) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.appointments.show', $booking->id) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
@endsection