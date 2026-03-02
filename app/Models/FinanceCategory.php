<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class FinanceCategory extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'finance_categories';

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'icon',
        'color',
        'is_default',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_default' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(Profile::class, 'user_id', '_id');
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
                ->orWhere('is_default', true);
        });
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeDefaults($query)
    {
        return $query->where('is_default', true);
    }
}
