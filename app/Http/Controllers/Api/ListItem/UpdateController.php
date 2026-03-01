<?php

namespace App\Http\Controllers\Api\ListItem;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateListItemRequest;
use App\Models\ListItem;
use App\Services\RecurringTaskService;
use Illuminate\Http\JsonResponse;

class UpdateController extends Controller
{
    public function __invoke(
        string $id,
        UpdateListItemRequest $request,
        RecurringTaskService $recurringTaskService
    ): JsonResponse
    {
        $item = ListItem::find($id);

        if (! $item) {
            throw new ResourceNotFoundException('Item not found');
        }

        $validated = $request->validated();
        $wasCompleted = $item->completed;

        if (array_key_exists('due_date', $validated)) {
            $validated['reminder_notified_at'] = null;
            $validated['expired_notified_at'] = null;
        }

        if (array_key_exists('completed', $validated) && $validated['completed'] === false) {
            $validated['expired_notified_at'] = null;
            $validated['missed_at'] = null;
        }

        $markAsCompleted = array_key_exists('completed', $validated)
            && $validated['completed'] === true
            && ! $wasCompleted;

        if ($markAsCompleted) {
            unset($validated['completed']);
        }

        $item->update($validated);

        if ($markAsCompleted) {
            $item->update([
                'completed' => true,
                'missed_at' => null,
            ]);

            $list = $item->list;
            $list?->increment('total_completed_count');

            $recurringTaskService->recordRecurrenceStatus($item, 'done');
            $recurringTaskService->cloneNextOccurrenceIfPossible($item, $list);
        } elseif ($wasCompleted && ! $item->completed) {
            $list = $item->list;
            $list?->decrement('total_completed_count');
        }

        return response()->json([
            'data' => $item,
            'message' => 'Item updated successfully',
        ], 200);
    }
}
