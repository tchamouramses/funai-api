<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Conversation extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'conversations';

    protected $fillable = [
        'user_id',
        'title',
        'type',
        'sub_type',
        'assistant_id',
        'thread_id',
        'pinned',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'pinned' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(Profile::class, 'user_id', '_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'conversation_id', '_id');
    }
}
