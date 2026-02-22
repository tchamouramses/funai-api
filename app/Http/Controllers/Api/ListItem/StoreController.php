<?php

namespace App\Http\Controllers\Api\ListItem;

use App\Exceptions\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreListItemRequest;
use App\Models\ListItem;
use App\Models\ListModel;
use App\Models\Profile;
use App\Services\ExpoPushNotificationService;
use App\Services\NotificationTranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class StoreController extends Controller
{
    public function __invoke(StoreListItemRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $list = ListModel::find($validated['list_id']);
        if (! $list) {
            throw new ResourceNotFoundException('List not found');
        }

        $item = ListItem::create($validated);
        $item->series_id = (string) $item->id;
        $item->save();
        $list->increment('total_item_count');

        $this->sendItemCreatedNotification($list, $item);

        return response()->json([
            'data' => $item,
            'message' => 'Item created successfully',
        ], 201);
    }

    private function sendItemCreatedNotification(ListModel $list, ListItem $item): void
    {
        try {
            // Récupérer le profil de l'utilisateur
            $user = User::find($list->user_id);
            $profile = Profile::where('email', $user->email)->first();
            if (! $profile) {
                return;
            }

            $tokens = (array) ($profile->notification_settings['expo_push_tokens'] ?? []);
            Log::info('Sending item created notification', [
                'profile' => $profile,
                'tokens' => $tokens,
            ]);
            if (empty($tokens)) {
                return;
            }


            // Récupérer la locale de l'utilisateur (par défaut 'en')
            $locale = $profile->locale ?? 'en';

            // Obtenir le titre et le corps traduits
            $notification = NotificationTranslationService::getTaskCreatedNotification(
                $locale,
                $item->content
            );

            $notificationService = new ExpoPushNotificationService();
            $notificationService->sendToTokens(
                $tokens,
                $notification['title'],
                $notification['body'],
                [
                    'type' => 'item_created',
                    'itemId' => (string) $item->_id,
                    'listId' => (string) $list->_id,
                    'listTitle' => $list->title,
                ]
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error sending item created notification', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
