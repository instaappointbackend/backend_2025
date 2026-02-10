<?php

namespace App\Http\Requests;

use App\Traits\ApiResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ReviewRequest extends FormRequest
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
            'appointment_id' => 'required|exists:appointments,id',
            'rating' => 'required|numeric|min:1|max:5',
            'review_text' => 'nullable|string|max:1000',
            'is_anonymous' => 'nullable|boolean',
        ];

        // If updating, the appointment_id is not required
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['appointment_id'] = 'nullable|exists:appointments,id';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'rating.required' => 'Please provide a rating.',
            'rating.numeric' => 'Rating must be a number.',
            'rating.min' => 'Rating must be at least 1.',
            'rating.max' => 'Rating cannot be more than 5.',
            'review_text.max' => 'Review text cannot exceed 1000 characters.',
            'appointment_id.required' => 'Appointment ID is required.',
            'appointment_id.exists' => 'The selected appointment is invalid.',
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
