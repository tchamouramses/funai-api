<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'type' => 'nullable|string|in:general,professionnel,educatif,sportif,sante,creatif,voyage,cuisine,finance,chat_assistant',
            'sub_type' => 'nullable|string|max:100',
        ];
    }
}
