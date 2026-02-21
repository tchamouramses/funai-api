<?php

namespace App\Console\Commands;

use App\Models\ListItem;
use App\Models\ListModel;
use App\Models\Profile;
use App\Services\ExpoPushNotificationService;
use App\Services\NotificationTranslationService;
use App\Services\RecurringTaskService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendTaskDueNotificationsCommand extends Command
{
    protected $signature = 'tasks:send-due-notifications';

    protected $description = 'Send reminder and expired push notifications for pending tasks';

    public function handle(
        ExpoPushNotificationService $expoPushNotificationService,
        RecurringTaskService $recurringTaskService
    ): int
    {
        $now = Carbon::now();

        $items = ListItem::where('completed', false)
            ->whereNotNull('due_date')
            ->where(function ($query) {
                $query->whereNull('reminder_notified_at')
                    ->orWhereNull('expired_notified_at')
                    ->orWhereNull('missed_processed_at');
            })
            ->get();

        foreach ($items as $item) {
            try {
                $list = ListModel::find($item->list_id);
                if (! $list) {
                    continue;
                }

                $user = Profile::find($list->user_id);
                if (! $user) {
                    continue;
                }

                $settings = (array) ($user->notification_settings ?? []);
                $enabled = (bool) ($settings['enabled'] ?? true);
                $tokens = array_values(array_filter((array) ($settings['expo_push_tokens'] ?? [])));
                $defaultReminderDelay = (int) ($settings['default_reminder_delay'] ?? ($settings['default_reminder_time'] ?? 15));
                if ($defaultReminderDelay < 0) {
                    $defaultReminderDelay = 0;
                }

                $dueAt = Carbon::parse($item->due_date);
                $reminderAt = (clone $dueAt)->subMinutes($defaultReminderDelay);

                // Récupérer la locale de l'utilisateur (par défaut 'en')
                $locale = $user->locale ?? 'en';

                if (
                    $enabled
                    && ! empty($tokens)
                    && ! $item->reminder_notified_at
                    && $now->greaterThanOrEqualTo($reminderAt)
                    && $now->lessThan($dueAt)
                ) {
                    $notification = NotificationTranslationService::getTaskReminderNotification($locale, $item->content);

                    $expoPushNotificationService->sendToTokens(
                        $tokens,
                        $notification['title'],
                        $notification['body'],
                        [
                            'type' => 'task_due_reminder',
                            'list_id' => (string) $list->id,
                            'task_id' => (string) $item->id,
                            'url' => 'myapp://lists/'.(string) $list->id.'?taskId='.(string) $item->id,
                        ]
                    );

                    $item->reminder_notified_at = $now;
                    $item->save();
                }

                if ($enabled && ! empty($tokens) && ! $item->expired_notified_at && $now->greaterThanOrEqualTo($dueAt)) {
                    $notification = NotificationTranslationService::getTaskExpiredNotification($locale, $item->content);

                    $expoPushNotificationService->sendToTokens(
                        $tokens,
                        $notification['title'],
                        $notification['body'],
                        [
                            'type' => 'task_due_expired',
                            'list_id' => (string) $list->id,
                            'task_id' => (string) $item->id,
                            'url' => 'myapp://lists/'.(string) $list->id.'?taskId='.(string) $item->id,
                        ]
                    );

                    $item->expired_notified_at = $now;
                    $item->save();
                }

                if (
                    $now->greaterThanOrEqualTo($dueAt)
                    && ! $item->completed
                    && ! $item->missed_processed_at
                    && $recurringTaskService->isRecurring($item, $list)
                ) {
                    $item->missed_at = $item->missed_at ?? $now;
                    $item->missed_processed_at = $now;
                    $item->save();

                    $recurringTaskService->recordRecurrenceStatus($item, 'missed');
                    $recurringTaskService->cloneNextOccurrenceIfNeeded($item, $list);
                }
            } catch (\Throwable $throwable) {
                Log::error('Error while sending due task notification', [
                    'item_id' => (string) $item->id,
                    'error' => $throwable->getMessage(),
                ]);
            }
        }

        return self::SUCCESS;
    }
}
