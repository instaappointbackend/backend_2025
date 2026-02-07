<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResponse extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $formattedDate = $this->date
            ? Carbon::parse($this->date)->format('F d, Y')
            : null;

        $formattedTime = null;
        if ($this->start_time) {
            $start = Carbon::parse($this->start_time)->format('g:i A');

            // Always calculate end time based on service duration (don't use stored end_time)
            $duration = $this->getTotalDuration();
            $endTime = Carbon::parse($this->start_time)->addMinutes($duration);
            $end = $endTime->format('g:i A');
            $formattedTime = "$start - $end";
        }

        return [
            'id' => $this->id,
            'provider' => $this->when($this->user, function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'phone' => $this->user->phone,
                    'profile_picture' => $this->user->profile_picture ? asset('storage/'.$this->user->profile_picture) : null,
                    'business_type' => $this->user->business_type,
                ];
            }),
            'client' => $this->when($this->client, function () {
                return [
                    'id' => $this->client->id,
                    'name' => $this->client->name,
                    'email' => $this->client->email,
                    'phone' => $this->client->phone,
                    'profile_picture' => $this->client->profile_picture ? asset('storage/'.$this->client->profile_picture) : null,

                ];
            }),
            'service' => $this->when($this->service, function () {
                return [
                    'id' => $this->service->id,
                    'name' => $this->service->name,
                    'description' => $this->service->description,
                    'duration' => $this->service->duration,
                    'formatted_duration' => $this->service->formatted_duration,
                    'price' => $this->service->price,
                    'formatted_price' => $this->service->formatted_price,
                ];
            }),
            'combo_service' => $this->when($this->comboService, function () {
                return [
                    'id' => $this->comboService->id,
                    'name' => $this->comboService->name,
                    'description' => $this->comboService->description,
                    'services' => ServiceResponse::collection($this->comboService->services),
                    'total_duration' => $this->comboService->total_duration,
                    'formatted_total_duration' => $this->comboService->formatted_total_duration,
                    'total_price' => $this->comboService->total_price,
                    'formatted_total_price' => $this->comboService->formatted_total_price,
                    'discounted_price' => $this->comboService->discounted_price,
                    'formatted_discounted_price' => $this->comboService->formatted_discounted_price,
                    'discount_percentage' => $this->comboService->discount_percentage,
                ];
            }),
            'date' => $this->date,
            'formatted_date' => $formattedDate,
            'start_time' => $this->start_time,
            'end_time' => $this->calculateEndTime(),
            'formatted_time' => $formattedTime,
            'status' => $this->status,
            'human_status' => $this->human_status,
            'notes' => $this->notes,
            'visit_type' => $this->visit_type,
            'payment' => [
                'status' => $this->payment_status,
                'human_status' => $this->human_payment_status,
                'transaction_id' => $this->payment_id,
                'method' => $this->payment_method,
                'mode' => $this->payment_mode ?? $this->payment_method,
                'amount' => $this->payment_amount,
                'formatted_amount' => $this->formatted_payment_amount,

                // Detailed payment breakdown
                'booking_price' => $this->booking_price,
                'formatted_booking_price' => $this->when($this->booking_price, function () {
                    return '₹'.number_format($this->booking_price, 2);
                }),

                'platform_fees' => $this->platform_fees,
                'formatted_platform_fees' => $this->when($this->platform_fees, function () {
                    return '₹'.number_format($this->platform_fees, 2);
                }),

                'other_charges' => $this->other_charges,
                'formatted_other_charges' => $this->when($this->other_charges, function () {
                    return '₹'.number_format($this->other_charges, 2);
                }),

                'gst' => $this->gst,
                'formatted_gst' => $this->when($this->gst, function () {
                    return '₹'.number_format($this->gst, 2);
                }),

                'original_price' => $this->original_price,
                'formatted_original_price' => $this->when($this->original_price, function () {
                    return '₹'.number_format($this->original_price, 2);
                }),

                'discount_amount' => $this->discount_amount,
                'formatted_discount_amount' => $this->when($this->discount_amount, function () {
                    return '₹'.number_format($this->discount_amount, 2);
                }),

                'discount_percentage' => $this->discount_percentage,

                'home_visit_fee' => $this->home_visit_fee,
                'formatted_home_visit_fee' => $this->when($this->home_visit_fee, function () {
                    return '₹'.number_format($this->home_visit_fee, 2);
                }),

                'additional_services_fee' => $this->additional_services_fee,
                'formatted_additional_services_fee' => $this->when($this->additional_services_fee, function () {
                    return '₹'.number_format($this->additional_services_fee, 2);
                }),

                'final_price' => $this->final_price ?? $this->payment_amount,
                'formatted_final_price' => $this->when($this->final_price, function () {
                    return '₹'.number_format($this->final_price, 2);
                }, function () {
                    return $this->when($this->payment_amount, function () {
                        return '₹'.number_format($this->payment_amount, 2);
                    });
                }),

                // Discount and offer information
                'coupon_code' => $this->coupon_code,
                'offer_title' => $this->offer_title,
                'offer_type' => $this->offer_type,
                'vendor_offer_id' => $this->vendor_offer_id,
                'admin_offer_id' => $this->admin_offer_id,
            ],
            'can_cancel' => $this->can_cancel ?? $this->canBeCancelled(),
            'can_reschedule' => $this->can_reschedule ?? $this->canBeRescheduled(),
            'can_complete' => $this->can_complete ?? $this->canBeCompleted(),
            'can_review' => $this->can_review ?? $this->canLeaveReview(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Calculate the correct end time based on service duration
     */
    private function calculateEndTime()
    {
        if ($this->end_time) {
            // If end_time already exists and is correct, return it
            // But we need to validate if it matches the duration
            if ($this->start_time) {
                $expectedDuration = $this->getTotalDuration();
                $calculatedEndTime = Carbon::parse($this->start_time)->addMinutes($expectedDuration);

                return $calculatedEndTime->toISOString();
            }

            return $this->end_time;
        }

        if ($this->start_time) {
            $duration = $this->getTotalDuration();

            return Carbon::parse($this->start_time)->addMinutes($duration)->toISOString();
        }

        return null;
    }

    /**
     * Get total duration for the appointment
     */
    private function getTotalDuration()
    {
        // For combo services, use total_duration or calculate from individual services
        if ($this->comboService) {
            if ($this->comboService->total_duration) {
                return $this->comboService->total_duration;
            }

            // If total_duration is not available, calculate from individual services
            if ($this->comboService->services) {
                $totalDuration = 0;
                foreach ($this->comboService->services as $service) {
                    $totalDuration += $service->duration ?? 0;
                }

                return $totalDuration;
            }
        }

        // For single services
        if ($this->service && $this->service->duration) {
            return $this->service->duration;
        }

        // Default fallback (should rarely be used)
        return 60;
    }
}
