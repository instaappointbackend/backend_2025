<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * Display a listing of the services.
     */
    public function index(Request $request)
    {
        $query = Service::with('user');
        
        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }
        
        // Filter by vendor
        if ($request->has('vendor_id') && $request->vendor_id) {
            $query->where('user_id', $request->vendor_id);
        }
        
        // Filter by search query
        if ($request->has('search') && $request->search) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }
        
        $services = $query->latest()
                        ->paginate(15)
                        ->withQueryString();
        
        $vendors = User::where('role', 'vendor')->get();
        
        return view('admin.services.index', compact('services', 'vendors'));
    }

    /**
     * Show the form for creating a new service.
     */
    public function create()
    {
        $vendors = User::where('role', 'vendor')->get();
        
        return view('admin.services.create', compact('vendors'));
    }

    /**
     * Store a newly created service in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'duration' => 'required|integer|min:5',
            'price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $service = Service::create($validated);

        return redirect()->route('admin.services.index')
            ->with('success', 'Service created successfully.');
    }

    /**
     * Display the specified service.
     */
    public function show(Service $service)
    {
        $service->load('user');
        
        // Get upcoming appointments for this service
        $upcomingAppointments = $service->appointments()
                                    ->with(['client', 'user'])
                                    ->whereIn('status', ['pending', 'confirmed'])
                                    ->where('date', '>=', now()->toDateString())
                                    ->orderBy('date')
                                    ->orderBy('start_time')
                                    ->take(5)
                                    ->get();
        
        return view('admin.services.show', compact('service', 'upcomingAppointments'));
    }

    /**
     * Show the form for editing the specified service.
     */
    public function edit(Service $service)
    {
        $vendors = User::where('role', 'vendor')->get();
        
        return view('admin.services.edit', compact('service', 'vendors'));
    }

    /**
     * Update the specified service in storage.
     */
    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'duration' => 'required|integer|min:5',
            'price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $service->update($validated);

        return redirect()->route('admin.services.index')
            ->with('success', 'Service updated successfully.');
    }

    /**
     * Remove the specified service from storage.
     */
    public function destroy(Service $service)
    {
        // Check if the service has appointments
        $hasAppointments = $service->appointments()->exists();
        
        if ($hasAppointments) {
            return back()->with('error', 'Cannot delete this service because it has appointments associated with it.');
        }
        
        $service->delete();

        return redirect()->route('admin.services.index')
            ->with('success', 'Service deleted successfully.');
    }
}