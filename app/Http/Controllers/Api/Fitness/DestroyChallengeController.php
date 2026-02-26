<?php

namespace App\Http\Controllers\Api\Fitness;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Models\ListModel;
use Illuminate\Http\JsonResponse;

class DestroyChallengeController extends Controller
{
    public function __invoke(string $listId, string $challengeId): JsonResponse
    {
        $list = ListModel::find($listId);

        if (! $list) {
            throw new ResourceNotFoundException('List not found');
        }

        if ($list->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $metadata = $list->metadata ?? [];
        $challenges = $metadata['challenges'] ?? [];

        $metadata['challenges'] = array_values(
            array_filter($challenges, fn ($c) => ($c['id'] ?? '') !== $challengeId)
        );

        $list->update(['metadata' => $metadata]);

        return response()->json([
            'data' => null,
            'message' => 'Challenge removed successfully',
        ], 200);
    }
}
