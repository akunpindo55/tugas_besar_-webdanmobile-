<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => 'required|string|max:5000',
            'visibility' => 'required|in:public,private',
            'media' => 'nullable|array|max:5', // limit to 5 media files
            'media.*' => 'file|mimes:jpeg,png,jpg,gif,mp4,mov|max:10240', // 10MB limit per file
        ];
    }
}
