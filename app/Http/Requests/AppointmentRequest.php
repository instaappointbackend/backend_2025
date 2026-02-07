<?php

namespace App\Http\Requests;

use App\Traits\ApiResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AppointmentRequest extends FormRequest
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
        $rules = [
            'service_id' => 'required_without:combo_service_id|exists:services,id',
            'combo_service_id' => 'required_without:service_id|exists:combo_services,id',
            'date' => 'required|date|date_format:Y-m-d|after_or_equal:today',
            'time_slot_id' => 'required|exists:time_slots,id',
            'time_slots_to_block' => 'nullable|array',
            'time_slots_to_block.*' => 'exists:time_slots,id',
            'notes' => 'nullable|string|max:500',

            // Payment information
            'payment_status' => 'nullable|string|in:pending,paid,failed,refunded',
            'payment_id' => 'nullable|string',
            'payment_method' => 'nullable|string',
            'payment_amount' => 'nullable|numeric|min:0',
            'payment_details' => 'nullable|json',

            // Detailed payment breakdown fields
            'booking_price' => 'nullable|numeric|min:0',
            'platform_fees' => 'nullable|numeric|min:0',
            'other_charges' => 'nullable|numeric|min:0',
            'gst' => 'nullable|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0',
            'home_visit_fee' => 'nullable|numeric|min:0',
            'additional_services_fee' => 'nullable|numeric|min:0',
            'final_price' => 'nullable|numeric|min:0',

            // Discount and offer related fields
            'coupon_code' => 'nullable|string',
            'offer_title' => 'nullable|string',
            'vendor_offer_id' => 'nullable|numeric',
            'admin_offer_id' => 'nullable|numeric',
            'offer_type' => 'nullable|in:vendor,admin',

            // Visit type
            'visit_type' => 'nullable|string|in:online,office,home',
        ];

        return $rules;
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'service_id.required_without' => 'Either a service or a combo service must be selected.',
            'combo_service_id.required_without' => 'Either a service or a combo service must be selected.',
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
