<?php

namespace App\Http\Controllers\Api\List;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Models\ListModel;
use Illuminate\Http\JsonResponse;

class ProgressController extends Controller
{
    public function __invoke(string $id): JsonResponse
    {
        $list = ListModel::find($id);

        if (! $list) {
            throw new ResourceNotFoundException('List not found');
        }

        $total = $list->total_item_count ?? 0;
        $completed = $list->total_completed_count ?? 0;

        return response()->json([
            'data' => [
                'total' => $total,
                'completed' => $completed,
                'pending' => $total - $completed,
                'percentage' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
            ],
        ], 200);
    }
}
