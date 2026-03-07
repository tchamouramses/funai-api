<?php

namespace App\Http\Controllers\Api\FlowAssistant;

use App\Http\Controllers\Controller;
use App\Models\ListModel;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreFlowMessageController extends Controller
{
    public function __invoke(Request $request, string $listId): JsonResponse
    {
        $validated = $request->validate([
            'role' => 'required|string|in:user,assistant',
            'content' => 'required|string',
        ]);

        $userId = (string) auth()->id();
        $list = ListModel::where('id', $listId)
            ->first();

        return response()->json($list);

        if (! $list) {
            return response()->json(['message' => 'List not found'], 404);
        }

        $message = Message::create([
            'conversation_id' => $listId,
            'role' => $validated['role'],
            'content' => $validated['content'],
        ]);

        return response()->json([
            'data' => [
                'message' => $message,
                'list_id' => $listId,
            ],
        ], 201);
    }
}
