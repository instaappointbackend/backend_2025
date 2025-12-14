<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentSettingsResponse extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'appointment_duration' => $this->appointment_duration,
            'formatted_appointment_duration' => $this->formattedDuration,
            'buffer_time' => $this->buffer_time,
            'formatted_buffer_time' => $this->formattedBufferTime,
            'advance_booking_days' => $this->advance_booking_days,
            'max_booking_date' => $this->maxBookingDate->format('Y-m-d'),
            'max_bookings_per_day' => $this->max_bookings_per_day,
            'is_online_booking_enabled' => $this->is_online_booking_enabled,
            'auto_confirm_appointments' => $this->auto_confirm_appointments,
            'appointment_modes' => $this->appointment_modes,
            'payment_methods' => $this->payment_methods,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
