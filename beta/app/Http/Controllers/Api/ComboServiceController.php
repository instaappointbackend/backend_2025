<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ComboServiceRequest;
use App\Http\Resources\ComboServiceResponse;
use App\Models\ComboService;
use App\Models\Service;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ComboServiceController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get all combo services.
     */
    public function index()
    {
        $comboServices = ComboService::with('services')
            ->where('user_id', Auth::id())
            ->get();

        return $this->success(
            ComboServiceResponse::collection($comboServices),
            'Combo services retrieved successfully.'
        );
    }

    /**
     * Store a new combo service.
     */
    public function store(ComboServiceRequest $request)
    {
        // Validate that we have at least 2 services
        $serviceIds = $request->service_ids;
        if (count($serviceIds) < 2) {
            return $this->error([], 'A combo must include at least two services.', 422);
        }

        // Check if all services exist and belong to the user
        $services = Service::where('user_id', Auth::id())
            ->whereIn('id', $serviceIds)
            ->get();

        if ($services->count() !== count($serviceIds)) {
            return $this->error([], 'One or more services were not found.', 422);
        }

        // Verify all services are active
        $inactiveServices = $services->where('is_active', false);
        if ($inactiveServices->count() > 0) {
            return $this->error([], 'All services in a combo must be active.', 422);
        }

        DB::beginTransaction();

        try {
            $comboService = ComboService::create([
                'user_id' => Auth::id(),
                'name' => $request->name,
                'description' => $request->description,
                'discount_percentage' => $request->discount_percentage,
                'is_active' => $request->is_active,
            ]);

            // Attach services
            $comboService->services()->attach($serviceIds);

            DB::commit();

            // Load the services relation
            $comboService->load('services');

            return $this->success(
                new ComboServiceResponse($comboService),
                'Combo service created successfully.',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error([], 'Failed to create combo service: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Show a specific combo service.
     */
    public function show($id)
    {
        $comboService = ComboService::with('services')
            ->where('user_id', Auth::id())
            ->where('id', $id)
            ->first();

        if (!$comboService) {
            return $this->error([], 'Combo service not found', 404);
        }

        return $this->success(
            new ComboServiceResponse($comboService),
            'Combo service retrieved successfully.'
        );
    }

    /**
     * Update a combo service.
     */
    public function update(ComboServiceRequest $request, $id)
    {
        $comboService = ComboService::where('user_id', Auth::id())
            ->where('id', $id)
            ->first();

        if (!$comboService) {
            return $this->error([], 'Combo service not found', 404);
        }

        // Validate that we have at least 2 services
        $serviceIds = $request->service_ids;
        if (count($serviceIds) < 2) {
            return $this->error([], 'A combo must include at least two services.', 422);
        }

        // Check if all services exist and belong to the user
        $services = Service::where('user_id', Auth::id())
            ->whereIn('id', $serviceIds)
            ->get();

        if ($services->count() !== count($serviceIds)) {
            return $this->error([], 'One or more services were not found.', 422);
        }

        // Verify all services are active
        $inactiveServices = $services->where('is_active', false);
        if ($inactiveServices->count() > 0) {
            return $this->error([], 'All services in a combo must be active.', 422);
        }

        DB::beginTransaction();

        try {
            $comboService->update([
                'name' => $request->name,
                'description' => $request->description,
                'discount_percentage' => $request->discount_percentage,
                'is_active' => $request->is_active,
            ]);

            // Sync services (detaches and attaches as needed)
            $comboService->services()->sync($serviceIds);

            DB::commit();

            // Load the services relation
            $comboService->load('services');

            return $this->success(
                new ComboServiceResponse($comboService),
                'Combo service updated successfully.'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error([], 'Failed to update combo service: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete a combo service.
     */
    public function destroy($id)
    {
        $comboService = ComboService::where('user_id', Auth::id())
            ->where('id', $id)
            ->first();

        if (!$comboService) {
            return $this->error([], 'Combo service not found', 404);
        }

        // Check if there are any appointments using this combo service
        $hasAppointments = $comboService->appointments()->exists();

        if ($hasAppointments) {
            return $this->error([], 'Cannot delete a combo service that has associated appointments.', 422);
        }

        DB::beginTransaction();

        try {
            // Detach all services first
            $comboService->services()->detach();

            // Then delete the combo service
            $comboService->delete();

            DB::commit();

            return $this->success(
                [],
                'Combo service deleted successfully.'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error([], 'Failed to delete combo service: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Toggle a combo service's active status.
     */
    public function toggleStatus($id)
    {
        $comboService = ComboService::where('user_id', Auth::id())
            ->where('id', $id)
            ->first();

        if (!$comboService) {
            return $this->error([], 'Combo service not found', 404);
        }

        // If deactivating, check for future appointments
        if ($comboService->is_active) {
            $hasAppointments = $comboService->appointments()
                ->where('date', '>=', now()->toDateString())
                ->whereIn('status', ['pending', 'confirmed'])
                ->exists();

            if ($hasAppointments) {
                return $this->error([], 'Cannot deactivate a combo service with future appointments.', 422);
            }
        }

        $comboService->is_active = !$comboService->is_active;
        $comboService->save();

        // Load the services relation
        $comboService->load('services');

        return $this->success(
            new ComboServiceResponse($comboService),
            'Combo service ' . ($comboService->is_active ? 'activated' : 'deactivated') . ' successfully.'
        );
    }

    /**
     * Get only active combo services.
     */
    public function active()
    {
        $comboServices = ComboService::with('services')
            ->where('user_id', Auth::id())
            ->where('is_active', true)
            ->get();

        return $this->success(
            ComboServiceResponse::collection($comboServices),
            'Active combo services retrieved successfully.'
        );
    }
}
