<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterPushTokenRequest;
use App\Models\Profile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PushTokenController extends Controller
{
    public function store(RegisterPushTokenRequest $request): JsonResponse
    {
        $user = Auth::user();

        Log::info("expo_token 1", ['token' => $request->validated('token'), 'user_id' => $user->id]);

        $profile = Profile::where('user_id', $user->id)->first();
        if (! $profile) {
            $profile = Profile::create([
                'email' => $user->email,
                'full_name' => $user->name,
                'notification_settings' => [],
                'user_id' => (string) $user->id,
            ]);
        }

        Log::info("expo_token 2", ['token' => $request->validated('token'), 'user_id' => $user->id]);

        $token = $request->validated('token');

        $settings = (array) ($profile->notification_settings ?? []);
        $tokens = array_values(array_filter((array) ($settings['expo_push_tokens'] ?? [])));

        if (! in_array($token, $tokens, true)) {
            $tokens[] = $token;
        }

        $settings['expo_push_tokens'] = array_values(array_unique($tokens));
        $profile->notification_settings = $settings;
        $profile->save();

        return response()->json([
            'data' => [
                'expo_push_tokens' => $settings['expo_push_tokens'],
            ],
            'message' => 'Push token registered',
        ], 200);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'token' => [
                'required',
                'string',
                'regex:/^(ExponentPushToken|ExpoPushToken)\[.+\]$/',
            ],
        ]);

        $user = $request->user();
        $profile = Profile::where('user_id', $user->id)->first();
        if (! $profile) {
            return response()->json([
                'data' => [
                    'expo_push_tokens' => [],
                ],
                'message' => 'No profile found',
            ], 200);
        }

        $token = $request->input('token');

        $settings = (array) ($profile->notification_settings ?? []);
        $tokens = array_values(array_filter((array) ($settings['expo_push_tokens'] ?? [])));
        $settings['expo_push_tokens'] = array_values(array_filter($tokens, fn ($registered) => $registered !== $token));

        $profile->notification_settings = $settings;
        $profile->save();

        return response()->json([
            'data' => [
                'expo_push_tokens' => $settings['expo_push_tokens'],
            ],
            'message' => 'Push token removed',
        ], 200);
    }
}
