<?php

namespace App\Http\Controllers\Api\Conversation;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;

class ShowController extends Controller
{
    public function __invoke(string $id): JsonResponse
    {
        $conversation = Conversation::with('messages')->find($id);

        if (! $conversation) {
            throw new ResourceNotFoundException('Conversation not found');
        }

        return response()->json(['data' => $conversation], 200);
    }
}
