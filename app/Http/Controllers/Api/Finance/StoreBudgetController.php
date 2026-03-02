<?php

namespace App\Http\Controllers\Api\Finance;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreBudgetRequest;
use App\Models\Budget;
use App\Models\ListModel;
use Illuminate\Http\JsonResponse;

class StoreBudgetController extends Controller
{
    public function __invoke(StoreBudgetRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $list = ListModel::find($validated['list_id']);

        if (! $list) {
            throw new ResourceNotFoundException('Finance flow not found');
        }

        if ($list->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated['user_id'] = auth()->id();
        $validated['spent'] = 0;
        $validated['alerts_sent'] = [];
        $validated['is_active'] = true;

        if (! isset($validated['alert_thresholds'])) {
            $validated['alert_thresholds'] = [70, 90, 100];
        }

        $budget = Budget::create($validated);

        return response()->json([
            'data' => $budget,
            'message' => 'Budget created successfully',
        ], 201);
    }
}
