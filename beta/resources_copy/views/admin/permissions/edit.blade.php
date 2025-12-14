<!-- resources/views/admin/permissions/edit.blade.php -->
@extends('admin.layouts.app')

@section('title', 'Edit Permission')

@section('page-title', 'Edit Permission')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb m-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.permissions.index') }}">Permissions</a></li>
        <li class="breadcrumb-item active" aria-current="page">Edit Permission</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Edit Permission: {{ $permission->display_name }}</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.permissions.update', $permission->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="display_name" class="form-label">Permission Name</label>
                <input type="text" class="form-control @error('display_name') is-invalid @enderror" id="display_name" name="display_name" value="{{ old('display_name', $permission->display_name) }}" required>
                @error('display_name')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="module" class="form-label">Module</label>
                <div class="input-group">
                    <select class="form-select @error('module') is-invalid @enderror" id="module" name="module">
                        @foreach($modules as $module)
                        <option value="{{ $module }}" {{ old('module', $permission->module) == $module ? 'selected' : '' }}>{{ ucfirst($module) }}</option>
                        @endforeach
                    </select>
                    <input type="text" class="form-control @error('module') is-invalid @enderror" id="new_module" name="new_module" placeholder="New module name" style="display: none;">
                    <button class="btn btn-outline-secondary" type="button" id="toggleNewModule">Add New</button>
                </div>
                @error('module')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $permission->description) }}</textarea>
                @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex justify-content-end">
                <a href="{{ route('admin.permissions.index') }}" class="btn btn-secondary me-2">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Permission</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const moduleSelect = document.getElementById('module');
        const newModuleInput = document.getElementById('new_module');
        const toggleBtn = document.getElementById('toggleNewModule');
        let isNewModule = false;

        toggleBtn.addEventListener('click', function() {
            isNewModule = !isNewModule;
            moduleSelect.style.display = isNewModule ? 'none' : 'block';
            newModuleInput.style.display = isNewModule ? 'block' : 'none';
            toggleBtn.textContent = isNewModule ? 'Select Existing' : 'Add New';

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
