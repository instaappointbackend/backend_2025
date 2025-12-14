<?php

namespace App\Http\Requests;

use App\Traits\ApiResponseTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SearchRequest extends FormRequest
{
    use ApiResponseTrait;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Allow all authenticated users to search
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        // Get the name of the controller method handling the request
        $action = $this->route()->getActionMethod();

        switch ($action) {
            case 'search':
                return [
                    'query' => 'required|string|min:1',
                    'latitude' => 'required|numeric',
                    'longitude' => 'required|numeric',
                    'type' => 'sometimes|array',
                    'type.*' => 'in:service,provider,business',
                    'rating' => 'sometimes|numeric|min:0|max:5',
                    'distance' => 'sometimes|numeric|min:1',
                    'business_type' => 'sometimes|exists:business_categories,id',
                    'sort' => 'sometimes|in:distance,rating,price',
                    'page' => 'sometimes|integer|min:1',
                    'limit' => 'sometimes|integer|min:1|max:50',
                ];

            case 'getSuggestions':
                return [
                    'query' => 'required|string|min:1',
                    'latitude' => 'required|numeric',
                    'longitude' => 'required|numeric',
                ];

            case 'saveSearch':
                return [
                    'query' => 'required|string|min:1',
                ];

            default:
                return [];
        }
    }

    /**
     * Handle a failed validation attempt and return a JSON response.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->error($validator->errors(), $validator->errors()->first(), 422));
    }
}
