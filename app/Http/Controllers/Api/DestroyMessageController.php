<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\JsonResponse;

class DestroyMessageController extends Controller
{
    public function __invoke(string $id): JsonResponse
    {
        $message = Message::find($id);

        if (! $message) {
            throw new ResourceNotFoundException('Message not found');
        }

        $message->delete();

        return response()->json(['message' => 'Message deleted successfully'], 200);
    }
}
