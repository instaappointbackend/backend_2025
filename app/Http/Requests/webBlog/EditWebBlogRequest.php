<?php

namespace App\Http\Requests\webBlog;

use Illuminate\Foundation\Http\FormRequest;

class EditWebBlogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'user_id' => 'required|exists:users,id',
            'sub_title' => 'required|string|max:255',
            'status' => 'required|string|in:draft,published',
            'description' => 'required|string',
            'category_id' => 'required|exists:blog_categories,id',
            'banner-images' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // ,svg,webp
            'style' => 'nullable',
        ];
    }
}
