<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ComboServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'service_ids' => 'required|array|min:2',
            'service_ids.*' => 'required|integer|exists:services,id',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'service_ids.min' => 'A combo must include at least 2 services.',
            'service_ids.*.exists' => 'One or more selected services do not exist.',
            'discount_percentage.min' => 'Discount percentage must be at least 0%.',
            'discount_percentage.max' => 'Discount percentage cannot exceed 100%.',
        ];
    }
}
