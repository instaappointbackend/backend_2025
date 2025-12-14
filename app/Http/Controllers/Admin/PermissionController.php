<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
{
    /**
     * Display a listing of the permissions.
     */
    public function index(Request $request)
    {
        $module = $request->get('module');
        $query = Permission::query();

        if ($module) {
            $query->where('module', $module);
        }

        $permissions = $query->orderBy('module')->orderBy('display_name')->paginate(20);
        $modules = Permission::select('module')->distinct()->orderBy('module')->pluck('module');

        return view('admin.permissions.index', compact('permissions', 'modules', 'module'));
    }

    /**
     * Show the form for creating a new permission.
     */
    public function create()
    {
        $modules = Permission::select('module')->distinct()->orderBy('module')->pluck('module');
        return view('admin.permissions.create', compact('modules'));
    }

    /**
     * Store a newly created permission in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'module' => 'required|string|max:255',
        ]);

        // Create a slug-friendly name from the display_name and module
        $name = Str::slug($request->module . ' ' . $request->display_name, '_');

        // Check if the name already exists
        if (Permission::where('name', $name)->exists()) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['display_name' => 'A permission with this name already exists in this module.']);
        }

        // Create the permission
        Permission::create([
            'name' => $name,
            'display_name' => $validated['display_name'],
            'description' => $validated['description'],
            'module' => $validated['module'],
        ]);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permission created successfully.');
    }

    /**
     * Display the specified permission.
     */
    public function show(Permission $permission)
    {
        $permission->load('roles');
        return view('admin.permissions.show', compact('permission'));
    }

    /**
     * Show the form for editing the specified permission.
     */
    public function edit(Permission $permission)
    {
        $modules = Permission::select('module')->distinct()->orderBy('module')->pluck('module');
        return view('admin.permissions.edit', compact('permission', 'modules'));
    }

    /**
     * Update the specified permission in storage.
     */
    public function update(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'display_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('permissions', 'display_name')
                    ->where('module', $request->module)
                    ->ignore($permission->id),
            ],
            'description' => 'nullable|string',
            'module' => 'required|string|max:255',
        ]);

        // Update the permission
        $permission->update([
            'display_name' => $validated['display_name'],
            'description' => $validated['description'],
            'module' => $validated['module'],
        ]);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permission updated successfully.');
    }

    /**
     * Remove the specified permission from storage.
     */
    public function destroy(Permission $permission)
    {
        // Detach permission from roles
        $permission->roles()->detach();

        // Delete the permission
        $permission->delete();

        return redirect()->route('admin.permissions.index')
            ->with('success', 'Permission deleted successfully.');
    }

    /**
     * Bulk create permissions.
     */
    public function bulkCreate()
    {
        $modules = Permission::select('module')->distinct()->orderBy('module')->pluck('module');
        return view('admin.permissions.bulk-create', compact('modules'));
    }

    /**
     * Store bulk permissions.
     */
    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'module' => 'required|string|max:255',
            'permissions' => 'required|string',
        ]);

        $permissionNames = explode("\n", $validated['permissions']);
        $createdCount = 0;

        foreach ($permissionNames as $permName) {
            $permName = trim($permName);

            if (empty($permName)) {
                continue;
            }

            $name = Str::slug($validated['module'] . ' ' . $permName, '_');

            // Skip if permission already exists
            if (Permission::where('name', $name)->exists()) {
                continue;
            }

            Permission::create([
                'name' => $name,
                'display_name' => $permName,
                'module' => $validated['module'],
            ]);

            $createdCount++;
        }

        return redirect()->route('admin.permissions.index')
            ->with('success', $createdCount . ' permissions created successfully.');
    }
}
