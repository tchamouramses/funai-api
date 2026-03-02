<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Budget extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'budgets';

    protected $fillable = [
        'user_id',
        'list_id',
        'name',
        'amount',
        'spent',
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
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'amount' => 'float',
        'spent' => 'float',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
        'alert_thresholds' => 'array',
        'alerts_sent' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(Profile::class, 'user_id', '_id');
    }

    public function list()
    {
        return $this->belongsTo(ListModel::class, 'list_id', '_id');
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

        return $query->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now);
    }

    /**
     * Get the usage percentage of the budget.
     */
    public function getUsagePercentageAttribute(): float
    {
        if ($this->amount <= 0) {
            return 0;
        }

        return round(($this->spent / $this->amount) * 100, 2);
    }

    /**
     * Get the remaining amount of the budget.
     */
    public function getRemainingAttribute(): float
    {
        return max(0, $this->amount - $this->spent);
    }

    /**
     * Check if any alert threshold has been reached but not yet notified.
     */
    public function getUnsentAlertsAttribute(): array
    {
        $thresholds = $this->alert_thresholds ?? [70, 90, 100];
        $sent = $this->alerts_sent ?? [];
        $currentPercentage = $this->usage_percentage;

        return array_filter($thresholds, function ($threshold) use ($sent, $currentPercentage) {
            return $currentPercentage >= $threshold && ! in_array($threshold, $sent);
        });
    }
}
