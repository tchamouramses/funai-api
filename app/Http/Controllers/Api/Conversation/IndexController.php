<?php

namespace App\Http\Controllers\Api\Conversation;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;

class IndexController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $conversations = Conversation::where('user_id', auth()->id())
            ->whereNot('type', 'chat_assistant')
            ->orderBy('pinned', 'desc')
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return response()->json(['data' => $conversations], 200);
    }
}
