<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index()
    {
        //$users =  Subscription::with('user')->orderBy('created_at', 'desc')->get();
        $users = Subscription::latest()
            ->paginate(3)
            ->withQueryString();

        return view('admin.subscription.index', compact('users'));
    }
}
