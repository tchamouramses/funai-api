<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class ResourceNotFoundException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'Resource not found',
            'message' => $this->message ?: 'The requested resource does not exist',
        ], 404);
    }
}
