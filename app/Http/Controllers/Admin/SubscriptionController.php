<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {

        $query = Subscription::with('user')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim($request->search);

                $q->where(function ($query) use ($search) {
                    $query->where('transaction_id', 'LIKE', "%{$search}%")
                        ->orWhere('plan_name', 'LIKE', "%{$search}%")
                        ->orWhereHas('user', function ($q) use ($search) {
                            $q->where('name', 'LIKE', "%{$search}%")
                                ->orWhere('mobile', 'LIKE', "%{$search}%");
                        });
                });
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', trim($request->status));
            })
            ->latest();

        $users = $query->paginate(10)->withQueryString();
        $isSocial = false;
        // dd($users);
        return view('admin.subscription.index', compact('users', 'isSocial'));
    }
}
