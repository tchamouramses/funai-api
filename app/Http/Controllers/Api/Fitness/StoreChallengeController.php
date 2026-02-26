<?php

namespace App\Http\Controllers\Api\Fitness;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChallengeRequest;
use App\Models\ListModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class StoreChallengeController extends Controller
{
    public function __invoke(StoreChallengeRequest $request, string $listId): JsonResponse
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

        // Check max active challenges
        $activeCount = collect($challenges)->where('active', true)->whereNull('completedAt')->count();

        if ($activeCount >= 5) {
            return response()->json([
                'message' => 'Maximum 5 active challenges allowed',
            ], 422);
        }

        $challenge = [
            'id' => 'challenge_'.Str::random(8),
            'type' => $request->input('type'),
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'target' => $request->input('target'),
            'current' => 0,
            'startedAt' => now()->toISOString(),
            'completedAt' => null,
            'active' => true,
        ];

        $challenges[] = $challenge;
        $metadata['challenges'] = $challenges;
        $list->update(['metadata' => $metadata]);

        return response()->json([
            'data' => $challenge,
            'message' => 'Challenge created successfully',
        ], 201);
    }
}
