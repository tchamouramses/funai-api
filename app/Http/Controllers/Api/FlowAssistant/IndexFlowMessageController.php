<?php

namespace App\Http\Controllers\Api\FlowAssistant;

use App\Http\Controllers\Controller;
use App\Models\ListModel;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IndexFlowMessageController extends Controller
{
    /**
     * Get messages for a specific list's thread
     */
    public function show(string $listId): JsonResponse
    {
        $userId = auth()->id();
        $list = ListModel::where('id', $listId)
            ->where('user_id', $userId)
            ->first();

        if (! $list) {
            return response()->json(['message' => 'List not found'], 404);
        }

        $messages = Message::where('conversation_id', $listId)
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        return response()->json([
            'data' => [
                'list_id' => $listId,
                'flow_type' => $list->type ?? 'todo',
                'messages' => $messages,
            ],
        ]);
    }

    /**
     * Get unified messages across all user's flow lists
     */
    public function index(): JsonResponse
    {
        $userId = (string) auth()->id();

        // Get all root lists (depth 0) for this user
        $listIds = ListModel::where('user_id', $userId)
            ->where('depth', 0)
            ->pluck('id')
            ->toArray();

        $messages = Message::whereIn('conversation_id', $listIds)
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json([
            'data' => [
                'messages' => $messages,
            ],
        ]);
    }
}
