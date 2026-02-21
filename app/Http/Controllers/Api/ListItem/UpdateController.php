<?php

namespace App\Http\Controllers\Api\ListItem;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateListItemRequest;
use App\Models\ListItem;
use Illuminate\Http\JsonResponse;

class UpdateController extends Controller
{
    public function __invoke(string $id, UpdateListItemRequest $request): JsonResponse
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
        }

        $item->update($validated);

        if (! $wasCompleted && $item->completed) {
            $list = $item->list;
            $list->increment('total_completed_count');
        } elseif ($wasCompleted && ! $item->completed) {
            $list = $item->list;
            $list->decrement('total_completed_count');
        }

        return response()->json([
            'data' => $item,
            'message' => 'Item updated successfully',
        ], 200);
    }
}
