<?php

namespace App\Http\Controllers\Api\Nutrition;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Models\ListItem;
use App\Models\ListModel;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NutritionDashboardController extends Controller
{
    public function __invoke(Request $request, string $listId): JsonResponse
    {
        $list = ListModel::find($listId);

        if (! $list) {
            throw new ResourceNotFoundException('Nutrition flow not found');
        }

        if ($list->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Get all sub-list IDs + root list
        $subListIds = ListModel::where('parent_list_id', $listId)
            ->pluck('_id')
            ->map(fn ($id) => (string) $id)
            ->toArray();
        array_push($subListIds, $listId);

        $allItems = ListItem::whereIn('list_id', $subListIds)->get();

        $metadata = $list->metadata ?? [];
        $profile = $metadata['nutritionProfile'] ?? [];
        $dailyTarget = $metadata['dailyCalorieTarget'] ?? ($profile['dailyCalories'] ?? 2000);
        $proteinTarget = $profile['proteinTarget'] ?? null;
        $carbsTarget = $profile['carbsTarget'] ?? null;
        $fatTarget = $profile['fatTarget'] ?? null;

        $today = Carbon::today();

        // --- Today's totals ---
        $todayItems = $this->getItemsForDate($allItems, $today);
        $todayTotals = $this->calculateMacros($todayItems);

        // --- Yesterday comparison ---
        $yesterdayItems = $this->getItemsForDate($allItems, $today->copy()->subDay());
        $yesterdayTotals = $this->calculateMacros($yesterdayItems);

        // --- Meal type breakdown for today ---
        $mealBreakdown = $this->getMealBreakdown($todayItems);

        // --- Weekly data (last 7 days) ---
        $weeklyData = $this->buildWeeklyData($allItems, 7);

        // --- Streak (consecutive days with logged meals) ---
        $streak = $this->calculateStreak($allItems);

        // --- Weekly averages ---
        $weeklyAvg = $this->calculateWeeklyAverages($weeklyData);

        // --- Recent meals (last 10) ---
        $recentMeals = $allItems
            ->sortByDesc(function ($item) {
                return $item->due_date ?? $item->created_at;
            })
            ->take(10)
            ->map(function ($item) {
                $meta = $item->metadata ?? [];

                return [
                    'id' => (string) $item->_id,
                    'content' => $item->content,
                    'calories' => $meta['calories'] ?? 0,
                    'protein' => $meta['protein'] ?? 0,
                    'carbs' => $meta['carbs'] ?? 0,
                    'fat' => $meta['fat'] ?? 0,
                    'mealType' => $meta['mealType'] ?? 'snack',
                    'quantity' => $meta['quantity'] ?? null,
                    'date' => $item->due_date ?? ($item->created_at ? $item->created_at->toDateString() : null),
                    'completed' => $item->completed ?? false,
                ];
            })
            ->values()
            ->toArray();

        return response()->json([
            'data' => [
                'targets' => [
                    'dailyCalories' => $dailyTarget,
                    'protein' => $proteinTarget,
                    'carbs' => $carbsTarget,
                    'fat' => $fatTarget,
                ],
                'today' => [
                    'calories' => $todayTotals['calories'],
                    'protein' => $todayTotals['protein'],
                    'carbs' => $todayTotals['carbs'],
                    'fat' => $todayTotals['fat'],
                    'mealCount' => $todayItems->count(),
                    'remaining' => max(0, $dailyTarget - $todayTotals['calories']),
                    'percentage' => $dailyTarget > 0
                        ? min(100, round(($todayTotals['calories'] / $dailyTarget) * 100))
                        : 0,
                ],
                'comparison' => [
                    'calorieChange' => $yesterdayTotals['calories'] > 0
                        ? round((($todayTotals['calories'] - $yesterdayTotals['calories']) / $yesterdayTotals['calories']) * 100)
                        : null,
                ],
                'mealBreakdown' => $mealBreakdown,
                'weeklyData' => $weeklyData,
                'weeklyAverage' => $weeklyAvg,
                'streak' => $streak,
                'recentMeals' => $recentMeals,
            ],
        ], 200);
    }

    private function getItemsForDate($items, Carbon $date)
    {
        $dateStr = $date->toDateString();

        return $items->filter(function ($item) use ($dateStr) {
            // Check due_date first, fallback to created_at
            $itemDate = $item->due_date
                ? Carbon::parse($item->due_date)->toDateString()
                : ($item->created_at ? $item->created_at->toDateString() : null);

            return $itemDate === $dateStr;
        });
    }

    private function calculateMacros($items): array
    {
        return [
            'calories' => $items->sum(fn ($item) => ($item->metadata ?? [])['calories'] ?? 0),
            'protein' => $items->sum(fn ($item) => ($item->metadata ?? [])['protein'] ?? 0),
            'carbs' => $items->sum(fn ($item) => ($item->metadata ?? [])['carbs'] ?? 0),
            'fat' => $items->sum(fn ($item) => ($item->metadata ?? [])['fat'] ?? 0),
        ];
    }

    private function getMealBreakdown($items): array
    {
        $types = ['breakfast', 'lunch', 'dinner', 'snack'];
        $breakdown = [];

        foreach ($types as $type) {
            $typeItems = $items->filter(fn ($item) => (($item->metadata ?? [])['mealType'] ?? 'snack') === $type);
            $breakdown[] = [
                'mealType' => $type,
                'count' => $typeItems->count(),
                'calories' => $typeItems->sum(fn ($item) => ($item->metadata ?? [])['calories'] ?? 0),
                'protein' => $typeItems->sum(fn ($item) => ($item->metadata ?? [])['protein'] ?? 0),
                'carbs' => $typeItems->sum(fn ($item) => ($item->metadata ?? [])['carbs'] ?? 0),
                'fat' => $typeItems->sum(fn ($item) => ($item->metadata ?? [])['fat'] ?? 0),
            ];
        }

        return $breakdown;
    }

    private function buildWeeklyData($items, int $days): array
    {
        $data = [];
        $today = Carbon::today();

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $dayItems = $this->getItemsForDate($items, $date);
            $macros = $this->calculateMacros($dayItems);

            $data[] = [
                'date' => $date->toDateString(),
                'dayLabel' => $date->locale('fr')->isoFormat('ddd'),
                'dayOfWeek' => $date->dayOfWeek,
                'calories' => $macros['calories'],
                'protein' => $macros['protein'],
                'carbs' => $macros['carbs'],
                'fat' => $macros['fat'],
                'mealCount' => $dayItems->count(),
            ];
        }

        return $data;
    }

    private function calculateStreak($items): array
    {
        $dates = $items
            ->map(function ($item) {
                return $item->due_date
                    ? Carbon::parse($item->due_date)->toDateString()
                    : ($item->created_at ? $item->created_at->toDateString() : null);
            })
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        if (empty($dates)) {
            return ['current' => 0, 'best' => 0];
        }

        $today = Carbon::today()->toDateString();
        $yesterday = Carbon::yesterday()->toDateString();

        $current = 0;
        $best = 0;
        $tempStreak = 1;

        for ($i = 1; $i < count($dates); $i++) {
            $prev = Carbon::parse($dates[$i - 1]);
            $curr = Carbon::parse($dates[$i]);

            if ($prev->diffInDays($curr) === 1) {
                $tempStreak++;
            } else {
                $best = max($best, $tempStreak);
                $tempStreak = 1;
            }
        }

        $best = max($best, $tempStreak);

        $lastDate = end($dates);
        if ($lastDate === $today || $lastDate === $yesterday) {
            $current = 1;
            for ($i = count($dates) - 2; $i >= 0; $i--) {
                $curr = Carbon::parse($dates[$i + 1]);
                $prev = Carbon::parse($dates[$i]);

                if ($curr->diffInDays($prev) === 1) {
                    $current++;
                } else {
                    break;
                }
            }
        }

        return ['current' => $current, 'best' => $best];
    }

    private function calculateWeeklyAverages(array $weeklyData): array
    {
        $count = count($weeklyData);
        if ($count === 0) {
            return ['calories' => 0, 'protein' => 0, 'carbs' => 0, 'fat' => 0];
        }

        return [
            'calories' => round(array_sum(array_column($weeklyData, 'calories')) / $count),
            'protein' => round(array_sum(array_column($weeklyData, 'protein')) / $count),
            'carbs' => round(array_sum(array_column($weeklyData, 'carbs')) / $count),
            'fat' => round(array_sum(array_column($weeklyData, 'fat')) / $count),
        ];
    }
}
