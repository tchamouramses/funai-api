<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'metadata' => 'nullable|array',
            'due_date' => 'nullable|date',
            'notification_time' => 'nullable|integer|min:0',
            'is_recurring' => 'nullable|boolean',
            'recurrence_pattern' => 'nullable|array',
        ];
    }
}
