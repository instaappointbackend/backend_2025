<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'appointment_id' => $this->appointment_id,
            'payment_id' => $this->payment_id,
            'refund_amount' => $this->refund_amount,
            'vendor_amount' => $this->vendor_amount,
            'admin_amount' => $this->admin_amount,
            'refund_type' => $this->refund_type,
            'refund_reason' => $this->refund_reason,
            'refund_status' => $this->refund_status,
            'refund_reference' => $this->refund_reference,
            'processed_at' => $this->processed_at,
            'cancellation_time_hours' => $this->cancellation_time_hours,
            'original_amount' => $this->original_amount,
            'service_charges' => $this->service_charges,
            'platform_fee' => $this->platform_fee,
            'other_charges' => $this->other_charges,
            'gst_amount' => $this->gst_amount,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Formatted values
            'formatted_refund_amount' => $this->formatted_refund_amount,
            'formatted_vendor_amount' => $this->formatted_vendor_amount,
            'formatted_admin_amount' => $this->formatted_admin_amount,
            'refund_type_display' => $this->refund_type_display,
            'human_status' => $this->human_status,
            'status_badge_class' => $this->status_badge_class,

            // Policy details
            'policy_applied' => $this->refund_details['policy_applied'] ?? null,
            'refund_details' => $this->refund_details,

            // Related data
            'appointment' => $this->whenLoaded('appointment', function () {
                return [
                    'id' => $this->appointment->id,
                    'date' => $this->appointment->date,
                    'start_time' => $this->appointment->start_time,
                    'end_time' => $this->appointment->end_time,
                    'status' => $this->appointment->status,
                    'service_name' => $this->appointment->appointment_title,
                ];
            }),

            'payment' => $this->whenLoaded('payment', function () {
                return [
                    'id' => $this->payment->id,
                    'transaction_id' => $this->payment->transaction_id,
                    'payment_method' => $this->payment->payment_method,
                    'amount' => $this->payment->amount,
                    'status' => $this->payment->status,
                ];
            }),

            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'mobile' => $this->user->mobile,
                ];
            }),

            'provider' => $this->whenLoaded('provider', function () {
                return [
                    'id' => $this->provider->id,
                    'name' => $this->provider->name,
                    'email' => $this->provider->email,
                    'mobile' => $this->provider->mobile,
                ];
            }),
        ];
    }
}
