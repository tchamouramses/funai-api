<?php

namespace App\Http\Controllers\Api\Assistant;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssistantMessageRequest;
use App\Models\Message;
use App\Services\ChatAssistantConversationService;
use Illuminate\Http\JsonResponse;

class StoreChatAssistantMessageController extends Controller
{
    public function __invoke(
        StoreAssistantMessageRequest $request,
        ChatAssistantConversationService $chatAssistantConversationService
    ): JsonResponse {
        $conversation = $chatAssistantConversationService->getOrCreateForUser((string) auth()->id());
        $validated = $request->validated();

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'role' => $validated['role'],
            'content' => $validated['content'],
        ]);

        $conversation->touch();

        return response()->json([
            'data' => [
                'conversation_id' => $conversation->id,
                'message' => $message,
            ],
            'message' => 'Assistant message created successfully',
        ], 201);
    }
}
