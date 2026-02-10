<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Appointment;
use App\Models\Review;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get reviews for a specific provider.
     */
    public function getProviderReviews($providerId)
    {
        $reviews = Review::where('provider_id', $providerId)
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return $this->success(ReviewResource::collection($reviews), 'Provider reviews retrieved successfully.');
    }

    /**
     * Get reviews for a specific service.
     */
    public function getServiceReviews($serviceId)
    {
        $reviews = Review::where('service_id', $serviceId)
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return $this->success(ReviewResource::collection($reviews), 'Service reviews retrieved successfully.');
    }

    /**
     * Get reviews for a specific combo service.
     */
    public function getComboServiceReviews($comboServiceId)
    {
        $reviews = Review::where('combo_service_id', $comboServiceId)
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return $this->success(ReviewResource::collection($reviews), 'Combo service reviews retrieved successfully.');
    }

    /**
     * Get reviews by the current user.
     */
    public function getUserReviews()
    {
        $reviews = Review::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return $this->success(ReviewResource::collection($reviews), 'User reviews retrieved successfully.');
    }

    /**
     * Get review for a specific appointment.
     */
    public function getAppointmentReview($appointmentId)
    {
        $review = Review::where('appointment_id', $appointmentId)
            ->first();

        if (! $review) {
            return $this->error(null, 'No review found for this appointment.', 404);
        }

        return $this->success(new ReviewResource($review), 'Appointment review retrieved successfully.');
    }

    /**
     * Check if the user can leave a review for a specific appointment.
     */
    public function canReview($appointmentId)
    {
        $appointment = Appointment::find($appointmentId);

        if (! $appointment) {
            return $this->error(null, 'Appointment not found.', 404);
        }

        // Check if the appointment belongs to the current user
        if ($appointment->client_id !== Auth::id()) {
            return $this->error(null, 'You are not authorized to review this appointment.', 403);
        }

        // Check if the appointment is completed
        if ($appointment->status !== Appointment::STATUS_COMPLETED) {
            return $this->error(null, 'You can only review completed appointments.', 400);
        }

        // Check if a review already exists
        $existingReview = Review::where('appointment_id', $appointmentId)->exists();
        if ($existingReview) {
            return $this->error(null, 'You have already reviewed this appointment.', 400);
        }

        return $this->success(['can_review' => true], 'You can review this appointment.');
    }

    /**
     * Submit a review.
     */
    public function store(ReviewRequest $request)
    {
        // Validate appointment
        $appointment = Appointment::find($request->appointment_id);

        if (! $appointment) {
            return $this->error(null, 'Appointment not found.', 404);
        }

        // Check if the appointment belongs to the current user
        if ($appointment->client_id !== Auth::id()) {
            return $this->error(null, 'You are not authorized to review this appointment.', 403);
        }

        // Check if the appointment is completed
        if ($appointment->status !== Appointment::STATUS_COMPLETED) {
            return $this->error(null, 'You can only review completed appointments.', 400);
        }

        // Check if a review already exists
        $existingReview = Review::where('appointment_id', $appointment->id)->exists();
        if ($existingReview) {
            return $this->error(null, 'You have already reviewed this appointment.', 400);
        }

        // Create the review
        $reviewData = [
            'user_id' => Auth::id(),
            'provider_id' => $appointment->user_id,
            'appointment_id' => $appointment->id,
            'rating' => $request->rating,
            'review_text' => $request->review_text,
            'is_anonymous' => $request->is_anonymous ?? false,
            'status' => 'pending', // All reviews start as pending until approved
        ];

        // Set service or combo service ID based on the appointment
        if ($appointment->service_id) {
            $reviewData['service_id'] = $appointment->service_id;
        } elseif ($appointment->combo_service_id) {
            $reviewData['combo_service_id'] = $appointment->combo_service_id;
        }

        $review = Review::create($reviewData);

        return $this->success(new ReviewResource($review), 'Review submitted successfully.', 201);
    }

    /**
     * Update a review.
     */
    public function update(ReviewRequest $request, $id)
    {
        $review = Review::find($id);

        if (! $review) {
            return $this->error(null, 'Review not found.', 404);
        }

        // Check if the review belongs to the current user
        if ($review->user_id !== Auth::id()) {
            return $this->error(null, 'You are not authorized to update this review.', 403);
        }

        // Check if the review is already approved (can't update approved reviews)
        if ($review->status === 'approved') {
            return $this->error(null, 'You cannot update an approved review.', 400);
        }

        $review->update([
            'rating' => $request->rating,
            'review_text' => $request->review_text,
            'is_anonymous' => $request->is_anonymous ?? $review->is_anonymous,
            'status' => 'pending', // Reset status to pending after updates
        ]);

        return $this->success(new ReviewResource($review), 'Review updated successfully.');
    }

    /**
     * Delete a review.
     */
    public function destroy($id)
    {
        $review = Review::find($id);

        if (! $review) {
            return $this->error(null, 'Review not found.', 404);
        }

        // Check if the review belongs to the current user
        if ($review->user_id !== Auth::id()) {
            return $this->error(null, 'You are not authorized to delete this review.', 403);
        }

        $review->delete();

        return $this->success(null, 'Review deleted successfully.');
    }

    /**
     * Get review statistics for a provider.
     */
    public function getProviderStats($providerId)
    {
        $reviews = Review::where('provider_id', $providerId)
            ->where('status', 'approved');

        $totalReviews = $reviews->count();

        if ($totalReviews === 0) {
            return $this->success([
                'average_rating' => 0,
                'total_reviews' => 0,
                'rating_distribution' => [
                    '5' => 0,
                    '4' => 0,
                    '3' => 0,
                    '2' => 0,
                    '1' => 0,
                ],
            ], 'Provider has no reviews yet.');
        }

        $averageRating = $reviews->avg('rating');

        // Get rating distribution
        $ratingDistribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $ratingDistribution[$i] = $reviews->where('rating', '>=', $i)
                ->where('rating', '<', $i + 1)
                ->count();
        }

        return $this->success([
            'average_rating' => round($averageRating, 1),
            'total_reviews' => $totalReviews,
            'rating_distribution' => $ratingDistribution,
        ], 'Provider review statistics retrieved successfully.');
    }

    /**
     * Respond to a review (for providers).
     */
    public function respondToReview(Request $request, $id)
    {
        $review = Review::find($id);

        if (! $review) {
            return $this->error(null, 'Review not found.', 404);
        }

        // Check if the current user is the provider being reviewed
        if ($review->provider_id !== Auth::id()) {
            return $this->error(null, 'You are not authorized to respond to this review.', 403);
        }

        $request->validate([
            'response' => 'required|string|max:1000',
        ]);

        $review->update([
            'review_response' => $request->response,
            'response_at' => now(),
        ]);

        return $this->success(new ReviewResource($review), 'Response added successfully.');
    }
}
