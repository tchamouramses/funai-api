<?php

namespace App\Http\Controllers\Api\List;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Models\ListModel;
use Illuminate\Http\JsonResponse;

class TogglePinController extends Controller
{
    public function __invoke(string $id): JsonResponse
    {
        $list = ListModel::find($id);

        if (! $list) {
            throw new ResourceNotFoundException('List not found');
        }

        $list->pinned = ! ($list->pinned ?? false);
        $list->save();

        return response()->json([
            'data' => $list,
            'message' => 'Pin status toggled successfully',
        ], 200);
    }
}
