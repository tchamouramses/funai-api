<?php

namespace App\Http\Controllers\Api\ListItem;

use App\Http\Controllers\Controller;
use App\Models\ListItem;
use Illuminate\Http\JsonResponse;

class IndexController extends Controller
{
    public function __invoke(string $listId): JsonResponse
    {
        $items = ListItem::where('list_id', $listId)
            ->orderBy('order', 'asc')
            ->paginate(10);

        return response()->json(['data' => $items], 200);
    }
}
