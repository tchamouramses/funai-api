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
        'reminder_notified_at',
        'expired_notified_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'due_date' => 'datetime',
        'metadata' => 'array',
        'completed' => 'boolean',
        'reminder_notified_at' => 'datetime',
        'expired_notified_at' => 'datetime',
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
