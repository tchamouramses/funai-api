<?php

namespace App\Http\Controllers\Api\Calendar;

use App\Http\Controllers\Controller;
use App\Models\ListItem;
use App\Models\ListModel;
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
