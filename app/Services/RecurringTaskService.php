<?php

namespace App\Services;

use App\Models\ListItem;
use App\Models\ListModel;
use App\Models\ProgressEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RecurringTaskService
{
    public function isRecurring(ListItem $item): bool
    {
        $nextDueDate = $this->calculateNextDueDateForSchedule($item);
        return $nextDueDate instanceof Carbon;
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

    public function cloneNextOccurrenceIfPossible(ListItem $item): ?ListItem
    {
        $seriesId = $this->ensureSeriesId($item);
        $nextDueDate = $this->calculateNextDueDateForSchedule($item);
        if (! isset($nextDueDate)) {
            return null;
        }

        $existing = ListItem::where('series_id', $item->series_id)
            ->where('completed', false)
            ->where('due_date', '>=', $nextDueDate)
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
            'due_date' => $nextDueDate,
            'series_id' => $seriesId,
            'source_item_id' => (string) $item->id,
            'reminder_notified_at' => null,
            'expired_notified_at' => null,
            'missed_at' => null,
            'missed_processed_at' => null,
        ]);
    }

    public function calculateNextDueDateForSchedule(ListItem $item): ?Carbon
    {
        if (empty((array) ($item->metadata['schedule'] ?? [])) || ! isset($item->due_date)) {
            return null;
        }

        $schedule = (array) $item->metadata['schedule'];
        $startDate = Carbon::parse($schedule['startDate'] ?? null);
        $currentDueDate = Carbon::parse($item->due_date);
        $daysOfWeek = (array) ($schedule['daysOfWeek'] ?? []);
        if(isset($schedule['weeksCount'])){
            $seriesId = $this->ensureSeriesId($item);
            $occurrenceCount = ListItem::where('series_id', $seriesId)->count();
            if($occurrenceCount >= (int) $schedule['weeksCount'] * count($daysOfWeek)){
                return null;
            }
        }

        $currentDayOfWeek = $currentDueDate->dayOfWeek;

        $currendDayOfWeekIndex = array_search($currentDayOfWeek, $daysOfWeek, true);

        if($currendDayOfWeekIndex === false) {
            return null;
        }

        $nexDayIndex = ($currendDayOfWeekIndex + 1) % count($daysOfWeek);
        $nextDayOfWeek = (int) $daysOfWeek[$nexDayIndex];
        $nextDueDate = $currentDueDate->copy()->addDays(($nextDayOfWeek - $currentDayOfWeek + 7) % 7);

        if(isset($schedule['endDate']) && (Carbon::parse($schedule['endDate'])->isPast() || $nextDueDate->greaterThan(Carbon::parse($schedule['endDate'])))){
            return null;
        }

        $nextDueDate->setHour($startDate->hour);
        $nextDueDate->setMinute($startDate->minute);

        return $nextDueDate;
    }

}
