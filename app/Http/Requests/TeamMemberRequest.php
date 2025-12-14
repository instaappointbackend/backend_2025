<?php

namespace App\Http\Requests;

use App\Traits\ApiResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;


class TeamMemberRequest extends FormRequest
{
    use ApiResponseTrait;

    /**
     * Determine if the user is authorized to make this request.
     * For simplicity, we return true. Adjust if needed.
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
        // For store (POST) requests, all fields are required.
        if ($this->isMethod('post')) {
            return [
                'name'            => 'required|string|max:255',
                'email'           => 'required|email|unique:users,email',
                'mobile'          => 'required|string|digits:10|unique:users,mobile',
                'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',

                'reference_code'  => 'nullable|string|max:255',
                'gender'          => 'required|in:male,female,other',
                'dob'             => 'required|date',
                // Address Details
                'address' => 'required|string|max:500',
                'full_address' => 'nullable|string|max:500',
                'street' => 'nullable|string|max:255',
                'city' => 'required|string|max:255',
                'state' => 'required|string|max:255',
                'country' => 'required|string|max:255',
                'postal_code' => 'required|string|max:10',
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',


            ];
        }
        // For update (PUT/PATCH) requests, fields are optional but must be valid if provided.
        if ($this->isMethod('put') || $this->isMethod('patch')) {

            $teamMemberId = $this->route('team_member');

            return [
                'name'   => 'sometimes|required|string|max:255',

                'mobile' => [
                    'sometimes',
                    'required',
                    'string',
                    'digits:10',
                    Rule::unique('users', 'mobile')->ignore($teamMemberId),
                ],

                'email' => [
                    'sometimes',
                    'required',
                    'email',
                    Rule::unique('users', 'email')->ignore($teamMemberId),
                ],

                'gender' => 'sometimes|required|in:male,female,other',
                'dob' => 'sometimes|required|date',

                // Address fields
                'address' => 'sometimes|required|string|max:500',
                'full_address' => 'nullable|string|max:500',
                'street' => 'nullable|string|max:255',
                'city' => 'sometimes|required|string|max:255',
                'state' => 'sometimes|required|string|max:255',
                'country' => 'sometimes|required|string|max:255',
                'postal_code' => 'sometimes|required|string|max:10',
                'latitude' => 'sometimes|required|numeric',
                'longitude' => 'sometimes|required|numeric',
            ];
        }


        return [];
    }


    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->error($validator->errors(), $validator->errors()->first(), 422));
    }
}
