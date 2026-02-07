<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppointmentSettingsResponse;
use App\Http\Resources\BlogResponse;
use App\Http\Resources\ProviderResource;
use App\Http\Resources\ReviewResource;
use App\Http\Resources\ServiceResponse;
use App\Http\Resources\TeamMemberResponse;
use App\Models\AppointmentSettings;
use App\Models\Blog;
use App\Models\ComboService;
use App\Models\Review;
use App\Models\Service;
use App\Models\TimeSlot;
use App\Models\User;
use App\Models\UserFavorite;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProviderController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get a specific service provider by ID
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {

        $provider = User::with([
            'kycDocument',
            'businessCategory',
            'workingHours',
            'appointmentSettings',
        ])
            ->where('id', $id)
            ->where('role', 'vendor')
            ->where('is_registered', true)
            ->where('is_kyc_completed', true)
            ->where('status', true)
            ->withCount('reviews')
            ->first();

        if (! $provider) {
            return $this->error([], 'Service provider not found.', 404);
        }

        return $this->success(new ProviderResource($provider), 'Service provider details retrieved successfully.');
    }

    /**
     * Get services offered by a specific provider
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getServices($id)
    {
        $provider = User::where('id', $id)
            ->where('role', 'vendor')
            ->where('is_registered', true)
            ->where('is_kyc_completed', true)
            ->where('status', true)
            ->first();

        if (! $provider) {
            return $this->error([], 'Service provider not found.', 404);
        }

        $services = Service::where('user_id', $id)
            ->where('is_active', true)
            ->get();

        return $this->success(ServiceResponse::collection($services), 'Provider services retrieved successfully.');
    }

    public function getService($id, $sid)
    {
        $provider = User::where('id', $id)
            ->where('role', 'vendor')
            ->where('is_registered', true)
            ->where('is_kyc_completed', true)
            ->where('status', true)
            ->first();

        if (! $provider) {
            return $this->error([], 'Service provider not found.', 404);
        }

        $service = Service::where('id', $sid)
            ->where('is_active', true)
            ->first();

        return $this->success(new ServiceResponse($service), 'Provider services retrieved successfully.');
    }

    /**
     * Get team members (staff) of a specific provider
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTeamMembers($id)
    {
        $provider = User::where('id', $id)
            ->where('role', 'vendor')
            ->where('is_registered', true)
            ->where('is_kyc_completed', true)
            ->where('status', true)
            ->first();

        if (! $provider) {
            return $this->error([], 'Service provider not found.', 404);
        }

        $teamMembers = User::where('vendor_id', $id)
            ->where('status', true)
            ->get();

        return $this->success(TeamMemberResponse::collection($teamMembers), 'Provider team members retrieved successfully.');
    }

    /**
     * Get blogs published by a specific provider
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBlogs($id)
    {
        $provider = User::where('id', $id)
            ->where('role', 'vendor')
            ->where('is_registered', true)
            ->where('is_kyc_completed', true)
            ->where('status', true)
            ->first();

        if (! $provider) {
            return $this->error([], 'Service provider not found.', 404);
        }

        $blogs = Blog::where('user_id', $id)
            ->where('status', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success(BlogResponse::collection($blogs), 'Provider blogs retrieved successfully.');
    }

    /**
     * Get a specific blog by provider and blog ID
     *
     * @param  int  $providerId
     * @param  int  $blogId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBlog($providerId, $blogId)
    {
        $provider = User::where('id', $providerId)
            ->where('role', 'vendor')
            ->where('is_registered', true)
            ->where('is_kyc_completed', true)
            ->where('status', true)
            ->first();

        if (! $provider) {
            return $this->error([], 'Service provider not found.', 404);
        }

        $blog = Blog::where('id', $blogId)
            ->where('user_id', $providerId)
            ->where('status', true)
            ->first();

        if (! $blog) {
            return $this->error([], 'Blog not found.', 404);
        }

        return $this->success(new BlogResource($blog), 'Blog details retrieved successfully.');
    }

    /**
     * Get reviews for a specific provider
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReviews($id)
    {
        $provider = User::where('id', $id)
            ->where('role', 'vendor')
            ->where('is_registered', true)
            ->where('is_kyc_completed', true)
            ->where('status', true)
            ->first();

        if (! $provider) {
            return $this->error([], 'Service provider not found.', 404);
        }

        $reviews = Review::with(['user', 'service'])
            ->where('provider_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success(ReviewResource::collection($reviews), 'Provider reviews retrieved successfully.');
    }

    /**
     * Submit a review for a provider
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitReview(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|between:1,5',
            'comment' => 'required|string|max:1000',
            'service_id' => 'nullable|exists:services,id',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        $provider = User::where('id', $id)
            ->where('role', 'vendor')
            ->where('is_registered', true)
            ->where('is_kyc_completed', true)
            ->where('status', true)
            ->first();

        if (! $provider) {
            return $this->error([], 'Service provider not found.', 404);
        }

        $user = Auth::user();

        // Check if service belongs to provider if service_id is provided
        if ($request->has('service_id')) {
            $service = Service::where('id', $request->service_id)
                ->where('user_id', $id)
                ->first();

            if (! $service) {
                return $this->error([], 'Service not found or does not belong to this provider.', 404);
            }
        }

        // Check if user has already reviewed this provider
        $existingReview = Review::where('user_id', $user->id)
            ->where('provider_id', $id)
            ->first();

        if ($existingReview) {
            // Update existing review
            $existingReview->update([
                'rating' => $request->rating,
                'comment' => $request->comment,
                'service_id' => $request->service_id,
            ]);

            // Recalculate provider's rating
            $this->updateProviderRating($id);

            return $this->success(new ReviewResource($existingReview), 'Review updated successfully.');
        }

        // Create new review
        $review = Review::create([
            'user_id' => $user->id,
            'provider_id' => $id,
            'service_id' => $request->service_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        // Recalculate provider's rating
        $this->updateProviderRating($id);

        return $this->success(new ReviewResource($review), 'Review submitted successfully.');
    }

    /**
     * Update a provider's average rating
     *
     * @param  int  $providerId
     * @return void
     */
    private function updateProviderRating($providerId)
    {
        $averageRating = Review::where('provider_id', $providerId)->avg('rating');
        $roundedRating = round($averageRating, 1);

        User::where('id', $providerId)->update([
            'rating' => $roundedRating,
        ]);
    }

    /**
     * Get provider availability - time slots
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailability($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'sometimes|date_format:Y-m-d',
            'days' => 'sometimes|integer|min:1|max:30',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        $provider = User::with(['workingHours', 'appointmentSettings'])
            ->where('id', $id)
            ->where('role', 'vendor')
            ->where('is_registered', true)
            ->where('is_kyc_completed', true)
            ->where('status', true)
            ->first();

        if (! $provider) {
            return $this->error([], 'Service provider not found.', 404);
        }

        // Determine date range to fetch
        $startDate = $request->has('date') ? $request->date : now()->format('Y-m-d');
        $days = $request->has('days') ? $request->days : 7;

        // Store the original Auth::user() to restore later
        $originalUser = Auth::user();

        // Set the authenticated user to the provider
        Auth::setUser($provider);

        $timeSlotController = new TimeSlotController;
        $availability = [];

        // Initialize variables for the current date check
        $currentDateStr = $request->has('date') ? $request->date : now()->format('Y-m-d');
        $currentDate = Carbon::parse($currentDateStr);
        $currentDayOfWeek = $currentDate->dayOfWeek;
        $isWorkingDay = false;
        $holidayName = '';

        // Check if the current date is a holiday
        $holiday = $provider->holidays()
            ->where(function ($query) use ($currentDateStr) {
                $query->where('date', $currentDateStr)
                    ->orWhere(function ($q) use ($currentDateStr) {
                        // Check for recurring annual holidays
                        $q->where('is_recurring', true)
                            ->whereRaw("DATE_FORMAT(date, '%m-%d') = ?", [date('m-d', strtotime($currentDateStr))]);
                    });
            })
            ->first();

        // Check if the current date is a working day
        $workingHoursToday = $provider->workingHours->where('day_of_week', $currentDayOfWeek)->first();
        if ($workingHoursToday && $workingHoursToday->is_working_day && ! $holiday) {
            $isWorkingDay = true;
        }

        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', strtotime("$startDate +$i days"));

            // Create Carbon date object for easier manipulation
            $carbonDate = Carbon::parse($date);

            // Check if this is a holiday
            $isHoliday = $provider->holidays()
                ->where('date', $date)
                ->orWhere(function ($query) use ($date) {
                    // Check for recurring annual holidays
                    $query->where('is_recurring', true)
                        ->whereRaw("DATE_FORMAT(date, '%m-%d') = ?", [date('m-d', strtotime($date))]);
                })
                ->exists();

            if ($isHoliday) {
                continue; // Skip holidays
            }

            // Get day of week (0 = Sunday, 6 = Saturday)
            $dayOfWeek = $carbonDate->dayOfWeek;

            // Check if this is a working day
            $workingHours = $provider->workingHours->where('day_of_week', $dayOfWeek)->first();

            if (! $workingHours || ! $workingHours->is_working_day) {
                continue; // Skip non-working days
            }

            // First, check if time slots exist for this date
            $existingSlots = TimeSlot::where('user_id', $provider->id)
                ->where('date', $date)
                ->count();

            // If no slots exist, generate them first
            if ($existingSlots == 0) {
                // Get the provider's appointment settings
                $appointmentSettings = $provider->appointmentSettings;
                $interval = $appointmentSettings ? $appointmentSettings->appointment_duration : 30;
                $bufferTime = $appointmentSettings ? $appointmentSettings->buffer_time : 0;

                // Create a generate request with appointment settings
                $generateRequest = new Request([
                    'date' => $date,
                    'interval' => $interval,
                    'buffer_time' => $bufferTime,
                ]);

                // Generate time slots - Auth::id() will now be the provider's ID
                $timeSlotController->generate($generateRequest);
            }

            // Now get the time slots
            $slotRequest = new Request(['date' => $date]);

            // Get time slots - Auth::id() will now be the provider's ID

            $response = $timeSlotController->index($slotRequest);
            $responseData = json_decode($response->getContent(), true);

            if (isset($responseData['data']) && count($responseData['data']) > 0) {
                // Create properly formatted time slots with the correct date
                $formattedSlots = collect($responseData['data'])->map(function ($slot) use ($carbonDate) {
                    // Extract time components from start_time and end_time
                    $startTime = Carbon::parse($slot['start_time']);
                    $endTime = Carbon::parse($slot['end_time']);

                    // Create new Carbon instances with the correct date but same time
                    $correctStartTime = Carbon::create(
                        $carbonDate->year,
                        $carbonDate->month,
                        $carbonDate->day,
                        $startTime->hour,
                        $startTime->minute,
                        0
                    );

                    $correctEndTime = Carbon::create(
                        $carbonDate->year,
                        $carbonDate->month,
                        $carbonDate->day,
                        $endTime->hour,
                        $endTime->minute,
                        0
                    );

                    return [
                        'id' => $slot['id'],
                        'startTime' => $correctStartTime->toIso8601String(),
                        'endTime' => $correctEndTime->toIso8601String(),
                        //                        'isAvailable' => $slot['is_available'] && !$slot['is_booked'],
                        'isAvailable' => $slot['is_available'],
                        'formatted_time' => $slot['formatted_time'],
                    ];
                })->toArray();

                $availability[] = [
                    'date' => $date,
                    'slots' => $formattedSlots,
                ];
            }
        }

        // Restore the original authenticated user
        if ($originalUser) {
            Auth::setUser($originalUser);
        } else {
            Auth::logout();
        }

        // Include debug information in the response
        $debug = [
            'provider_id' => $provider->id,
            'request_date' => $startDate,
            'days' => $days,
        ];

        return $this->success([
            'availability' => $availability,
            'debug' => $debug,
            'is_working' => $isWorkingDay,
            'holiday' => $holiday ? $holiday->name : '',
        ], 'Provider availability retrieved successfully.');
    }

    /**
     * Check if a provider is in the current user's favorites
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkFavorite($id)
    {
        $user = Auth::user();

        $isFavorite = UserFavorite::where('user_id', $user->id)
            ->where('vendor_id', $id)
            ->exists();

        return $this->success($isFavorite, 'Favorite status retrieved successfully.');
    }

    /**
     * Toggle favorite status for a provider
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleFavorite($id)
    {
        $provider = User::where('id', $id)
            ->where('role', 'vendor')
            ->where('is_registered', true)
            ->where('is_kyc_completed', true)
            ->where('status', true)
            ->first();

        if (! $provider) {
            return $this->error([], 'Service provider not found.', 404);
        }

        $user = Auth::user();

        $favorite = UserFavorite::where('user_id', $user->id)
            ->where('vendor_id', $id)
            ->first();

        if ($favorite) {
            // Remove from favorites
            $favorite->delete();

            return $this->success(false, 'Provider removed from favorites.');
        } else {
            // Add to favorites
            UserFavorite::create([
                'user_id' => $user->id,
                'vendor_id' => $id,
            ]);

            return $this->success(true, 'Provider added to favorites.');
        }
    }

    /**
     * Book an appointment with a provider
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function bookAppointment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|exists:services,id',
            'date' => 'required|date_format:Y-m-d',
            'time_slot_id' => 'required|exists:time_slots,id',
            'notes' => 'sometimes|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        $provider = User::where('id', $id)
            ->where('role', 'vendor')
            ->where('is_registered', true)
            ->where('is_kyc_completed', true)
            ->where('status', true)
            ->first();

        if (! $provider) {
            return $this->error([], 'Service provider not found.', 404);
        }

        // Check if service belongs to provider
        $service = Service::where('id', $request->service_id)
            ->where('user_id', $id)
            ->where('is_active', true)
            ->first();

        if (! $service) {
            return $this->error([], 'Service not found or not available.', 404);
        }

        // Use the AppointmentController to handle booking
        $appointmentController = new AppointmentController;

        // Create a new request with needed data
        $appointmentRequest = new Request([
            'date' => $request->date,
            'time_slot_id' => $request->time_slot_id,
            'service_id' => $request->service_id,
            'notes' => $request->notes,
        ]);

        // Set current user on the request
        $appointmentRequest->setUserResolver(function () {
            return Auth::user();
        });

        // Forward to appointment controller
        $response = $appointmentController->store($appointmentRequest);

        return $response;
    }

    /**
     * Get user's favorite providers
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFavorites()
    {
        $user = Auth::user();

        $favorites = UserFavorite::with(['vendor' => function ($query) {
            $query->with(['kycDocument', 'businessCategory']);
        }])
            ->where('user_id', $user->id)

            ->get();

        $providers = $favorites->map(function ($favorite) {
            return $favorite->vendor;
        })->filter();

        return $this->success(ProviderResource::collection($providers), 'Favorites retrieved successfully.');
    }

    /**
     * Get service providers by category
     *
     * @param  int  $categoryId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProvidersByCategory($categoryId, Request $request)
    {
        $validator = Validator::make(['category_id' => $categoryId] + $request->all(), [
            'category_id' => 'required|exists:business_categories,id',
            'latitude' => 'sometimes|numeric',
            'longitude' => 'sometimes|numeric',
            'radius' => 'sometimes|numeric|min:1|max:100', // in kilometers
            'sort_by' => 'sometimes|in:rating,distance',
            'search' => 'sometimes|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        // Base query for providers with the specified category
        $query = User::with([
            'kycDocument',
            'businessCategory',
            'workingHours',
        ])
            ->whereHas('kycDocument', function ($query) use ($categoryId) {
                $query->where('business_category_id', $categoryId);
            })
            ->where('role', 'vendor')
            ->where('is_registered', true)
            ->where('is_kyc_completed', true)
            ->where('status', true)
            ->withCount('reviews');

        // Apply search if provided
        if ($request->has('search') && ! empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhereHas('kycDocument', function ($kq) use ($searchTerm) {
                        $kq->where('business_name', 'like', "%{$searchTerm}%")
                            ->orWhere('description', 'like', "%{$searchTerm}%");
                    });
            });
        }

        // If latitude and longitude are provided, calculate distance
        if ($request->has('latitude') && $request->has('longitude')) {
            $latitude = $request->latitude;
            $longitude = $request->longitude;
            $radius = $request->radius ?? 25; // Default 25km radius

            // Calculate distance using Haversine formula
            $query->whereHas('kycDocument', function ($q) use ($latitude, $longitude, $radius) {
                $q->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->selectRaw('(
                  6371 * acos(
                      cos(radians(?)) *
                      cos(radians(latitude)) *
                      cos(radians(longitude) - radians(?)) +
                      sin(radians(?)) *
                      sin(radians(latitude))
                  )
              ) AS distance', [$latitude, $longitude, $latitude])
                    ->havingRaw('distance < ?', [$radius]);
            });

            // Add distance as a select
            $query->addSelect(DB::raw("(
            6371 * acos(
                cos(radians({$latitude})) *
                cos(radians(kyc_documents.latitude)) *
                cos(radians(kyc_documents.longitude) - radians({$longitude})) +
                sin(radians({$latitude})) *
                sin(radians(kyc_documents.latitude))
            )
        ) AS distance"));

            $query->join('kyc_documents', 'users.id', '=', 'kyc_documents.user_id');

            // Sort by distance if requested
            if ($request->sort_by === 'distance') {
                $query->orderBy('distance', 'asc');
            }
        }

        // Sort by rating if requested (or by default if no other sort is specified)
        if (! $request->has('sort_by') || $request->sort_by === 'rating') {
            $query->orderBy('rating', 'desc');
        }

        // Get the providers
        $providers = $query->paginate(20);

        return $this->success([
            'providers' => ProviderResource::collection($providers->items()),
            'pagination' => [
                'total' => $providers->total(),
                'per_page' => $providers->perPage(),
                'current_page' => $providers->currentPage(),
                'last_page' => $providers->lastPage(),
            ],
        ], 'Service providers by category retrieved successfully.');
    }

    public function getSettings($id)
    {
        $settings = AppointmentSettings::where('user_id', $id)->first();

        if (! $settings) {
            $settings = AppointmentSettings::create([
                'user_id' => Auth::id(),
                'appointment_duration' => 30,
                'buffer_time' => 0,
                'advance_booking_days' => 14,
                'max_bookings_per_day' => 10,
                'is_online_booking_enabled' => true,
                'auto_confirm_appointments' => false,
                'appointment_modes' => ['in-person'],
            ]);
        }

        return $this->success(new AppointmentSettingsResponse($settings), 'Appointment settings retrieved successfully.');
    }

    /**
     * Get popular providers near a specified location
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPopularNearbyProviders(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'sometimes|numeric|min:1|max:100', // in kilometers
            'limit' => 'sometimes|integer|min:1|max:50',    // number of results to return
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $radius = $request->radius ?? 25; // Default 25km radius
        $limit = $request->limit ?? 20;   // Default 20 results

        // Get popular providers within the radius of given coordinates
        // We define "popular" as providers with high ratings and many reviews
        $providers = User::with([
            'kycDocument',
            'businessCategory',
            'reviews',
        ])
            ->join('kyc_documents', 'users.id', '=', 'kyc_documents.user_id')
            ->select([
                'users.*',
                DB::raw("(
                6371 * acos(
                    cos(radians({$latitude})) *
                    cos(radians(kyc_documents.latitude)) *
                    cos(radians(kyc_documents.longitude) - radians({$longitude})) +
                    sin(radians({$latitude})) *
                    sin(radians(kyc_documents.latitude))
                )
            ) AS distance"),
            ])
            ->where('users.role', 'vendor')
            ->where('users.is_registered', true)
            ->where('users.is_kyc_completed', true)
            ->where('users.status', true)
            ->whereNotNull('kyc_documents.latitude')
            ->whereNotNull('kyc_documents.longitude')
            ->having('distance', '<=', $radius)
            ->withCount('reviews')
            // Calculate a popularity score based on rating and number of reviews
            // This SQL creates a weighted score giving importance to both high ratings and number of reviews
            ->orderByRaw('(users.rating * 0.6) + (LEAST(reviews_count, 100) / 100 * 0.4) DESC, distance ASC')
            ->limit($limit)
            ->get();

        // If there's a logged-in user, add "is_favorite" flag
        if (Auth::check()) {
            $userId = Auth::id();
            foreach ($providers as $provider) {
                $provider->is_favorite = UserFavorite::where('user_id', $userId)
                    ->where('vendor_id', $provider->id)
                    ->exists();
            }
        }

        return $this->success(ProviderResource::collection($providers), 'Popular nearby providers retrieved successfully.');
    }

    /**
     * Get nearby vendors/providers based on location
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNearbyProviders(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'sometimes|numeric|min:1|max:100', // in kilometers
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $radius = $request->radius ?? 25; // Default 25km radius

        // Get providers within the radius of given coordinates
        $providers = User::with([
            'kycDocument',
            'businessCategory',
            'reviews',
        ])
            ->join('kyc_documents', 'users.id', '=', 'kyc_documents.user_id')
            ->select([
                'users.*',
                DB::raw("(
                6371 * acos(
                    cos(radians({$latitude})) *
                    cos(radians(kyc_documents.latitude)) *
                    cos(radians(kyc_documents.longitude) - radians({$longitude})) +
                    sin(radians({$latitude})) *
                    sin(radians(kyc_documents.latitude))
                )
            ) AS distance"),
            ])
            ->where('users.role', 'vendor')
            ->where('users.is_registered', true)
            ->where('users.is_kyc_completed', true)
            ->where('users.status', true)
            ->whereNotNull('kyc_documents.latitude')
            ->whereNotNull('kyc_documents.longitude')
            ->having('distance', '<=', $radius)
            ->orderBy('distance', 'asc') // Sort by closest first
            ->limit(20)
            ->withCount('reviews')
            ->get();

        // If there's a logged-in user, add "is_favorite" flag
        if (Auth::check()) {
            $userId = Auth::id();
            foreach ($providers as $provider) {
                $provider->is_favorite = UserFavorite::where('user_id', $userId)
                    ->where('vendor_id', $provider->id)
                    ->exists();
            }
        }

        return $this->success(ProviderResource::collection($providers), 'Nearby providers retrieved successfully.');
    }

    /**
     * Get combo services offered by a specific provider
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getComboServices($id)
    {
        $provider = User::where('id', $id)
            ->where('role', 'vendor')
            ->where('is_registered', true)
            ->where('is_kyc_completed', true)
            ->where('status', true)
            ->first();

        if (! $provider) {
            return $this->error([], 'Service provider not found.', 404);
        }

        $comboServices = ComboService::with('services')
            ->where('user_id', $id)
            ->where('is_active', true)
            ->get();

        return $this->success($comboServices, 'Provider combo services retrieved successfully.');
    }
}
