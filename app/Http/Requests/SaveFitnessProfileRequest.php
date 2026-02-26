<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveFitnessProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'profile' => 'required|array',
            'profile.goal' => 'required|string',
            'profile.customGoal' => 'nullable|string',
            'profile.height' => 'required|integer|min:100|max:250',
            'profile.weight' => 'required|integer|min:30|max:300',
            'profile.sex' => 'required|string|in:male,female,not_specified',
            'profile.age' => 'required|integer|min:12|max:100',
            'profile.level' => 'required|string|in:beginner,intermediate,advanced',
        ];
    }
}
