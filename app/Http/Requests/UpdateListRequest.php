<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'metadata' => 'nullable|array',
            'due_date' => 'nullable|date',
            'notification_time' => 'nullable|integer|min:0',
            'is_recurring' => 'nullable|boolean',
            'recurrence_start_date' => 'nullable|date',
            'recurrence_pattern' => 'nullable|array',
            'recurrence_pattern.frequency' => 'required_with:recurrence_pattern|in:daily,weekly,monthly',
            'recurrence_pattern.interval' => 'nullable|integer|min:1',
            'recurrence_pattern.daysOfWeek' => 'nullable|array',
            'recurrence_pattern.daysOfWeek.*' => 'integer|min:0|max:6',
            'recurrence_pattern.endType' => 'nullable|in:never,date,count',
            'recurrence_pattern.endDate' => 'nullable|date',
            'recurrence_pattern.count' => 'nullable|integer|min:1',
            'recurrence_pattern.executionHour' => 'nullable|integer|min:0|max:23',
            'recurrence_pattern.executionMinute' => 'nullable|integer|min:0|max:59',
        ];
    }
}
