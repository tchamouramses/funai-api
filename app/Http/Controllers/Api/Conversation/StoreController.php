<?php

namespace App\Http\Controllers\Api\Conversation;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConversationRequest;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;

class StoreController extends Controller
{
    public function __invoke(StoreConversationRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = auth()->id();

        $conversation = Conversation::create($validated);

        return response()->json([
            'data' => $conversation,
            'message' => 'Conversation created successfully',
        ], 201);
    }
}
