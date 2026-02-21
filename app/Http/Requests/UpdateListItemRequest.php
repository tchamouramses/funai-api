<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateListItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => 'nullable|string|min:1|max:500',
            'completed' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
            'task_day' => 'nullable|integer|min:0',
            'due_date' => 'nullable|date',
            'notification_time' => 'nullable|integer|min:0',
            'metadata' => 'nullable|array',
        ];
    }
}
