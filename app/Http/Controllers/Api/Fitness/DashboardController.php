<?php

namespace App\Http\Controllers\Api\Fitness;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Models\ListItem;
use App\Models\ListModel;
use App\Models\ProgressEntry;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(string $listId): JsonResponse
    {
        $list = ListModel::find($listId);

        if (! $list) {
            throw new ResourceNotFoundException('List not found');
        }

        if ($list->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $subListIds = ListModel::where('parent_list_id', $list->_id)
            ->pluck('_id')
            ->toArray();

        $allItems = ListItem::whereIn('list_id', $subListIds)->get();

        $now = Carbon::now();
        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek = $now->copy()->endOfWeek();
        $startOfLastWeek = $startOfWeek->copy()->subWeek();
        $endOfLastWeek = $startOfWeek->copy()->subSecond();

        // --- Overview ---
        $totalExercises = $allItems->count();
        $completedExercises = $allItems->where('completed', true)->count();
        $completionRate = $totalExercises > 0
            ? round(($completedExercises / $totalExercises) * 100)
            : 0;

        // --- This week stats ---
        $thisWeekItems = $this->getItemsCompletedBetween($allItems, $startOfWeek, $endOfWeek);
        $thisWeekSessions = $this->countSessionsFromItems($thisWeekItems, $subListIds);
        $thisWeekExercises = $thisWeekItems->count();
        $thisWeekVolume = $this->calculateVolume($thisWeekItems);

        $metadata = $list->metadata ?? [];
        $plannedSessions = $metadata['sessionsPerWeek'] ?? count($subListIds);

        // --- Last week stats ---
        $lastWeekItems = $this->getItemsCompletedBetween($allItems, $startOfLastWeek, $endOfLastWeek);
        $lastWeekSessions = $this->countSessionsFromItems($lastWeekItems, $subListIds);
        $lastWeekExercises = $lastWeekItems->count();
        $lastWeekVolume = $this->calculateVolume($lastWeekItems);

        // --- Streak ---
        $streak = $this->calculateStreak($allItems);

        // --- Daily data (last 7 days) ---
        $dailyData = $this->buildDailyData($allItems, 7);

        // --- Weekly progress (last 8 weeks) ---
        $weeklyProgress = $this->buildWeeklyProgress($allItems, $subListIds, 8);

        // --- Exercise progression (from progress_entries) ---
        $exerciseProgress = $this->buildExerciseProgress($allItems);

        // --- Challenges ---
        $challenges = $metadata['challenges'] ?? [];

        return response()->json([
            'data' => [
                'overview' => [
                    'totalExercises' => $totalExercises,
                    'completedExercises' => $completedExercises,
                    'completionRate' => $completionRate,
                ],
                'thisWeek' => [
                    'sessions' => $thisWeekSessions,
                    'plannedSessions' => $plannedSessions,
                    'exercises' => $thisWeekExercises,
                    'volume' => $thisWeekVolume,
                ],
                'lastWeek' => [
                    'sessions' => $lastWeekSessions,
                    'exercises' => $lastWeekExercises,
                    'volume' => $lastWeekVolume,
                ],
                'streak' => $streak,
                'dailyData' => $dailyData,
                'weeklyProgress' => $weeklyProgress,
                'exerciseProgress' => $exerciseProgress,
                'challenges' => $challenges,
            ],
        ], 200);
    }

    /**
     * Filter items completed between two dates.
     */
    private function getItemsCompletedBetween($items, Carbon $start, Carbon $end)
    {
        return $items->filter(function ($item) use ($start, $end) {
            if (! $item->completed || ! $item->updated_at) {
                return false;
            }

            $completedAt = Carbon::parse($item->updated_at);

            return $completedAt->between($start, $end);
        });
    }

    /**
     * Count distinct sessions (sub-lists) that have at least one completed item.
     */
    private function countSessionsFromItems($completedItems, array $subListIds): int
    {
        return $completedItems
            ->pluck('list_id')
            ->unique()
            ->intersect($subListIds)
            ->count();
    }

    /**
     * Calculate total volume (sets * reps * weight) from items.
     */
    private function calculateVolume($items): float
    {
        return $items->reduce(function ($carry, $item) {
            $meta = $item->metadata ?? [];
            $sets = $meta['sets'] ?? 0;
            $reps = $meta['reps'] ?? 0;
            $weight = $meta['weight'] ?? 0;

            return $carry + ($sets * $reps * $weight);
        }, 0);
    }

    /**
     * Calculate current and best streak (consecutive days with at least one completion).
     */
    private function calculateStreak($items): array
    {
        $completedDates = $items
            ->filter(fn ($item) => $item->completed && $item->updated_at)
            ->map(fn ($item) => Carbon::parse($item->updated_at)->toDateString())
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        if (empty($completedDates)) {
            return ['current' => 0, 'best' => 0];
        }

        $today = Carbon::today()->toDateString();
        $yesterday = Carbon::yesterday()->toDateString();

        $current = 0;
        $best = 0;
        $tempStreak = 1;

        for ($i = 1; $i < count($completedDates); $i++) {
            $prev = Carbon::parse($completedDates[$i - 1]);
            $curr = Carbon::parse($completedDates[$i]);

            if ($prev->diffInDays($curr) === 1) {
                $tempStreak++;
            } else {
                $best = max($best, $tempStreak);
                $tempStreak = 1;
            }
        }

        $best = max($best, $tempStreak);

        // Current streak: must include today or yesterday
        $lastDate = end($completedDates);
        if ($lastDate === $today || $lastDate === $yesterday) {
            $current = 1;
            for ($i = count($completedDates) - 2; $i >= 0; $i--) {
                $curr = Carbon::parse($completedDates[$i + 1]);
                $prev = Carbon::parse($completedDates[$i]);

                if ($curr->diffInDays($prev) === 1) {
                    $current++;
                } else {
                    break;
                }
            }
        }

        return ['current' => $current, 'best' => $best];
    }

    /**
     * Build daily completed counts for the last N days.
     */
    private function buildDailyData($items, int $days): array
    {
        $dailyData = [];
        $today = Carbon::today();

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $dateStr = $date->toDateString();

            $completed = $items->filter(function ($item) use ($dateStr) {
                return $item->completed
                    && $item->updated_at
                    && Carbon::parse($item->updated_at)->toDateString() === $dateStr;
            })->count();

            $dailyData[] = [
                'date' => $dateStr,
                'dayOfWeek' => $date->dayOfWeek,
                'completed' => $completed,
            ];
        }

        return $dailyData;
    }

    /**
     * Build weekly progress for the last N weeks.
     */
    private function buildWeeklyProgress($items, array $subListIds, int $weeks): array
    {
        $weeklyProgress = [];
        $now = Carbon::now();

        for ($i = $weeks - 1; $i >= 0; $i--) {
            $weekStart = $now->copy()->subWeeks($i)->startOfWeek();
            $weekEnd = $weekStart->copy()->endOfWeek();

            $weekItems = $items->filter(function ($item) use ($weekStart, $weekEnd) {
                return $item->updated_at
                    && Carbon::parse($item->updated_at)->between($weekStart, $weekEnd);
            });

            $total = $weekItems->count();
            $completed = $weekItems->where('completed', true)->count();

            $weeklyProgress[] = [
                'weekStart' => $weekStart->toDateString(),
                'total' => $total,
                'completed' => $completed,
                'rate' => $total > 0 ? round(($completed / $total) * 100) : 0,
            ];
        }

        return $weeklyProgress;
    }

    /**
     * Build exercise progression from progress_entries, grouped by exercise name.
     */
    private function buildExerciseProgress($items): array
    {
        $itemIds = $items->pluck('_id')->toArray();

        if (empty($itemIds)) {
            return [];
        }

        $entries = ProgressEntry::whereIn('item_id', $itemIds)
            ->orderBy('date', 'asc')
            ->get();

        if ($entries->isEmpty()) {
            return [];
        }

        // Map item_id to exercise name
        $itemMap = $items->keyBy('_id');

        $grouped = [];
        foreach ($entries as $entry) {
            $item = $itemMap->get($entry->item_id);
            if (! $item) {
                continue;
            }

            $name = $item->content;

            if (! isset($grouped[$name])) {
                $grouped[$name] = [];
            }

            $value = $entry->value ?? [];
            $sets = $value['sets'] ?? $item->metadata['sets'] ?? null;
            $reps = $value['reps'] ?? $item->metadata['reps'] ?? null;
            $weight = $value['weight'] ?? $item->metadata['weight'] ?? null;
            $volume = ($sets ?? 0) * ($reps ?? 0) * ($weight ?? 0);

            $grouped[$name][] = [
                'date' => $entry->date ? Carbon::parse($entry->date)->toDateString() : $entry->created_at?->toDateString(),
                'weight' => $weight,
                'sets' => $sets,
                'reps' => $reps,
                'volume' => $volume,
            ];
        }

        return empty($grouped) ? [] : $grouped;
    }
}
