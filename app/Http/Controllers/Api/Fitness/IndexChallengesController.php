<?php

namespace App\Http\Controllers\Api\Fitness;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Models\ListModel;
use Illuminate\Http\JsonResponse;

class IndexChallengesController extends Controller
{
    public function __invoke(string $listId): JsonResponse
    {
        $list = ListModel::find($listId);

        if (! $list) {
            throw new ResourceNotFoundException('List not found');
        }

        if ($list->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $challenges = $list->metadata['challenges'] ?? [];

        return response()->json(['data' => $challenges], 200);
    }
}
