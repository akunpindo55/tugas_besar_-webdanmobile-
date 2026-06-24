<?php

namespace App\Repositories\Contracts;

use App\Models\Conversation;
use App\Models\GroupInvitation;
use App\Models\User;
use Illuminate\Support\Collection;

interface ConversationRepositoryInterface
{
    public function allForUser(User $user): Collection;

    public function findById(int $id): ?Conversation;

    public function findDirectBetween(int $userId1, int $userId2): ?Conversation;

    public function createDirect(int $userId1, int $userId2): Conversation;

    public function createGroup(array $data, User $owner): Conversation;

    public function addMember(Conversation $conversation, User $user, string $role = 'member'): void;

    public function removeMember(Conversation $conversation, User $user): void;

    public function isMember(Conversation $conversation, User $user): bool;

    public function getMemberRole(Conversation $conversation, User $user): ?string;

    public function createInvitation(Conversation $conversation, User $inviter, User $invitedUser): GroupInvitation;

    public function findInvitation(int $invitationId): ?GroupInvitation;

    public function updateInvitationStatus(GroupInvitation $invitation, string $status): void;

    public function delete(Conversation $conversation): void;
}
