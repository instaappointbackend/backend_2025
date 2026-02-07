<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (! Auth::check()) {
            return redirect()->route('admin.login');
        }

        $user = Auth::user();

        // Super admin bypass all role checks
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if user has the required role
        if ($user->hasRole($role)) {
            return $next($request);
        }

        // If not, redirect to dashboard with error
        return redirect()->route('admin.dashboard')
            ->with('error', 'You do not have permission to access this feature.');
    }
}
