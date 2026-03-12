<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    /**
     * Get the users for the role.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the permissions for the role.
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    /**
     * Check if the role has a specific permission.
     */
    public function hasPermission($permission)
    {
        if (is_string($permission)) {
            return $this->permissions->contains('name', $permission);
        }

        return $permission->intersect($this->permissions)->count() > 0;
    }

    /**
     * Assign permissions to the role.
     */
    public function assignPermissions($permissions)
    {
        return $this->permissions()->sync($permissions);
    }

    /**
     * Give permissions to the role.
     */
    public function givePermissionTo($permissions)
    {
        $permissions = is_array($permissions) ? $permissions : [$permissions];

        return $this->permissions()->syncWithoutDetaching($permissions);
    }

    /**
     * Revoke permissions from the role.
     */
    public function revokePermissionTo($permissions)
    {
        $permissions = is_array($permissions) ? $permissions : [$permissions];

        return $this->permissions()->detach($permissions);
    }
}
