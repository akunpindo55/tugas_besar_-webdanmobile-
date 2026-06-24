<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Repositories\Contracts\ConversationRepositoryInterface;
use App\Repositories\Contracts\MessageRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;

class MessageService
{
    public function __construct(
        protected MessageRepositoryInterface $messageRepository,
        protected ConversationRepositoryInterface $conversationRepository,
        protected UserRepositoryInterface $userRepository
    ) {}

    public function sendMessage(User $sender, int $conversationId, array $data, $file = null): Message
    {
        $conversation = $this->conversationRepository->findById($conversationId);
        if (!$conversation) {
            throw new \Exception('Percakapan tidak ditemukan.');
        }

        if (!$this->conversationRepository->isMember($conversation, $sender)) {
            throw new \Exception('Anda bukan anggota percakapan ini.');
        }

        // For direct chat, verify no block relationship
        if ($conversation->type === 'direct') {
            $otherMember = $conversation->members()->where('users.id', '!=', $sender->id)->first();
            if ($otherMember && $this->userRepository->isAnyBlocked($sender->id, $otherMember->id)) {
                throw new \Exception('Pesan tidak dapat dikirim karena pemblokiran.');
            }
        }

        return DB::transaction(function () use ($conversation, $conversationId, $sender, $data, $file) {
            $fileUrl = null;
            if ($file) {
                $path = $file->store('messages', 'public');
                $fileUrl = asset('storage/' . $path);
            }

            $message = $this->messageRepository->create([
                'conversation_id' => $conversationId,
                'sender_id' => $sender->id,
                'message_type' => $data['message_type'],
                'body' => $data['body'] ?? null,
                'file_url' => $fileUrl,
                'reply_to' => $data['reply_to'] ?? null,
            ]);

            $message->load(['sender', 'replyTo', 'reads']);

            $serializedMessage = (new \App\Http\Resources\MessageResource($message))->resolve();

            // Broadcast MessageSent event via Reverb
            event(new \App\Events\MessageSent($serializedMessage));

            // Create notification for other members
            $otherMembers = $conversation->members()->where('users.id', '!=', $sender->id)->get();
            foreach ($otherMembers as $member) {
                $notification = $member->notifications()->create([
                    'id' => \Illuminate\Support\Str::uuid()->toString(),
                    'type' => 'message',
                    'data' => [
                        'message_id' => $message->id,
                        'conversation_id' => $conversationId,
                        'conversation_name' => $conversation->type === 'group' ? $conversation->name : $sender->name,
                        'sender_name' => $sender->name,
                        'body' => $data['body'] ?? 'Mengirim file',
                    ],
                ]);

                // Broadcast NotificationCreated event
                event(new \App\Events\NotificationCreated($member->id, [
                    'id' => $notification->id,
                    'type' => 'message',
                    'data' => $notification->data,
                    'is_read' => false,
                    'created_at' => $notification->created_at->toIso8601String(),
                ]));
            }

            return $message;
        });
    }

    public function getHistory(User $user, int $conversationId, int $limit = 20): CursorPaginator
    {
        $conversation = $this->conversationRepository->findById($conversationId);
        if (!$conversation) {
            throw new \Exception('Percakapan tidak ditemukan.');
        }

        if (!$this->conversationRepository->isMember($conversation, $user)) {
            throw new \Exception('Anda bukan anggota percakapan ini.');
        }

        return $this->messageRepository->findHistory($conversation, $limit);
    }

    public function markAsRead(User $user, int $messageId): void
    {
        $message = Message::findOrFail($messageId);
        $conversation = $message->conversation;

        if (!$this->conversationRepository->isMember($conversation, $user)) {
            throw new \Exception('Anda bukan anggota percakapan ini.');
        }

        $this->messageRepository->markAsRead($message, $user);

        // Broadcast MessageRead event
        event(new \App\Events\MessageRead(
            $conversation->id,
            $user->id,
            [$messageId],
            now()->toIso8601String()
        ));
    }

    public function markAllAsRead(User $user, int $conversationId): void
    {
        $conversation = $this->conversationRepository->findById($conversationId);
        if (!$conversation) {
            throw new \Exception('Percakapan tidak ditemukan.');
        }

        if (!$this->conversationRepository->isMember($conversation, $user)) {
            throw new \Exception('Anda bukan anggota percakapan ini.');
        }

        // Get unread message IDs to broadcast
        $unreadIds = Message::where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $user->id)
            ->whereDoesntHave('reads', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->pluck('id')
            ->toArray();

        if (empty($unreadIds)) {
            return;
        }

        $this->messageRepository->markAllAsRead($conversation, $user);

        // Broadcast MessageRead event for all of them
        event(new \App\Events\MessageRead(
            $conversationId,
            $user->id,
            $unreadIds,
            now()->toIso8601String()
        ));
    }

    public function deleteMessage(User $user, int $messageId): void
    {
        $message = Message::findOrFail($messageId);
        
        if ($message->sender_id !== $user->id) {
            throw new \Exception('Anda hanya dapat menghapus pesan Anda sendiri.');
        }

        // Broadcast MessageDeleted event if needed, skipped for simplicity
        $message->delete();
    }
}
