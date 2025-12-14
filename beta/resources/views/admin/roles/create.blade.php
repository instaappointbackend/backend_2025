@extends('admin.layouts.app')

@section('title', 'Create Role')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-plus mr-2"></i>
                        Create New Role
                    </h3>
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Roles
                    </a>
                </div>

                <form method="POST" action="{{ route('admin.roles.store') }}">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="display_name">Display Name <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control @error('display_name') is-invalid @enderror" 
                                           id="display_name" 
                                           name="display_name" 
                                           value="{{ old('display_name') }}" 
                                           placeholder="e.g., Content Manager"
                                           required>
                                    @error('display_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        This is the human-readable name for the role.
                                    </small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" 
                                              id="description" 
                                              name="description" 
                                              rows="3"
                                              placeholder="Brief description of this role's purpose">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h5 class="mb-3">
                            <i class="fas fa-key mr-2"></i>
                            Assign Permissions
                        </h5>

                        @if($modulePermissions->count() > 0)
                            <div class="row">
                                @foreach($modulePermissions as $module => $permissions)
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card">
                                            <div class="card-header">
                                                <h6 class="mb-0">
                                                    <i class="fas fa-folder mr-1"></i>
                                                    {{ ucwords(str_replace('_', ' ', $module)) }}
                                                </h6>
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" 
                                                           class="custom-control-input module-toggle" 
                                                           id="module_{{ $module }}"
                                                           data-module="{{ $module }}">
                                                    <label class="custom-control-label" for="module_{{ $module }}">
                                                        <small>Select All</small>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                @foreach($permissions as $permission)
                                                    <div class="custom-control custom-checkbox mb-2">
                                                        <input type="checkbox" 
                                                               class="custom-control-input permission-checkbox" 
                                                               id="permission_{{ $permission->id }}" 
                                                               name="permissions[]" 
                                                               value="{{ $permission->id }}"
                                                               data-module="{{ $module }}"
                                                               {{ in_array($permission->id, old('permissions', [])) ? 'checked' : '' }}>
                                                        <label class="custom-control-label" for="permission_{{ $permission->id }}">
                                                            <strong>{{ $permission->display_name }}</strong>
                                                            @if($permission->description)
                                                                <br><small class="text-muted">{{ $permission->description }}</small>
                                                            @endif
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                No permissions found. Please create permissions first.
                            </div>
                        @endif
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Create Role
                        </button>
                        <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary ml-2">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Module toggle functionality
    $('.module-toggle').change(function() {
        var module = $(this).data('module');
        var isChecked = $(this).is(':checked');
        
        $('input[data-module="' + module + '"].permission-checkbox').prop('checked', isChecked);
    });
    
    // Update module toggle when individual permissions change
    $('.permission-checkbox').change(function() {
        var module = $(this).data('module');
        var totalPermissions = $('input[data-module="' + module + '"].permission-checkbox').length;
        var checkedPermissions = $('input[data-module="' + module + '"].permission-checkbox:checked').length;
        
        var moduleToggle = $('#module_' + module);
        
        if (checkedPermissions === 0) {
            moduleToggle.prop('indeterminate', false);
            moduleToggle.prop('checked', false);
        } else if (checkedPermissions === totalPermissions) {
            moduleToggle.prop('indeterminate', false);
            moduleToggle.prop('checked', true);
        } else {
            moduleToggle.prop('indeterminate', true);
        }
    });
    
    // Initialize module toggles
    $('.module-toggle').each(function() {
        $(this).trigger('change');
    });
});
</script>
@endsection