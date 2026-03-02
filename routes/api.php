<?php

use Illuminate\Support\Facades\Route;

// Auth Routes (public)
Route::post('/auth/register', \App\Http\Controllers\Api\Auth\RegisterController::class);
Route::post('/auth/login', \App\Http\Controllers\Api\Auth\LoginController::class);

// Auth Routes (protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', \App\Http\Controllers\Api\Auth\LogoutController::class);
    Route::post('/auth/refresh', \App\Http\Controllers\Api\Auth\RefreshController::class);
    Route::get('/auth/me', \App\Http\Controllers\Api\Auth\MeController::class);
    Route::put('/auth/profile', \App\Http\Controllers\Api\Auth\UpdateProfileController::class);
    Route::put('/auth/notification-settings', \App\Http\Controllers\Api\Auth\UpdateNotificationSettingsController::class);
    Route::post('/auth/push-token', [\App\Http\Controllers\Api\Auth\PushTokenController::class, 'store']);
    Route::delete('/auth/push-token', [\App\Http\Controllers\Api\Auth\PushTokenController::class, 'destroy']);

    // Conversation Routes
    Route::prefix('conversations')->group(function () {
        Route::post('/', \App\Http\Controllers\Api\Conversation\StoreController::class);
        Route::get('/{id}', \App\Http\Controllers\Api\Conversation\ShowController::class);
        Route::put('/{id}', \App\Http\Controllers\Api\Conversation\UpdateController::class);
        Route::delete('/{id}', \App\Http\Controllers\Api\Conversation\DestroyController::class);
        Route::post('/{id}/toggle-pin', \App\Http\Controllers\Api\Conversation\TogglePinController::class);
        Route::get('', \App\Http\Controllers\Api\Conversation\IndexController::class);
    });

    // Message Routes
    Route::prefix('messages')->group(function () {
        Route::post('/', \App\Http\Controllers\Api\StoreMessageController::class);
        Route::delete('/{id}', \App\Http\Controllers\Api\DestroyMessageController::class);
        Route::get('/conversation/{conversationId}', \App\Http\Controllers\Api\IndexMessageController::class);
    });

    // Assistant Routes
    Route::prefix('assistants')->group(function () {
        Route::post('/user', \App\Http\Controllers\Api\Assistant\GetOrCreateUserAssistantController::class);
        Route::get('/chat/messages', \App\Http\Controllers\Api\Assistant\IndexChatAssistantMessageController::class);
        Route::post('/chat/messages', \App\Http\Controllers\Api\Assistant\StoreChatAssistantMessageController::class);
    });

    Route::prefix('lists')->group(function () {
        Route::post('/', \App\Http\Controllers\Api\List\StoreController::class);
        Route::get('/{id}', \App\Http\Controllers\Api\List\ShowController::class);
        Route::put('/{id}', \App\Http\Controllers\Api\List\UpdateController::class);
        Route::delete('/{id}', \App\Http\Controllers\Api\List\DestroyController::class);
        Route::post('/{id}/toggle-pin', \App\Http\Controllers\Api\List\TogglePinController::class);
        Route::get('/{id}/progress', \App\Http\Controllers\Api\List\ProgressController::class);
        Route::post('/{id}/duplicate', \App\Http\Controllers\Api\List\DuplicateController::class);
        Route::get('/', \App\Http\Controllers\Api\List\IndexController::class);
        Route::get('/{listId}/items', \App\Http\Controllers\Api\ListItem\IndexController::class);
    });

    Route::prefix('list-items')->group(function () {
        Route::post('/', \App\Http\Controllers\Api\ListItem\StoreController::class);
        Route::get('/{id}', \App\Http\Controllers\Api\ListItem\ShowController::class);
        Route::put('/{id}', \App\Http\Controllers\Api\ListItem\UpdateController::class);
        Route::delete('/{id}', \App\Http\Controllers\Api\ListItem\DestroyController::class);
        Route::post('/{id}/move', \App\Http\Controllers\Api\ListItem\MoveController::class);
        Route::post('/{id}/log-progress', \App\Http\Controllers\Api\ListItem\LogProgressController::class);
        Route::get('/{id}/progress', \App\Http\Controllers\Api\ListItem\GetProgressController::class);
        Route::post('/{id}/complete', \App\Http\Controllers\Api\ListItem\CompleteController::class);
        Route::post('/{id}/incomplete', \App\Http\Controllers\Api\ListItem\IncompleteController::class);
    });

    // Fitness Routes
    Route::prefix('fitness')->group(function () {
        Route::post('/generate-program', \App\Http\Controllers\Api\Fitness\GenerateProgramController::class);
        Route::post('/{listId}/profile', \App\Http\Controllers\Api\Fitness\SaveProfileController::class);
        Route::get('/{listId}/dashboard', \App\Http\Controllers\Api\Fitness\DashboardController::class);
        Route::get('/challenge-templates', \App\Http\Controllers\Api\Fitness\ChallengeTemplatesController::class);
        Route::get('/{listId}/challenges', \App\Http\Controllers\Api\Fitness\IndexChallengesController::class);
        Route::post('/{listId}/challenges', \App\Http\Controllers\Api\Fitness\StoreChallengeController::class);
        Route::delete('/{listId}/challenges/{challengeId}', \App\Http\Controllers\Api\Fitness\DestroyChallengeController::class);
    });

    // Finance Routes
    Route::prefix('finance')->group(function () {
        // Defaults & profile
        Route::get('/defaults', \App\Http\Controllers\Api\Finance\DefaultsController::class);
        Route::post('/{listId}/profile', \App\Http\Controllers\Api\Finance\SaveProfileController::class);

        // Dashboard
        Route::get('/{listId}/dashboard', \App\Http\Controllers\Api\Finance\FinanceDashboardController::class);

        // Transactions
        Route::prefix('transactions')->group(function () {
            Route::post('/', \App\Http\Controllers\Api\Finance\StoreTransactionController::class);
            Route::get('/', \App\Http\Controllers\Api\Finance\IndexTransactionController::class);
            Route::get('/{id}', \App\Http\Controllers\Api\Finance\ShowTransactionController::class);
            Route::put('/{id}', \App\Http\Controllers\Api\Finance\UpdateTransactionController::class);
            Route::delete('/{id}', \App\Http\Controllers\Api\Finance\DestroyTransactionController::class);
        });

        // Budgets
        Route::prefix('budgets')->group(function () {
            Route::post('/', \App\Http\Controllers\Api\Finance\StoreBudgetController::class);
            Route::get('/', \App\Http\Controllers\Api\Finance\IndexBudgetController::class);
            Route::put('/{id}', \App\Http\Controllers\Api\Finance\UpdateBudgetController::class);
            Route::delete('/{id}', \App\Http\Controllers\Api\Finance\DestroyBudgetController::class);
        });

        // Categories
        Route::prefix('categories')->group(function () {
            Route::get('/', \App\Http\Controllers\Api\Finance\IndexCategoryController::class);
            Route::post('/', \App\Http\Controllers\Api\Finance\StoreCategoryController::class);
            Route::delete('/{id}', \App\Http\Controllers\Api\Finance\DestroyCategoryController::class);
        });

        // File attachments
        Route::get('/attachments/{id}', \App\Http\Controllers\Api\Finance\ShowAttachmentController::class);
        Route::delete('/attachments/{id}', \App\Http\Controllers\Api\Finance\DestroyAttachmentController::class);
    });
});
