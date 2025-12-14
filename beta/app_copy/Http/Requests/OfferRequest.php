<?php

namespace App\Http\Requests;

use App\Traits\ApiResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\Offer;

class OfferRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'start_date' => 'required|date|date_format:Y-m-d',
            'end_date' => 'required|date|date_format:Y-m-d|after_or_equal:start_date',
            'is_active' => 'sometimes|boolean',
            'offer_type' => 'required|in:' . Offer::TYPE_ADMIN . ',' . Offer::TYPE_PROVIDER,
        ];

        // Add coupon code validation for admin offers only
        if ($this->input('offer_type') === Offer::TYPE_ADMIN) {
            $rules['coupon_code'] = 'required|string|unique:offers,coupon_code' . 
                ($this->isMethod('PUT') ? ',' . $this->route('id') : '');
            $rules['usage_limit'] = 'nullable|integer|min:1';
        }

        // Add service_id validation for provider offers
        if ($this->input('offer_type') === Offer::TYPE_PROVIDER) {
            $rules['service_id'] = 'nullable|exists:services,id';
        }

        return $rules;
    }

    /**
     * Handle a failed validation attempt and return a JSON response.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->error($validator->errors(), $validator->errors()->first(), 422));
    }
}