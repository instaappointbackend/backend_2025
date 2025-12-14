<?php

namespace App\Http\Requests;

use App\Traits\ApiResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class WorkingHoursRequest extends FormRequest
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
        if ($this->isMethod('post')) {
            return [
                'days' => 'required|array',
                'days.*.day_of_week' => 'required|integer|min:0|max:6',
                'days.*.is_working_day' => 'required|boolean',
                'days.*.start_time' => 'required_if:days.*.is_working_day,true|nullable|date_format:H:i',
                'days.*.end_time' => 'required_if:days.*.is_working_day,true|nullable|date_format:H:i|after:days.*.start_time',
                'days.*.break_start' => 'nullable|date_format:H:i',
                'days.*.break_end' => 'nullable|date_format:H:i|after:days.*.break_start',
            ];
        } else {
            return [
                'is_working_day' => 'required|boolean',
                'start_time' => 'required_if:is_working_day,true|nullable|date_format:H:i',
                'end_time' => 'required_if:is_working_day,true|nullable|date_format:H:i|after:start_time',
                'break_start' => 'nullable|date_format:H:i',
                'break_end' => 'nullable|date_format:H:i|after:break_start',
            ];
        }
    }

    /**
     * Handle a failed validation attempt and return a JSON response.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->error($validator->errors(), $validator->errors()->first(), 422));
    }
}
