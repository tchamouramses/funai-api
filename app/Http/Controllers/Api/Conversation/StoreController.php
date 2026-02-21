<?php

namespace App\Http\Controllers\Api\Conversation;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConversationRequest;
use App\Models\Conversation;
use App\Services\ChatAssistantConversationService;
use Illuminate\Http\JsonResponse;

class StoreController extends Controller
{
    public function __invoke(
        StoreConversationRequest $request,
        ChatAssistantConversationService $chatAssistantConversationService
    ): JsonResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = auth()->id();

        if (($validated['type'] ?? null) === 'chat_assistant') {
            $conversation = $chatAssistantConversationService->getOrCreateForUser((string) auth()->id());

            if (! $conversation->assistant_id && ! empty($validated['assistant_id'])) {
                $conversation->update([
                    'assistant_id' => $validated['assistant_id'],
                ]);
            }

            return response()->json([
                'data' => $conversation,
                'message' => $conversation->wasRecentlyCreated
                    ? 'Conversation created successfully'
                    : 'Conversation already exists for chat assistant',
            ], $conversation->wasRecentlyCreated ? 201 : 200);
        }

        $conversation = Conversation::create($validated);

        return response()->json([
            'data' => $conversation,
            'message' => 'Conversation created successfully',
        ], 201);
    }
}
