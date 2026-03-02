<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IndexBudgetController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = Budget::forUser(auth()->id())
            ->orderBy('created_at', 'desc');

        if ($request->has('list_id')) {
            $query->forList($request->input('list_id'));
        }

        if ($request->has('is_active')) {
            $isActive = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $isActive);
        }

        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }

        $budgets = $query->paginate($request->input('per_page', 20));

        return response()->json(['data' => $budgets], 200);
    }
}
