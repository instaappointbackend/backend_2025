<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminNotificationController extends Controller
{
    /**
     * Show the notification test panel
     */
    public function showTestPanel()
    {
        // Only allow admins to access this
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized access.');
        }

        // Get all users for the dropdown
        $users = User::orderBy('role')
            ->orderBy('name')
            ->get();

        return view('admin.notification-test', compact('users'));
    }
}