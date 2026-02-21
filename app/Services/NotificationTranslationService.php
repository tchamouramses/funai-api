<?php

namespace App\Services;

class NotificationTranslationService
{
    private const TRANSLATIONS = [
        'en' => [
            'task_created' => 'Task Created',
            'task_created_body' => '{content}',
            'task_completed' => 'Task Completed',
            'task_completed_body' => '{content}',
            'task_deleted' => 'Task Deleted',
            'task_deleted_body' => '{content}',
            'task_reminder' => 'Task Reminder',
            'task_reminder_body' => 'The task "{content}" is coming due soon.',
            'task_expired' => 'Task Expired',
            'task_expired_body' => 'The task "{content}" has reached its due date and is not completed.',
        ],
        'fr' => [
            'task_created' => 'Tâche créée',
            'task_created_body' => '{content}',
            'task_completed' => 'Tâche complétée',
            'task_completed_body' => '{content}',
            'task_deleted' => 'Tâche supprimée',
            'task_deleted_body' => '{content}',
            'task_reminder' => 'Rappel de tâche',
            'task_reminder_body' => 'La tâche "{content}" approche de son échéance.',
            'task_expired' => 'Tâche expirée',
            'task_expired_body' => 'La tâche "{content}" est arrivée à expiration et n\'est pas terminée.',
        ],
        'es' => [
            'task_created' => 'Tarea creada',
            'task_created_body' => '{content}',
            'task_completed' => 'Tarea completada',
            'task_completed_body' => '{content}',
            'task_deleted' => 'Tarea eliminada',
            'task_deleted_body' => '{content}',
            'task_reminder' => 'Recordatorio de tarea',
            'task_reminder_body' => 'La tarea "{content}" está próxima a su fecha de vencimiento.',
            'task_expired' => 'Tarea vencida',
            'task_expired_body' => 'La tarea "{content}" ha alcanzado su fecha de vencimiento y no está completada.',
        ],
    ];

    public static function translate(string $key, string $locale = 'en', array $replace = []): string
    {
        $locale = strtolower($locale);

        // Fallback to English if locale not found
        if (!isset(self::TRANSLATIONS[$locale])) {
            $locale = 'en';
        }

        $translation = self::TRANSLATIONS[$locale][$key] ?? $key;

        // Replace placeholders
        foreach ($replace as $placeholder => $value) {
            $translation = str_replace('{' . $placeholder . '}', $value, $translation);
        }

        return $translation;
    }

    /**
     * Get notification title and body in user's locale
     */
    public static function getTaskCreatedNotification(string $locale, string $content): array
    {
        return [
            'title' => self::translate('task_created', $locale),
            'body' => self::translate('task_created_body', $locale, ['content' => $content]),
        ];
    }

    /**
     * Get notification title and body for task completion
     */
    public static function getTaskCompletedNotification(string $locale, string $content): array
    {
        return [
            'title' => self::translate('task_completed', $locale),
            'body' => self::translate('task_completed_body', $locale, ['content' => $content]),
        ];
    }

    /**
     * Get notification title and body for task deletion
     */
    public static function getTaskDeletedNotification(string $locale, string $content): array
    {
        return [
            'title' => self::translate('task_deleted', $locale),
            'body' => self::translate('task_deleted_body', $locale, ['content' => $content]),
        ];
    }

    /**
     * Get notification title and body for task due reminder
     */
    public static function getTaskReminderNotification(string $locale, string $content): array
    {
        return [
            'title' => self::translate('task_reminder', $locale),
            'body' => self::translate('task_reminder_body', $locale, ['content' => $content]),
        ];
    }

    /**
     * Get notification title and body for task expired
     */
    public static function getTaskExpiredNotification(string $locale, string $content): array
    {
        return [
            'title' => self::translate('task_expired', $locale),
            'body' => self::translate('task_expired_body', $locale, ['content' => $content]),
        ];
    }
}
