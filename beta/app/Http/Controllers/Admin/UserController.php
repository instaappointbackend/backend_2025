<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\BusinessCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the backend users.
     */
    public function index(Request $request)
    {
        // Find users who are admins or have assigned roles
        $query = User::query()
            ->where(function($q) {
                $q->where('role', 'admin')
                    ->orWhereNotNull('role_id');
            });

        // Filter by role ID if provided
        if ($request->has('role_id') && $request->role_id) {
            $query->where('role_id', $request->role_id);
        }

        // Filter by system role if provided
        if ($request->has('system_role') && $request->system_role) {
            $query->where('role', $request->system_role);
        }

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status == 'active');
        }

        // Search by name, email or mobile
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(15);

        // Get all available roles for filtering
        $roles = Role::orderBy('display_name')->get();

        // Get statistics for dashboard cards
        $totalUsers = $query->count();
        $totalAdmins = User::where('role', 'admin')->count();
        $totalWithRoles = User::whereNotNull('role_id')->count();

        return view('admin.users.index', compact('users', 'roles', 'totalUsers', 'totalAdmins', 'totalWithRoles'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $businessCategories = BusinessCategory::all();
        $roles = Role::orderBy('display_name')->get();
        return view('admin.users.create', compact('businessCategories', 'roles'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'mobile' => 'required|string|max:20|unique:users',
            'role' => 'required|string|in:admin,vendor,customer',
            'role_id' => 'nullable|exists:roles,id',
            'gender' => 'nullable|string|in:male,female,other',
            'dob' => 'nullable|date',
            'bio' => 'nullable|string',
            'business_category_id' => 'nullable|exists:business_categories,id',
            'experience' => 'nullable|integer',
            'status' => 'boolean',
            'profile_picture' => 'nullable|image|max:5120',
            'address' => 'nullable|string',
            'full_address' => 'nullable|string',
            'street' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'password' => 'required|min:8|confirmed',
        ]);

        // Generate referral code
        $validated['referral_code'] = strtoupper(Str::random(8));

        // Hash the password
        $validated['password'] = bcrypt($request->password);

        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            $validated['profile_picture'] = $request->file('profile_picture')
                ->store('profile_pictures', 'public');
        }

        // Create the user
        $user = User::create($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $user->load(['businessCategory', 'kycDocument', 'teamMembers', 'userRole']);
        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $businessCategories = BusinessCategory::all();
        $roles = Role::orderBy('display_name')->get();
        return view('admin.users.edit', compact('user', 'businessCategories', 'roles'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'mobile' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users')->ignore($user->id),
            ],
            'role' => 'required|string|in:admin,vendor,customer',
            'role_id' => 'nullable|exists:roles,id',
            'gender' => 'nullable|string|in:male,female,other',
            'dob' => 'nullable|date',
            'bio' => 'nullable|string',
            'business_category_id' => 'nullable|exists:business_categories,id',
            'experience' => 'nullable|integer',
            'status' => 'boolean',
            'profile_picture' => 'nullable|image|max:5120',
            'address' => 'nullable|string',
            'full_address' => 'nullable|string',
            'street' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'password' => 'nullable|min:8|confirmed',
        ]);

        // Handle password update (only if provided)
        if ($request->filled('password')) {
            $validated['password'] = bcrypt($request->password);
        } else {
            unset($validated['password']);
        }

        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            // Delete previous profile picture if exists
            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            $validated['profile_picture'] = $request->file('profile_picture')
                ->store('profile_pictures', 'public');
        }

        // Update the user
        $user->update($validated);

        return redirect()->back()
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        try {
            // First, delete any payout requests that reference this user's bank accounts
            $user->payoutRequests()->delete();

            // Then delete the user's bank accounts
            $user->bankAccounts()->delete();

            // Delete profile picture if exists
            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            // Delete the user
            $user->delete();

            return redirect()->back()
                ->with('success', 'User deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete user: ' . $e->getMessage());
        }
    }

    /**
     * Toggle the user's status.
     */
    public function toggleStatus(User $user)
    {
        $user->status = !$user->status;
        $user->save();

        return redirect()->back()
            ->with('success', 'User status updated successfully.');
    }

    /**
     * Show vendors list.
     */
    public function vendors(Request $request)
    {
        $query = User::where('role', 'vendor');

        // Filter by status if provided
        if (!empty($request->has('status')) && $request->has('status') && $request->status !== '') {
            $query->where('status', $request->status == '1');
        }

        // Filter by KYC status if provided
        if ($request->has('kyc_status') && $request->kyc_status !== '') {
            if ($request->kyc_status == 'verified') {
                $query->where('is_kyc_completed', true);
            } elseif ($request->kyc_status == 'pending') {
                $query->where('is_kyc_completed', false);
            }
        }

        // Filter by business type if provided
        if ($request->has('business_category_id') && $request->business_category_id) {
            $query->where('business_category_id', $request->business_category_id);
        }

        // Search by name, email or mobile
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        // Add recent filter from dashboard notifications
        if ($request->has('recent') && $request->recent == 'today') {
            $query->whereDate('created_at', today());
        }

        $vendors = $query->with(['businessCategory'])
            ->latest()
            ->paginate(15);

        $businessCategories = BusinessCategory::all();

        return view('admin.users.vendors', compact('vendors', 'businessCategories'));
    }

// Also update your customers() method for consistency:

    /**
     * Show customers list.
     */
    public function customers(Request $request)
    {
        $query = User::where('role', 'customer');

        // Filter by status if provided
//        if (!empty($request->has('status')) && $request->has('status') && $request->status !== '') {
//            $query->where('status', $request->status == '1');
//        }

        // Search by name, email or mobile
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        // Add recent filter from dashboard notifications
        if ($request->has('recent') && $request->recent == 'today') {
            $query->whereDate('created_at', today());
        }

        $customers = $query->latest()->paginate(15);

        return view('admin.users.customers', compact('customers'));
    }
}
