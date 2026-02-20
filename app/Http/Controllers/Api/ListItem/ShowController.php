<?php

namespace App\Http\Controllers\Api\ListItem;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Models\ListItem;
use Illuminate\Http\JsonResponse;

class ShowController extends Controller
{
    public function __invoke(string $id): JsonResponse
    {
        $item = ListItem::with('progressEntries')->find($id);

        if (! $item) {
            throw new ResourceNotFoundException('Item not found');
        }

        return response()->json(['data' => $item], 200);
    }
}
