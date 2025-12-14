<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    /**
     * Display a listing of the roles.
     */
    public function index()
    {
        $roles = Role::withCount('users')->orderBy('name')->get();
        return view('admin.roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new role.
     */
    public function create()
    {
        $modulePermissions = Permission::getModulesWithPermissions();
        return view('admin.roles.create', compact('modulePermissions'));
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        // Create a slug-friendly name from the display_name
        $name = Str::slug($request->display_name, '_');

        // Check if the name already exists
        if (Role::where('name', $name)->exists()) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['display_name' => 'A role with this name already exists.']);
        }

        // Create the role
        $role = Role::create([
            'name' => $name,
            'display_name' => $validated['display_name'],
            'description' => $validated['description'],
        ]);

        // Assign permissions to the role
        if (isset($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role created successfully.');
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role)
    {
        $role->load('permissions');
        $users = User::where('role_id', $role->id)->paginate(10);
        return view('admin.roles.show', compact('role', 'users'));
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Role $role)
    {
        $modulePermissions = Permission::getModulesWithPermissions();
        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('admin.roles.edit', compact('role', 'modulePermissions', 'rolePermissions'));
    }

    /**
     * Update the specified role in storage.
     */
    public function update(Request $request, Role $role)
    {
        // Prevent editing system roles
        if ($role->is_system) {
            return redirect()->route('admin.roles.index')
                ->with('error', 'System roles cannot be edited.');
        }

        $validated = $request->validate([
            'display_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'display_name')->ignore($role->id),
            ],
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        // Update the role
        $role->update([
            'display_name' => $validated['display_name'],
            'description' => $validated['description'],
        ]);

        // Update permissions
        if (isset($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        } else {
            $role->permissions()->detach();
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(Role $role)
    {
        // Prevent deleting system roles
        if ($role->is_system) {
            return redirect()->route('admin.roles.index')
                ->with('error', 'System roles cannot be deleted.');
        }

        // Check if any users are assigned to this role
        if ($role->users()->count() > 0) {
            return redirect()->route('admin.roles.index')
                ->with('error', 'This role cannot be deleted because it is assigned to users.');
        }

        $role->permissions()->detach();
        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role deleted successfully.');
    }

    /**
     * Assign users to a role.
     */
    public function assignUsers(Request $request, Role $role)
    {
        $validated = $request->validate([
            'users' => 'required|array',
            'users.*' => 'exists:users,id',
        ]);

        try {
            $assignedCount = 0;
            foreach ($validated['users'] as $userId) {
                $user = User::find($userId);
                if ($user) {
                    $user->role_id = $role->id;
                    $user->save();
                    $assignedCount++;
                }
            }

            return redirect()->route('admin.roles.show', $role)
                ->with('success', "Successfully assigned {$assignedCount} user(s) to the {$role->display_name} role.");
        } catch (\Exception $e) {
            return redirect()->route('admin.roles.show', $role)
                ->with('error', 'Failed to assign users to role: ' . $e->getMessage());
        }
    }

    /**
     * Remove a user from a role.
     */
    public function removeUser(Role $role, User $user)
    {
        $user->update(['role_id' => null]);

        return redirect()->route('admin.roles.show', $role)
            ->with('success', 'User removed from role successfully.');
    }
}
