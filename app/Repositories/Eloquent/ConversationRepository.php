<?php

namespace App\Repositories\Eloquent;

use App\Models\Conversation;
use App\Models\GroupInvitation;
use App\Models\User;
use App\Repositories\Contracts\ConversationRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ConversationRepository implements ConversationRepositoryInterface
{
    public function allForUser(User $user): Collection
    {
        return $user->conversations()
            ->with(['members', 'messages' => function ($q) {
                $q->latest()->limit(1);
            }])
            ->get()
            ->sortByDesc(function ($conversation) {
                return $conversation->messages->first()?->created_at ?? $conversation->created_at;
            })
            ->values();
    }

    public function findById(int $id): ?Conversation
    {
        return Conversation::with(['members'])->find($id);
    }

    public function findDirectBetween(int $userId1, int $userId2): ?Conversation
    {
        return Conversation::where('type', 'direct')
            ->whereHas('members', function ($q) use ($userId1) {
                $q->where('user_id', $userId1);
            })
            ->whereHas('members', function ($q) use ($userId2) {
                $q->where('user_id', $userId2);
            })
            ->first();
    }

    public function createDirect(int $userId1, int $userId2): Conversation
    {
        return DB::transaction(function () use ($userId1, $userId2) {
            $conversation = Conversation::create([
                'type' => 'direct',
            ]);

            $conversation->members()->attach([
                $userId1 => ['role' => 'member', 'joined_at' => now()],
                $userId2 => ['role' => 'member', 'joined_at' => now()],
            ]);

            return $conversation;
        });
    }

    public function createGroup(array $data, User $owner): Conversation
    {
        return DB::transaction(function () use ($data, $owner) {
            $conversation = Conversation::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'type' => 'group',
                'created_by' => $owner->id,
                'avatar' => $data['avatar'] ?? null,
            ]);

            $conversation->members()->attach($owner->id, [
                'role' => 'owner',
                'joined_at' => now(),
            ]);

            return $conversation;
        });
    }

    public function addMember(Conversation $conversation, User $user, string $role = 'member'): void
    {
        $conversation->members()->syncWithoutDetaching([
            $user->id => [
                'role' => $role,
                'joined_at' => now(),
            ]
        ]);
    }

    public function removeMember(Conversation $conversation, User $user): void
    {
        $conversation->members()->detach($user->id);
    }

    public function isMember(Conversation $conversation, User $user): bool
    {
        return $conversation->members()->where('user_id', $user->id)->exists();
    }

    public function getMemberRole(Conversation $conversation, User $user): ?string
    {
        $member = $conversation->members()->where('user_id', $user->id)->first();
        return $member ? $member->pivot->role : null;
    }

    public function createInvitation(Conversation $conversation, User $inviter, User $invitedUser): GroupInvitation
    {
        return GroupInvitation::updateOrCreate(
            [
                'conversation_id' => $conversation->id,
                'invited_user_id' => $invitedUser->id,
            ],
            [
                'invited_by' => $inviter->id,
                'status' => 'pending',
                'created_at' => now(),
                'responded_at' => null,
            ]
        );
    }

    public function findInvitation(int $invitationId): ?GroupInvitation
    {
        return GroupInvitation::find($invitationId);
    }

    public function updateInvitationStatus(GroupInvitation $invitation, string $status): void
    {
        $invitation->update([
            'status' => $status,
            'responded_at' => now(),
        ]);
    }
}
