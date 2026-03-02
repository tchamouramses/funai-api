<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class StoreBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'list_id' => 'required|string',
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|max:5',
            'category' => 'nullable|string|max:100',
            'period' => 'required|string|in:weekly,monthly,quarterly,yearly,custom',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'alert_thresholds' => 'nullable|array',
            'alert_thresholds.*' => 'integer|min:1|max:100',
            'is_recurring' => 'nullable|boolean',
        ];
    }
}
