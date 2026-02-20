<?php

namespace App\Http\Controllers\Api\ListItem;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Models\ListItem;
use App\Models\ProgressEntry;
use Illuminate\Http\JsonResponse;

class DestroyController extends Controller
{
    public function __invoke(string $id): JsonResponse
    {
        $item = ListItem::find($id);

        if (! $item) {
            throw new ResourceNotFoundException('Item not found');
        }

        $list = $item->list;

        ProgressEntry::where('item_id', $id)->delete();

        if ($item->completed) {
            $list->decrement('total_completed_count');
        }
        $list->decrement('total_item_count');

        $item->delete();

        return response()->json(['message' => 'Item deleted successfully'], 200);
    }
}
