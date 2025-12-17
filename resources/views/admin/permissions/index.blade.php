<!-- resources/views/admin/permissions/index.blade.php -->
@extends('admin.layouts.app')

@section('title', 'Permission Management')

@section('page-title', 'Permission Management')

@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Permissions</li>
        </ol>
    </nav>
@endsection

@section('page-actions')
    <div class="btn-group">
        <a href="{{ route('admin.permissions.create') }}" class="btn btn-primary">
            <i class="fas fa-plus-circle me-1"></i> Add Permission
        </a>
        <a href="{{ route('admin.permissions.bulk-create') }}" class="btn btn-success">
            <i class="fas fa-upload me-1"></i> Bulk Create
        </a>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Permissions</h5>

            <form action="{{ route('admin.permissions.index') }}" method="GET" class="d-flex">
                <select name="module" class="form-select me-2" onchange="this.form.submit()">
                    <option value="">All Modules</option>
                    @foreach ($modules as $m)
                        <option value="{{ $m }}" {{ $module == $m ? 'selected' : '' }}>{{ ucfirst($m) }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Permission Name</th>
                            <th>Module</th>
                            <th>Description</th>
                            <th>Roles</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($permissions as $permission)
                            <tr>
                                <td>{{ $permission->display_name }}</td>
                                <td><span class="badge bg-info">{{ ucfirst($permission->module) }}</span></td>
                                <td>{{ $permission->description ?? 'N/A' }}</td>
                                <td>{{ $permission->roles->count() }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('admin.permissions.show', $permission->id) }}"
                                            class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.permissions.edit', $permission->id) }}"
                                            class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal"
                                            data-bs-target="#deleteModal{{ $permission->id }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>

                                    <!-- Delete Modal -->
                                    <div class="modal fade" id="deleteModal{{ $permission->id }}" tabindex="-1"
                                        aria-labelledby="deleteModalLabel{{ $permission->id }}" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deleteModalLabel{{ $permission->id }}">
                                                        Confirm Delete</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to delete the permission
                                                        <strong>{{ $permission->display_name }}</strong>?
                                                    </p>
                                                    <p class="text-danger">This action cannot be undone and will revoke this
                                                        permission from all roles.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-bs-dismiss="modal">Cancel</button>
                                                    <form
                                                        action="{{ route('admin.permissions.destroy', $permission->id) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger">Delete
                                                            Permission</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No permissions found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    Showing {{ $permissions->firstItem() ?? 0 }} to {{ $permissions->lastItem() ?? 0 }} of
                    {{ $permissions->total() }}
                    permissions
                </div>
                <div class="d-flex justify-content-end mt-3">
                    {{ $permissions->onEachSide(5)->links() }}
                </div>
            </div>

        </div>
    </div>
@endsection
