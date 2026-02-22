<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateNotificationSettingsRequest;
use App\Models\Profile;
use Illuminate\Http\JsonResponse;

class UpdateNotificationSettingsController extends Controller
{
    public function __invoke(UpdateNotificationSettingsRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $profile = Profile::where('email', $user->email)->first();
        if (! $profile) {
            $profile = Profile::create([
                'email' => $user->email,
                'full_name' => $user->name,
                'notification_settings' => [],
                'user_id' => (string) $user->id,
            ]);
        }

        $settings = (array) ($profile->notification_settings ?? []);

        if (array_key_exists('enabled', $validated)) {
            $settings['enabled'] = (bool) $validated['enabled'];
        }

        if (array_key_exists('default_reminder_delay', $validated)) {
            $settings['default_reminder_delay'] = (int) $validated['default_reminder_delay'];
        }

        $profile->notification_settings = $settings;
        $profile->save();

        return response()->json([
            'data' => [
                'notification_settings' => $settings,
            ],
            'message' => 'Notification settings updated',
        ], 200);
    }
}
