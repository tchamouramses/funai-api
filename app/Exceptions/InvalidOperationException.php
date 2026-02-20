<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InvalidOperationException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'Invalid operation',
            'message' => $this->message ?: 'The operation cannot be performed',
        ], 409);
    }
}
