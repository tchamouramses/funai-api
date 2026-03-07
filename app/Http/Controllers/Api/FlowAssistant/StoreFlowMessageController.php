<?php

namespace App\Http\Controllers\Api\FlowAssistant;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\ListModel;
use App\Models\Message;
use App\Models\SharedAssistant;
use App\Services\FlowAssistantConfigService;
use App\Services\OpenAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StoreFlowMessageController extends Controller
{
    public function __invoke(Request $request, string $listId): JsonResponse
    {
        $validated = $request->validate([
            'role' => 'required|string|in:user,assistant',
            'content' => 'required|string',
        ]);

        $userId = auth()->id();
        $list = ListModel::where('id', $listId)
            ->where('user_id', $userId)
            ->first();

        if (! $list) {
            return response()->json(['message' => 'List not found'], 404);
        }

        $message = Message::create([
            'conversation_id' => $listId,
            'role' => $validated['role'],
            'content' => $validated['content'],
            'type' => 'flow',
        ]);

        return response()->json([
            'data' => [
                'message' => $message,
                'list_id' => $listId,
            ],
        ], 201);
    }

    public function storeGeneral(
        Request $request,
        OpenAIService $openAIService,
        FlowAssistantConfigService $configService
    ): JsonResponse
    {
        $validated = $request->validate([
            'role' => 'required|string|in:user,assistant',
            'content' => 'required|string',
        ]);

        $userId = auth()->id();
        $conversation = Conversation::where('user_id', $userId)
            ->where('type', 'flow_general')
            ->first();

        // Self-heal: if general thread does not exist yet, create it now.
        if (! $conversation) {
            try {
                $sharedAssistant = SharedAssistant::where('type', 'flow_general')->first();

                if (! $sharedAssistant) {
                    $config = $configService->buildGeneralConfig();
                    $assistantData = $openAIService->createAssistant($config);

                    $sharedAssistant = SharedAssistant::create([
                        'type' => 'flow_general',
                        'sub_type' => null,
                        'assistant_id' => $assistantData['id'],
                    ]);
                }

                $threadData = $openAIService->createThread();

                $conversation = Conversation::create([
                    'user_id' => $userId,
                    'title' => 'Flow General Assistant',
                    'type' => 'flow_general',
                    'thread_id' => $threadData['id'],
                    'assistant_id' => $sharedAssistant->assistant_id,
                    'pinned' => false,
                ]);
            } catch (\Exception $e) {
                Log::error('Error auto-initializing general flow thread', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'message' => 'Unable to initialize general flow thread',
                ], 500);
            }
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'role' => $validated['role'],
            'content' => $validated['content'],
            'type' => 'flow',
        ]);

        $conversation->touch();

        return response()->json([
            'data' => [
                'message' => $message,
                'conversation_id' => $conversation->id,
            ],
        ], 201);
    }
}
