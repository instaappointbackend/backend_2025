<?php

namespace App\Http\Requests;

use App\Traits\ApiResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class AuthRequest extends FormRequest
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
     * Get the validation rules that apply to the request based on the controller action.
     */
    public function rules(): array
    {
        // Get the name of the controller method handling the request.
        $action = $this->route()->getActionMethod();

        switch ($action) {
            case 'sendOtp':
                return [
                    'mobile' => 'required|string|digits:10',
                ];

            case 'verifyOtp':
                return [
                    'mobile' => 'required|string|digits:10',
                    'otp' => 'required|string|digits:6',
                ];

            case 'refreshToken':
                return [
                    'refresh_token' => 'required|string',
                ];

            case 'register':
                return [
                    'mobile' => 'required|string|min:10|max:15',
                    'name' => 'required|string|max:255',
                    'email' => 'required|email|max:255',
                    'role' => 'required|in:vendor,customer',
                    'gender' => 'required|in:male,female,other',
                    'dob' => 'required|date',
                    'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
                    'reference_code' => 'nullable|string|exists:users,referral_code',

                    // New fields for vendor-specific information
                    'business_category_id' => [
                        Rule::requiredIf(function () {
                            return request()->role === 'vendor';
                        }),
                        'nullable',
                        'exists:business_categories,id',
                    ],
                    'experience' => [
                        Rule::requiredIf(function () {
                            return request()->role === 'vendor';
                        }),
                        'nullable',
                        'string',
                        'in:beginner,intermediate,experienced,advanced,expert,master',
                    ],

                    // Terms and conditions acceptance
                    'terms_accepted' => 'required|boolean|accepted',

                    // Address fields
                    'address' => 'required|string',
                    'full_address' => 'nullable|string',
                    'street' => 'nullable|string',
                    'city' => 'nullable|string',
                    'state' => 'nullable|string',
                    'country' => 'nullable|string',
                    'postal_code' => 'nullable|string',
                    'latitude' => 'nullable|string',
                    'longitude' => 'nullable|string',
                ];

            default:
                return [];
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
