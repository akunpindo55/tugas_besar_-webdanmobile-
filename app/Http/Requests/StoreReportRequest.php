<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reportable_type' => 'required|string|max:50',
            'reportable_id' => 'required|integer',
            'reason' => 'required|string|max:1000',
        ];
    }
}
