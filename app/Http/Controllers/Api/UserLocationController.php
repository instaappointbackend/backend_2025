<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserLocation;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserLocationController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get user's saved locations.
     */
    public function index(Request $request)
    {
        $userId = Auth::id();

        // Query parameters
        $limit = $request->input('limit', 10);
        $favorites = $request->boolean('favorites');

        $query = UserLocation::where('user_id', $userId);

        // Filter by favorites if requested
        if ($favorites) {
            $query->where('is_favorite', true);
        }

        // Get locations ordered by most recently used
        $locations = $query->orderBy('last_used_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $this->success($locations, 'User locations retrieved successfully');
    }

    /**
     * Save a new location for the user.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'full_address' => 'required|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'is_favorite' => 'sometimes|boolean',
            'is_current' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        $userId = Auth::id();

        // Check if this location already exists for the user
        $existingLocation = UserLocation::where('user_id', $userId)
            ->where('latitude', $request->latitude)
            ->where('longitude', $request->longitude)
            ->first();

        if ($existingLocation) {
            // Update the last used timestamp
            $existingLocation->last_used_at = now();
            $existingLocation->save();

            return $this->success($existingLocation, 'Location updated successfully');
        }

        // Ensure we don't exceed a reasonable number of saved locations per user
        $maxLocations = 20; // Adjust as needed
        $locationCount = UserLocation::where('user_id', $userId)->count();

        if ($locationCount >= $maxLocations) {
            // Delete the oldest non-favorite location to make room
            UserLocation::where('user_id', $userId)
                ->where('is_favorite', false)
                ->orderBy('last_used_at', 'asc')
                ->orderBy('created_at', 'asc')
                ->first()
                ?->delete();
        }

        // Create a new location
        $location = UserLocation::create([
            'user_id' => $userId,
            'name' => $request->name,
            'full_address' => $request->full_address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'is_favorite' => $request->is_favorite ?? false,
            'is_current' => $request->is_current ?? false,
            'last_used_at' => now(),
        ]);

        return $this->success($location, 'Location saved successfully', 201);
    }

    /**
     * Mark a location as used (updates last_used_at).
     */
    public function markAsUsed($id)
    {
        $location = UserLocation::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (! $location) {
            return $this->error([], 'Location not found', 404);
        }

        $location->last_used_at = now();
        $location->save();

        return $this->success($location, 'Location updated successfully');
    }

    /**
     * Toggle favorite status for a location.
     */
    public function toggleFavorite($id)
    {
        $location = UserLocation::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (! $location) {
            return $this->error([], 'Location not found', 404);
        }

        $location->is_favorite = ! $location->is_favorite;
        $location->last_used_at = now();
        $location->save();

        return $this->success($location, 'Favorite status updated successfully');
    }

    /**
     * Delete a saved location.
     */
    public function destroy($id)
    {
        $location = UserLocation::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (! $location) {
            return $this->error([], 'Location not found', 404);
        }

        $location->delete();

        return $this->success([], 'Location deleted successfully');
    }

    /**
     * Clear all user locations except favorites.
     */
    public function clearAll(Request $request)
    {
        $keepFavorites = $request->boolean('keep_favorites', true);

        $query = UserLocation::where('user_id', Auth::id());

        if ($keepFavorites) {
            $query->where('is_favorite', false);
        }

        $deleted = $query->delete();

        return $this->success(['count' => $deleted], 'Locations cleared successfully');
    }
}
