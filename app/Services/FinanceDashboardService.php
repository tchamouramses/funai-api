<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class FinanceDashboardService
{
    /**
     * Generate full dashboard data for a finance flow.
     */
    public function getDashboard(string $listId, string $period = 'monthly', ?string $customStart = null, ?string $customEnd = null): array
    {
        [$startDate, $endDate] = $this->resolvePeriodDates($period, $customStart, $customEnd);

        $transactions = Transaction::where('list_id', $listId)
            ->whereBetween('date', [$startDate, $endDate])
            ->latest()
            ->get();

        Log::info("transactions", [$transactions]);

        $previousPeriodTransactions = $this->getPreviousPeriodTransactions($listId, $period, $startDate, $endDate);

        return [
            'period' => [
                'type' => $period,
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'summary' => $this->buildSummary($transactions, $previousPeriodTransactions),
            'cashFlow' => $this->buildCashFlow($transactions, $startDate, $endDate),
            'expensesByCategory' => $this->buildCategoryBreakdown($transactions, 'expense'),
            'incomesByCategory' => $this->buildCategoryBreakdown($transactions, 'income'),
            'monthlyTrend' => $this->buildMonthlyTrend($listId, 6),
            'topExpenses' => $this->buildTopExpenses($transactions, 5),
            'recentTransactions' => $this->buildRecentTransactions($transactions, 10),
            'budgets' => $this->buildBudgetsSummary($listId),
        ];
    }

    /**
     * Build summary: total income, total expenses, net result.
     */
    private function buildSummary(Collection $transactions, Collection $previousTransactions): array
    {
        $totalIncome = $transactions->where('type', 'income')->sum('amount');
        $totalExpenses = $transactions->where('type', 'expense')->sum('amount');
        $netResult = $totalIncome - $totalExpenses;

        $prevIncome = $previousTransactions->where('type', 'income')->sum('amount');
        $prevExpenses = $previousTransactions->where('type', 'expense')->sum('amount');
        $prevNet = $prevIncome - $prevExpenses;

        return [
            'totalIncome' => round($totalIncome, 2),
            'totalExpenses' => round($totalExpenses, 2),
            'netResult' => round($netResult, 2),
            'transactionCount' => $transactions->count(),
            'comparison' => [
                'incomeChange' => $this->calculatePercentageChange($prevIncome, $totalIncome),
                'expensesChange' => $this->calculatePercentageChange($prevExpenses, $totalExpenses),
                'netChange' => $this->calculatePercentageChange($prevNet, $netResult),
            ],
        ];
    }

    /**
     * Build cash flow data: daily income vs expenses.
     */
    private function buildCashFlow(Collection $transactions, Carbon $start, Carbon $end): array
    {
        $days = [];
        $current = $start->copy();

        while ($current->lte($end)) {
            $dateStr = $current->toDateString();

            $dayTransactions = $transactions->filter(
                fn ($t) => Carbon::parse($t->date)->toDateString() === $dateStr
            );

            $income = $dayTransactions->where('type', 'income')->sum('amount');
            $expenses = $dayTransactions->where('type', 'expense')->sum('amount');

            $days[] = [
                'date' => $dateStr,
                'income' => round($income, 2),
                'expenses' => round($expenses, 2),
                'net' => round($income - $expenses, 2),
            ];

            $current->addDay();
        }

        return $days;
    }

    /**
     * Build breakdown by category for a given type (income or expense).
     */
    private function buildCategoryBreakdown(Collection $transactions, string $type): array
    {
        $filtered = $transactions->where('type', $type);
        $total = $filtered->sum('amount');

        if ($total <= 0) {
            return [];
        }

        return $filtered->groupBy('category')
            ->map(function ($group, $category) use ($total) {
                $amount = $group->sum('amount');

                return [
                    'category' => $category,
                    'amount' => round($amount, 2),
                    'count' => $group->count(),
                    'percentage' => round(($amount / $total) * 100, 1),
                ];
            })
            ->sortByDesc('amount')
            ->values()
            ->toArray();
    }

    /**
     * Build monthly trend over the last N months.
     */
    private function buildMonthlyTrend(string $listId, int $months): array
    {
        $trend = [];
        $now = Carbon::now();

        for ($i = $months - 1; $i >= 0; $i--) {
            $monthStart = $now->copy()->subMonths($i)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();

            $transactions = Transaction::where('list_id', $listId)
                ->dateBetween($monthStart, $monthEnd)
                ->where('status', '!=', 'cancelled')
                ->get();

            $income = $transactions->where('type', 'income')->sum('amount');
            $expenses = $transactions->where('type', 'expense')->sum('amount');

            $trend[] = [
                'month' => $monthStart->format('Y-m'),
                'monthLabel' => $monthStart->translatedFormat('M Y'),
                'income' => round($income, 2),
                'expenses' => round($expenses, 2),
                'net' => round($income - $expenses, 2),
            ];
        }

        return $trend;
    }

    /**
     * Build top N expenses.
     */
    private function buildTopExpenses(Collection $transactions, int $limit): array
    {
        return $transactions->where('type', 'expense')
            ->sortByDesc('amount')
            ->take($limit)
            ->map(fn ($t) => [
                'id' => (string) $t->_id,
                'amount' => $t->amount,
                'category' => $t->category,
                'notes' => $t->notes,
                'date' => Carbon::parse($t->date)->toDateString(),
            ])
            ->values()
            ->toArray();
    }

    /**
     * Build recent transactions.
     */
    private function buildRecentTransactions(Collection $transactions, int $limit): array
    {
        return $transactions->take($limit)
            ->map(fn ($t) => [
                'id' => (string) $t->_id,
                'type' => $t->type,
                'amount' => $t->amount,
                'category' => $t->category,
                'source' => $t->source,
                'status' => $t->status,
                'date' => Carbon::parse($t->date)->toDateString(),
                'notes' => $t->notes,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Build budgets summary for the current period.
     */
    private function buildBudgetsSummary(string $listId): array
    {
        $budgets = Budget::where('list_id', $listId)
            ->active()
            ->get();

        return $budgets->map(function ($budget) use ($listId) {
            // Recalculate spent from actual transactions
            $spent = Transaction::where('list_id', $listId)
                ->expenses()
                ->dateBetween($budget->start_date, $budget->end_date)
                ->where('status', '!=', 'cancelled')
                ->when($budget->category && $budget->category !== 'global', function ($q) use ($budget) {
                    $q->where('category', $budget->category);
                })
                ->sum('amount');

            return [
                'id' => (string) $budget->_id,
                'name' => $budget->name,
                'amount' => $budget->amount,
                'spent' => round($spent, 2),
                'remaining' => round(max(0, $budget->amount - $spent), 2),
                'percentage' => $budget->amount > 0 ? round(($spent / $budget->amount) * 100, 1) : 0,
                'category' => $budget->category,
                'period' => $budget->period,
                'startDate' => Carbon::parse($budget->start_date)->toDateString(),
                'endDate' => Carbon::parse($budget->end_date)->toDateString(),
            ];
        })
            ->values()
            ->toArray();
    }

    /**
     * Get previous period transactions for comparison.
     */
    private function getPreviousPeriodTransactions(string $listId, string $period, Carbon $currentStart, Carbon $currentEnd): Collection
    {
        $duration = $currentStart->diffInDays($currentEnd);
        $prevEnd = $currentStart->copy()->subDay();
        $prevStart = $prevEnd->copy()->subDays($duration);

        return Transaction::where('list_id', $listId)
            ->dateBetween($prevStart, $prevEnd)
            ->get();
    }

    /**
     * Resolve start/end dates from period type.
     */
    private function resolvePeriodDates(string $period, ?string $customStart, ?string $customEnd): array
    {
        $now = Carbon::now();

        return match ($period) {
            'weekly' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'monthly' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'quarterly' => [$now->copy()->firstOfQuarter(), $now->copy()->lastOfQuarter()->endOfDay()],
            'yearly' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'custom' => [
                $customStart ? Carbon::parse($customStart)->startOfDay() : $now->copy()->startOfMonth(),
                $customEnd ? Carbon::parse($customEnd)->endOfDay() : $now->copy()->endOfMonth(),
            ],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }

    /**
     * Calculate percentage change between two values.
     */
    private function calculatePercentageChange(float $previous, float $current): ?float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : ($current < 0 ? -100 : 0);
        }

        return round((($current - $previous) / abs($previous)) * 100, 1);
    }
}
