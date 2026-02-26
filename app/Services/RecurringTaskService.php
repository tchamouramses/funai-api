<?php

namespace App\Services;

use App\Models\ListItem;
use App\Models\ListModel;
use App\Models\ProgressEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RecurringTaskService
{
    public function isRecurring(ListItem $item, ?ListModel $list = null): bool
    {
        $list = $list ?? $item->list;
        $metadata = (array) ($item->metadata ?? []);

        return (bool) ($list?->is_recurring)
            || (bool) ($metadata['is_recurring'] ?? false)
            || ! empty($metadata['recurrence_pattern'])
            || $item->task_day !== null;
    }

    public function ensureSeriesId(ListItem $item): string
    {
        if (! $item->series_id) {
            $item->series_id = (string) $item->id;
            $item->save();
        }

        return (string) $item->series_id;
    }

    public function recordRecurrenceStatus(ListItem $item, string $status): void
    {
        $seriesId = $this->ensureSeriesId($item);
        $scheduledDate = $item->due_date
            ? Carbon::parse($item->due_date)->startOfDay()
            : Carbon::now()->startOfDay();

        if ($status === 'done') {
            ProgressEntry::where('series_id', $seriesId)
                ->where('date', $scheduledDate->toDateString())
                ->where('status', 'missed')
                ->delete();
        }

        ProgressEntry::where('series_id', $seriesId)
            ->where('date', $scheduledDate->toDateString())
            ->where('status', $status)
            ->firstOr(function () use ($item, $seriesId, $scheduledDate, $status) {
                ProgressEntry::create([
                    'item_id' => (string) $item->id,
                    'series_id' => $seriesId,
                    'date' => $scheduledDate,
                    'value' => $status === 'done',
                    'status' => $status,
                    'notes' => $status === 'done' ? 'recurrence_done' : 'recurrence_missed',
                ]);
            });
    }

    public function cloneNextOccurrenceIfNeeded(ListItem $item, ?ListModel $list = null): ?ListItem
    {
        $list = $list ?? $item->list;

        if (! $this->isRecurring($item, $list) || ! $item->due_date) {
            Log::error("", [$item, $list]);
            return null;
        }

        $seriesId = $this->ensureSeriesId($item);
        $currentDueDate = Carbon::parse($item->due_date);
        $nextDueDate = $this->calculateNextDueDate($currentDueDate, $item, $list);

        $nextStart = $nextDueDate->copy()->startOfDay();
        $nextEnd = $nextDueDate->copy()->endOfDay();

        $existing = ListItem::where('series_id', $seriesId)
            ->where('completed', false)
            ->where('due_date', '>=', $nextStart)
            ->where('due_date', '<=', $nextEnd)
            ->first();

        if ($existing) {
            return $existing;
        }

        return ListItem::create([
            'list_id' => (string) $item->list_id,
            'content' => $item->content,
            'completed' => false,
            'order' => $item->order,
            'notification_time' => $item->notification_time,
            'notification_id' => null,
            'metadata' => $item->metadata,
            'task_day' => $item->task_day,
            'due_date' => $nextDueDate,
            'series_id' => $seriesId,
            'source_item_id' => (string) $item->id,
            'reminder_notified_at' => null,
            'expired_notified_at' => null,
            'missed_at' => null,
            'missed_processed_at' => null,
        ]);
    }

    public function calculateNextDueDate(Carbon $fromDueDate, ListItem $item, ?ListModel $list = null): Carbon
    {
        $pattern = $this->resolvePattern($item, $list ?? $item->list);
        $frequency = $pattern['frequency'] ?? 'daily';
        $interval = max(1, (int) ($pattern['interval'] ?? 1));

        $next = $fromDueDate->copy();

        return match ($frequency) {
            'weekly' => $next->addWeeks($interval),
            'monthly' => $next->addMonths($interval),
            default => $next->addDays($interval),
        };
    }

    private function resolvePattern(ListItem $item, ?ListModel $list = null): array
    {
        $metadata = (array) ($item->metadata ?? []);
        $itemPattern = (array) ($metadata['recurrence_pattern'] ?? []);
        $listPattern = (array) ($list?->recurrence_pattern ?? []);

        $pattern = ! empty($itemPattern) ? $itemPattern : $listPattern;

        if (empty($pattern)) {
            $pattern = [
                'frequency' => 'daily',
                'interval' => 1,
            ];
        }

        return $pattern;
    }
}
