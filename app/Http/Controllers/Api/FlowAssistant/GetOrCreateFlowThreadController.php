<?php

namespace App\Http\Controllers\Api\FlowAssistant;

use App\Http\Controllers\Controller;
use App\Models\ListModel;
use App\Models\SharedAssistant;
use App\Services\FlowAssistantConfigService;
use App\Services\OpenAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GetOrCreateFlowThreadController extends Controller
{
    public function __invoke(
        Request $request,
        string $listId,
        OpenAIService $openAIService,
        FlowAssistantConfigService $configService
    ): JsonResponse {
        $userId = auth()->id();
        $list = ListModel::where('id', $listId)
            ->where('user_id', $userId)
            ->first();

        if (! $list) {
            return response()->json(['message' => 'List not found'], 404);
        }

        $flowType = $list->type ?? 'todo';
        $assistantType = FlowAssistantConfigService::getAssistantType($flowType);

        // Get or create the shared assistant for this flow type
        $sharedAssistant = SharedAssistant::where('type', $assistantType)->first();

        if (! $sharedAssistant) {
            try {
                $config = $configService->buildConfig($flowType);
                $assistantData = $openAIService->createAssistant($config);

                $sharedAssistant = SharedAssistant::create([
                    'type' => $assistantType,
                    'sub_type' => null,
                    'assistant_id' => $assistantData['id'],
                ]);
            } catch (\Exception $e) {
                Log::error('Error creating flow assistant', [
                    'flow_type' => $flowType,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'message' => 'Error creating assistant',
                ], 500);
            }
        }

        // Get or create the thread for this list
        if (! $list->thread_id) {
            try {
                $threadData = $openAIService->createThread();
                $list->thread_id = $threadData['id'];
                $list->save();
            } catch (\Exception $e) {
                Log::error('Error creating thread', [
                    'list_id' => $listId,
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
                'thread_id' => $list->thread_id,
                'list_id' => $listId,
                'flow_type' => $flowType,
            ],
        ]);
    }
}
