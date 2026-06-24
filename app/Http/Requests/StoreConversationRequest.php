<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:direct,group',
            'target_user_id' => 'required_if:type,direct|integer|exists:users,id',
            'name' => 'required_if:type,group|string|max:100',
            'description' => 'nullable|string|max:500',
            'avatar' => 'nullable|string|max:255',
        ];
    }
}
