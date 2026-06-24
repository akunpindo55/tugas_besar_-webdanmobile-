<?php

namespace App\Repositories\Eloquent;

use App\Models\Forum;
use App\Models\ForumComment;
use App\Models\ForumInvitation;
use App\Models\ForumTopic;
use App\Models\User;
use App\Repositories\Contracts\ForumRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ForumRepository implements ForumRepositoryInterface
{
    public function allPublic(int $limit = 20): LengthAwarePaginator
    {
        return Forum::where('is_private', false)
            ->withCount(['members', 'topics'])
            ->latest()
            ->paginate($limit);
    }

    public function allForUser(User $user): Collection
    {
        return $user->forums()
            ->withCount(['members', 'topics'])
            ->get();
    }

    public function findById(int $id): ?Forum
    {
        return Forum::with(['members'])->find($id);
    }

    public function create(array $data, User $creator): Forum
    {
        return DB::transaction(function () use ($data, $creator) {
            $forum = Forum::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'created_by' => $creator->id,
                'is_private' => $data['is_private'] ?? false,
            ]);

            $forum->members()->attach($creator->id, [
                'role' => 'owner',
                'joined_at' => now(),
            ]);

            return $forum;
        });
    }

    public function addMember(Forum $forum, User $user, string $role = 'member'): void
    {
        $forum->members()->syncWithoutDetaching([
            $user->id => [
                'role' => $role,
                'joined_at' => now(),
            ]
        ]);
    }

    public function removeMember(Forum $forum, User $user): void
    {
        $forum->members()->detach($user->id);
    }

    public function isMember(Forum $forum, User $user): bool
    {
        return $forum->members()->where('user_id', $user->id)->exists();
    }

    public function getMemberRole(Forum $forum, User $user): ?string
    {
        $member = $forum->members()->where('user_id', $user->id)->first();
        return $member ? $member->pivot->role : null;
    }

    public function createInvitation(Forum $forum, User $inviter, User $invitedUser): ForumInvitation
    {
        return ForumInvitation::updateOrCreate(
            [
                'forum_id' => $forum->id,
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

    public function findInvitation(int $invitationId): ?ForumInvitation
    {
        return ForumInvitation::find($invitationId);
    }

    public function updateInvitationStatus(ForumInvitation $invitation, string $status): void
    {
        $invitation->update([
            'status' => $status,
            'responded_at' => now(),
        ]);
    }

    public function createTopic(Forum $forum, User $user, array $data): ForumTopic
    {
        return ForumTopic::create([
            'forum_id' => $forum->id,
            'user_id' => $user->id,
            'title' => $data['title'],
            'content' => $data['content'],
            'file_url' => $data['file_url'] ?? null,
            'media_type' => $data['media_type'] ?? null,
        ]);
    }

    public function findTopicById(int $id): ?ForumTopic
    {
        return ForumTopic::with(['forum', 'user', 'comments.user'])->find($id);
    }

    public function deleteTopic(ForumTopic $topic): void
    {
        $topic->delete();
    }

    public function addComment(ForumTopic $topic, User $user, array $data): ForumComment
    {
        return ForumComment::create([
            'topic_id' => $topic->id,
            'user_id' => $user->id,
            'parent_comment_id' => $data['parent_comment_id'] ?? null,
            'content' => $data['content'],
            'file_url' => $data['file_url'] ?? null,
            'media_type' => $data['media_type'] ?? null,
            'created_at' => now(),
        ]);
    }

    public function deleteComment(ForumComment $comment): void
    {
        $comment->delete();
    }

    public function deleteForum(Forum $forum): void
    {
        $forum->delete();
    }
}
