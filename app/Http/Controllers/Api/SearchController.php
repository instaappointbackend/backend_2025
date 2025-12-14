<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Service;
use App\Models\BusinessCategory;
use App\Models\KycDocument;
use App\Models\SearchHistory;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class SearchController extends Controller
{
    use ApiResponseTrait;

    /**
     * Search for services, service providers, and businesses.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        // Validate required parameters
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:1',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'type' => 'sometimes|array',
            'type.*' => 'in:service,provider,business',
            'rating' => 'sometimes|numeric|min:0|max:5',
            'distance' => 'sometimes|numeric|min:1',
            'business_type' => 'sometimes|exists:business_categories,id',
            'sort' => 'sometimes|in:distance,rating,price',
            'page' => 'sometimes|integer|min:1',
            'limit' => 'sometimes|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        // Extract search parameters
        $query = $request->query('query');
        $userLat = $request->query('latitude');
        $userLng = $request->query('longitude');
        $types = $request->query('type', ['service', 'provider', 'business']);
        $minRating = $request->query('rating', 0);
        $maxDistance = $request->query('distance', 50); // Default 50km radius
        $businessTypeId = $request->query('business_type');
        $sort = $request->query('sort', 'distance');
        $page = $request->query('page', 1);
        $limit = $request->query('limit', 10);

        // Save search query to history
        $this->saveSearchHistory($query);

        // Initialize results
        $results = [];
        $searchCategory = null;

        // Create a distance calculation SQL statement with aliased columns
        $distanceSQL = "
            (6371 * acos(cos(radians($userLat))
            * cos(radians(kyc_documents.latitude))
            * cos(radians(kyc_documents.longitude) - radians($userLng))
            + sin(radians($userLat))
            * sin(radians(kyc_documents.latitude))))
        ";

        // Check if this search matches a specific business category
        $matchingCategory = null;
        if ($businessTypeId) {
            // If business_type is specified, find the category by ID
            $matchingCategory = BusinessCategory::find($businessTypeId);
        } else {
            // Otherwise check if the query matches a category name
            $matchingCategory = BusinessCategory::where('name', 'like', '%' . $query . '%')->first();
        }

        if ($matchingCategory) {
            $searchCategory = [
                'id' => $matchingCategory->id,
                'name' => $matchingCategory->name,
                'description' => $matchingCategory->description ?? 'Browse ' . $matchingCategory->name . ' service providers',
                'image' => $matchingCategory->image ? asset('storage/' . $matchingCategory->image) : null
            ];
            // If it's a category search, filter by that category
            $businessTypeId = $matchingCategory->id;
        }

        // FIRST PASS: Check if search query directly matches a business name
        // Only perform if 'business' is in the allowed types
        $businessMatch = null;
        if (in_array('business', $types)) {
            $businessMatchQuery = KycDocument::join('users', 'kyc_documents.user_id', '=', 'users.id')
                ->where('users.role', 'vendor')
                ->where('users.is_registered', true)
                ->where('users.is_kyc_completed', true)
                ->where('users.status', true)
                ->where(function($q) use ($query) {
                    $q->whereRaw('LOWER(kyc_documents.business_name) = ?', [strtolower($query)]);
                });

            // Apply business type filter to business match if specified
            if ($businessTypeId) {
                $businessMatchQuery->where('kyc_documents.business_category_id', $businessTypeId);
            }

            $businessMatch = $businessMatchQuery->first();

            if ($businessMatch) {
                // This is a direct business match - prioritize business results
                $searchCategory = [
                    'id' => 'business-' . $businessMatch->id,
                    'name' => $businessMatch->business_name,
                    'description' => 'View services and information about ' . $businessMatch->business_name,
                    'type' => 'business'
                ];
            }
        }

        // SECOND PASS: Check if search query directly matches a provider name
        // Only perform if 'provider' is in the allowed types
        $providerMatch = null;
        if (in_array('provider', $types) && !$businessMatch) {
            $providerMatchQuery = User::join('kyc_documents', 'users.id', '=', 'kyc_documents.user_id')
                ->where('users.role', 'vendor')
                ->where('users.is_registered', true)
                ->where('users.is_kyc_completed', true)
                ->where('users.status', true)
                ->where(function($q) use ($query) {
                    $q->whereRaw('LOWER(users.name) = ?', [strtolower($query)]);
                });

            // Apply business type filter to provider match if specified
            if ($businessTypeId) {
                $providerMatchQuery->where('kyc_documents.business_category_id', $businessTypeId);
            }

            $providerMatch = $providerMatchQuery->first();

            if ($providerMatch) {
                // This is a direct provider match - prioritize provider results
                $searchCategory = [
                    'id' => 'provider-' . $providerMatch->id,
                    'name' => $providerMatch->name,
                    'description' => 'View services offered by ' . $providerMatch->name,
                    'type' => 'provider'
                ];
            }
        }

        // THIRD PASS: Check if search query directly matches a service name
        // Only perform if 'service' is in the allowed types
        $serviceMatch = null;
        if (in_array('service', $types) && !$businessMatch && !$providerMatch) {
            $serviceMatchQuery = Service::join('users', 'services.user_id', '=', 'users.id')
                ->join('kyc_documents', 'users.id', '=', 'kyc_documents.user_id')
                ->where('services.is_active', true)
                ->where('users.status', true)
                ->where('users.is_kyc_completed', true)
                ->where(function($q) use ($query) {
                    $q->whereRaw('LOWER(services.name) = ?', [strtolower($query)]);
                });

            // Apply business type filter to service match if specified
            if ($businessTypeId) {
                $serviceMatchQuery->where('kyc_documents.business_category_id', $businessTypeId);
            }

            $serviceMatch = $serviceMatchQuery->first('services.*');

            if ($serviceMatch) {
                // This is a direct service match - add context to search
                $searchCategory = [
                    'id' => 'service-' . $serviceMatch->id,
                    'name' => $serviceMatch->name,
                    'description' => 'Providers offering ' . $serviceMatch->name,
                    'type' => 'service'
                ];
            }
        }

        // Search for users (service providers/vendors) - ONLY IF 'provider' or 'business' is in allowed types
        if (in_array('provider', $types) || in_array('business', $types)) {
            $usersWithKyc = User::with(['businessCategory'])
                ->join('kyc_documents', 'users.id', '=', 'kyc_documents.user_id')
                ->leftJoin('business_categories', 'kyc_documents.business_category_id', '=', 'business_categories.id')
                ->select([
                    'users.id',
                    'users.name',
                    'users.profile_picture',
                    'users.rating',
                    'kyc_documents.business_name',
                    'kyc_documents.business_category_id',
                    'kyc_documents.address',
                    'kyc_documents.full_address',
                    'kyc_documents.city',
                    'kyc_documents.state',
                    'kyc_documents.country',
                    'kyc_documents.postal_code',
                    'kyc_documents.description',
                    'business_categories.name as business_category_name',
                    DB::raw("'provider' as type"),
                    DB::raw("$distanceSQL as distance")
                ])
                ->where('users.role', 'vendor')
                ->where('users.is_registered', true)
                ->where('users.is_kyc_completed', true)
                ->where('users.status', true)
                ->where(function($q) use ($query) {
                    // Search in user table
                    $q->whereRaw('LOWER(users.name) LIKE ?', ['%' . strtolower($query) . '%']);

                    // Search in KYC document fields
                    $q->orWhereRaw('LOWER(kyc_documents.business_name) LIKE ?', ['%' . strtolower($query) . '%'])
                        ->orWhereRaw('LOWER(kyc_documents.address) LIKE ?', ['%' . strtolower($query) . '%'])
                        ->orWhereRaw('LOWER(kyc_documents.full_address) LIKE ?', ['%' . strtolower($query) . '%'])
                        ->orWhereRaw('LOWER(kyc_documents.city) LIKE ?', ['%' . strtolower($query) . '%'])
                        ->orWhereRaw('LOWER(kyc_documents.state) LIKE ?', ['%' . strtolower($query) . '%'])
                        ->orWhereRaw('LOWER(kyc_documents.country) LIKE ?', ['%' . strtolower($query) . '%']);

                    // Search in business category name
                    $q->orWhereRaw('LOWER(business_categories.name) LIKE ?', ['%' . strtolower($query) . '%']);
                })
                ->whereNotNull('kyc_documents.latitude')
                ->whereNotNull('kyc_documents.longitude')
                ->having('distance', '<=', $maxDistance);

            // Filter by business type if specified
            if ($businessTypeId) {
                $usersWithKyc->where('kyc_documents.business_category_id', $businessTypeId);
            }

            // Apply minimum rating filter
            if ($minRating > 0) {
                $usersWithKyc->where('users.rating', '>=', $minRating);
            }

            // If we have a direct business match, prioritize that business
            if ($businessMatch) {
                $usersWithKyc->orderByRaw('kyc_documents.user_id = ? DESC', [$businessMatch->user_id]);
            }
            // If we have a direct provider match, prioritize that provider
            else if ($providerMatch) {
                $usersWithKyc->orderByRaw('users.id = ? DESC', [$providerMatch->id]);
            }

            $users = $usersWithKyc->get();

            // Format user results
            foreach ($users as $user) {
                $results[] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'type' => 'provider',
                    'description' => $user->description ?? '',
                    'address' => $user->full_address ?? $user->address,
                    'rating' => $user->rating ?? 0,
                    'image' => $user->profile_picture ? asset('storage/' . $user->profile_picture) : null,
                    'distance' => round($user->distance, 1) . ' km',
                    'businessType' => $user->business_category_name,
                    'businessTypeId' => $user->business_category_id,
                    'businessName' => $user->business_name,
                    'city' => $user->city,
                    'state' => $user->state,
                ];
            }
        }

        // Search for services if requested
        if (in_array('service', $types)) {
            $servicesQuery = Service::select([
                'services.id',
                'services.name',
                'services.description',
                'services.price',
                'services.duration',
                'services.user_id',
                'kyc_documents.business_name',
                'kyc_documents.address',
                'kyc_documents.full_address',
                'kyc_documents.city',
                'kyc_documents.state',
                'kyc_documents.business_category_id',
                'business_categories.name as business_category_name',
                'users.profile_picture',
                'users.rating',
                DB::raw("'service' as type"),
                DB::raw("$distanceSQL as distance")
            ])
                ->join('users', 'services.user_id', '=', 'users.id')
                ->join('kyc_documents', 'users.id', '=', 'kyc_documents.user_id')
                ->leftJoin('business_categories', 'kyc_documents.business_category_id', '=', 'business_categories.id')
                ->where('services.is_active', true)
                ->where('users.status', true)
                ->where('users.is_kyc_completed', true)
                ->whereRaw('LOWER(services.name) LIKE ? OR LOWER(services.description) LIKE ?',
                    ['%' . strtolower($query) . '%', '%' . strtolower($query) . '%'])
                ->whereNotNull('kyc_documents.latitude')
                ->whereNotNull('kyc_documents.longitude')
                ->having('distance', '<=', $maxDistance);

            // Filter by business type if specified
            if ($businessTypeId) {
                $servicesQuery->where('kyc_documents.business_category_id', $businessTypeId);
            }

            // Apply minimum rating filter
            if ($minRating > 0) {
                $servicesQuery->where('users.rating', '>=', $minRating);
            }

            // If we have a direct service match, prioritize that service
            if ($serviceMatch) {
                $servicesQuery->orderByRaw('services.id = ? DESC', [$serviceMatch->id]);
            }

            $services = $servicesQuery->get();

            // Format service results
            foreach ($services as $service) {
                $results[] = [
                    'id' => $service->id,
                    'name' => $service->name,
                    'type' => 'service',
                    'description' => $service->description,
                    'address' => $service->full_address ?? $service->address,
                    'rating' => $service->rating ?? 0,
                    'image' => $service->profile_picture ? asset('storage/' . $service->profile_picture) : null,
                    'distance' => round($service->distance, 1) . ' km',
                    'businessType' => $service->business_category_name,
                    'businessTypeId' => $service->business_category_id,
                    'price' => $service->price,
                    'providerId' => $service->user_id,
                    'duration' => $service->duration,
                    'businessName' => $service->business_name,
                    'city' => $service->city,
                    'state' => $service->state,
                ];
            }
        }

        // If this is a direct business match, prioritize the matching business's services
        // Only do this if both 'business' and 'service' are in the allowed types
        if ($businessMatch && in_array('service', $types) && in_array('business', $types)) {
            $businessServicesQuery = Service::select([
                'services.id',
                'services.name',
                'services.description',
                'services.price',
                'services.duration',
                'services.user_id',
                'kyc_documents.business_name',
                'kyc_documents.address',
                'kyc_documents.full_address',
                'kyc_documents.city',
                'kyc_documents.state',
                'kyc_documents.business_category_id',
                'business_categories.name as business_category_name',
                'users.profile_picture',
                'users.rating',
                DB::raw("'service' as type"),
                DB::raw("$distanceSQL as distance")
            ])
                ->join('users', 'services.user_id', '=', 'users.id')
                ->join('kyc_documents', 'users.id', '=', 'kyc_documents.user_id')
                ->leftJoin('business_categories', 'kyc_documents.business_category_id', '=', 'business_categories.id')
                ->where('services.is_active', true)
                ->where('users.status', true)
                ->where('users.is_kyc_completed', true)
                ->where('kyc_documents.user_id', $businessMatch->user_id)
                ->whereNotNull('kyc_documents.latitude')
                ->whereNotNull('kyc_documents.longitude')
                ->having('distance', '<=', $maxDistance);

            // Also apply business type filter here
            if ($businessTypeId) {
                $businessServicesQuery->where('kyc_documents.business_category_id', $businessTypeId);
            }

            $businessServices = $businessServicesQuery->get();

            foreach ($businessServices as $service) {
                $results[] = [
                    'id' => $service->id,
                    'name' => $service->name,
                    'type' => 'service',
                    'description' => $service->description,
                    'address' => $service->full_address ?? $service->address,
                    'rating' => $service->rating ?? 0,
                    'image' => $service->profile_picture ? asset('storage/' . $service->profile_picture) : null,
                    'distance' => round($service->distance, 1) . ' km',
                    'businessType' => $service->business_category_name,
                    'businessTypeId' => $service->business_category_id,
                    'price' => $service->price,
                    'providerId' => $service->user_id,
                    'duration' => $service->duration,
                    'businessName' => $service->business_name,
                    'city' => $service->city,
                    'state' => $service->state,
                ];
            }
        }

        // Add debug info if needed
        if ($request->has('debug') && $request->debug == 1) {
            // Only in development/debug mode
            $debugInfo = [
                'applied_filters' => [
                    'query' => $query,
                    'types' => $types,
                    'distance' => $maxDistance,
                    'rating' => $minRating,
                    'business_type' => $businessTypeId,
                    'sort' => $sort
                ],
                'businessMatch' => $businessMatch ? $businessMatch->business_name : null,
                'matchingCategory' => $matchingCategory ? $matchingCategory->name : null,
                'search_category' => $searchCategory
            ];
        }

        // Remove duplicate entries (if both user and their service appear in results)
        $uniqueResults = [];
        $seenUserIds = [];
        $seenServiceIds = [];

        foreach ($results as $result) {
            if ($result['type'] === 'provider') {
                if (!isset($seenUserIds[$result['id']])) {
                    $seenUserIds[$result['id']] = true;
                    $uniqueResults[] = $result;
                }
            } elseif ($result['type'] === 'service') {
                if (!isset($seenServiceIds[$result['id']])) {
                    $seenServiceIds[$result['id']] = true;
                    $uniqueResults[] = $result;
                }
            }
        }

        // Sort results
        if ($sort === 'distance') {
            usort($uniqueResults, function($a, $b) {
                return (float)$a['distance'] <=> (float)$b['distance'];
            });
        } elseif ($sort === 'rating') {
            usort($uniqueResults, function($a, $b) {
                return $b['rating'] <=> $a['rating'];
            });
        } elseif ($sort === 'price' && in_array('service', $types)) {
            // Only sort by price if services are included
            usort($uniqueResults, function($a, $b) {
                // Handle cases where price might not be set for non-service types
                $priceA = isset($a['price']) ? $a['price'] : PHP_FLOAT_MAX;
                $priceB = isset($b['price']) ? $b['price'] : PHP_FLOAT_MAX;
                return $priceA <=> $priceB;
            });
        }

        // Paginate results
        $offset = ($page - 1) * $limit;
        $paginatedResults = array_slice($uniqueResults, $offset, $limit);

        // Prepare pagination metadata
        $pagination = [
            'total' => count($uniqueResults),
            'per_page' => $limit,
            'current_page' => $page,
            'last_page' => ceil(count($uniqueResults) / $limit),
            'from' => $offset + 1,
            'to' => min($offset + $limit, count($uniqueResults))
        ];

        $response = [
            'data' => $paginatedResults,
            'pagination' => $pagination
        ];

        // Add search category information if available
        if ($searchCategory) {
            $response['category'] = $searchCategory;
        }

        // Add debug info if available
        if (isset($debugInfo)) {
            $response['debug'] = $debugInfo;
        }

        return $this->success($response, 'Search results retrieved successfully.');
    }

    // Rest of the controller methods remain unchanged...

    /**
     * Get search suggestions based on query.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSuggestions(Request $request)
    {
        // Validate required parameters
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:1',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        $query = $request->query('query');
        $userLat = $request->query('latitude');
        $userLng = $request->query('longitude');
        $maxDistance = 50; // Default 50km radius for suggestions

        // Define distance calculation for all queries
        $distanceSQL = "
            (6371 * acos(cos(radians($userLat))
            * cos(radians(kyc_documents.latitude))
            * cos(radians(kyc_documents.longitude) - radians($userLng))
            + sin(radians($userLat))
            * sin(radians(kyc_documents.latitude))))
        ";

        // Get service name suggestions
        $serviceNameSuggestions = Service::select('services.name')
            ->join('users', 'services.user_id', '=', 'users.id')
            ->join('kyc_documents', 'users.id', '=', 'kyc_documents.user_id')
            ->where('services.is_active', true)
            ->where('users.status', true)
            ->where('users.is_kyc_completed', true)
            ->whereRaw('LOWER(services.name) LIKE ?', ['%' . strtolower($query) . '%'])
            ->whereNotNull('kyc_documents.latitude')
            ->whereNotNull('kyc_documents.longitude')
            ->selectRaw("$distanceSQL as distance")
            ->having('distance', '<=', $maxDistance)
            ->limit(5)
            ->pluck('services.name')
            ->toArray();

        // Get provider name suggestions
        $providerNameSuggestions = User::select('users.name')
            ->join('kyc_documents', 'users.id', '=', 'kyc_documents.user_id')
            ->where('users.role', 'vendor')
            ->where('users.is_registered', true)
            ->where('users.is_kyc_completed', true)
            ->where('users.status', true)
            ->whereRaw('LOWER(users.name) LIKE ?', ['%' . strtolower($query) . '%'])
            ->whereNotNull('kyc_documents.latitude')
            ->whereNotNull('kyc_documents.longitude')
            ->selectRaw("$distanceSQL as distance")
            ->having('distance', '<=', $maxDistance)
            ->limit(5)
            ->pluck('users.name')
            ->toArray();

        // Get business name suggestions from KYC documents
        $businessNameSuggestions = KycDocument::select('business_name')
            ->join('users', 'kyc_documents.user_id', '=', 'users.id')
            ->where('users.status', true)
            ->where('users.is_kyc_completed', true)
            ->whereRaw('LOWER(business_name) LIKE ?', ['%' . strtolower($query) . '%'])
            ->whereNotNull('kyc_documents.latitude')
            ->whereNotNull('kyc_documents.longitude')
            ->selectRaw("$distanceSQL as distance")
            ->having('distance', '<=', $maxDistance)
            ->limit(3)
            ->pluck('business_name')
            ->toArray();

        // Get business category suggestions
        $businessCategorySuggestions = BusinessCategory::select('name')
            ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($query) . '%'])
            ->limit(3)
            ->pluck('name')
            ->toArray();

        // Get location suggestions (cities, states, etc.)
        $locationSuggestions = KycDocument::selectRaw('CONCAT(kyc_documents.city, ", ", kyc_documents.state) as location')
            ->join('users', 'kyc_documents.user_id', '=', 'users.id')
            ->where('users.status', true)
            ->where('users.is_kyc_completed', true)
            ->where(function($q) use ($query) {
                $q->whereRaw('LOWER(kyc_documents.city) LIKE ?', ['%' . strtolower($query) . '%'])
                    ->orWhereRaw('LOWER(kyc_documents.state) LIKE ?', ['%' . strtolower($query) . '%'])
                    ->orWhereRaw('LOWER(kyc_documents.address) LIKE ?', ['%' . strtolower($query) . '%']);
            })
            ->whereNotNull('kyc_documents.city')
            ->whereNotNull('kyc_documents.state')
            ->whereNotNull('kyc_documents.latitude')
            ->whereNotNull('kyc_documents.longitude')
            ->selectRaw("$distanceSQL as distance")
            ->having('distance', '<=', $maxDistance)
            ->distinct()
            ->limit(3)
            ->pluck('location')
            ->toArray();

        // Combine all suggestions and remove duplicates
        $suggestions = array_unique(array_merge(
            $serviceNameSuggestions,
            $providerNameSuggestions,
            $businessNameSuggestions,
            $businessCategorySuggestions,
            $locationSuggestions
        ));

        // Limit to 10 suggestions
        $suggestions = array_slice($suggestions, 0, 10);

        return $this->success($suggestions, 'Search suggestions retrieved successfully.');
    }

    /**
     * Get recent searches for the current user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRecentSearches()
    {
        $user = Auth::user();

        $recentSearches = SearchHistory::where('user_id', $user->id)
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->pluck('query')
            ->toArray();

        return $this->success($recentSearches, 'Recent searches retrieved successfully.');
    }

    /**
     * Save search query to history.
     *
     * @param  string  $query
     * @return void
     */
    private function saveSearchHistory($query)
    {
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();

        // Check if this query already exists for the user
        $existingSearch = SearchHistory::where('user_id', $user->id)
            ->where('query', $query)
            ->first();

        if ($existingSearch) {
            // Update the timestamp of the existing search
            $existingSearch->touch();
        } else {
            // Create a new search history entry
            SearchHistory::create([
                'user_id' => $user->id,
                'query' => $query
            ]);

            // Delete old searches if more than 20
            $oldSearchIds = SearchHistory::where('user_id', $user->id)
                ->orderBy('updated_at', 'desc')
                ->skip(20)
                ->take(100)
                ->pluck('id');

            if ($oldSearchIds->count() > 0) {
                SearchHistory::whereIn('id', $oldSearchIds)->delete();
            }
        }
    }

    /**
     * Save a search query to history.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveSearch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:1',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }
        $this->saveSearchHistory($request->input('query'));

        return $this->success([], 'Search saved successfully.');
    }

    /**
     * Clear all recent searches for the current user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearRecentSearches()
    {
        $user = Auth::user();

        SearchHistory::where('user_id', $user->id)->delete();

        return $this->success([], 'Recent searches cleared successfully.');
    }
}
