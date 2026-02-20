<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ProgressEntry extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'progress_entries';

    protected $fillable = [
        'item_id',
        'date',
        'value',
        'notes',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'date' => 'date',
    ];

    public $timestamps = false;

    public function item()
    {
        return $this->belongsTo(ListItem::class, 'item_id', '_id');
    }
}
