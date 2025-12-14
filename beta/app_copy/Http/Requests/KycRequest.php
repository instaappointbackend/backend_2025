<?php

namespace App\Http\Requests;

use App\Traits\ApiResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class KycRequest extends FormRequest
{
    use ApiResponseTrait;

    /**
     * Determine if the user is authorized to make this request.
     * For this example, we always return true.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules()
    {
        return [
            // Personal Identification
            'aadhar_number' => 'required|string|max:12',
            'aadhar_attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'pan_number' => 'required|string|max:10',
            'pan_attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',

            // Bank Details
            'bank_name' => 'nullable|string|max:255',
            'bank_account' => 'nullable|string|max:20',
            'ifsc_code' => 'nullable|string|max:11',
            'bank_attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',

            // Business Details
            'business_name' => 'required|string|max:255',

            'business_category_id' => 'required|numeric|exists:business_categories,id',
            'business_established_date' => 'required|date|before_or_equal:today', // Add validation for business established date

            'description' => 'nullable|string|max:1000',
            'business_logo' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'identity_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',

            // Address Details
            'address' => 'required|string|max:500',
            'full_address' => 'nullable|string|max:500',
            'street' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
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
