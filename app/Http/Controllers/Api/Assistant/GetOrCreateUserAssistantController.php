<?php

namespace App\Http\Controllers\Api\Assistant;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetOrCreateAssistantRequest;
use App\Models\SharedAssistant;
use App\Services\AssistantConfigService;
use App\Services\OpenAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class GetOrCreateUserAssistantController extends Controller
{
    public function __invoke(
        GetOrCreateAssistantRequest $request,
        OpenAIService $openAIService,
        AssistantConfigService $assistantConfigService
    ): JsonResponse {
        $assistantType = $request->input('assistant_type');
        $subType = $request->input('sub_type');

        // Chercher un assistant partagé existant du même type
        $sharedAssistant = SharedAssistant::where('type', $assistantType)
            ->where('sub_type', $subType)
            ->first();

        // Si l'assistant existe, retourner son ID
        if ($sharedAssistant) {
            return response()->json([
                'data' => [
                    'assistant_id' => $sharedAssistant->assistant_id,
                    'is_new' => false,
                ],
            ], 200);
        }

        // Sinon, créer un nouvel assistant via l'API OpenAI
        try {
            $config = $assistantConfigService->buildConfig($assistantType, $subType);
            $assistantData = $openAIService->createAssistant($config);
            $assistantId = $assistantData['id'];

            // Sauvegarder l'assistant partagé dans la base de données
            $sharedAssistant = SharedAssistant::create([
                'type' => $assistantType,
                'sub_type' => $subType,
                'assistant_id' => $assistantId,
            ]);

            return response()->json([
                'data' => [
                    'assistant_id' => $assistantId,
                    'is_new' => true,
                ],
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => 'Unsupported assistant type',
                'error' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error("Error creating assistant", ['exception' => $e]);
            return response()->json([
                'message' => 'Error creating assistant',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
