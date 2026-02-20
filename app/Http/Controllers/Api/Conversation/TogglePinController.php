<?php

namespace App\Http\Controllers\Api\Conversation;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;

class TogglePinController extends Controller
{
    public function __invoke(string $id): JsonResponse
    {
        $conversation = Conversation::find($id);

        if (! $conversation) {
            throw new ResourceNotFoundException('Conversation not found');
        }

        $conversation->pinned = ! ($conversation->pinned ?? false);
        $conversation->save();

        return response()->json([
            'data' => $conversation,
            'message' => 'Pin status toggled successfully',
        ], 200);
    }
}
