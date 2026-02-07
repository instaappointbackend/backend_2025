<?php

namespace App\Http\Requests;

use App\Traits\ApiResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AppSettingRequest extends FormRequest
{
    use ApiResponseTrait;

    public function authorize()
    {
        return true; // Ensure authentication is handled in middleware
    }

    public function rules()
    {
        return [
            'key' => 'required|string|max:255|unique:app_settings,key,'.$this->route('app_setting'),
            'value' => 'required|string',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        //        throw new HttpResponseException(response()->json([
        // //            'status'  => false,
        // //            'message' => $validator->errors()->first(),
        // //            'errors'  => $validator->errors(),
        // //        ], 422));

        throw new HttpResponseException($this->error($validator->errors(), $validator->errors()->first(), 422));
    }
}
