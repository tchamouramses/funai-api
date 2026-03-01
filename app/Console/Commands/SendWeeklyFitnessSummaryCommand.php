<?php

namespace App\Console\Commands;

use App\Models\ListItem;
use App\Models\ListModel;
use App\Models\Notification;
use App\Models\Profile;
use App\Services\ExpoPushNotificationService;
use App\Services\FitnessAIService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendWeeklyFitnessSummaryCommand extends Command
{
    protected $signature = 'fitness:weekly-summary';

    protected $description = 'Generate and send weekly fitness summary for all fitness lists';

    public function handle(
        ExpoPushNotificationService $expoPushNotificationService,
        FitnessAIService $fitnessAIService
    ): int {
        $this->info('Starting weekly fitness summary generation...');

        // Get all root fitness lists (no parent)
        $fitnessLists = ListModel::where('type', 'fitness')
            ->whereNull('parent_list_id')
            ->whereNotNull('metadata.fitnessProfile')
            ->where('metadata.setupCompleted', true)
            ->get();

        $this->info("Found {$fitnessLists->count()} configured fitness lists");

        $successCount = 0;
        $errorCount = 0;

        foreach ($fitnessLists as $list) {
            try {
                $this->processListSummary($list, $fitnessAIService, $expoPushNotificationService);
                $successCount++;
            } catch (\Throwable $e) {
                $errorCount++;
                Log::error('Error processing weekly fitness summary', [
                    'list_id' => (string) $list->_id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("Error for list {$list->_id}: {$e->getMessage()}");
            }
        }

        $this->info("Completed: {$successCount} success, {$errorCount} errors");

        return self::SUCCESS;
    }

    private function processListSummary(
        ListModel $list,
        FitnessAIService $fitnessAIService,
        ExpoPushNotificationService $expoPushNotificationService
    ): void {
        $metadata = $list->metadata ?? [];
        $profile = $metadata['fitnessProfile'] ?? null;

        if (! $profile) {
            return;
        }

        $user = Profile::find($list->user_id);

        if (! $user) {
            return;
        }

        $locale = $user->locale ?? 'fr';

        // Calculate weekly stats
        $stats = $this->calculateWeeklyStats($list);

        // Generate AI summary
        $summary = $fitnessAIService->generateWeeklySummary($stats, $profile, $locale);

        // Determine notification title
        $title = $locale === 'fr'
            ? "📊 Résumé hebdo - {$list->title}"
            : "📊 Weekly Summary - {$list->title}";

        // Save to notifications collection
        Notification::create([
            'user_id' => $list->user_id,
            'type' => 'fitness_weekly_summary',
            'title' => $title,
            'body' => $summary,
            'data' => [
                'list_id' => (string) $list->_id,
                'list_title' => $list->title,
                'stats' => $stats,
                'week_start' => Carbon::now()->startOfWeek()->format('Y-m-d'),
                'week_end' => Carbon::now()->endOfWeek()->format('Y-m-d'),
            ],
        ]);

        // Send push notification
        $settings = (array) ($user->notification_settings ?? []);
        $enabled = (bool) ($settings['enabled'] ?? true);
        $tokens = array_values(array_filter((array) ($settings['expo_push_tokens'] ?? [])));

        if ($enabled && ! empty($tokens)) {
            $shortBody = mb_strlen($summary) > 150
                ? mb_substr($summary, 0, 147).'...'
                : $summary;

            $expoPushNotificationService->sendToTokens(
                $tokens,
                $title,
                $shortBody,
                [
                    'type' => 'fitness_weekly_summary',
                    'list_id' => (string) $list->_id,
                    'url' => 'myapp://flow/'.(string) $list->_id,
                ]
            );
        }

        $this->info("Summary sent for list: {$list->title} (user: {$list->user_id})");
    }

    private function calculateWeeklyStats(ListModel $list): array
    {
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();

        // Get all child list IDs
        $allListIds = $this->getAllChildListIds((string) $list->_id);
        $allListIds[] = (string) $list->_id;

        $allItems = ListItem::whereIn('list_id', $allListIds)->get();

        // This week completed exercises
        $completedThisWeek = $allItems->filter(function ($item) use ($weekStart) {
            return $item->completed && Carbon::parse($item->updated_at)->gte($weekStart);
        });

        // Total volume
        $totalVolume = 0;

        foreach ($completedThisWeek as $item) {
            $metadata = $item->metadata ?? [];
            $sets = $metadata['sets'] ?? 0;
            $reps = $metadata['reps'] ?? 0;
            $weight = $metadata['weight'] ?? 0;

            if ($sets > 0 && $reps > 0 && $weight > 0) {
                $totalVolume += $sets * $reps * $weight;
            }
        }

        // Sessions (child lists with at least one completed exercise this week)
        $childLists = ListModel::where('parent_list_id', (string) $list->_id)->get();
        $completedSessions = 0;

        foreach ($childLists as $child) {
            $hasCompleted = $allItems
                ->where('list_id', (string) $child->_id)
                ->filter(fn ($item) => $item->completed && Carbon::parse($item->updated_at)->gte($weekStart))
                ->isNotEmpty();

            if ($hasCompleted) {
                $completedSessions++;
            }
        }

        // Current streak
        $streak = $this->calculateStreak($allListIds);

        // Improvements (exercises where weight increased vs last week)
        $improvements = $this->findImprovements($allItems, $weekStart);

        return [
            'completed_sessions' => $completedSessions,
            'planned_sessions' => $childLists->count(),
            'completed_exercises' => $completedThisWeek->count(),
            'total_exercises' => $allItems->count(),
            'total_volume' => $totalVolume,
            'current_streak' => $streak,
            'improvements' => $improvements,
        ];
    }

    private function getAllChildListIds(string $parentId): array
    {
        $children = ListModel::where('parent_list_id', $parentId)->pluck('_id')->toArray();
        $allIds = $children;

        foreach ($children as $childId) {
            $allIds = array_merge($allIds, $this->getAllChildListIds($childId));
        }

        return $allIds;
    }

    private function calculateStreak(array $listIds): int
    {
        $streak = 0;
        $date = Carbon::today();

        for ($i = 0; $i < 365; $i++) {
            $dayStart = $date->copy()->startOfDay();
            $dayEnd = $date->copy()->endOfDay();

            $completedToday = ListItem::whereIn('list_id', $listIds)
                ->where('completed', true)
                ->whereBetween('updated_at', [$dayStart, $dayEnd])
                ->exists();

            if ($completedToday) {
                $streak++;
                $date->subDay();
            } else {
                if ($i === 0) {
                    $date->subDay();

                    continue;
                }

                break;
            }
        }

        return $streak;
    }

    private function findImprovements($allItems, Carbon $weekStart): array
    {
        $lastWeekStart = $weekStart->copy()->subWeek();
        $improvements = [];

        $grouped = $allItems->groupBy('content');

        foreach ($grouped as $name => $items) {
            $thisWeek = $items->filter(fn ($i) => $i->completed && Carbon::parse($i->updated_at)->gte($weekStart));
            $lastWeek = $items->filter(fn ($i) => $i->completed && Carbon::parse($i->updated_at)->gte($lastWeekStart) && Carbon::parse($i->updated_at)->lt($weekStart));

            if ($thisWeek->isEmpty() || $lastWeek->isEmpty()) {
                continue;
            }

            $thisWeekMaxWeight = $thisWeek->max(fn ($i) => ($i->metadata ?? [])['weight'] ?? 0);
            $lastWeekMaxWeight = $lastWeek->max(fn ($i) => ($i->metadata ?? [])['weight'] ?? 0);

            if ($thisWeekMaxWeight > $lastWeekMaxWeight && $lastWeekMaxWeight > 0) {
                $improvements[] = "{$name}: {$lastWeekMaxWeight}kg → {$thisWeekMaxWeight}kg";
            }
        }

        return array_slice($improvements, 0, 5);
    }
}
