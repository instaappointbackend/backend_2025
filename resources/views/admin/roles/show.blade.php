@extends('admin.layouts.app')

@section('title', 'Role Details')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8">
                <!-- Role Details -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">
                            <i class="fas fa-user-shield mr-2"></i>
                            Role Details: {{ $role->display_name }}
                        </h3>
                        <div>
                            @if (!$role->is_system)
                                <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-primary mr-2">
                                    <i class="fas fa-edit mr-1"></i> Edit Role
                                </a>
                            @endif
                            <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i> Back to Roles
                            </a>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6>Basic Information</h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>ID:</strong></td>
                                        <td>{{ $role->id }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Name:</strong></td>
                                        <td><code>{{ $role->name }}</code></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Display Name:</strong></td>
                                        <td>{{ $role->display_name }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Description:</strong></td>
                                        <td>{{ $role->description ?? 'No description provided' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Type:</strong></td>
                                        <td>
                                            @if ($role->is_system)
                                                <span class="badge badge-warning">System Role</span>
                                            @else
                                                <span class="badge badge-info">Custom Role</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Created:</strong></td>
                                        <td>{{ $role->created_at->format('M d, Y \a\t h:i A') }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Updated:</strong></td>
                                        <td>{{ $role->updated_at->format('M d, Y \a\t h:i A') }}</td>
                                    </tr>
                                </table>
                            </div>

                            <div class="col-md-6">
                                <h6>Statistics</h6>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-info">
                                                <i class="fas fa-users"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Users</span>
                                                <span class="info-box-number">{{ $users->total() }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-success">
                                                <i class="fas fa-key"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Permissions</span>
                                                <span class="info-box-number">{{ $role->permissions->count() }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Permissions -->
                        <h6>Assigned Permissions</h6>
                        @if ($role->permissions->count() > 0)
                            <div class="row">
                                @php
                                    $groupedPermissions = $role->permissions->groupBy('module');
                                @endphp
                                @foreach ($groupedPermissions as $module => $permissions)
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card">
                                            <div class="card-header">
                                                <h6 class="mb-0">
                                                    <i class="fas fa-folder mr-1"></i>
                                                    {{ ucwords(str_replace('_', ' ', $module)) }}
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                @foreach ($permissions as $permission)
                                                    <div class="mb-2">
                                                        <i class="fas fa-check text-success mr-1"></i>
                                                        <strong>{{ $permission->display_name }}</strong>
                                                        @if ($permission->description)
                                                            <br><small
                                                                class="text-muted ml-3">{{ $permission->description }}</small>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                No permissions assigned to this role.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Assigned Users -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-users mr-2"></i>
                            Assigned Users ({{ $users->total() }})
                        </h5>
                    </div>

                    <div class="card-body">
                        @if ($users->count() > 0)
                            <div class="list-group list-group-flush">
                                @foreach ($users as $user)
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <div class="d-flex align-items-center">
                                            @if ($user->profile_picture)
                                                <img src="{{ asset('storage/' . $user->profile_picture) }}"
                                                    class="rounded-circle mr-2" width="32" height="32"
                                                    alt="{{ $user->name }}">
                                            @else
                                                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center mr-2"
                                                    style="width: 32px; height: 32px;">
                                                    <i class="fas fa-user text-white"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="font-weight-bold">{{ $user->name }}</div>
                                                <small class="text-muted">{{ $user->email }}</small>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="badge badge-{{ $user->status ? 'success' : 'secondary' }}">
                                                {{ $user->status ? 'Active' : 'Inactive' }}
                                            </span>
                                            @if (!$role->is_system)
                                                <form method="POST"
                                                    action="{{ route('admin.roles.remove-user', [$role, $user]) }}"
                                                    style="display: inline;"
                                                    onsubmit="return confirm('Remove this user from the role?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger ml-1">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-3">
                                {{ $users->onEachSide(5)->links() }}
                            </div>
                        @else
                            <div class="text-center py-3">
                                <i class="fas fa-users fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No users assigned to this role</p>
                            </div>
                        @endif
                    </div>

                    @if (!$role->is_system)
                        <div class="card-footer">
                            <button type="button" class="btn btn-primary btn-sm btn-block" data-toggle="modal"
                                data-target="#assignUsersModal">
                                <i class="fas fa-plus mr-1"></i> Assign Users
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Users Modal -->
    @if (!$role->is_system)
        <div class="modal fade" id="assignUsersModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Assign Users to {{ $role->display_name }}</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('admin.roles.assign-users', $role) }}">
                        @csrf
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Select Users to Assign:</label>
                                <div class="row">
                                    @php
                                        $availableUsers = \App\Models\User::whereNull('role_id')
                                            ->orWhere('role_id', '!=', $role->id)
                                            ->get();
                                    @endphp
                                    @forelse($availableUsers as $user)
                                        <div class="col-md-6 mb-2">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input"
                                                    id="user_{{ $user->id }}" name="users[]"
                                                    value="{{ $user->id }}">
                                                <label class="custom-control-label" for="user_{{ $user->id }}">
                                                    <div class="d-flex align-items-center">
                                                        @if ($user->profile_picture)
                                                            <img src="{{ asset('storage/' . $user->profile_picture) }}"
                                                                class="rounded-circle mr-2" width="24" height="24"
                                                                alt="{{ $user->name }}">
                                                        @else
                                                            <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center mr-2"
                                                                style="width: 24px; height: 24px;">
                                                                <i class="fas fa-user text-white"
                                                                    style="font-size: 10px;"></i>
                                                            </div>
                                                        @endif
                                                        <div>
                                                            <div class="font-weight-bold">{{ $user->name }}</div>
                                                            <small class="text-muted">{{ $user->email }}</small>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-12">
                                            <div class="alert alert-info">
                                                No available users to assign.
                                            </div>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Assign Users
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection
