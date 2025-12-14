@extends('admin.layouts.app')

@section('title', 'Manage Roles')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-user-shield mr-2"></i>
                        Manage Roles
                    </h3>
                    <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus mr-1"></i> Create New Role
                    </a>
                </div>

                <div class="card-body">
                    @if($roles->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Role Name</th>
                                        <th>Display Name</th>
                                        <th>Description</th>
                                        <th>Users Count</th>
                                        <th>System Role</th>
                                        <th>Created</th>
                                        <th width="200">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($roles as $role)
                                        <tr>
                                            <td>{{ $role->id }}</td>
                                            <td>
                                                <code>{{ $role->name }}</code>
                                            </td>
                                            <td>
                                                <strong>{{ $role->display_name }}</strong>
                                            </td>
                                            <td>
                                                {{ $role->description ?? 'No description' }}
                                            </td>
                                            <td>
                                                <span class="badge badge-info">
                                                    {{ $role->users_count }} users
                                                </span>
                                            </td>
                                            <td>
                                                @if($role->is_system)
                                                    <span class="badge badge-warning">System</span>
                                                @else
                                                    <span class="badge badge-secondary">Custom</span>
                                                @endif
                                            </td>
                                            <td>
                                                {{ $role->created_at->format('M d, Y') }}
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="{{ route('admin.roles.show', $role) }}" 
                                                       class="btn btn-info" 
                                                       title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    
                                                    @if(!$role->is_system)
                                                        <a href="{{ route('admin.roles.edit', $role) }}" 
                                                           class="btn btn-primary" 
                                                           title="Edit Role">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        
                                                        @if($role->users_count == 0)
                                                            <form method="POST" 
                                                                  action="{{ route('admin.roles.destroy', $role) }}" 
                                                                  style="display: inline;"
                                                                  onsubmit="return confirm('Are you sure you want to delete this role?')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" 
                                                                        class="btn btn-danger" 
                                                                        title="Delete Role">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        @endif
                                                    @else
                                                        <span class="btn btn-secondary btn-sm disabled" title="System role cannot be edited">
                                                            <i class="fas fa-lock"></i>
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-user-shield fa-3x text-muted mb-3"></i>
                            <h5>No roles found</h5>
                            <p class="text-muted">Create your first role to get started.</p>
                            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus mr-1"></i> Create Role
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection