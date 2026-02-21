<?php

use App\Exceptions\InvalidOperationException;
use App\Exceptions\ModelException;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            HandleCors::class,
        ]);

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ResourceNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 404,
            ], 404);
        });

        $exceptions->render(function (UnauthorizedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 403,
            ], 403);
        });

        $exceptions->render(function (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors ?? [],
                'status' => 422,
            ], 422);
        });

        $exceptions->render(function (InvalidOperationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 409,
            ], 409);
        });

        $exceptions->render(function (ModelException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 500,
            ], 500);
        });
    })->create();
