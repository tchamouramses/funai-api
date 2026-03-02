<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class FileAttachment extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'file_attachments';

    protected $fillable = [
        'user_id',
        'entity_type',
        'entity_id',
        'filename',
        'mime_type',
        'size',
        'data',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'size' => 'integer',
    ];

    /**
     * Hidden from serialization by default to avoid huge payloads.
     */
    protected $hidden = [
        'data',
    ];

    public function user()
    {
        return $this->belongsTo(Profile::class, 'user_id', '_id');
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForEntity($query, string $entityType, string $entityId)
    {
        return $query->where('entity_type', $entityType)
            ->where('entity_id', $entityId);
    }
}
