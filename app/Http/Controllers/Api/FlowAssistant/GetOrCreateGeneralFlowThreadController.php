<?php

namespace App\Http\Controllers\Api\FlowAssistant;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\SharedAssistant;
use App\Services\FlowAssistantConfigService;
use App\Services\OpenAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class GetOrCreateGeneralFlowThreadController extends Controller
{
    public function __invoke(
        OpenAIService $openAIService,
        FlowAssistantConfigService $configService
    ): JsonResponse {
        $userId = auth()->id();

        // Get or create the shared "flow_general" assistant
        $sharedAssistant = SharedAssistant::where('type', 'flow_general')->first();

        if (! $sharedAssistant) {
            try {
                $config = $configService->buildGeneralConfig();
                $assistantData = $openAIService->createAssistant($config);

                $sharedAssistant = SharedAssistant::create([
                    'type' => 'flow_general',
                    'sub_type' => null,
                    'assistant_id' => $assistantData['id'],
                ]);
            } catch (\Exception $e) {
                Log::error('Error creating general flow assistant', [
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'message' => 'Error creating assistant',
                ], 500);
            }
        }

        // Get or create a conversation for the general flow thread
        $conversation = Conversation::where('user_id', $userId)
            ->where('type', 'flow_general')
            ->first();

        if (! $conversation) {
            try {
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
                Log::error('Error creating general flow thread', [
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'message' => 'Error creating thread',
                ], 500);
            }
        }

        return response()->json([
            'data' => [
                'assistant_id' => $sharedAssistant->assistant_id,
                'thread_id' => $conversation->thread_id,
                'conversation_id' => $conversation->id,
            ],
        ]);
    }
}
