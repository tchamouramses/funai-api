<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Notification;
use App\Models\Profile;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BudgetAlertService
{
    public function __construct(
        private ExpoPushNotificationService $pushService,
    ) {}

    /**
     * Check all active budgets for the given user and list,
     * and send alerts for any thresholds that have been exceeded.
     */
    public function checkBudgetsAfterTransaction(string $userId, string $listId): void
    {
        $budgets = Budget::forUser($userId)
            ->forList($listId)
            ->active()
            ->current()
            ->get();

        foreach ($budgets as $budget) {
            $this->checkSingleBudget($budget, $userId, $listId);
        }
    }

    /**
     * Check a single budget and send alerts for exceeded thresholds.
     */
    private function checkSingleBudget(Budget $budget, string $userId, string $listId): void
    {
        $spent = Transaction::forUser($userId)
            ->forList($listId)
            ->expenses()
            ->dateBetween($budget->start_date, $budget->end_date)
            ->where('status', '!=', 'cancelled')
            ->when($budget->category && $budget->category !== 'global', function ($q) use ($budget) {
                $q->where('category', $budget->category);
            })
            ->sum('amount');

        // Update the spent field
        $budget->update(['spent' => round($spent, 2)]);

        $thresholds = $budget->alert_thresholds ?? [70, 90, 100];
        $alertsSent = $budget->alerts_sent ?? [];
        $percentage = $budget->amount > 0 ? round(($spent / $budget->amount) * 100, 1) : 0;

        foreach ($thresholds as $threshold) {
            if ($percentage >= $threshold && ! in_array($threshold, $alertsSent)) {
                $this->sendBudgetAlert($budget, $threshold, $percentage, $spent, $userId);
                $alertsSent[] = $threshold;
            }
        }

        if (count($alertsSent) !== count($budget->alerts_sent ?? [])) {
            $budget->update(['alerts_sent' => $alertsSent]);
        }
    }

    /**
     * Send push notification and create in-app notification for budget alert.
     */
    private function sendBudgetAlert(Budget $budget, int $threshold, float $currentPercentage, float $spent, string $userId): void
    {
        $profile = Profile::find($userId);

        if (! $profile) {
            return;
        }

        $locale = $profile->locale ?? 'fr';
        $budgetName = $budget->name;
        $currency = $budget->currency ?? 'XAF';

        $title = $this->getAlertTitle($threshold, $locale);
        $body = $this->getAlertBody($threshold, $currentPercentage, $budgetName, $spent, $budget->amount, $currency, $locale);

        // Create in-app notification
        Notification::create([
            'user_id' => $userId,
            'type' => 'budget_alert',
            'title' => $title,
            'body' => $body,
            'data' => [
                'budget_id' => (string) $budget->_id,
                'list_id' => $budget->list_id,
                'threshold' => $threshold,
                'percentage' => $currentPercentage,
                'spent' => $spent,
                'budget_amount' => $budget->amount,
            ],
        ]);

        // Send push notification
        $tokens = $profile->notification_settings['expo_push_tokens'] ?? [];

        if (! empty($tokens)) {
            $this->pushService->sendToTokens($tokens, $title, $body, [
                'type' => 'budget_alert',
                'budget_id' => (string) $budget->_id,
                'list_id' => $budget->list_id,
            ]);
        }

        Log::info('Budget alert sent', [
            'budget_id' => (string) $budget->_id,
            'threshold' => $threshold,
            'percentage' => $currentPercentage,
        ]);
    }

    /**
     * Get localized alert title.
     */
    private function getAlertTitle(int $threshold, string $locale): string
    {
        $titles = [
            'fr' => [
                70 => '⚠️ Budget bientôt atteint',
                90 => '🔴 Budget presque épuisé',
                100 => '🚨 Budget dépassé !',
            ],
            'en' => [
                70 => '⚠️ Budget nearing limit',
                90 => '🔴 Budget almost exhausted',
                100 => '🚨 Budget exceeded!',
            ],
        ];

        $localeTitles = $titles[$locale] ?? $titles['fr'];

        if (isset($localeTitles[$threshold])) {
            return $localeTitles[$threshold];
        }

        return $locale === 'en'
            ? "⚠️ Budget alert: {$threshold}% reached"
            : "⚠️ Alerte budget : {$threshold}% atteint";
    }

    /**
     * Get localized alert body.
     */
    private function getAlertBody(int $threshold, float $percentage, string $budgetName, float $spent, float $total, string $currency, string $locale): string
    {
        $formattedSpent = number_format($spent, 0, ',', ' ');
        $formattedTotal = number_format($total, 0, ',', ' ');

        if ($locale === 'en') {
            return "Your budget \"{$budgetName}\" has reached {$percentage}% ({$formattedSpent} / {$formattedTotal} {$currency}).";
        }

        return "Votre budget \"{$budgetName}\" a atteint {$percentage}% ({$formattedSpent} / {$formattedTotal} {$currency}).";
    }

    /**
     * Renew recurring budgets that have expired.
     */
    public function renewExpiredBudgets(): void
    {
        $expiredBudgets = Budget::where('is_recurring', true)
            ->where('is_active', true)
            ->where('end_date', '<', now())
            ->get();

        foreach ($expiredBudgets as $budget) {
            $this->renewBudget($budget);
        }
    }

    /**
     * Create a new budget period from an expired recurring budget.
     */
    private function renewBudget(Budget $budget): void
    {
        $duration = Carbon::parse($budget->start_date)->diffInDays(Carbon::parse($budget->end_date));
        $newStart = Carbon::parse($budget->end_date)->addDay();
        $newEnd = $newStart->copy()->addDays($duration);

        Budget::create([
            'user_id' => $budget->user_id,
            'list_id' => $budget->list_id,
            'name' => $budget->name,
            'amount' => $budget->amount,
            'spent' => 0,
            'currency' => $budget->currency,
            'category' => $budget->category,
            'period' => $budget->period,
            'start_date' => $newStart,
            'end_date' => $newEnd,
            'alert_thresholds' => $budget->alert_thresholds,
            'alerts_sent' => [],
            'is_recurring' => true,
            'is_active' => true,
        ]);

        // Keep the old budget for history but mark inactive
        $budget->update(['is_active' => false]);
    }
}
