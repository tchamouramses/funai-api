<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'locale' => 'sometimes|string|in:en,fr,es',
        ]);

        $locale = $request->input('locale', 'en');

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'locale' => $locale,
            'password' => Hash::make($request->password),
        ]);

        Profile::create([
            'email' => $user->email,
            'full_name' => $user->name,
            'locale' => $locale,
            'notification_settings' => [
                'enabled' => true,
                'default_reminder_delay' => 15,
                'expo_push_tokens' => [],
            ],
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
            'message' => 'User registered successfully',
        ], 201);
    }
}
