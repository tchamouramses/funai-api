<?php

namespace App\Http\Controllers\Api\ListItem;

use App\Http\Controllers\Controller;
use App\Models\ListItem;
use Illuminate\Http\JsonResponse;

class GetProgressController extends Controller
{
    public function __invoke(string $id): JsonResponse
    {
        $item = ListItem::with('progressEntries')->find($id);

        if (! $item) {
            throw new ResourceNotFoundException('Item not found');
        }

        return response()->json([
            'data' => [
                'item_id' => $item->_id,
                'total_entries' => $item->progressEntries->count(),
                'entries' => $item->progressEntries,
                'latest' => $item->progressEntries->latest('date')->first(),
            ],
        ], 200);
    }
}
