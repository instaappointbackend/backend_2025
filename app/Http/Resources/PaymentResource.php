<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // Get formatted date and time
        $createdAt = $this->created_at ? Carbon::parse($this->created_at) : null;
        $updatedAt = $this->updated_at ? Carbon::parse($this->updated_at) : null;

        return [
            'id' => $this->id,
            'appointment_id' => $this->appointment_id,
            'user_id' => $this->user_id,
            'provider_id' => $this->provider_id,
            'transaction_id' => $this->transaction_id,
            'payment_method' => $this->payment_method,
            'payment_mode' => $this->payment_mode,
            'amount' => $this->amount,
            'formatted_amount' => $this->getFormattedAmountAttribute(),
            'currency' => $this->currency ?? 'INR',
            'status' => $this->status,
            'human_status' => $this->getHumanStatusAttribute(),
            'payment_details' => $this->payment_details,

            // Pricing breakdown
            'original_price' => $this->original_price,
            'formatted_original_price' => $this->original_price ? $this->getFormattedOriginalPriceAttribute() : null,
            'booking_price' => $this->booking_price,
            'formatted_booking_price' => $this->booking_price ? $this->getFormattedBookingPriceAttribute() : null,
            'platform_fee' => $this->platform_fee,
            'formatted_platform_fee' => $this->platform_fee ? $this->getFormattedPlatformFeeAttribute() : null,
            'other_charges' => $this->other_charges,
            'formatted_other_charges' => $this->other_charges ? $this->getFormattedOtherChargesAttribute() : null,
            'gst_amount' => $this->gst_amount,
            'formatted_gst_amount' => $this->gst_amount ? $this->getFormattedGstAmountAttribute() : null,
            'discount_amount' => $this->discount_amount,
            'formatted_discount_amount' => $this->discount_amount ? $this->getFormattedDiscountAmountAttribute() : null,
            'discount_percentage' => $this->discount_percentage,
            'home_visit_fee' => $this->home_visit_fee,
            'formatted_home_visit_fee' => $this->home_visit_fee ? $this->getFormattedHomeVisitFeeAttribute() : null,
            'additional_services_fee' => $this->additional_services_fee,
            'formatted_additional_services_fee' => $this->additional_services_fee ? $this->getFormattedAdditionalServicesFeeAttribute() : null,
            'net_amount' => $this->net_amount,
            'formatted_net_amount' => $this->net_amount ? $this->getFormattedNetAmountAttribute() : null,
            'vendor_earnings' => $this->vendor_earnings,
            'formatted_vendor_earnings' => $this->getFormattedVendorEarningsAttribute(),
            'admin_earnings' => $this->admin_earnings,
            'formatted_admin_earnings' => $this->getFormattedAdminEarningsAttribute(),

            // Offer details
            'coupon_code' => $this->coupon_code,
            'offer_title' => $this->offer_title,
            'vendor_offer_id' => $this->vendor_offer_id,
            'admin_offer_id' => $this->admin_offer_id,
            'offer_type' => $this->offer_type,
            'additional_notes' => $this->additional_notes,

            // Payment breakdown for UI
            'payment_breakdown' => $this->getPaymentBreakdownAttribute(),

            // Display helpers
            'payment_method_display' => $this->getPaymentMethodDisplayAttribute(),
            'status_badge_class' => $this->getStatusBadgeClassAttribute(),

            // Timestamps
            'created_at' => $createdAt ? $createdAt->toISOString() : null,
            'formatted_created_at' => $createdAt ? $createdAt->format('Y-m-d H:i:s') : null,
            'updated_at' => $updatedAt ? $updatedAt->toISOString() : null,

            // Related appointment data with conditional loading
            'appointment' => $this->whenLoaded('appointment', function () {
                return [
                    'id' => $this->appointment->id,
                    'date' => $this->appointment->date,
                    'formatted_date' => $this->appointment->date ? Carbon::parse($this->appointment->date)->format('F d, Y') : null,
                    'start_time' => $this->appointment->start_time,
                    'end_time' => $this->appointment->end_time,
                    'formatted_time' => $this->getFormattedAppointmentTime(),
                    'status' => $this->appointment->status,
                    'human_status' => ucfirst($this->appointment->status),
                    'visit_type' => $this->appointment->visit_type,
                    'payment_status' => $this->appointment->payment_status,
                    'notes' => $this->appointment->notes,
                    'combo_service' => $this->when($this->appointment->comboService, function () {
                        return [
                            'id' => $this->appointment->comboService->id,
                            'name' => $this->appointment->comboService->name,
                            'description' => $this->appointment->comboService->description,
                            'services' => $this->appointment->comboService->services ?
                                ServiceResponse::collection($this->appointment->comboService->services) : [],
                            'total_duration' => $this->appointment->comboService->total_duration,
                            'formatted_total_duration' => $this->appointment->comboService->formatted_total_duration,
                            'total_price' => $this->appointment->comboService->total_price,
                            'formatted_total_price' => $this->appointment->comboService->formatted_total_price,
                            'discounted_price' => $this->appointment->comboService->discounted_price,
                            'formatted_discounted_price' => $this->appointment->comboService->formatted_discounted_price,
                            'discount_percentage' => $this->appointment->comboService->discount_percentage,
                        ];
                    }),
                    'service' => $this->when($this->appointment->service, function () {
                        return [
                            'id' => $this->appointment->service->id,
                            'name' => $this->appointment->service->name,
                            'duration' => $this->appointment->service->duration,
                            'price' => $this->appointment->service->price,
                            'formatted_price' => $this->appointment->service->formatted_price ?? null,
                            'formatted_duration' => $this->appointment->service->formatted_duration ?? null,
                        ];
                    }),
                    'client' => $this->when($this->appointment->client, function () {
                        return [
                            'id' => $this->appointment->client->id,
                            'name' => $this->appointment->client->name,
                            'email' => $this->appointment->client->email,
                            'phone' => $this->appointment->client->phone,
                            'profile_picture' => $this->appointment->client->profile_picture ?
                                asset('storage/'.$this->appointment->client->profile_picture) : null,
                        ];
                    }),
                    'provider' => $this->when($this->appointment->provider, function () {
                        return [
                            'id' => $this->appointment->provider->id,
                            'name' => $this->appointment->provider->name,
                            'email' => $this->appointment->provider->email,
                            'phone' => $this->appointment->provider->phone,
                            'business_name' => $this->getProviderBusinessName(),
                            'address' => $this->getProviderAddress(),
                            'profile_picture' => $this->appointment->provider->profile_picture ?
                                asset('storage/'.$this->appointment->provider->profile_picture) : null,
                        ];
                    }),
                ];
            }),

            // Client data (user who made the payment)
            'client' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'phone' => $this->user->phone,
                    'profile_picture' => $this->user->profile_picture ?
                        asset('storage/'.$this->user->profile_picture) : null,
                ];
            }),

            // Provider data (user who received the payment)
            'provider' => $this->whenLoaded('provider', function () {
                return [
                    'id' => $this->provider->id,
                    'name' => $this->provider->name,
                    'email' => $this->provider->email,
                    'phone' => $this->provider->phone,
                    'business_name' => $this->getProviderBusinessName(),
                    'address' => $this->getProviderAddress(),
                    'profile_picture' => $this->provider->profile_picture ?
                        asset('storage/'.$this->provider->profile_picture) : null,
                    'business_details' => $this->provider->kycDetails ? [
                        'business_name' => $this->provider->kycDetails->business_name,
                        'business_type' => $this->provider->businessCategory ?
                            $this->provider->businessCategory->name : null,
                    ] : null,
                ];
            }),
        ];
    }

    /**
     * Helper to get formatted appointment time
     */
    protected function getFormattedAppointmentTime()
    {
        if (! $this->appointment) {
            return null;
        }

        if ($this->appointment->formatted_time) {
            return $this->appointment->formatted_time;
        }

        try {
            if ($this->appointment->start_time && $this->appointment->end_time) {
                $startTime = Carbon::parse($this->appointment->start_time)->format('g:i A');
                $endTime = Carbon::parse($this->appointment->end_time)->format('g:i A');

                return "$startTime - $endTime";
            }
        } catch (\Exception $e) {
            // If parsing fails, return null
            return null;
        }

        return null;
    }

    /**
     * Helper to get provider business name
     */
    protected function getProviderBusinessName()
    {
        if ($this->appointment && $this->appointment->provider && $this->appointment->provider->kycDetails) {
            return $this->appointment->provider->kycDetails->business_name;
        }

        if ($this->provider && $this->provider->kycDetails) {
            return $this->provider->kycDetails->business_name;
        }

        return null;
    }

    /**
     * Helper to get provider address
     */
    protected function getProviderAddress()
    {
        if ($this->appointment && $this->appointment->provider && $this->appointment->provider->kycDetails) {
            return $this->appointment->provider->kycDetails->address;
        }

        if ($this->provider && $this->provider->kycDetails) {
            return $this->provider->kycDetails->address;
        }

        if ($this->appointment && $this->appointment->provider) {
            return $this->appointment->provider->address;
        }

        if ($this->provider) {
            return $this->provider->address;
        }

        return null;
    }
}
