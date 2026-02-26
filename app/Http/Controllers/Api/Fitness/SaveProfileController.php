<?php

namespace App\Http\Controllers\Api\Fitness;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveFitnessProfileRequest;
use App\Models\ListModel;
use Illuminate\Http\JsonResponse;

class SaveProfileController extends Controller
{
    public function __invoke(SaveFitnessProfileRequest $request, string $listId): JsonResponse
    {
        $list = ListModel::find($listId);

        if (! $list) {
            throw new ResourceNotFoundException('List not found');
        }

        if ($list->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $metadata = $list->metadata ?? [];
        $metadata['fitnessProfile'] = $request->input('profile');
        $metadata['setupCompleted'] = true;
        $metadata['challenges'] = $metadata['challenges'] ?? [];

        $list->update(['metadata' => $metadata]);

        return response()->json([
            'data' => $list->fresh(),
            'message' => 'Fitness profile saved successfully',
        ], 200);
    }
}
