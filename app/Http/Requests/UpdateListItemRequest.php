<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateListItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => 'nullable|string|min:1|max:500',
            'completed' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
            'due_date' => 'nullable|date',
            'notification_time' => 'nullable|integer|min:0',
            'metadata' => 'nullable|array',
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
