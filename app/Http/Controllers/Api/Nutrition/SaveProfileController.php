<?php

namespace App\Http\Controllers\Api\Nutrition;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Models\ListModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaveProfileController extends Controller
{
    public function __invoke(Request $request, string $listId): JsonResponse
    {
        $list = ListModel::find($listId);

        if (! $list) {
            throw new ResourceNotFoundException('Nutrition flow not found');
        }

        if ($list->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'daily_calories' => 'required|integer|min:500|max:10000',
            'goal' => 'required|string|in:lose_weight,gain_muscle,maintain,eat_healthy',
            'protein_target' => 'nullable|integer|min:0|max:500',
            'carbs_target' => 'nullable|integer|min:0|max:1000',
            'fat_target' => 'nullable|integer|min:0|max:500',
        ]);

        $metadata = $list->metadata ?? [];
        $metadata['setupCompleted'] = true;
        $metadata['dailyCalorieTarget'] = $validated['daily_calories'];
        $metadata['nutritionProfile'] = [
            'goal' => $validated['goal'],
            'dailyCalories' => $validated['daily_calories'],
            'proteinTarget' => $validated['protein_target'] ?? null,
            'carbsTarget' => $validated['carbs_target'] ?? null,
            'fatTarget' => $validated['fat_target'] ?? null,
        ];

        $list->update(['metadata' => $metadata]);

        return response()->json([
            'data' => $list->fresh(),
            'message' => 'Nutrition profile saved successfully',
        ], 200);
    }
}
