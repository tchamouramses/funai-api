<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class SharedAssistant extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'shared_assistants';

    protected $fillable = [
        'type',
        'sub_type',
        'assistant_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
