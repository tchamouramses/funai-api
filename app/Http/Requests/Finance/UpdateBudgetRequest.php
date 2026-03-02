<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'amount' => 'nullable|numeric|min:0.01',
            'currency' => 'nullable|string|max:5',
            'category' => 'nullable|string|max:100',
            'period' => 'nullable|string|in:weekly,monthly,quarterly,yearly,custom',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'alert_thresholds' => 'nullable|array',
            'alert_thresholds.*' => 'integer|min:1|max:100',
            'is_recurring' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ];
    }
}
