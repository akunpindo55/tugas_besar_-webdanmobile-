<?php

namespace App\Repositories\Contracts;

use App\Models\Forum;
use App\Models\ForumComment;
use App\Models\ForumInvitation;
use App\Models\ForumTopic;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ForumRepositoryInterface
{
    public function allPublic(int $limit = 20): LengthAwarePaginator;

    public function allForUser(User $user): Collection;

    public function findById(int $id): ?Forum;

    public function create(array $data, User $creator): Forum;

    public function addMember(Forum $forum, User $user, string $role = 'member'): void;

    public function removeMember(Forum $forum, User $user): void;

    public function isMember(Forum $forum, User $user): bool;

    public function getMemberRole(Forum $forum, User $user): ?string;

    public function createInvitation(Forum $forum, User $inviter, User $invitedUser): ForumInvitation;

    public function findInvitation(int $invitationId): ?ForumInvitation;

    public function updateInvitationStatus(ForumInvitation $invitation, string $status): void;

    public function createTopic(Forum $forum, User $user, array $data): ForumTopic;

    public function findTopicById(int $id): ?ForumTopic;

    public function deleteTopic(ForumTopic $topic): void;

    public function addComment(ForumTopic $topic, User $user, array $data): ForumComment;

    public function deleteComment(ForumComment $comment): void;

    public function deleteForum(Forum $forum): void;
}
