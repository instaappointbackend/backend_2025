<?php

namespace App\Http\Resources;

use App\Models\Review;
use App\Models\UserFavorite;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProviderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $kyc = $this->kycDocument;
        $userId = auth()->check() ? auth()->id() : null;
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'role' => $this->role,
            'bio' => $this->bio ? $this->bio : '...',
            'experience' => $this->experience,
            'profile_picture' => $this->profile_picture ? asset('storage/' . $this->profile_picture) : null,
            'rating'          => $this->role=='vendor' ? number_format(Review::getAverageRatingForProvider($this->id),1) : '',
            'review_count' => $this->whenHas('reviews_count', function() {
                return $this->reviews_count;
            }, 0),
            'is_verified' => (bool) $this->is_kyc_completed,
            'is_favorite' => $userId ? UserFavorite::where('user_id', $userId)
                ->where('vendor_id', $this->id)
                ->exists() : false,
            'business_details' => $kyc ? [
                'business_name' => $kyc->business_name,
                'business_type' => $this->businessCategory ? $this->businessCategory->name : null,
                'business_category_id' => $kyc->business_category_id,
                'business_logo' => $kyc->business_logo ? asset('storage/' . $kyc->business_logo) : null,
                'business_established_date' => $kyc->business_established_date,
                'description' => $kyc->description,
            ] : null,
            'location' => $kyc ? [
                'address' => $kyc->address,
                'full_address' => $kyc->full_address,
                'street' => $kyc->street,
                'city' => $kyc->city,
                'state' => $kyc->state,
                'country' => $kyc->country,
                'postal_code' => $kyc->postal_code,
                'latitude' => (float) $kyc->latitude,
                'longitude' => (float) $kyc->longitude,
            ] : [
                'address' => $this->address,
                'full_address' => $this->full_address,
                'street' => $this->street,
                'city' => $this->city,
                'state' => $this->state,
                'country' => $this->country,
                'postal_code' => $this->postal_code,
                'latitude' => (float) $this->latitude,
                'longitude' => (float) $this->longitude,
            ],
            'operating_hours' => $this->when($this->workingHours, function() {
                // Use WorkingHoursResponse to format each working hour
                return $this->workingHours->map(function ($workingHour) {
                    return [
                        'id' => $workingHour->id,
                        'day_of_week' => $workingHour->day_of_week,
                        'day_name' => $this->getDayName($workingHour->day_of_week),
                        'is_working_day' => (bool) $workingHour->is_working_day,
                        'start_time' => $workingHour->start_time ? date('H:i', strtotime($workingHour->start_time)) : null,
                        'end_time' => $workingHour->end_time ? date('H:i', strtotime($workingHour->end_time)) : null,
                        'break_start' => $workingHour->break_start ? date('H:i', strtotime($workingHour->break_start)) : null,
                        'break_end' => $workingHour->break_end ? date('H:i', strtotime($workingHour->break_end)) : null,
                        'has_break' => ($workingHour->break_start && $workingHour->break_end),
                    ];
                })->values();
            }, []),
            'contact_info' => [
                'phone' => $this->mobile,
                'email' => $this->email,
                'website' => $kyc && $kyc->website ? $kyc->website : null,
            ],
            'social_media' => $kyc ? [
                'facebook' => $kyc->facebook ?? null,
                'instagram' => $kyc->instagram ?? null,
                'twitter' => $kyc->twitter ?? null,
            ] : [
                'facebook' => null,
                'instagram' => null,
                'twitter' => null,
            ],
            'appointment_settings' => $this->when($this->appointmentSettings, function() {
                return [
                    'appointment_duration' => $this->appointmentSettings->appointment_duration,
                    'buffer_time' => $this->appointmentSettings->buffer_time,
                    'advance_booking_days' => $this->appointmentSettings->advance_booking_days,
                    'max_bookings_per_day' => $this->appointmentSettings->max_bookings_per_day,
                    'is_online_booking_enabled' => (bool) $this->appointmentSettings->is_online_booking_enabled,
                    'auto_confirm_appointments' => (bool) $this->appointmentSettings->auto_confirm_appointments,
                    'appointment_modes' =>  $this->appointmentSettings->appointment_modes,
                    'payment_methods' =>  $this->appointmentSettings->payment_methods,
                ];
            }),
//            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
//            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get the day name from day of week number.
     */
    private function getDayName($dayOfWeek)
    {
        $days = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        return $days[$dayOfWeek] ?? 'Unknown';
    }
}
