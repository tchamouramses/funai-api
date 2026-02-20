<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class ModelException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'Model error',
            'message' => $this->message ?: 'An error occurred with the model',
        ], 500);
    }
}
