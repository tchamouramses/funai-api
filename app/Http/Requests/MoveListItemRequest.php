<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveListItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'list_id' => 'required|string',
        ];
    }
}
