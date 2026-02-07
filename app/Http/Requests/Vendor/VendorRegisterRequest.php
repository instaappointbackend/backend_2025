<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class VendorRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {

        return [
            'mobile' => [
                'required',
                'regex:/^[6-9][0-9]{9}$/',
                // 'unique:users,mobile',
            ],

            'name' => 'required|string|max:255',

            'email' => 'required|email',

            'gender' => 'required|in:male,female,other',

            'dob' => 'required|date|before:today',

            'business_category_id' => 'required|exists:business_categories,id',

            'full_address' => 'required|string',

            'city' => 'required|string|max:100',

            'state' => 'required|string|max:100',

            'country' => 'required|string|max:100',

            'postal_code' => [
                'required',
                'regex:/^[1-9][0-9]{5}$/',
            ],

            'experience' => 'required|in:beginner,intermediate,experienced,advanced,expert,master',

            'reference_code' => 'nullable|string|max:50',

            'profile_picture' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',

            'role' => 'required|in:vendor',
        ];
    }

    public function messages(): array
    {
        return [
            'mobile.regex' => 'Mobile number must be a valid Indian number.',
            'postal_code.regex' => 'Enter a valid 6-digit Indian PIN code.',
            'dob.before' => 'Date of birth must be before today.',
            'business_category_id.exists' => 'Selected business category is invalid.',
        ];
    }
}
