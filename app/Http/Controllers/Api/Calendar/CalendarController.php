<?php

namespace App\Http\Controllers\Api\Calendar;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\ListItem;
use App\Models\ListModel;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    /**
     * Get all incomplete tasks with due dates for the authenticated user,
     * grouped by date with flow type color info.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'month' => 'nullable|date_format:Y-m',
        ]);

        $userId = auth()->id();

        // Determine date range (default: current month ± 1 month for calendar padding)
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $startDate = Carbon::parse($month . '-01')->subMonth()->startOfMonth()->startOfDay();
        $endDate = Carbon::parse($month . '-01')->addMonth()->endOfMonth()->endOfDay();

        // Get all lists belonging to the user (with type info)
        $userLists = ListModel::where('user_id', $userId)
            ->get(['_id', 'title', 'type', 'parent_list_id', 'depth']);

        $listIds = $userLists->pluck('_id')->map(fn($id) => (string) $id)->toArray();

        if (empty($listIds)) {
            return response()->json(['data' => []], 200);
        }

        // Build a map of list_id => list info (including parent lookup for sub-lists)
        $listMap = [];
        foreach ($userLists as $list) {
            $listMap[(string) $list->_id] = [
                'id' => (string) $list->_id,
                'title' => $list->title,
                'type' => $list->type,
                'parentListId' => $list->parent_list_id ? (string) $list->parent_list_id : null,
                'depth' => $list->depth ?? 0,
            ];
        }

        // For sub-lists, resolve the root flow type from the parent chain
        foreach ($listMap as $id => &$info) {
            if ($info['depth'] > 0 && $info['parentListId']) {
                $rootType = $this->resolveRootType($info['parentListId'], $listMap);
                if ($rootType) {
                    $info['type'] = $rootType;
                }
            }
        }
        unset($info);

        // Get all incomplete items with a due_date in range
        $items = ListItem::whereIn('list_id', $listIds)
            ->where('completed', false)
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$startDate, $endDate])
            ->orderBy('due_date', 'asc')
            ->get();

        // Group items by date
        $grouped = [];
        foreach ($items as $item) {
            $date = Carbon::parse($item->due_date)->toDateString();
            $listId = (string) $item->list_id;
            $listInfo = $listMap[$listId] ?? null;

            $grouped[$date][] = [
                'id' => (string) $item->_id,
                'content' => $item->content,
                'dueDate' => $date,
                'listId' => $listId,
                'listTitle' => $listInfo ? $listInfo['title'] : 'Unknown',
                'flowType' => $listInfo ? $listInfo['type'] : 'todo',
                'completed' => $item->completed,
                'metadata' => $item->metadata,
            ];
        }

        // ─── Transactions from finance lists ─────────────────────
        $financeListIds = collect($listMap)
            ->filter(fn($info) => $info['type'] === 'finance')
            ->keys()
            ->toArray();

        if (!empty($financeListIds)) {
            $transactions = Transaction::where('user_id', $userId)
                ->whereIn('list_id', $financeListIds)
                ->whereBetween('date', [$startDate, $endDate])
                ->orderBy('date', 'asc')
                ->get();

            foreach ($transactions as $tx) {
                $date = Carbon::parse($tx->date)->toDateString();
                $txListId = (string) $tx->list_id;
                $txListInfo = $listMap[$txListId] ?? null;

                $sign = $tx->type === 'income' ? '+' : '-';
                $grouped[$date][] = [
                    'id' => 'tx_' . (string) $tx->_id,
                    'content' => $sign . ' ' . number_format($tx->amount, 0, ',', ' ') . ' ' . ($tx->currency ?? 'XAF') . ' • ' . $tx->category,
                    'dueDate' => $date,
                    'listId' => $txListId,
                    'listTitle' => $txListInfo ? $txListInfo['title'] : 'Unknown',
                    'flowType' => 'finance',
                    'completed' => false,
                    'metadata' => [
                        'calendarItemType' => 'transaction',
                        'transactionType' => $tx->type,
                        'amount' => $tx->amount,
                        'currency' => $tx->currency,
                        'category' => $tx->category,
                        'status' => $tx->status,
                    ],
                ];
            }

            // ─── Budgets from finance lists ─────────────────────
            $budgets = Budget::where('user_id', $userId)
                ->whereIn('list_id', $financeListIds)
                ->where('is_active', true)
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_date', [$startDate, $endDate])
                          ->orWhereBetween('end_date', [$startDate, $endDate]);
                })
                ->get();

            foreach ($budgets as $budget) {
                $date = Carbon::parse($budget->start_date)->toDateString();
                $budgetListId = (string) $budget->list_id;
                $budgetListInfo = $listMap[$budgetListId] ?? null;

                // Compute spent from related expense transactions
                $spent = Transaction::where('list_id', $budgetListId)
                    ->where('type', 'expense')
                    ->where('category', $budget->category)
                    ->whereBetween('date', [
                        Carbon::parse($budget->start_date)->startOfDay(),
                        Carbon::parse($budget->end_date)->endOfDay(),
                    ])
                    ->sum('amount');

                $grouped[$date][] = [
                    'id' => 'bg_' . (string) $budget->_id,
                    'content' => '📊 ' . $budget->name . ' • ' . number_format($budget->amount, 0, ',', ' ') . ' ' . ($budget->currency ?? 'XAF'),
                    'dueDate' => $date,
                    'listId' => $budgetListId,
                    'listTitle' => $budgetListInfo ? $budgetListInfo['title'] : 'Unknown',
                    'flowType' => 'finance',
                    'completed' => false,
                    'metadata' => [
                        'calendarItemType' => 'budget',
                        'name' => $budget->name,
                        'amount' => $budget->amount,
                        'spent' => $spent,
                        'remaining' => max(0, $budget->amount - $spent),
                        'percentage' => $budget->amount > 0 ? round(($spent / $budget->amount) * 100) : 0,
                        'currency' => $budget->currency,
                        'period' => $budget->period,
                        'category' => $budget->category,
                    ],
                ];
            }
        }

        return response()->json([
            'data' => $grouped,
        ], 200);
    }

    /**
     * Walk up the parent chain to find the root list's type.
     */
    private function resolveRootType(string $parentId, array $listMap): ?string
    {
        $visited = [];
        $currentId = $parentId;

        while ($currentId && isset($listMap[$currentId]) && !in_array($currentId, $visited)) {
            $visited[] = $currentId;
            $info = $listMap[$currentId];

            if (!$info['parentListId'] || $info['depth'] === 0) {
                return $info['type'];
            }

            $currentId = $info['parentListId'];
        }

        return null;
    }
}
