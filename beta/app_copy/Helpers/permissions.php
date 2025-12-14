<?php

use Illuminate\Support\Facades\Auth;

/**
 * Check if the authenticated user has a specific permission
 *
 * @param string $permission
 * @return bool
 */
function hasPermission($permission)
{
    $user = Auth::user();
    if (!$user) {
        return false;
    }

    return $user->hasPermission($permission);
}

/**
 * Check if the authenticated user has a specific role
 *
 * @param string $role
 * @return bool
 */
function hasRole($role)
{
    $user = Auth::user();
    if (!$user) {
        return false;
    }

    return $user->hasRole($role);
}

/**
 * Check if the authenticated user is a super admin
 *
 * @return bool
 */
function isSuperAdmin()
{
    $user = Auth::user();
    if (!$user) {
        return false;
    }

    return $user->isSuperAdmin();
}

/**
 * Get all permissions for the authenticated user
 *
 * @return \Illuminate\Support\Collection
 */
function getUserPermissions()
{
    $user = Auth::user();
    if (!$user) {
        return collect();
    }

    return $user->getAllPermissions();
}
