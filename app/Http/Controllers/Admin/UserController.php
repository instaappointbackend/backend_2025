<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessCategory;
use App\Models\Role;
use App\Models\User;
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
            ->where(function ($q) {
                $q->where('role', 'admin')
                    ->orWhereNotNull('role_id');
            });

        // Filter by role ID if provided
        if ($request->filled('role_id') && $request->role_id) {
            $query->where('role_id', $request->role_id);
        }

        // Filter by system role if provided
        if ($request->filled('system_role') && $request->system_role) {
            $query->where('role', $request->system_role);
        }

        // Filter by status if provided
        if ($request->filled('status')) {
            $query->where('status', $request->status == 'active');
        }

        // Search by name, email or mobile
        if ($request->filled('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
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
                // Rule::unique('users')->ignore($user->id),
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
                ->with('error', 'Failed to delete user: '.$e->getMessage());
        }
    }

    /**
     * Toggle the user's status.
     */
    public function toggleStatus(User $user)
    {
        $user->status = ! $user->status;
        $user->save();

        return redirect()->back()
            ->with('success', 'User status updated successfully.');
    }

    /**
     * Show vendors list.
     */
    public function vendors(Request $request)
    {
        $query = User::with('businessCategory')->where('role', 'vendor');

        // Filter by status if provided
        if (! empty($request->filled('status')) && $request->filled('status') && $request->status !== '') {
            $query->where('status', $request->status == '1');
        }

        // Filter by KYC status if provided
        if ($request->filled('kyc_status') && $request->kyc_status !== '') {
            if ($request->kyc_status == 'verified') {
                $query->where('is_kyc_completed', true);
            } elseif ($request->kyc_status == 'pending') {
                $query->where('is_kyc_completed', false);
            }
        }

        // Filter by business type if provided
        if ($request->filled('business_category_id') && $request->business_category_id) {
            $query->where('business_category_id', $request->business_category_id);
        }

        // Search by name, email or mobile
        if ($request->filled('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        // Add recent filter from dashboard notifications
        if ($request->filled('recent') && $request->recent == 'today') {
            $query->whereDate('created_at', today());
        }

        $vendors = $query->with(['businessCategory'])
            ->latest()
            ->paginate(15);

        $businessCategories = BusinessCategory::all();

        return view('admin.users.vendors', compact('vendors', 'businessCategories'));
    }

    public function exportVendors(Request $request)
    {
        $query = User::where('role', 'vendor')->with(['businessCategory']);

        // Status filter
        if ($request->has('status') && $request->status !== '' && $request->status != 'all') {
            $query->where('status', $request->status == '1');
        }

        // KYC Status filter
        if ($request->has('kyc_status') && $request->kyc_status !== '') {
            if ($request->kyc_status == 'verified') {
                $query->where('is_kyc_completed', true);
            } elseif ($request->kyc_status == 'pending') {
                $query->where('is_kyc_completed', false);
            }
        }

        // Business category filter
        if ($request->has('business_category_id') && $request->business_category_id) {
            $query->where('business_category_id', $request->business_category_id);
        }

        // Search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        // Recent vendors (today)
        if ($request->has('recent') && $request->recent == 'today') {
            $query->whereDate('created_at', today());
        }

        // Fetch vendors
        $vendors = $query->get();

        // CSV data
        $csvData = [];

        // CSV headers
        $csvData[] = [
            'Vendor ID',
            'Name',
            'Email',
            'Mobile',
            'Business Category',
            'Status',
            'KYC Status',
            'Created Date',
        ];

        // Data rows
        foreach ($vendors as $vendor) {
            $csvData[] = [
                $vendor->id,
                $vendor->name,
                $vendor->email,
                $vendor->mobile,
                $vendor->businessCategory ? $vendor->businessCategory->name : 'N/A',
                $vendor->status ? 'Active' : 'Inactive',
                $vendor->is_kyc_completed ? 'Verified' : 'Pending',
                $vendor->created_at->format('Y-m-d H:i:s'),
            ];
        }

        // Create filename
        $filename = 'vendors_export_'.date('Y-m-d_H-i-s').'.csv';
        $filepath = storage_path('app/public/exports/'.$filename);

        // Ensure directory exists
        if (! file_exists(storage_path('app/public/exports/'))) {
            mkdir(storage_path('app/public/exports/'), 0755, true);
        }

        // Write CSV file
        $file = fopen($filepath, 'w');
        foreach ($csvData as $row) {
            fputcsv($file, $row);
        }
        fclose($file);

        // Download response
        return response()->download($filepath, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Show vendors list.
     */
    public function deletedVendors(Request $request)
    {
        $query = User::onlyTrashed()->where('role', 'vendor');

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status == '1');
        }

        // Filter by KYC status
        if ($request->has('kyc_status') && $request->kyc_status !== '') {
            if ($request->kyc_status == 'verified') {
                $query->where('is_kyc_completed', true);
            } elseif ($request->kyc_status == 'pending') {
                $query->where('is_kyc_completed', false);
            }
        }

        // Filter by business category
        if ($request->has('business_category_id') && $request->business_category_id) {
            $query->where('business_category_id', $request->business_category_id);
        }

        // Search by name, email, or mobile inside JSON
        if ($request->has('search') && $request->search) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        // Filter today's records
        if ($request->has('recent') && $request->recent == 'today') {
            $query->whereDate('created_at', today());
        }

        $vendors = $query->latest()->paginate(15);

        $businessCategories = BusinessCategory::all();

        // dd($vendors);

        return view('admin.users.deletedVendors', compact('vendors', 'businessCategories'));
    }

    public function exportDeletedVendors(Request $request)
    {
        $query = User::onlyTrashed()->where('role', 'vendor');

        // Status filter
        if ($request->has('status') && $request->status !== '' && $request->status != 'all') {
            $query->where('data->status', $request->status == '1');
        }

        // KYC status filter
        if ($request->has('kyc_status') && $request->kyc_status !== '') {
            if ($request->kyc_status == 'verified') {
                $query->where('data->is_kyc_completed', true);
            } elseif ($request->kyc_status == 'pending') {
                $query->where('data->is_kyc_completed', false);
            }
        }

        // Business category filter
        if ($request->has('business_category_id') && $request->business_category_id) {
            $query->where('data->business_category_id', $request->business_category_id);
        }

        // Search inside JSON
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('data->name', 'like', "%{$search}%")
                    ->orWhere('data->email', 'like', "%{$search}%")
                    ->orWhere('data->mobile', 'like', "%{$search}%");
            });
        }

        // Recent (today)
        if ($request->has('recent') && $request->recent == 'today') {
            $query->whereDate('created_at', today());
        }

        // Get vendors
        $vendors = $query->get();

        // CSV data array
        $csvData = [];

        // Headers
        $csvData[] = [
            'Deleted Vendor ID',
            'Name',
            'Email',
            'Mobile',
            'Business Category',
            'Status',
            'KYC Status',
            'Deleted At',
        ];

        // Rows
        foreach ($vendors as $vendor) {
            $csvData[] = [
                $vendor->id,
                $vendor->name ?? 'N/A',
                $vendor->email ?? 'N/A',
                $vendor->mobile ?? 'N/A',
                $vendor->businessCategory->name
                    ?? ($vendor->business_category_name ?? 'N/A'),
                ($vendor->status ?? 0) == 1 ? 'Active' : 'Inactive',
                ! empty($vendor->is_kyc_completed) ? 'Verified' : 'Pending',
                $vendor->created_at->format('Y-m-d H:i:s'),
            ];
        }

        // File name + path
        $filename = 'deleted_vendors_export_'.date('Y-m-d_H-i-s').'.csv';
        $filepath = storage_path('app/public/exports/'.$filename);

        // Ensure directory exists
        if (! file_exists(storage_path('app/public/exports/'))) {
            mkdir(storage_path('app/public/exports/'), 0755, true);
        }

        // Create CSV
        $file = fopen($filepath, 'w');
        foreach ($csvData as $row) {
            fputcsv($file, $row);
        }
        fclose($file);

        // Download CSV
        return response()->download($filepath, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function restoreDeletedVendor($id)
    {
        try {
            User::withTrashed()->where('id', $id)->restore();

            return redirect()->back()
                ->with('success', 'User restored successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to restore user: '.$e->getMessage());
        }
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
            $query->where(function ($q) use ($search) {
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

    public function exportCustomers(Request $request)
    {
        $query = User::where('role', 'customer');

        // Search by name, email, mobile
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        // Recent customers (today)
        if ($request->has('recent') && $request->recent == 'today') {
            $query->whereDate('created_at', today());
        }

        // Fetch customers
        $customers = $query->get();

        // CSV data array
        $csvData = [];

        // CSV headers
        $csvData[] = [
            'Customer ID',
            'Name',
            'Email',
            'Mobile',
            'Status',
            'Created Date',
        ];

        // Add each customer row
        foreach ($customers as $customer) {
            $csvData[] = [
                $customer->id,
                $customer->name,
                $customer->email,
                $customer->mobile,
                $customer->status ? 'Active' : 'Inactive',
                $customer->created_at->format('Y-m-d H:i:s'),
            ];
        }

        // File name + path
        $filename = 'customers_export_'.date('Y-m-d_H-i-s').'.csv';
        $filepath = storage_path('app/public/exports/'.$filename);

        // Ensure directory exists
        if (! file_exists(storage_path('app/public/exports/'))) {
            mkdir(storage_path('app/public/exports/'), 0755, true);
        }

        // Create CSV file
        $file = fopen($filepath, 'w');
        foreach ($csvData as $row) {
            fputcsv($file, $row);
        }
        fclose($file);

        // Download file
        return response()->download($filepath, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function deletedCustomersList(Request $request)
    {
        $query = User::onlyTrashed()->where('role', 'customer');

        // Search by name, email or mobile
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
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

        return view('admin.users.deletedCustomers', compact('customers'));
    }

    public function exportDeletedCustomers(Request $request)
    {
        $query = User::onlyTrashed()->where('role', 'customer');

        // Search by name, email, mobile inside JSON
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        // Recent filter (today)
        if ($request->has('recent') && $request->recent == 'today') {
            $query->whereDate('created_at', today());
        }

        // Fetch deleted customers
        $customers = $query->get();

        // CSV content array
        $csvData = [];

        // Headers
        $csvData[] = [
            'Deleted Customer ID',
            'Name',
            'Email',
            'Mobile',
            'Status',
            'Deleted At',
        ];

        // Rows
        foreach ($customers as $customer) {
            $csvData[] = [
                $customer->id,
                $customer->name ?? 'N/A',
                $customer->email ?? 'N/A',
                $customer->mobile ?? 'N/A',
                'DELETED',
                $customer->created_at->format('Y-m-d H:i:s'),
            ];
        }

        // Filename + path
        $filename = 'deleted_customers_export_'.date('Y-m-d_H-i-s').'.csv';
        $filepath = storage_path('app/public/exports/'.$filename);

        // Ensure directory exists
        if (! file_exists(storage_path('app/public/exports/'))) {
            mkdir(storage_path('app/public/exports/'), 0755, true);
        }

        // Create CSV
        $file = fopen($filepath, 'w');
        foreach ($csvData as $row) {
            fputcsv($file, $row);
        }
        fclose($file);

        // Return CSV download
        return response()->download($filepath, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function destroyCustomer($id)
    {
        try {

            User::where('id', $id)->forceDelete();

            return redirect()->back()
                ->with('success', 'User deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete user: '.$e->getMessage());
        }
    }
}
