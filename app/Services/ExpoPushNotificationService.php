<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpoPushNotificationService
{
    private string $endpoint = 'https://exp.host/--/api/v2/push/send';

    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): void
    {
        $validTokens = array_values(array_filter($tokens, function ($token) {
            return is_string($token)
                && preg_match('/^(ExponentPushToken|ExpoPushToken)\[.+\]$/', $token);
        }));

        if (empty($validTokens)) {
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

        $response = Http::withHeaders($headers)
            ->timeout(20)
            ->post($this->endpoint, $messages);

        if (! $response->successful()) {
            Log::error('Expo push send failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }
}
