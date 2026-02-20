<?php

namespace App\Http\Controllers\Api\List;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateListRequest;
use App\Models\ListModel;
use Illuminate\Http\JsonResponse;

class UpdateController extends Controller
{
    public function __invoke(string $id, UpdateListRequest $request): JsonResponse
    {
        $list = ListModel::find($id);

        if (! $list) {
            throw new ResourceNotFoundException('List not found');
        }

        $list->update($request->validated());

        return response()->json([
            'data' => $list,
            'message' => 'List updated successfully',
        ], 200);
    }
}
