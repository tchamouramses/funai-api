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
            'type' => 'required|string|in:general,professionnel,educatif,sportif,sante,creatif,voyage,cuisine,finance',
            'list_category' => 'required|string|in:tasks,projects,study,workout,wellness,creative,travel,meal,budget',
            'description' => 'nullable|string|max:1000',
            'metadata' => 'nullable|array',
            'parent_list_id' => 'nullable|string',
            'due_date' => 'nullable|date',
            'notification_time' => 'nullable|integer|min:0',
        ];
    }
}
