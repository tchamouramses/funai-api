<?php

namespace App\Http\Controllers\Api\List;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreListRequest;
use App\Models\ListModel;
use Illuminate\Http\JsonResponse;

class StoreController extends Controller
{
    public function __invoke(StoreListRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = auth()->id();
        $depth = 0;

        if ($validated['parent_list_id'] ?? false) {
            $parent = ListModel::find($validated['parent_list_id']);
            if ($parent) {
                $depth = ($parent->depth ?? 0) + 1;
                $parent->increment('children_count');
            }
        }

        $validated['depth'] = $depth;
        $list = ListModel::create($validated);

        return response()->json([
            'data' => $list,
            'message' => 'List created successfully',
        ], 201);
    }
}
