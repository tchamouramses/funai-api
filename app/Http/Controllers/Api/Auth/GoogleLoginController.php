<?php

namespace App\Http\Controllers\Api\Auth;

use App\Exceptions\UnauthorizedException;
use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleLoginController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'id_token' => 'required|string',
            'locale' => 'sometimes|string|in:en,fr,es',
        ]);

        $tokenInfo = Http::timeout(8)->get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $request->id_token,
        ]);

        if (! $tokenInfo->ok()) {
            throw new UnauthorizedException('Invalid Google token');
        }

        $payload = $tokenInfo->json();
        $email = strtolower((string) ($payload['email'] ?? ''));
        $name = (string) ($payload['name'] ?? '');
        $emailVerified = (string) ($payload['email_verified'] ?? 'false');
        $aud = (string) ($payload['aud'] ?? '');

        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new UnauthorizedException('Google account email is missing');
        }

        if ($emailVerified !== 'true') {
            throw new UnauthorizedException('Google account email is not verified');
        }

        $allowedAudiences = array_values(array_filter([
            config('services.google.android_client_id'),
            config('services.google.ios_client_id'),
            config('services.google.web_client_id'),
        ]));

        if (! empty($allowedAudiences) && ! in_array($aud, $allowedAudiences, true)) {
            throw new UnauthorizedException('Invalid Google token audience');
        }

        $locale = $request->input('locale', 'en');
        $fallbackName = Str::headline(Str::before($email, '@'));
        $displayName = $name !== '' ? $name : $fallbackName;

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $displayName,
                'locale' => $locale,
                'password' => Hash::make(Str::random(48)),
            ]
        );

        if (! $user->wasRecentlyCreated && (! $user->name || trim($user->name) === '')) {
            $user->name = $displayName;
            $user->save();
        }

        $profile = Profile::where('email', $email)->first();

        if (! $profile) {
            Profile::create([
                'email' => $user->email,
                'full_name' => $user->name,
                'locale' => $locale,
                'notification_settings' => [
                    'enabled' => true,
                    'default_reminder_delay' => 15,
                    'expo_push_tokens' => [],
                ],
                'user_id' => (string) $user->id,
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
            'message' => 'Google authentication successful',
        ], 200);
    }
}
