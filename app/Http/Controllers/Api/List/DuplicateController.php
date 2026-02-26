<?php

namespace App\Http\Controllers\Api\List;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\DuplicateListRequest;
use App\Models\ListModel;
use Illuminate\Http\JsonResponse;

class DuplicateController extends Controller
{
    public function __invoke(string $id, DuplicateListRequest $request): JsonResponse
    {
        $original = ListModel::find($id);

        if (! $original) {
            throw new ResourceNotFoundException('List not found');
        }

        $duplicate = ListModel::create([
            'user_id' => $original->user_id,
            'title' => $request->validated()['title'],
            'type' => $original->type,
            'description' => $original->description,
            'metadata' => $original->metadata,
            'depth' => 0,
            'children_count' => 0,
        ]);

        foreach ($original->items as $item) {
            $duplicate->items()->create([
                'content' => $item->content,
                'completed' => false,
                'order' => $item->order,
                'due_date' => $item->due_date,
                'metadata' => $item->metadata,
            ]);
        }

        $duplicate->updateCounters();

        return response()->json([
            'data' => $duplicate,
            'message' => 'List duplicated successfully',
        ], 201);
    }
}
