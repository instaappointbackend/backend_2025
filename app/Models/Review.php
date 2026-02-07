<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'provider_id',
        'appointment_id',
        'service_id',
        'combo_service_id',
        'rating',
        'review_text',
        'is_anonymous',
        'status',
        'review_response',
        'response_at',
        'admin_notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'rating' => 'float',
        'is_anonymous' => 'boolean',
        'response_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The possible status values for a review.
     */
    const STATUS_PENDING = 'pending';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    /**
     * Get the user who wrote the review.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the provider being reviewed.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    /**
     * Get the appointment this review is for.
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the service this review is for.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the combo service this review is for.
     */
    public function comboService(): BelongsTo
    {
        return $this->belongsTo(ComboService::class, 'combo_service_id');
    }

    /**
     * Get formatted rating with decimal format.
     */
    public function getFormattedRatingAttribute(): string
    {
        return number_format($this->rating, 1);
    }

    /**
     * Get the star rating as an array of filled and unfilled stars.
     */
    public function getStarRatingAttribute(): array
    {
        $filledStars = floor($this->rating);
        $halfStar = ($this->rating - $filledStars) >= 0.5;
        $emptyStars = 5 - $filledStars - ($halfStar ? 1 : 0);

        return [
            'filled' => $filledStars,
            'half' => $halfStar,
            'empty' => $emptyStars,
        ];
    }

    /**
     * Get the review status in a human-readable format.
     */
    public function getHumanStatusAttribute(): string
    {
        return ucfirst($this->status);
    }

    /**
     * Get whether the review has a response.
     */
    public function getHasResponseAttribute(): bool
    {
        return ! is_null($this->review_response);
    }

    /**
     * Scope a query to only include pending reviews.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope a query to only include approved reviews.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope a query to only include rejected reviews.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope a query to only include reviews for a specific provider.
     */
    public function scopeForProvider($query, $providerId)
    {
        return $query->where('provider_id', $providerId);
    }

    /**
     * Scope a query to only include reviews for a specific service.
     */
    public function scopeForService($query, $serviceId)
    {
        return $query->where('service_id', $serviceId);
    }

    /**
     * Scope a query to only include reviews for a specific combo service.
     */
    public function scopeForComboService($query, $comboServiceId)
    {
        return $query->where('combo_service_id', $comboServiceId);
    }

    /**
     * Scope a query to only include reviews with a minimum rating.
     */
    public function scopeWithMinRating($query, $minRating)
    {
        return $query->where('rating', '>=', $minRating);
    }

    /**
     * Scope a query to only include reviews with a maximum rating.
     */
    public function scopeWithMaxRating($query, $maxRating)
    {
        return $query->where('rating', '<=', $maxRating);
    }

    /**
     * Scope a query to only include reviews by a specific user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include reviews with a response.
     */
    public function scopeWithResponse($query)
    {
        return $query->whereNotNull('review_response');
    }

    /**
     * Scope a query to only include reviews without a response.
     */
    public function scopeWithoutResponse($query)
    {
        return $query->whereNull('review_response');
    }

    /**
     * Mark the review as approved.
     */
    public function approve()
    {
        $this->update(['status' => self::STATUS_APPROVED]);
    }

    /**
     * Mark the review as rejected.
     */
    public function reject($reason = null)
    {
        $updateData = ['status' => self::STATUS_REJECTED];

        if ($reason) {
            $updateData['admin_notes'] = $reason;
        }

        $this->update($updateData);
    }

    /**
     * Add a response to the review.
     */
    public function addResponse($response)
    {
        $this->update([
            'review_response' => $response,
            'response_at' => now(),
        ]);
    }

    /**
     * Remove the response from the review.
     */
    public function removeResponse()
    {
        $this->update([
            'review_response' => null,
            'response_at' => null,
        ]);
    }

    /**
     * Check if the review is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the review is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if the review is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Calculate average rating for a provider.
     */
    public static function getAverageRatingForProvider($providerId)
    {
        return self::where('provider_id', $providerId)
            ->where('status', self::STATUS_APPROVED)
            ->avg('rating') ?? 0;
    }

    /**
     * Calculate average rating for a service.
     */
    public static function getAverageRatingForService($serviceId)
    {
        return self::where('service_id', $serviceId)
            ->where('status', self::STATUS_APPROVED)
            ->avg('rating') ?? 0;
    }

    /**
     * Calculate average rating for a combo service.
     */
    public static function getAverageRatingForComboService($comboServiceId)
    {
        return self::where('combo_service_id', $comboServiceId)
            ->where('status', self::STATUS_APPROVED)
            ->avg('rating') ?? 0;
    }

    /**
     * Get rating distribution for a provider.
     */
    public static function getRatingDistributionForProvider($providerId)
    {
        $reviews = self::where('provider_id', $providerId)
            ->where('status', self::STATUS_APPROVED)
            ->get();

        $distribution = [
            '5' => 0,
            '4' => 0,
            '3' => 0,
            '2' => 0,
            '1' => 0,
        ];

        foreach ($reviews as $review) {
            $rating = floor($review->rating);
            if ($rating < 1) {
                $rating = 1;
            }
            if ($rating > 5) {
                $rating = 5;
            }

            $distribution[(string) $rating]++;
        }

        return $distribution;
    }
}
