<?php

namespace App\Http\Controllers\Api\ListItem;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Models\ListItem;
use Illuminate\Http\JsonResponse;

class CompleteController extends Controller
{
    public function __invoke(string $id): JsonResponse
    {
        $item = ListItem::find($id);

        if (! $item) {
            throw new ResourceNotFoundException('Item not found');
        }

        if (! $item->completed) {
            $item->update(['completed' => true]);
            $item->list->increment('total_completed_count');
        }

        return response()->json([
            'data' => $item,
            'message' => 'Item marked as complete',
        ], 200);
    }
}
