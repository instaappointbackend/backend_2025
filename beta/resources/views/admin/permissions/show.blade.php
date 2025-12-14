<!-- resources/views/admin/permissions/show.blade.php -->
@extends('admin.layouts.app')

@section('title', 'Permission Details')

@section('page-title', 'Permission Details')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb m-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.permissions.index') }}">Permissions</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $permission->display_name }}</li>
    </ol>
</nav>
@endsection

@section('page-actions')
<a href="{{ route('admin.permissions.edit', $permission->id) }}" class="btn btn-primary">
    <i class="fas fa-edit me-1"></i> Edit Permission
</a>
@endsection

@section('content')
<div class="row">
    <div class="col-md-5">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Permission Information</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted">Name</label>
                    <div class="form-control-plaintext">{{ $permission->display_name }}</div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">System Name</label>
                    <div class="form-control-plaintext">{{ $permission->name }}</div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">Module</label>
                    <div class="form-control-plaintext">
                        <span class="badge bg-info">{{ ucfirst($permission->module) }}</span>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">Description</label>
                    <div class="form-control-plaintext">{{ $permission->description ?? 'No description provided.' }}</div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">Created</label>
                    <div class="form-control-plaintext">{{ $permission->created_at->format('M d, Y H:i') }}</div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">Last Updated</label>
                    <div class="form-control-plaintext">{{ $permission->updated_at->format('M d, Y H:i') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Roles with this Permission</h5>
            </div>
            <div class="card-body">
                @if($permission->roles->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th>Role Name</th>
                            <th>Description</th>
                            <th>Users</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($permission->roles as $role)
                        <tr>
                            <td>{{ $role->display_name }}</td>
                            <td>{{ $role->description ?? 'N/A' }}</td>
                            <td>{{ $role->users->count() }}</td>
                            <td>
                                <a href="{{ route('admin.roles.show', $role->id) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> View Role
                                </a>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="alert alert-info">
                    This permission is not assigned to any roles yet.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
