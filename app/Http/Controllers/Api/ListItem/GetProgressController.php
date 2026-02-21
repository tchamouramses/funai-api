<?php

namespace App\Http\Controllers\Api\ListItem;

use App\Http\Controllers\Controller;
use App\Models\ListItem;
use App\Models\ProgressEntry;
use App\Exceptions\ResourceNotFoundException;
use Illuminate\Http\JsonResponse;

class GetProgressController extends Controller
{
    public function __invoke(string $id): JsonResponse
    {
        $item = ListItem::find($id);

        if (! $item) {
            throw new ResourceNotFoundException('Item not found');
        }

        $seriesId = $item->series_id ?: (string) $item->id;
        $entries = ProgressEntry::where('series_id', $seriesId)
            ->orderBy('date', 'desc')
            ->get();

        $doneCount = $entries->where('status', 'done')->count();
        $missedCount = $entries->where('status', 'missed')->count();

        $calendar = $entries
            ->map(function ($entry) {
                return [
                    'date' => optional($entry->date)->format('Y-m-d'),
                    'status' => $entry->status,
                    'item_id' => $entry->item_id,
                ];
            })
            ->values();

        return response()->json([
            'data' => [
                'item_id' => $item->_id,
                'series_id' => $seriesId,
                'total_entries' => $entries->count(),
                'entries' => $entries,
                'latest' => $entries->first(),
                'recurrence_summary' => [
                    'done_days' => $doneCount,
                    'missed_days' => $missedCount,
                    'calendar' => $calendar,
                ],
            ],
        ], 200);
    }
}
