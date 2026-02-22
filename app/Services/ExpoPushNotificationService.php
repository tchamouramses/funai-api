<?php

namespace App\Services;

use App\Jobs\SendExpoPushNotifications;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpoPushNotificationService
{
    private string $endpoint = 'https://exp.host/--/api/v2/push/send';
    private int $chunkSize = 100;

    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): void
    {
        SendExpoPushNotifications::dispatch($tokens, $title, $body, $data);
    }

    public function sendToTokensNow(array $tokens, string $title, string $body, array $data = []): void
    {
        $validTokens = array_values(array_filter($tokens, function ($token) {
            return is_string($token)
                && preg_match('/^(ExponentPushToken|ExpoPushToken)\[.+\]$/', $token);
        }));
        if (empty($validTokens)) {
            Log::info('Expo push: No valid tokens');
            return;
        }

        $messages = array_map(function ($token) use ($title, $body, $data) {
            return [
                'to' => $token,
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'sound' => 'default',
                'priority' => 'high',
            ];
        }, $validTokens);

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $accessToken = config('services.expo.access_token');
        if ($accessToken) {
            $headers['Authorization'] = 'Bearer '.$accessToken;
        }

        foreach (array_chunk($messages, $this->chunkSize) as $chunk) {
            $response = Http::withHeaders($headers)
                ->timeout(20)
                ->post($this->endpoint, $chunk);

            if (! $response->successful()) {
                Log::error('Expo push HTTP error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                continue;
            }

            $responseData = $response->json();

            foreach ($responseData['data'] ?? [] as $index => $ticket) {
                if (($ticket['status'] ?? null) === 'ok') {
                    continue;
                }

                $token = $chunk[$index]['to'] ?? null;

                Log::warning('Expo push ticket error', [
                    'token' => $token,
                    'ticket' => $ticket,
                ]);
            }
        }
    }
}
