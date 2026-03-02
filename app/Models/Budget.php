<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Support\Carbon;

class Budget extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'budgets';

    protected $fillable = [
        'user_id',
        'list_id',
        'name',
        'amount',
        'currency',
        'category',
        'period',
        'start_date',
        'end_date',
        'alert_thresholds',
        'alerts_sent',
        'is_recurring',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'alert_thresholds' => 'array',
        'alerts_sent' => 'array',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'remaining',
        'percentage',
        'spent',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getRemainingAttribute()
    {
        return $this->amount - ($this->spent ?? 0);
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForList($query, string $listId)
    {
        return $query->where('list_id', $listId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrent($query)
    {
        $now = now();
        return $query->where('start_date', '<=', $now)->where('end_date', '>=', $now);
    }

    public function getSpentAttribute()
    {
        return Transaction::where('list_id', $this->list_id)
            ->expenses()
            ->whereBetween('date', [Carbon::parse($this->start_date), Carbon::parse($this->end_date)])
            ->when($this->category && $this->category !== 'global', function ($q) {
                $q->where('category', $this->category);
            })
            ->sum('amount');
    }

    public function getPercentageAttribute()
    {
        if (!$this->amount || $this->amount == 0) {
            return 0;
        }

        return round(($this->spent ?? 0) / $this->amount * 100, 2);
    }
}
