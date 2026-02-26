<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|string|in:streak,volume_double,perfect_week,thirty_days,weight_increase,early_bird',
            'title' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'target' => 'required|integer|min:1',
        ];
    }
}
