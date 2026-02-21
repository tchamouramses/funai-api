<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UpdateProfileController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'locale' => ['sometimes', Rule::in('en', 'fr', 'es')],
        ]);

        $user = $request->user();

        if (isset($validated['locale'])) {
            $user->locale = $validated['locale'];
            $user->save();
        }

        return response()->json([
            'data' => $user,
            'message' => 'Profile updated successfully',
        ], 200);
    }
}
