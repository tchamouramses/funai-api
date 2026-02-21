<?php

namespace App\Http\Controllers\Api\Assistant;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Services\ChatAssistantConversationService;
use Illuminate\Http\JsonResponse;

class IndexChatAssistantMessageController extends Controller
{
    public function __invoke(
        ChatAssistantConversationService $chatAssistantConversationService
    ): JsonResponse {
        $conversation = $chatAssistantConversationService->getOrCreateForUser((string) auth()->id());

        $messages = Message::where('conversation_id', $conversation->id)
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        return response()->json([
            'data' => [
                'conversation' => $conversation,
                'messages' => $messages,
            ],
        ], 200);
    }
}
