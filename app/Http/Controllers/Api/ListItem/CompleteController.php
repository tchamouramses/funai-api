<?php

namespace App\Http\Controllers\Api\ListItem;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Models\ListItem;
use App\Services\RecurringTaskService;
use Illuminate\Http\JsonResponse;

class CompleteController extends Controller
{
    public function __invoke(string $id, RecurringTaskService $recurringTaskService): JsonResponse
    {
        $item = ListItem::find($id);

        if (! $item) {
            throw new ResourceNotFoundException('Item not found');
        }

        if (! $item->completed) {
            $item->update([
                'completed' => true,
                'missed_at' => null,
                'completed_at' => now(),
            ]);

            $item->list?->increment('total_completed_count');

            $recurringTaskService->recordRecurrenceStatus($item, 'done');
            $recurringTaskService->cloneNextOccurrenceIfPossible($item, $item->list);
        }

        return response()->json([
            'data' => $item,
            'message' => 'Item marked as complete',
        ], 200);
    }
}
