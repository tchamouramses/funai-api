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

class IndexFlowMessageController extends Controller
{
    /**
     * Get messages for a specific list's thread
     */
    public function show(string $listId): JsonResponse
    {
        $userId = auth()->id();
        $list = ListModel::where('id', $listId)
            ->where('user_id', $userId)
            ->first();

        if (! $list) {
            return response()->json(['message' => 'List not found'], 404);
        }

        $messages = Message::where('conversation_id', $listId)
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        return response()->json([
            'data' => [
                'list_id' => $listId,
                'flow_type' => $list->type ?? 'todo',
                'messages' => $messages,
            ],
        ]);
    }

    /**
     * Get unified messages across all user's flow lists
     */
    public function index(): JsonResponse
    {
        $userId = auth()->id();

        // Get all root lists (depth 0) for this user
        $listIds = ListModel::where('user_id', $userId)
            ->where('depth', 0)
            ->pluck('id')
            ->toArray();

        $messages = Message::whereIn('conversation_id', $listIds)
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json([
            'data' => [
                'messages' => $messages,
            ],
        ]);
    }

    /**
     * Get messages for the general flow assistant conversation
     */
    public function general(
        OpenAIService $openAIService,
        FlowAssistantConfigService $configService
    ): JsonResponse
    {
        $userId = (string) auth()->id();
        $conversation = Conversation::where('user_id', $userId)
            ->where('type', 'flow_general')
            ->first();

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
                Log::error('Error auto-initializing general flow thread on message index', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'message' => 'Unable to initialize general flow thread',
                ], 500);
            }
        }

        $messages = Message::where('conversation_id', (string) $conversation->_id)
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        return response()->json([
            'data' => [
                'conversation_id' => (string) $conversation->_id,
                'messages' => $messages,
            ],
        ]);
    }
}
