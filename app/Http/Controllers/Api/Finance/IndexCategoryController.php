<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IndexCategoryController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = FinanceCategory::forUser(auth()->id());

        if ($request->has('type')) {
            $query->forType($request->input('type'));
        }

        $categories = $query->orderBy('is_default', 'desc')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json(['data' => $categories], 200);
    }
}
