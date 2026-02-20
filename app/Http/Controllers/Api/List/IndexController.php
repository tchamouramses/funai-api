<?php

namespace App\Http\Controllers\Api\List;

use App\Http\Controllers\Controller;
use App\Models\ListModel;
use Illuminate\Http\JsonResponse;

class IndexController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $lists = ListModel::where('user_id', auth()->id())
            ->whereNull('parent_list_id')
            ->orderBy('pinned', 'desc')
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return response()->json(['data' => $lists], 200);
    }
}
