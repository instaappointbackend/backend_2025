<!-- resources/views/admin/permissions/bulk-create.blade.php -->
@extends('admin.layouts.app')

@section('title', 'Bulk Create Permissions')

@section('page-title', 'Bulk Create Permissions')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb m-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.permissions.index') }}">Permissions</a></li>
        <li class="breadcrumb-item active" aria-current="page">Bulk Create</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Bulk Create Permissions</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.permissions.bulk-store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="module" class="form-label">Module</label>
                <div class="input-group">
                    <select class="form-select @error('module') is-invalid @enderror" id="module" name="module">
                        @foreach($modules as $module)
                        <option value="{{ $module }}" {{ old('module') == $module ? 'selected' : '' }}>{{ ucfirst($module) }}</option>
                        @endforeach
                    </select>
                    <input type="text" class="form-control @error('module') is-invalid @enderror" id="new_module" name="new_module" placeholder="New module name" style="display: none;">
                    <button class="btn btn-outline-secondary" type="button" id="toggleNewModule">Add New Module</button>
                </div>
                @error('module')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="permissions" class="form-label">Permissions (One per line)</label>
                <textarea class="form-control @error('permissions') is-invalid @enderror" id="permissions" name="permissions" rows="10" placeholder="View users&#10;Create users&#10;Edit users&#10;Delete users" required>{{ old('permissions') }}</textarea>
                @error('permissions')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">Enter each permission on a new line. These will be the display names for the permissions.</div>
            </div>

            <div class="mb-3">
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Commonly Used Permission Sets</h6>
                    </div>
                    <div class="card-body">
                        <p>Click to add common permission sets to the textarea:</p>
                        <div class="mb-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary permission-set" data-permissions="View {resource}&#10;Create {resource}&#10;Edit {resource}&#10;Delete {resource}">
                                CRUD Set
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary permission-set" data-permissions="View {resource}&#10;List {resource}&#10;Create {resource}&#10;Edit {resource}&#10;Delete {resource}&#10;Export {resource}">
                                Extended CRUD
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary permission-set" data-permissions="View {resource}&#10;List {resource}">
                                Read-Only
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary permission-set" data-permissions="Create {resource}&#10;Edit {resource}">
                                Create/Edit
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary permission-set" data-permissions="Approve {resource}&#10;Reject {resource}&#10;Process {resource}">
                                Workflow
                            </button>
                        </div>
                        <div class="input-group mb-3">
                            <span class="input-group-text">Replace {resource} with:</span>
                            <input type="text" class="form-control" id="resourceName" placeholder="e.g. users, products">
                            <button class="btn btn-outline-primary" type="button" id="replaceResourceBtn">Replace</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <a href="{{ route('admin.permissions.index') }}" class="btn btn-secondary me-2">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Permissions</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Module toggle
        const moduleSelect = document.getElementById('module');
        const newModuleInput = document.getElementById('new_module');
        const toggleBtn = document.getElementById('toggleNewModule');
        let isNewModule = false;

        toggleBtn.addEventListener('click', function() {
            isNewModule = !isNewModule;
            moduleSelect.style.display = isNewModule ? 'none' : 'block';
            newModuleInput.style.display = isNewModule ? 'block' : 'none';
            toggleBtn.textContent = isNewModule ? 'Select Existing Module' : 'Add New Module';

            // Clear values when switching
            if (isNewModule) {
                newModuleInput.required = true;
                moduleSelect.required = false;
                moduleSelect.value = '';
            } else {
                newModuleInput.required = false;
                moduleSelect.required = true;
                newModuleInput.value = '';
            }
        });

        // Permission set buttons
        const permissionButtons = document.querySelectorAll('.permission-set');
        const permissionsTextarea = document.getElementById('permissions');

        permissionButtons.forEach(button => {
            button.addEventListener('click', function() {
                const permissions = this.dataset.permissions;

                // Append to textarea or replace if empty
                if (permissionsTextarea.value.trim() === '') {
                    permissionsTextarea.value = permissions;
                } else {
                    permissionsTextarea.value += '\n' + permissions;
                }
            });
        });

        // Replace resource placeholder
        const resourceInput = document.getElementById('resourceName');
        const replaceBtn = document.getElementById('replaceResourceBtn');

        replaceBtn.addEventListener('click', function() {
            const resourceName = resourceInput.value.trim();
            if (resourceName) {
                permissionsTextarea.value = permissionsTextarea.value.replace(/{resource}/g, resourceName);
            }
        });

        // Handle form submission
        document.querySelector('form').addEventListener('submit', function(e) {
            if (isNewModule) {
                // Use the new module input value
                const newModuleValue = newModuleInput.value.trim();
                if (newModuleValue) {
                    moduleSelect.innerHTML += `<option value="${newModuleValue}" selected>${newModuleValue}</option>`;
                    newModuleInput.value = '';
                    newModuleInput.style.display = 'none';
                    moduleSelect.style.display = 'block';
                    isNewModule = false;
                } else {
                    e.preventDefault();
                    alert('Please enter a module name');
                }
            }
        });
    });
</script>
@endsection
