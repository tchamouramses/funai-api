<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:todo,fitness,nutrition,finance',
            'description' => 'nullable|string|max:1000',
            'metadata' => 'nullable|array',
            'parent_list_id' => 'nullable|string',
            'due_date' => 'nullable|date',
            'notification_time' => 'nullable|integer|min:0',
        ];
    }
}
