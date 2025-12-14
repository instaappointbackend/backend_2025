<?php

namespace App\Http\Requests;

use App\Traits\ApiResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AppointmentSettingsRequest extends FormRequest
{
    use ApiResponseTrait;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'appointment_duration' => 'sometimes|integer|min:5|max:480', // 5 minutes to 8 hours
            'buffer_time' => 'sometimes|integer|min:0|max:120', // 0 to 2 hours
            'advance_booking_days' => 'sometimes|integer|min:1|max:365', // 1 day to 1 year
            'max_bookings_per_day' => 'sometimes|integer|min:1|max:50',
            'is_online_booking_enabled' => 'sometimes|boolean',
            'auto_confirm_appointments' => 'sometimes|boolean',

            // Appointment modes
            'appointment_modes' => 'sometimes|array',
            'appointment_modes.online_mode' => 'sometimes|boolean',
            'appointment_modes.office_visit_mode' => 'sometimes|boolean',
            'appointment_modes.home_visit_mode' => 'sometimes|boolean',
            'appointment_modes.online_mode_url' => 'sometimes|nullable|string|max:255',
            'appointment_modes.office_address' => 'sometimes|nullable|string|max:500',
            'appointment_modes.home_visit_radius' => 'sometimes|nullable|integer|min:1|max:100',
            'appointment_modes.home_visit_fee' => 'sometimes|nullable|numeric|min:0',

            // Payment methods
            'payment_methods' => 'sometimes|array',
            'payment_methods.phonepe' => 'sometimes|boolean',
            'payment_methods.cash' => 'sometimes|boolean',
            'payment_methods.card' => 'sometimes|boolean',
            'payment_methods.upi' => 'sometimes|boolean',
        ];
    }

    /**
     * Handle a failed validation attempt and return a JSON response.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->error($validator->errors(), $validator->errors()->first(), 422));
    }
}
