<?php

namespace App\Http\Controllers\Api\Finance;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Models\FinanceCategory;
use Illuminate\Http\JsonResponse;

class DestroyCategoryController extends Controller
{
    public function __invoke(string $id): JsonResponse
    {
        $category = FinanceCategory::find($id);

        if (! $category) {
            throw new ResourceNotFoundException('Category not found');
        }

        if ($category->is_default) {
            return response()->json(['message' => 'Cannot delete a default category'], 422);
        }

        if ($category->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $category->delete();

        return response()->json([
            'data' => null,
            'message' => 'Category deleted successfully',
        ], 200);
    }
}
