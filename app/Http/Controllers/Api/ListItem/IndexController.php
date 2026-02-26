<?php

namespace App\Http\Controllers\Api\ListItem;

use App\Http\Controllers\Controller;
use App\Models\ListItem;
use App\Models\ProgressEntry;
use Illuminate\Http\JsonResponse;

class IndexController extends Controller
{
    public function __invoke(string $listId): JsonResponse
    {
        $items = ListItem::where('list_id', $listId)
            ->orderBy('order', 'asc')
            ->paginate(10);

        $items->getCollection()->transform(function ($item) {
            $seriesId = $item->series_id ?: (string) $item->id;
            $entries = ProgressEntry::where('series_id', $seriesId)->latest()->get();

            $item->recurrence_summary = [
                'done_days' => $entries->where('status', 'done')->count(),
                'missed_days' => $entries->where('status', 'missed')->count(),
            ];

            return $item;
        });

        return response()->json(['data' => $items], 200);
    }
}
