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
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:general,professionnel,educatif,sportif,sante,creatif,voyage,cuisine,finance,chat_assistant',
            'sub_type' => 'nullable|string|max:100',
            'assistant_id' => 'nullable|string',
        ];
    }
}
