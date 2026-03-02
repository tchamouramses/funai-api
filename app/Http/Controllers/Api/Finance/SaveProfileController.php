<?php

namespace App\Http\Controllers\Api\Finance;

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
            throw new ResourceNotFoundException('Finance flow not found');
        }

        if ($list->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'currency' => 'required|string|max:5',
            'income_categories' => 'nullable|array',
            'expense_categories' => 'nullable|array',
        ]);

        $metadata = $list->metadata ?? [];
        $metadata['currency'] = $validated['currency'];
        $metadata['setupCompleted'] = true;

        if (isset($validated['income_categories'])) {
            $metadata['incomeCategories'] = $validated['income_categories'];
        }

        if (isset($validated['expense_categories'])) {
            $metadata['expenseCategories'] = $validated['expense_categories'];
        }

        $list->update(['metadata' => $metadata]);

        return response()->json([
            'data' => $list->fresh(),
            'message' => 'Finance profile saved successfully',
        ], 200);
    }
}
