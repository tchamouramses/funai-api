<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class ValidationException extends Exception
{
    protected $errors;

    public function __construct(string $message = '', array $errors = [])
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'Validation failed',
            'message' => $this->message ?: 'The given data was invalid',
            'errors' => $this->errors,
        ], 422);
    }
}
