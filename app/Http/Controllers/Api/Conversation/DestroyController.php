<?php

namespace App\Http\Controllers\Api\Conversation;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;

class DestroyController extends Controller
{
    public function __invoke(string $id): JsonResponse
    {
        $conversation = Conversation::find($id);

        if (! $conversation) {
            throw new ResourceNotFoundException('Conversation not found');
        }

        $conversation->messages()->delete();
        $conversation->delete();

        return response()->json(['message' => 'Conversation deleted successfully'], 200);
    }
}
