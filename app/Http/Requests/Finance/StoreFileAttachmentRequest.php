<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class StoreFileAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entity_type' => 'required|string|in:transaction,budget',
            'entity_id' => 'required|string',
            'filename' => 'required|string|max:255',
            'mime_type' => 'required|string|max:100',
            'data' => 'required|string',
            'description' => 'nullable|string|max:255',
        ];
    }
}
