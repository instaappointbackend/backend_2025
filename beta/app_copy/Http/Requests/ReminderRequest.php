<?php

namespace App\Http\Requests;

use App\Traits\ApiResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ReminderRequest extends FormRequest
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
            'description' => 'required|string',
            'reminder_date' => 'required|date|date_format:Y-m-d',
            'reminder_time' => 'required|date_format:H:i',
            'status' => 'required|in:pending,completed,cancelled',
            'type' => 'required|in:one-time,recurring',
            'priority' => 'required|in:low,medium,high',
            'target_id' => 'nullable|integer',
            'target_type' => 'nullable|in:customer,appointment',
        ];

        // Add conditional validation for recurring reminders
        if ($this->type === 'recurring') {
            $rules['recurrence_pattern'] = 'required|in:daily,weekly,monthly';
            $rules['recurrence_end_date'] = 'nullable|date|date_format:Y-m-d|after_or_equal:reminder_date';
        }

        // If updating, make fields optional
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            foreach ($rules as $key => $rule) {
                $rules[$key] = str_replace('required|', 'sometimes|', $rule);
            }
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
