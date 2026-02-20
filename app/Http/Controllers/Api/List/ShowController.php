<?php

namespace App\Http\Controllers\Api\List;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Models\ListModel;
use Illuminate\Http\JsonResponse;

class ShowController extends Controller
{
    public function __invoke(string $id): JsonResponse
    {
        $list = ListModel::with('children')->find($id);

        if (! $list) {
            throw new ResourceNotFoundException('List not found');
        }

        return response()->json(['data' => $list], 200);
    }
}
