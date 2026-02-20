<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpenAIService
{
    private string $apiKey;

    private string $baseUrl = 'https://api.openai.com/v1';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');

        if (! $this->apiKey) {
            throw new \Exception('OpenAI API key not configured');
        }
    }

    /**
     * Crée un client HTTP configuré pour l'API OpenAI
     */
    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiKey,
            'Content-Type' => 'application/json',
            'OpenAI-Beta' => 'assistants=v2',
        ])->timeout(60);
    }

    /**
     * Crée un nouvel assistant OpenAI
     *
     * @param  array  $config  Configuration de l'assistant
     * @return array Données de l'assistant créé
     *
     * @throws \Exception
     */
    public function createAssistant(array $config): array
    {
        $payload = [
            'model' => $config['model'] ?? 'gpt-4o',
            'name' => $config['name'] ?? 'Assistant manager',
        ];

        // Ajouter les champs optionnels seulement s'ils sont définis
        if (isset($config['description']) && $config['description']) {
            $payload['description'] = $config['description'];
        }

        if (isset($config['instructions']) && $config['instructions']) {
            $payload['instructions'] = $config['instructions'];
        }

        if (isset($config['tools']) && is_array($config['tools'])) {
            $payload['tools'] = $config['tools'];
        }

        if (isset($config['temperature'])) {
            $payload['temperature'] = (float) $config['temperature'];
        }

        $response = $this->client()->post("{$this->baseUrl}/assistants", $payload);

        if (! $response->successful()) {
            throw new \Exception(
                'Failed to create assistant: '.$response->body(),
                $response->status()
            );
        }

        return $response->json();
    }

    /**
     * Récupère un assistant par son ID
     *
     * @throws \Exception
     */
    public function getAssistant(string $assistantId): array
    {
        $response = $this->client()->get("{$this->baseUrl}/assistants/{$assistantId}");

        if (! $response->successful()) {
            throw new \Exception(
                'Failed to get assistant: '.$response->body(),
                $response->status()
            );
        }

        return $response->json();
    }

    /**
     * Modifie un assistant existant
     *
     * @throws \Exception
     */
    public function updateAssistant(string $assistantId, array $config): array
    {
        $response = $this->client()->post(
            "{$this->baseUrl}/assistants/{$assistantId}",
            $config
        );

        if (! $response->successful()) {
            throw new \Exception(
                'Failed to update assistant: '.$response->body(),
                $response->status()
            );
        }

        return $response->json();
    }

    /**
     * Supprime un assistant
     *
     * @throws \Exception
     */
    public function deleteAssistant(string $assistantId): bool
    {
        $response = $this->client()->delete("{$this->baseUrl}/assistants/{$assistantId}");

        if (! $response->successful()) {
            throw new \Exception(
                'Failed to delete assistant: '.$response->body(),
                $response->status()
            );
        }

        return true;
    }

    /**
     * Crée un nouveau thread
     *
     * @throws \Exception
     */
    public function createThread(): array
    {
        $response = $this->client()->post("{$this->baseUrl}/threads");

        if (! $response->successful()) {
            throw new \Exception(
                'Failed to create thread: '.$response->body(),
                $response->status()
            );
        }

        return $response->json();
    }

    /**
     * Ajoute un message à un thread
     *
     * @param  string  $role  (user ou assistant)
     *
     * @throws \Exception
     */
    public function addMessage(string $threadId, string $role, string $content): array
    {
        $response = $this->client()->post("{$this->baseUrl}/threads/{$threadId}/messages", [
            'role' => $role,
            'content' => $content,
        ]);

        if (! $response->successful()) {
            throw new \Exception(
                'Failed to add message: '.$response->body(),
                $response->status()
            );
        }

        return $response->json();
    }

    /**
     * Exécute un run sur un thread
     *
     * @throws \Exception
     */
    public function createRun(string $threadId, string $assistantId, array $additionalParams = []): array
    {
        $payload = array_merge([
            'assistant_id' => $assistantId,
        ], $additionalParams);

        $response = $this->client()->post("{$this->baseUrl}/threads/{$threadId}/runs", $payload);

        if (! $response->successful()) {
            throw new \Exception(
                'Failed to create run: '.$response->body(),
                $response->status()
            );
        }

        return $response->json();
    }

    /**
     * Récupère le statut d'un run
     *
     * @throws \Exception
     */
    public function getRun(string $threadId, string $runId): array
    {
        $response = $this->client()->get("{$this->baseUrl}/threads/{$threadId}/runs/{$runId}");

        if (! $response->successful()) {
            throw new \Exception(
                'Failed to get run: '.$response->body(),
                $response->status()
            );
        }

        return $response->json();
    }

    /**
     * Liste les messages d'un thread
     *
     * @throws \Exception
     */
    public function listMessages(string $threadId): array
    {
        $response = $this->client()->get("{$this->baseUrl}/threads/{$threadId}/messages");

        if (! $response->successful()) {
            throw new \Exception(
                'Failed to list messages: '.$response->body(),
                $response->status()
            );
        }

        return $response->json();
    }
}
