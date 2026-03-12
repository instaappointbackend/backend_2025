<?php

namespace App\Http\Requests;

use App\Traits\ApiResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProfileRequest extends FormRequest
{
    use ApiResponseTrait;

    /**
     * Determine if the user is authorized to make this request.
     * Here, we allow all authenticated users.
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
        // Using $this->user() to fetch the currently authenticated user
        $userId = $this->user() ? $this->user()->id : null;

        return [
            'name' => 'sometimes|required|string|max:255',
            // 'email'           => 'sometimes|email|unique:users,email,' . $userId,
            'email' => 'sometimes|email',
            'profile_picture' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:5120',
            'gender' => 'sometimes|required|in:male,female,other',
            'dob' => 'sometimes|required|date',
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
