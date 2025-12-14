<!-- resources/views/admin/users/index.blade.php -->
@extends('admin.layouts.app')

@section('title', 'Manage System Users')

@section('page-title', 'System Users Management')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb m-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">System Users</li>
    </ol>
</nav>
@endsection

@section('page-actions')
@if(hasPermission('users_create_users'))
<a href="{{ route('admin.users.create') }}" class="btn btn-primary">
    <i class="fas fa-plus-circle me-1"></i> Add New System User
</a>
@endif
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">System Users</h5>

        <div class="search-filter">
            <form action="{{ route('admin.users.index') }}" method="GET" class="d-flex gap-2">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search users..." value="{{ request('search') }}">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="fas fa-search"></i>
                    </button>
                </div>

                <select name="role_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- All Roles --</option>
                    @foreach($roles as $role)
                    <option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>
                    {{ $role->display_name }}
                    </option>
                    @endforeach
                </select>

                <select name="system_role" class="form-select" onchange="this.form.submit()">
                    <option value="">-- System Role --</option>
                    <option value="admin" {{ request('system_role') == 'admin' ? 'selected' : '' }}>Admin</option>
                </select>

                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Status --</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </form>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>System Role</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created On</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="me-2">
                                <img src="{{ $user->profile_picture ? asset('storage/' . $user->profile_picture) : asset('admin/images/default-avatar.png') }}" alt="{{ $user->name }}" class="avatar-img rounded-circle" width="40" height="40">
                            </div>
                            <div>
                                <h6 class="mb-0">{{ $user->name }}</h6>
                            </div>
                        </div>
                    </td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->mobile }}</td>
                    <td>
                        @if($user->role == 'admin')
                        <span class="badge bg-danger">Admin</span>
                        @elseif($user->role == 'vendor')
                        <span class="badge bg-primary">Vendor</span>
                        @elseif($user->role == 'customer')
                        <span class="badge bg-info">Customer</span>
                        @endif
                    </td>
                    <td>
                        @if($user->userRole)
                        <span class="badge bg-success">{{ $user->userRole->display_name }}</span>
                        @else
                        <span class="badge bg-secondary">No Role</span>
                        @endif
                    </td>
                    <td>
                        @if($user->status)
                        <span class="badge bg-success">Active</span>
                        @else
                        <span class="badge bg-danger">Inactive</span>
                        @endif
                    </td>
                    <td>{{ $user->created_at->format('M d, Y') }}</td>
                    <td>
                        <div class="d-flex">
                            @if(hasPermission('users_view_users'))
                            <a href="{{ route('admin.users.show', $user->id) }}" class="btn btn-sm btn-info me-1" data-bs-toggle="tooltip" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                            @endif

                            @if(hasPermission('users_edit_users'))
                            <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-sm btn-primary me-1" data-bs-toggle="tooltip" title="Edit User">
                                <i class="fas fa-edit"></i>
                            </a>

                            <form action="{{ route('admin.users.toggle-status', $user->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm {{ $user->status ? 'btn-warning' : 'btn-success' }} me-1" data-bs-toggle="tooltip" title="{{ $user->status ? 'Deactivate' : 'Activate' }} User">
                                    <i class="fas fa-{{ $user->status ? 'ban' : 'check' }}"></i>
                                </button>
                            </form>
                            @endif

                            @if(hasPermission('users_delete_users') && $user->id != 1 && !$user->isSuperAdmin())
                            <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" data-confirm="Are you sure you want to delete this user? This action cannot be undone." data-bs-toggle="tooltip" title="Delete User">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center">No system users found.</td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3">
            <div>
                Showing {{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }} of {{ $users->total() }} users
            </div>
            <div>
                {{ $users->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mt-4">
    <div class="col-md-4 col-sm-6 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-primary mb-2">
                    <i class="fas fa-users-cog"></i>
                </div>
                <h5 class="card-title">Total System Users</h5>
                <h2 class="card-text fw-bold">{{ $totalUsers }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-sm-6 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-danger mb-2">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h5 class="card-title">Administrators</h5>
                <h2 class="card-text fw-bold">{{ $totalAdmins }}</h2>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-sm-6 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-success mb-2">
                    <i class="fas fa-user-tag"></i>
                </div>
                <h5 class="card-title">Users with Roles</h5>
                <h2 class="card-text fw-bold">{{ $totalWithRoles }}</h2>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
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
</script>
@endsection
