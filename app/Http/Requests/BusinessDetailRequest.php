<?php

namespace App\Http\Requests;

use App\Traits\ApiResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BusinessDetailRequest extends FormRequest
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
            'business_name' => 'required|string|max:255',
            'industry' => 'required|string|max:100',
            'email' => 'required|email|unique:business_details,email,'.$this->id,
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url',
            'address' => 'nullable|string',
            'logo' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:5120',
        ];

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
