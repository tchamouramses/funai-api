<?php

namespace App\Http\Controllers\Api\Finance;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Models\Budget;
use Illuminate\Http\JsonResponse;

class DestroyBudgetController extends Controller
{
    public function __invoke(string $id): JsonResponse
    {
        $budget = Budget::find($id);

        if (! $budget) {
            throw new ResourceNotFoundException('Budget not found');
        }

        if ($budget->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $budget->delete();

        return response()->json([
            'data' => null,
            'message' => 'Budget deleted successfully',
        ], 200);
    }
}
