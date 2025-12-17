<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceRequest;
use App\Http\Resources\ServiceResponse;
use App\Models\Appointment;
use App\Models\Service;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ServiceController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get all services.
     */
    public function index()
    {
        $services = Service::where('user_id', Auth::id())->get();
        return $this->success(ServiceResponse::collection($services), 'Services retrieved successfully.');
    }

    /**
     * Store a new service.
     */
    public function store(ServiceRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = Auth::id();
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('services', 'public');
            $data['image'] = $imagePath;
        }
        $service = Service::create($data);
        return $this->success(new ServiceResponse($service), 'Service created successfully.', 201);
    }

    /**
     * Show a specific service.
     */
    public function show($id)
    {
        $service = Service::where('user_id', Auth::id())
            ->where('id', $id)
            ->first();

        if (!$service) {
            return $this->error([], 'Service not found', 404);
        }

        return $this->success(new ServiceResponse($service), 'Service retrieved successfully.');
    }

    /**
     * Update a service.
     */
    public function update(ServiceRequest $request, $id)
    {
        $service = Service::where('user_id', Auth::id())
            ->where('id', $id)
            ->first();


        if (!$service) {
            return $this->error([], 'Service not found', 404);
        }

        $data = $request->validated();

        // If duration is being changed, check for future appointments
        if (isset($data['duration']) && $data['duration'] != $service->duration) {
            $hasAppointments = Appointment::where('service_id', $id)
                ->where('date', '>=', now()->toDateString())
                ->whereIn('status', [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED])
                ->exists();

            if ($hasAppointments) {
                return $this->error([], 'Cannot change duration for a service with future appointments.', 422);
            }
        }

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($service->image && Storage::disk('public')->exists($service->image)) {
                Storage::disk('public')->delete($service->image);
            }

            $path = $request->file('image')->store('services', 'public');
            $validated['image'] = $path;
        }


        $service->update($data);

        return $this->success(new ServiceResponse($service), 'Service updated successfully.');
    }

    /**
     * Delete a service.
     */
    public function destroy($id)
    {
        $service = Service::where('user_id', Auth::id())
            ->where('id', $id)
            ->first();

        if (!$service) {
            return $this->error([], 'Service not found', 404);
        }

        // Check if there are any appointments using this service
        $hasAppointments = Appointment::where('service_id', $id)->exists();

        if ($hasAppointments) {
            return $this->error([], 'Cannot delete a service that has associated appointments.', 422);
        }

        $service->delete();

        return $this->success([], 'Service deleted successfully.');
    }

    /**
     * Toggle a service's active status.
     */
    public function toggleStatus($id)
    {
        $service = Service::where('user_id', Auth::id())
            ->where('id', $id)
            ->first();

        if (!$service) {
            return $this->error([], 'Service not found', 404);
        }

        // If deactivating, check for future appointments
        if ($service->is_active) {
            $hasAppointments = Appointment::where('service_id', $id)
                ->where('date', '>=', now()->toDateString())
                ->whereIn('status', [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED])
                ->exists();

            if ($hasAppointments) {
                return $this->error([], 'Cannot deactivate a service with future appointments.', 422);
            }
        }

        $service->is_active = !$service->is_active;
        $service->save();

        return $this->success(
            new ServiceResponse($service),
            'Service ' . ($service->is_active ? 'activated' : 'deactivated') . ' successfully.'
        );
    }

    /**
     * Get only active services.
     */
    public function active()
    {
        $services = Service::where('user_id', Auth::id())
            ->where('is_active', true)
            ->get();

        return $this->success(ServiceResponse::collection($services), 'Active services retrieved successfully.');
    }
}
