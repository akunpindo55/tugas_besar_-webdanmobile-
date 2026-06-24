<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\GroupInvitation;
use App\Models\User;
use App\Repositories\Contracts\ConversationRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConversationService
{
    public function __construct(
        protected ConversationRepositoryInterface $conversationRepository,
        protected UserRepositoryInterface $userRepository
    ) {}

    public function listConversations(User $user): Collection
    {
        return $this->conversationRepository->allForUser($user);
    }

    public function startDirect(User $user, int $targetUserId): Conversation
    {
        if ($user->id === $targetUserId) {
            throw ValidationException::withMessages([
                'user_id' => ['Anda tidak dapat memulai percakapan dengan diri sendiri.'],
            ]);
        }

        // Check if either user has blocked the other
        if ($this->userRepository->isAnyBlocked($user->id, $targetUserId)) {
            throw ValidationException::withMessages([
                'user_id' => ['Percakapan tidak dapat dimulai karena pemblokiran.'],
            ]);
        }

        // Find existing direct chat
        $existing = $this->conversationRepository->findDirectBetween($user->id, $targetUserId);
        if ($existing) {
            return $existing;
        }

        // Otherwise, create new
        return $this->conversationRepository->createDirect($user->id, $targetUserId);
    }

    public function createGroup(User $owner, array $data): Conversation
    {
        return $this->conversationRepository->createGroup($data, $owner);
    }

    public function inviteToGroup(User $inviter, int $conversationId, int $invitedUserId): GroupInvitation
    {
        $conversation = $this->conversationRepository->findById($conversationId);
        if (!$conversation || $conversation->type !== 'group') {
            throw new \Exception('Grup tidak ditemukan.');
        }

        // Verify inviter is a member
        if (!$this->conversationRepository->isMember($conversation, $inviter)) {
            throw new \Exception('Anda bukan anggota grup ini.');
        }

        // Check if invited user exists
        $invitedUser = User::findOrFail($invitedUserId);

        // Check if already a member
        if ($this->conversationRepository->isMember($conversation, $invitedUser)) {
            throw ValidationException::withMessages([
                'invited_user_id' => ['Pengguna sudah bergabung dalam grup ini.'],
            ]);
        }

        // Check blocking relations
        if ($this->userRepository->isAnyBlocked($inviter->id, $invitedUserId)) {
            throw ValidationException::withMessages([
                'invited_user_id' => ['Tidak dapat mengundang pengguna ini.'],
            ]);
        }

        return DB::transaction(function () use ($conversation, $inviter, $invitedUser) {
            $invitation = $this->conversationRepository->createInvitation($conversation, $inviter, $invitedUser);

            // Create notification record
            $notification = $invitedUser->notifications()->create([
                'id' => \Illuminate\Support\Str::uuid()->toString(),
                'type' => 'group_invite',
                'data' => [
                    'invitation_id' => $invitation->id,
                    'conversation_id' => $conversation->id,
                    'conversation_name' => $conversation->name,
                    'invited_by_name' => $inviter->name,
                ],
            ]);

            // Broadcast notification created event
            event(new \App\Events\NotificationCreated($invitedUser->id, [
                'id' => $notification->id,
                'type' => 'group_invite',
                'data' => $notification->data,
                'is_read' => false,
                'created_at' => $notification->created_at->toIso8601String(),
            ]));

            return $invitation;
        });
    }

    public function respondToInvitation(User $user, int $invitationId, string $status): GroupInvitation
    {
        $invitation = $this->conversationRepository->findInvitation($invitationId);

        if (!$invitation || $invitation->invited_user_id !== $user->id || $invitation->status !== 'pending') {
            throw new \Exception('Undangan tidak valid atau sudah diproses.');
        }

        if (!in_array($status, ['accepted', 'declined'])) {
            throw new \Exception('Respon status tidak valid.');
        }

        return DB::transaction(function () use ($invitation, $status, $user) {
            $this->conversationRepository->updateInvitationStatus($invitation, $status);

            $conversation = $invitation->conversation;

            if ($status === 'accepted') {
                $this->conversationRepository->addMember($conversation, $user, 'member');

                // Optional: notify members or send system message
                $conversation->messages()->create([
                    'sender_id' => $user->id,
                    'message_type' => 'system',
                    'body' => "{$user->name} telah bergabung dengan grup.",
                ]);
            }

            return $invitation;
        });
    }

    public function leaveGroup(User $user, int $conversationId): void
    {
        $conversation = $this->conversationRepository->findById($conversationId);
        if (!$conversation || $conversation->type !== 'group') {
            throw new \Exception('Grup tidak ditemukan.');
        }

        if (!$this->conversationRepository->isMember($conversation, $user)) {
            throw new \Exception('Anda bukan anggota grup ini.');
        }

        DB::transaction(function () use ($conversation, $user) {
            $this->conversationRepository->removeMember($conversation, $user);

            // Send system message
            $conversation->messages()->create([
                'sender_id' => $user->id,
                'message_type' => 'system',
                'body' => "{$user->name} telah meninggalkan grup.",
            ]);
        });
    }
}
