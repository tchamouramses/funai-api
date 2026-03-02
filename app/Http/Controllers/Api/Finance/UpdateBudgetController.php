<?php

namespace App\Http\Controllers\Api\Finance;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\UpdateBudgetRequest;
use App\Models\Budget;
use Illuminate\Http\JsonResponse;

class UpdateBudgetController extends Controller
{
    public function __invoke(UpdateBudgetRequest $request, string $id): JsonResponse
    {
        $budget = Budget::find($id);

        if (! $budget) {
            throw new ResourceNotFoundException('Budget not found');
        }

        if ($budget->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validated();

        // If amount changed, reset alerts_sent so they can fire again
        if (isset($validated['amount']) && $validated['amount'] !== $budget->amount) {
            $validated['alerts_sent'] = [];
        }

        $budget->update($validated);

        return response()->json([
            'data' => $budget->fresh(),
            'message' => 'Budget updated successfully',
        ], 200);
    }
}
