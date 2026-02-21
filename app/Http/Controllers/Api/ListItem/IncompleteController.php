<?php

namespace App\Http\Controllers\Api\ListItem;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Models\ListItem;
use Illuminate\Http\JsonResponse;

class IncompleteController extends Controller
{
    public function __invoke(string $id): JsonResponse
    {
        $item = ListItem::find($id);

        if (! $item) {
            throw new ResourceNotFoundException('Item not found');
        }

        if ($item->completed) {
            $item->update([
                'completed' => false,
                'expired_notified_at' => null,
            ]);
            $item->list->decrement('total_completed_count');
        }

        return response()->json([
            'data' => $item,
            'message' => 'Item marked as incomplete',
        ], 200);
    }
}
