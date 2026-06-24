<?php

namespace App\Repositories\Eloquent;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Repositories\Contracts\MessageRepositoryInterface;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;

class MessageRepository implements MessageRepositoryInterface
{
    public function create(array $data): Message
    {
        return Message::create($data);
    }

    public function findHistory(Conversation $conversation, int $limit = 20): CursorPaginator
    {
        return Message::where('conversation_id', $conversation->id)
            ->with(['sender', 'replyTo'])
            ->latest()
            ->cursorPaginate($limit);
    }

    public function markAsRead(Message $message, User $user): void
    {
        $message->reads()->updateOrCreate(
            ['user_id' => $user->id],
            ['read_at' => now()]
        );
    }

    public function markAllAsRead(Conversation $conversation, User $user): void
    {
        // Find message IDs that are unread by this user and not sent by this user
        $unreadMessageIds = Message::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $user->id)
            ->whereDoesntHave('reads', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->pluck('id');

        $insertData = $unreadMessageIds->map(function ($messageId) use ($user) {
            return [
                'message_id' => $messageId,
                'user_id' => $user->id,
                'read_at' => now(),
            ];
        })->toArray();

        if (!empty($insertData)) {
            DB::table('message_reads')->insertOrIgnore($insertData);
        }
    }
}
