<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message_type' => 'required|in:text,image,file,voice,system',
            'body' => 'required_without:file|nullable|string',
            'file' => 'nullable|file|max:10240',
            'reply_to' => 'nullable|integer|exists:messages,id',
        ];
    }
}
