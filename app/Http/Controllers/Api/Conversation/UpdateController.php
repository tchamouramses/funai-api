<?php

namespace App\Http\Controllers\Api\Conversation;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateConversationRequest;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;

class UpdateController extends Controller
{
    public function __invoke(string $id, UpdateConversationRequest $request): JsonResponse
    {
        $conversation = Conversation::find($id);

        if (! $conversation) {
            throw new ResourceNotFoundException('Conversation not found');
        }

        $conversation->update($request->validated());

        return response()->json([
            'data' => $conversation,
            'message' => 'Conversation updated successfully',
        ], 200);
    }
}
