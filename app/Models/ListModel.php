<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ListModel extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'lists';

    protected $fillable = [
        'user_id',
        'title',
        'type',
        'list_category',
        'description',
        'metadata',
        'pinned',
        'parent_list_id',
        'depth',
        'children_count',
        'item_count',
        'completed_count',
        'total_item_count',
        'total_completed_count',
        'due_date',
        'notification_time',
        'notification_id',
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
        'pinned' => 'boolean',
        'is_recurring' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(Profile::class, 'user_id', '_id');
    }

    public function items()
    {
        return $this->hasMany(ListItem::class, 'list_id', '_id');
    }

    public function parent()
    {
        return $this->belongsTo(ListModel::class, 'parent_list_id', '_id');
    }

    public function children()
    {
        return $this->hasMany(ListModel::class, 'parent_list_id', '_id');
    }

    public function updateCounters()
    {
        $this->item_count = $this->items()->count();
        $this->completed_count = $this->items()->where('completed', true)->count();
        $this->children_count = $this->children()->count();
        $this->save();
    }
}
