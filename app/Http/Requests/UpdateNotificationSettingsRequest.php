<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'enabled' => 'nullable|boolean',
            'default_reminder_delay' => 'nullable|integer|min:0|max:10080',
        ];
    }
}
