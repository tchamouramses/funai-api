<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMessageRequest;
use App\Models\Message;
use Illuminate\Http\JsonResponse;

class StoreMessageController extends Controller
{
    public function __invoke(StoreMessageRequest $request): JsonResponse
    {
        $message = Message::create($request->validated());

        return response()->json([
            'data' => $message,
            'message' => 'Message created successfully',
        ], 201);
    }
}
