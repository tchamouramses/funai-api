<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LogProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'value' => 'required',
            'notes' => 'nullable|string|max:500',
        ];
    }
}
