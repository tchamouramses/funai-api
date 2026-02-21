<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ProgressEntry extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'progress_entries';

    protected $fillable = [
        'item_id',
        'series_id',
        'date',
        'value',
        'status',
        'notes',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'date' => 'datetime',
    ];

    public $timestamps = false;

    public function item()
    {
        return $this->belongsTo(ListItem::class, 'item_id', '_id');
    }
}
