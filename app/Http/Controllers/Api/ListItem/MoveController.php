<?php

namespace App\Http\Controllers\Api\ListItem;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\MoveListItemRequest;
use App\Models\ListItem;
use App\Models\ListModel;
use Illuminate\Http\JsonResponse;

class MoveController extends Controller
{
    public function __invoke(string $id, MoveListItemRequest $request): JsonResponse
    {
        $item = ListItem::find($id);

        if (! $item) {
            throw new ResourceNotFoundException('Item not found');
        }

        $newList = ListModel::find($request->validated()['target_list_id']);

        if (! $newList) {
            throw new ResourceNotFoundException('Target list not found');
        }

        $oldList = $item->list;

        $item->update(['list_id' => (string) $newList->_id]);

        $oldList->decrement('total_item_count');
        if ($item->completed) {
            $oldList->decrement('total_completed_count');
        }

        $newList->increment('total_item_count');
        if ($item->completed) {
            $newList->increment('total_completed_count');
        }

        return response()->json([
            'data' => $item,
            'message' => 'Item moved successfully',
        ], 200);
    }
}
