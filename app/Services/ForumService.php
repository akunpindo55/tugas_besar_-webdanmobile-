<?php

namespace App\Services;

use App\Models\Forum;
use App\Models\ForumComment;
use App\Models\ForumInvitation;
use App\Models\ForumTopic;
use App\Models\User;
use App\Repositories\Contracts\ForumRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ForumService
{
    public function __construct(
        protected ForumRepositoryInterface $forumRepository,
        protected UserRepositoryInterface $userRepository
    ) {}

    public function listPublicForums(int $limit = 20): LengthAwarePaginator
    {
        return $this->forumRepository->allPublic($limit);
    }

    public function listUserForums(User $user): Collection
    {
        return $this->forumRepository->allForUser($user);
    }

    public function createForum(User $creator, array $data): Forum
    {
        return $this->forumRepository->create($data, $creator);
    }

    public function joinForum(User $user, int $forumId): void
    {
        $forum = $this->forumRepository->findById($forumId);
        if (!$forum) {
            throw new \Exception('Forum tidak ditemukan.');
        }

        if ($forum->is_private) {
            throw new \Exception('Forum ini bersifat privat. Anda membutuhkan undangan untuk bergabung.');
        }

        if ($this->forumRepository->isMember($forum, $user)) {
            throw ValidationException::withMessages([
                'forum_id' => ['Anda sudah menjadi anggota forum ini.'],
            ]);
        }

        $this->forumRepository->addMember($forum, $user, 'member');
    }

    public function inviteToForum(User $inviter, int $forumId, int $invitedUserId): ForumInvitation
    {
        $forum = $this->forumRepository->findById($forumId);
        if (!$forum) {
            throw new \Exception('Forum tidak ditemukan.');
        }

        // Verify inviter is owner/admin
        $role = $this->forumRepository->getMemberRole($forum, $inviter);
        if (!in_array($role, ['owner', 'admin'])) {
            throw new \Exception('Hanya owner atau admin yang dapat mengundang pengguna.');
        }

        $invitedUser = User::findOrFail($invitedUserId);

        // Check if already member
        if ($this->forumRepository->isMember($forum, $invitedUser)) {
            throw ValidationException::withMessages([
                'invited_user_id' => ['Pengguna sudah bergabung dalam forum ini.'],
            ]);
        }

        // Check block relationships
        if ($this->userRepository->isAnyBlocked($inviter->id, $invitedUserId)) {
            throw ValidationException::withMessages([
                'invited_user_id' => ['Tidak dapat mengundang pengguna ini.'],
            ]);
        }

        return DB::transaction(function () use ($forum, $inviter, $invitedUser) {
            $invitation = $this->forumRepository->createInvitation($forum, $inviter, $invitedUser);

            $notification = $invitedUser->notifications()->create([
                'id' => Str::uuid()->toString(),
                'type' => 'forum_invite',
                'data' => [
                    'invitation_id' => $invitation->id,
                    'forum_id' => $forum->id,
                    'forum_name' => $forum->name,
                    'invited_by_name' => $inviter->name,
                ],
            ]);

            event(new \App\Events\NotificationCreated($invitedUser->id, [
                'id' => $notification->id,
                'type' => 'forum_invite',
                'data' => $notification->data,
                'is_read' => false,
                'created_at' => $notification->created_at->toIso8601String(),
            ]));

            return $invitation;
        });
    }

    public function respondToInvitation(User $user, int $invitationId, string $status): ForumInvitation
    {
        $invitation = $this->forumRepository->findInvitation($invitationId);

        if (!$invitation || $invitation->invited_user_id !== $user->id || $invitation->status !== 'pending') {
            throw new \Exception('Undangan tidak valid atau sudah diproses.');
        }

        if (!in_array($status, ['accepted', 'declined'])) {
            throw new \Exception('Respon status tidak valid.');
        }

        return DB::transaction(function () use ($invitation, $status, $user) {
            $this->forumRepository->updateInvitationStatus($invitation, $status);

            if ($status === 'accepted') {
                $this->forumRepository->addMember($invitation->forum, $user, 'member');
            }

            return $invitation;
        });
    }

    public function leaveForum(User $user, int $forumId): void
    {
        $forum = $this->forumRepository->findById($forumId);
        if (!$forum) {
            throw new \Exception('Forum tidak ditemukan.');
        }

        if (!$this->forumRepository->isMember($forum, $user)) {
            throw new \Exception('Anda bukan anggota forum ini.');
        }

        $role = $this->forumRepository->getMemberRole($forum, $user);
        if ($role === 'owner') {
            throw new \Exception('Sebagai owner, Anda tidak bisa keluar sebelum mentransfer kepemilikan forum.');
        }

        $this->forumRepository->removeMember($forum, $user);
    }

    public function kickMember(User $operator, int $forumId, int $memberId): void
    {
        $forum = $this->forumRepository->findById($forumId);
        if (!$forum) {
            throw new \Exception('Forum tidak ditemukan.');
        }

        $operatorRole = $this->forumRepository->getMemberRole($forum, $operator);
        if (!in_array($operatorRole, ['owner', 'admin'])) {
            throw new \Exception('Hanya owner atau admin yang dapat mengeluarkan anggota.');
        }

        $member = User::findOrFail($memberId);
        $memberRole = $this->forumRepository->getMemberRole($forum, $member);
        if (!$memberRole) {
            throw new \Exception('Pengguna bukan anggota forum ini.');
        }

        if ($memberRole === 'owner') {
            throw new \Exception('Anda tidak dapat mengeluarkan owner forum.');
        }

        if ($operatorRole === 'admin' && $memberRole === 'admin') {
            throw new \Exception('Admin tidak dapat mengeluarkan sesama admin.');
        }

        $this->forumRepository->removeMember($forum, $member);
    }

    public function createTopic(User $user, int $forumId, array $data, array $mediaData = []): ForumTopic
    {
        $forum = $this->forumRepository->findById($forumId);
        if (!$forum) {
            throw new \Exception('Forum tidak ditemukan.');
        }

        if (!$this->forumRepository->isMember($forum, $user)) {
            throw new \Exception('Hanya anggota yang dapat membuat topik di forum ini.');
        }

        $topicData = $data;
        if (!empty($mediaData)) {
            $topicData['file_url'] = $mediaData[0]['file_url'];
            $topicData['media_type'] = $mediaData[0]['media_type'];
        }

        return $this->forumRepository->createTopic($forum, $user, $topicData);
    }

    public function deleteTopic(User $user, int $topicId): void
    {
        $topic = $this->forumRepository->findTopicById($topicId);
        if (!$topic) {
            throw new \Exception('Topik tidak ditemukan.');
        }

        $forum = $topic->forum;
        $role = $this->forumRepository->getMemberRole($forum, $user);

        if ($topic->user_id !== $user->id && $role !== 'owner' && $role !== 'admin') {
            throw new \Exception('Anda tidak memiliki izin untuk menghapus topik ini.');
        }

        $this->forumRepository->deleteTopic($topic);
    }

    public function replyTopic(User $user, int $topicId, array $data, array $mediaData = []): ForumComment
    {
        $topic = $this->forumRepository->findTopicById($topicId);
        if (!$topic) {
            throw new \Exception('Topik tidak ditemukan.');
        }

        $forum = $topic->forum;
        if (!$this->forumRepository->isMember($forum, $user)) {
            throw new \Exception('Hanya anggota forum yang dapat memberikan komentar.');
        }

        return DB::transaction(function () use ($topic, $user, $data, $mediaData) {
            $commentData = $data;
            if (!empty($mediaData)) {
                $commentData['file_url'] = $mediaData[0]['file_url'];
                $commentData['media_type'] = $mediaData[0]['media_type'];
            }
            $comment = $this->forumRepository->addComment($topic, $user, $commentData);

            // Notify owner of topic or owner of parent comment
            $targetUserId = null;
            if ($comment->parent_comment_id) {
                $parent = ForumComment::find($comment->parent_comment_id);
                if ($parent && $parent->user_id !== $user->id) {
                    $targetUserId = $parent->user_id;
                }
            } else {
                if ($topic->user_id !== $user->id) {
                    $targetUserId = $topic->user_id;
                }
            }

            if ($targetUserId) {
                $targetUser = User::find($targetUserId);
                if ($targetUser) {
                    $notification = $targetUser->notifications()->create([
                        'id' => Str::uuid()->toString(),
                        'type' => 'forum_reply',
                        'data' => [
                            'topic_id' => $topic->id,
                            'forum_id' => $topic->forum_id,
                            'topic_title' => $topic->title,
                            'reply_by_name' => $user->name,
                            'comment_id' => $comment->id,
                        ],
                    ]);

                    event(new \App\Events\NotificationCreated($targetUserId, [
                        'id' => $notification->id,
                        'type' => 'forum_reply',
                        'data' => $notification->data,
                        'is_read' => false,
                        'created_at' => $notification->created_at->toIso8601String(),
                    ]));
                }
            }

            return $comment;
        });
    }

    public function deleteTopicComment(User $user, int $commentId): void
    {
        $comment = ForumComment::findOrFail($commentId);

        if ($comment->user_id !== $user->id) {
            throw new \Exception('Anda hanya dapat menghapus komentar Anda sendiri.');
        }

        $this->forumRepository->deleteComment($comment);
    }

    public function deleteForum(User $user, int $forumId): void
    {
        $forum = $this->forumRepository->findById($forumId);
        if (!$forum) {
            throw new \Exception('Forum tidak ditemukan.');
        }

        $role = $this->forumRepository->getMemberRole($forum, $user);
        if ($role !== 'owner') {
            throw new \Exception('Hanya pemilik forum yang dapat menghapus forum.');
        }

        $this->forumRepository->deleteForum($forum);
    }
}
