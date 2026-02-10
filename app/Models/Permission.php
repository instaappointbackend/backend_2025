<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'module',
    ];

    /**
     * Get the roles that own the permission.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permission');
    }

    /**
     * Scope a query to only include permissions of a specific module.
     */
    public function scopeModule($query, $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Get all modules with their permissions.
     */
    public static function getModulesWithPermissions()
    {
        return self::select('module')
            ->distinct()
            ->orderBy('module')
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->module => self::where('module', $item->module)
                        ->orderBy('display_name')
                        ->get(),
                ];
            });
    }
}
