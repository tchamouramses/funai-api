<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreCategoryRequest;
use App\Models\FinanceCategory;
use Illuminate\Http\JsonResponse;

class StoreCategoryController extends Controller
{
    public function __invoke(StoreCategoryRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = auth()->id();
        $validated['is_default'] = false;

        $category = FinanceCategory::create($validated);

        return response()->json([
            'data' => $category,
            'message' => 'Category created successfully',
        ], 201);
    }
}
