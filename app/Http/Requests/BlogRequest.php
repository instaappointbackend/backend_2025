<?php

namespace App\Http\Requests;

use App\Traits\ApiResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BlogRequest extends FormRequest
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
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
            'status'  => 'required|in:published,draft',
            'attachment' => 'sometimes|file|max:102400', // Max 20MB, either image or video
        ];

        // Custom validation: Allow only one attachment (image or video)
        if ($this->hasFile('attachment')) {
            $file = $this->file('attachment');
            $mime = $file->getMimeType();

            if (str_starts_with($mime, 'image/')) {
                $rules['attachment'] .= '|mimes:jpeg,png,jpg,gif|max:5120'; // Image max 2MB
            } elseif (str_starts_with($mime, 'video/')) {
                $rules['attachment'] .= '|mimes:mp4,mov,avi,wmv|max:102400'; // Video max 100MB
            } else {
                $rules['attachment'] .= '|mimes:jpeg,png,jpg,gif,mp4,mov,avi,wmv';
            }
        }

        // If updating, make fields optional
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            foreach ($rules as $key => $rule) {
                $rules[$key] = str_replace('required|', 'sometimes|required|', $rule);
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
