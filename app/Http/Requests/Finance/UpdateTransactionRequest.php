<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'nullable|string|in:income,expense',
            'amount' => 'nullable|numeric|min:0.01',
            'currency' => 'nullable|string|max:5',
            'category' => 'nullable|string|max:100',
            'source' => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|in:cash,card,bank_transfer,mobile_money,check,other',
            'status' => 'nullable|string|in:planned,received,paid,cancelled',
            'is_recurring' => 'nullable|boolean',
            'recurrence_pattern' => 'nullable|array',
            'recurrence_pattern.frequency' => 'required_with:recurrence_pattern|in:daily,weekly,monthly,yearly',
            'recurrence_pattern.interval' => 'nullable|integer|min:1',
            'recurrence_pattern.endType' => 'nullable|in:never,date,count',
            'recurrence_pattern.endDate' => 'nullable|date',
            'recurrence_pattern.count' => 'nullable|integer|min:1',
            'date' => 'nullable|date',
            'collection_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'attachments' => 'nullable|array',
            'attachments.*.filename' => 'required_with:attachments|string|max:255',
            'attachments.*.mime_type' => 'required_with:attachments|string|max:100',
            'attachments.*.data' => 'required_with:attachments|string',
            'attachments.*.description' => 'nullable|string|max:255',
        ];
    }
}
