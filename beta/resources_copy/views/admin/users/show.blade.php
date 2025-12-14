<!-- resources/views/admin/users/show.blade.php -->
@extends('admin.layouts.app')

@section('title', 'User Details')

@section('page-title', 'User Details')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb m-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">System Users</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $user->name }}</li>
    </ol>
</nav>
@endsection

@section('page-actions')
<div class="btn-group">
    @if(hasPermission('users_edit_users'))
    <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-primary">
        <i class="fas fa-edit me-1"></i> Edit User
    </a>
    @endif

    @if($user->userRole && hasPermission('roles_view_roles'))
    <a href="{{ route('admin.roles.show', $user->role_id) }}" class="btn btn-info">
        <i class="fas fa-user-tag me-1"></i> View Role
    </a>
    @endif
</div>
@endsection

@section('content')
<div class="row">
    <!-- User Basic Info -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">User Information</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <img src="{{ $user->profile_picture ? asset('storage/' . $user->profile_picture) : asset('admin/images/default-avatar.png') }}"
                         alt="{{ $user->name }}" class="img-fluid rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">

                    <h5 class="mt-3 mb-0">{{ $user->name }}</h5>

                    <div class="mt-2">
                        @if($user->role == 'admin')
                        <span class="badge bg-danger">Administrator</span>
                        @elseif($user->role == 'vendor')
                        <span class="badge bg-primary">Vendor</span>
                        @elseif($user->role == 'customer')
                        <span class="badge bg-info">Customer</span>
                        @endif

                        @if($user->userRole)
                        <span class="badge bg-success">{{ $user->userRole->display_name }}</span>
                        @endif

                        @if($user->status)
                        <span class="badge bg-success">Active</span>
                        @else
                        <span class="badge bg-danger">Inactive</span>
                        @endif
                    </div>
                </div>

                <div class="list-group list-group-flush">
                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">Email</h6>
                        </div>
                        <a href="mailto:{{ $user->email }}" class="text-decoration-none">{{ $user->email }}</a>
                    </div>

                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">Mobile</h6>
                        </div>
                        <a href="tel:{{ $user->mobile }}" class="text-decoration-none">{{ $user->mobile }}</a>
                    </div>

                    @if($user->role == 'vendor' && $user->businessCategory)
                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">Business Category</h6>
                        </div>
                        <p class="mb-1">{{ $user->businessCategory->name }}</p>
                    </div>
                    @endif

                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">Created On</h6>
                        </div>
                        <p class="mb-1">{{ $user->created_at->format('M d, Y h:i A') }}</p>
                    </div>

                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">Last Updated</h6>
                        </div>
                        <p class="mb-1">{{ $user->updated_at->format('M d, Y h:i A') }}</p>
                    </div>
                </div>
            </div>
        </div>

        @if($user->role == 'vendor' && $user->kycDocument)
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">KYC Information</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">KYC Status</h6>
                        </div>
                        @if($user->is_kyc_completed)
                        <span class="badge bg-success">Verified</span>
                        @else
                        <span class="badge bg-warning">Pending</span>
                        @endif
                    </div>

                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">Verification Date</h6>
                        </div>
                        <p class="mb-1">{{ $user->kycDocument->verified_at ? $user->kycDocument->verified_at->format('M d, Y') : 'Not Verified' }}</p>
                    </div>
                </div>

                <div class="mt-3">
                    <a href="{{ route('admin.kyc.show', $user->kycDocument->id) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-id-card me-1"></i> View KYC Details
                    </a>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Permissions and Access -->
    <div class="col-md-8">
        @if($user->userRole)
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Role & Permissions</h5>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <h6>Assigned Role</h6>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-success py-2 px-3 fs-6 me-2">{{ $user->userRole->display_name }}</span>
                        <span class="text-muted">{{ $user->userRole->description }}</span>
                    </div>
                </div>

                @if($user->userRole->permissions->isNotEmpty())
                @php
                $permissionsByModule = $user->userRole->permissions->groupBy('module');
                @endphp

                <div class="mt-4">
                    <h6>Permissions</h6>
                    <div class="accordion" id="permissionsAccordion">
                        @foreach($permissionsByModule as $module => $permissions)
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading{{ Str::slug($module) }}">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ Str::slug($module) }}" aria-expanded="false" aria-controls="collapse{{ Str::slug($module) }}">
                                    {{ ucfirst($module) }} ({{ $permissions->count() }})
                                </button>
                            </h2>
                            <div id="collapse{{ Str::slug($module) }}" class="accordion-collapse collapse" aria-labelledby="heading{{ Str::slug($module) }}" data-bs-parent="#permissionsAccordion">
                                <div class="accordion-body">
                                    <div class="row">
                                        @foreach($permissions as $permission)
                                        <div class="col-md-6 mb-2">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-check-circle text-success me-2"></i>
                                                <span>{{ $permission->display_name }}</span>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @else
                <div class="alert alert-info">
                    This role has no permissions assigned.
                </div>
                @endif
            </div>
        </div>
        @else
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Role & Permissions</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    This user does not have a role assigned. Assign a role to define permissions.

                    @if(hasPermission('users_edit_users'))
                    <div class="mt-3">
                        <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-primary">
                            <i class="fas fa-user-tag me-1"></i> Assign Role
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- Activity Logs, if you have them -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Recent Activity</h5>
            </div>
            <div class="card-body">
                <!-- If you have activity logs, show them here -->
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Activity logs will be shown here when available.
                </div>
            </div>
        </div>

        <!-- If this is a vendor with team members -->
        @if($user->role == 'vendor' && $user->teamMembers->isNotEmpty())
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Team Members</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($user->teamMembers as $member)
                        <tr>
                            <td>{{ $member->name }}</td>
                            <td>{{ $member->email }}</td>
                            <td>{{ $member->userRole ? $member->userRole->display_name : 'No Role' }}</td>
                            <td>
                                @if($member->status)
                                <span class="badge bg-success">Active</span>
                                @else
                                <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>
                            <td>
                                @if(hasPermission('users_view_users'))
                                <a href="{{ route('admin.users.show', $member->id) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
