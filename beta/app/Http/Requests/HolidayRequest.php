<?php

namespace App\Http\Requests;

use App\Traits\ApiResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class HolidayRequest extends FormRequest
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
            'date' => 'required|date|date_format:Y-m-d',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_recurring' => 'sometimes|boolean',
        ];

        // If updating, make fields optional
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['date'] = 'sometimes|date|date_format:Y-m-d';
            $rules['name'] = 'sometimes|string|max:255';
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