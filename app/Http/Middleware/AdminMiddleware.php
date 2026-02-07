<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user is logged in and has admin role
        if (Auth::check() && Auth::user()->role === 'admin') {
            return $next($request);
        }

        // If not admin, logout and redirect to admin login
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')
            ->with('error', 'Unauthorized access. Please login with admin credentials.');
    }
}

// Then, register the middleware in bootstrap/app.php:
/*
->withMiddleware(function (Middleware $middleware) {
    // Add your middleware here
    $middleware->alias([
        'admin' => \App\Http\Middleware\AdminMiddleware::class,
    ]);
})
*/
