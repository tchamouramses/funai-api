<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $profile = Profile::where('email', $user->email)->first();
        $settings = (array) ($profile?->notification_settings ?? []);
        $settings['enabled'] = (bool) ($settings['enabled'] ?? true);
        $settings['default_reminder_delay'] = (int) ($settings['default_reminder_delay'] ?? 15);
        $settings['expo_push_tokens'] = array_values(array_filter((array) ($settings['expo_push_tokens'] ?? [])));

        return response()->json([
            'data' => [
                ...$user->toArray(),
                'profile' => $profile,
                'notification_settings' => $settings,
            ],
        ], 200);
    }
}
