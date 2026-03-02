<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Transaction extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'transactions';

    protected $fillable = [
        'user_id',
        'list_id',
        'type',
        'amount',
        'currency',
        'category',
        'source',
        'payment_method',
        'status',
        'is_recurring',
        'recurrence_pattern',
        'date',
        'collection_date',
        'notes',
        'attachments',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'date' => 'datetime',
        'collection_date' => 'datetime',
        'amount' => 'float',
        'is_recurring' => 'boolean',
        'recurrence_pattern' => 'array',
        'attachments' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(Profile::class, 'user_id', '_id');
    }

    public function list()
    {
        return $this->belongsTo(ListModel::class, 'list_id', '_id');
    }

    public function fileAttachments()
    {
        return $this->hasMany(FileAttachment::class, 'entity_id', '_id');
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForList($query, string $listId)
    {
        return $query->where('list_id', $listId);
    }

    public function scopeIncomes($query)
    {
        return $query->where('type', 'income');
    }

    public function scopeExpenses($query)
    {
        return $query->where('type', 'expense');
    }

    public function scopeDateBetween($query, $start, $end)
    {
        return $query->where('date', '>=', $start)->where('date', '<=', $end);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
