<?php

namespace App\Services;

use App\Models\Conversation;

class ChatAssistantConversationService
{
    public function getOrCreateForUser(string $userId): Conversation
    {
        $conversation = Conversation::where('user_id', $userId)
            ->where('type', 'chat_assistant')
            ->orderBy('created_at', 'asc')
            ->first();

        if ($conversation) {
            return $conversation;
        }

        return Conversation::create([
            'user_id' => $userId,
            'title' => 'Assistant Personnel',
            'type' => 'chat_assistant',
            'sub_type' => null,
            'pinned' => false,
        ]);
    }
}
