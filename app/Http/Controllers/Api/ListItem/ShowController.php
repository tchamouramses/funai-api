<?php

namespace App\Http\Controllers\Api\ListItem;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Models\ListItem;
use App\Models\ProgressEntry;
use Illuminate\Http\JsonResponse;

class ShowController extends Controller
{
    public function __invoke(string $id): JsonResponse
    {
        $item = ListItem::with('progressEntries')->find($id);

        if (! $item) {
            throw new ResourceNotFoundException('Item not found');
        }

        $seriesId = $item->series_id ?: (string) $item->id;
        $entries = ProgressEntry::where('series_id', $seriesId)->get();
        $item->recurrence_summary = [
            'done_days' => $entries->where('status', 'done')->count(),
            'missed_days' => $entries->where('status', 'missed')->count(),
        ];

        return response()->json(['data' => $item], 200);
    }
}
