<?php

namespace App\Crons\Ressources;

use App\Models\ListItem;
use App\Models\ListModel;
use App\Models\Profile;
use App\Services\ExpoPushNotificationService;
use App\Services\NotificationTranslationService;
use App\Services\RecurringTaskService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class TaskDueNotificationRessouces
{
    public function __construct(public ExpoPushNotificationService $expoPushNotificationService, public RecurringTaskService $recurringTaskService)
    {}

    public function __invoke()
    {
         $now = Carbon::now();

        $items = ListItem::where('completed', false)
            ->whereNotNull('due_date')
            ->where(function ($query) use ($now) {
                $query->orWhereNull('reminder_notified_at')
                    ->orWhereNull('expired_notified_at');
            })
            ->whereNull('reminder_notified_at')
            ->get();

        foreach ($items as $item) {
            try {
                $list = ListModel::find($item->list_id);
                if (! $list) {
                    Log::info("List not found for item: " . $item->id);
                    continue;
                }

                $_user = User::find($list->user_id);

                $user = Profile::where('email', $_user->email)->first();
                if (! $user) {
                    Log::info("User not found for item: " . $item->id);
                    continue;
                }

                $settings = (array) ($user->notification_settings ?? []);
                $enabled = (bool) ($settings['enabled'] ?? true);
                $tokens = (array) ($settings['expo_push_tokens'] ?? []);
                $defaultReminderDelay = (int) ($settings['default_reminder_delay'] ?? 15);
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
                ) {
                    $notification = NotificationTranslationService::getTaskReminderNotification($locale, $item->content);
                    Log::info("Sending reminder notification for item: " . $item->id, $notification);

                    $this->expoPushNotificationService->sendToTokens(
                        $tokens,
                        $notification['title'],
                        $notification['body'],
                        [
                            'type' => 'task_due_reminder',
                            'list_id' => $list->id,
                            'task_id' => $item->id,
                            'url' => 'myapp://flow/' . $list->id.'?taskId=' . $item->id,
                        ]
                    );

                    $item->reminder_notified_at = $now;
                    $item->save();
                }

                if ($enabled && ! empty($tokens) && ! $item->expired_notified_at && $now->greaterThanOrEqualTo($dueAt)) {
                    $notification = NotificationTranslationService::getTaskExpiredNotification($locale, $item->content);

                    $this->expoPushNotificationService->sendToTokens(
                        $tokens,
                        $notification['title'],
                        $notification['body'],
                        [
                            'type' => 'task_due_expired',
                            'list_id' => $list->id,
                            'task_id' => $item->id,
                            'url' => 'myapp://flow/' . $list->id.'?taskId=' . $item->id,
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
                    $recurringTaskService->cloneNextOccurrenceIfPossible($item, $list);
                }
                    Log::info("Fin du cron pour l'item: " . $item->id);
            } catch (\Throwable $throwable) {
                Log::error('Error while sending due task notification', [
                    'item_id' => (string) $item->id,
                    'error' => $throwable->getMessage(),
                ]);
            }
        }
    }
}
