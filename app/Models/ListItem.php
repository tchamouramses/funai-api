<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ListItem extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'list_items';

    protected $fillable = [
        'list_id',
        'content',
        'completed',
        'order',
        'notification_time',
        'notification_id',
        'metadata',
        'task_day',
        'due_date',
        'series_id',
        'source_item_id',
        'reminder_notified_at',
        'expired_notified_at',
        'missed_at',
        'missed_processed_at',
        'is_recurring',
        'recurrence_pattern',
        'recurrence_start_date',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'due_date' => 'datetime',
        'recurrence_start_date' => 'datetime',
        'metadata' => 'array',
        'recurrence_pattern' => 'array',
        'completed' => 'boolean',
        'is_recurring' => 'boolean',
        'series_id' => 'string',
        'source_item_id' => 'string',
        'reminder_notified_at' => 'datetime',
        'expired_notified_at' => 'datetime',
        'missed_at' => 'datetime',
        'missed_processed_at' => 'datetime',
    ];

    public function list()
    {
        return $this->belongsTo(ListModel::class, 'list_id', '_id');
    }

    public function progressEntries()
    {
        return $this->hasMany(ProgressEntry::class, 'item_id', '_id');
    }
}
