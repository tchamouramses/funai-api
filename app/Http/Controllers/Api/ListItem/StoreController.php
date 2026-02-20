<?php

namespace App\Http\Controllers\Api\ListItem;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreListItemRequest;
use App\Models\ListItem;
use App\Models\ListModel;
use Illuminate\Http\JsonResponse;

class StoreController extends Controller
{
    public function __invoke(StoreListItemRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $list = ListModel::find($validated['list_id']);
        if (! $list) {
            throw new ResourceNotFoundException('List not found');
        }

        $item = ListItem::create($validated);
        $list->increment('total_item_count');

        return response()->json([
            'data' => $item,
            'message' => 'Item created successfully',
        ], 201);
    }
}
