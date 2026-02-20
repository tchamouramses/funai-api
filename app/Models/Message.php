<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Message extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'messages';

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'is_hidden',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'is_hidden' => 'boolean',
    ];

    protected $dates = ['created_at'];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'conversation_id', '_id');
    }
}
