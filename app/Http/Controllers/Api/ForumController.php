<?php

namespace App\Http\Controllers\Api;

use App\Helpers\StorageHelper;
use App\Http\Requests\InviteMemberRequest;
use App\Http\Requests\RespondInvitationRequest;
use App\Http\Requests\StoreForumCommentRequest;
use App\Http\Requests\StoreForumRequest;
use App\Http\Requests\StoreTopicRequest;
use App\Http\Resources\ForumCommentResource;
use App\Http\Resources\ForumInvitationResource;
use App\Http\Resources\ForumResource;
use App\Http\Resources\ForumTopicResource;
use App\Models\Forum;
use App\Models\ForumTopic;
use App\Services\ForumService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ForumController extends ApiController
{
    public function __construct(
        protected ForumService $forumService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 20);
        $paginator = $this->forumService->listPublicForums($limit);

        return $this->paginatedResponse(
            ForumResource::collection($paginator),
            'Daftar forum publik berhasil dimuat.'
        );
    }

    public function indexUser(Request $request): JsonResponse
    {
        $forums = $this->forumService->listUserForums($request->user());

        return $this->successResponse(
            ForumResource::collection($forums),
            'Daftar forum Anda berhasil dimuat.'
        );
    }

    public function store(StoreForumRequest $request): JsonResponse
    {
        $forum = $this->forumService->createForum($request->user(), $request->validated());

        return $this->successResponse(
            new ForumResource($forum),
            'Forum berhasil dibuat.',
            201
        );
    }

    public function join(Request $request, int $id): JsonResponse
    {
        try {
            $this->forumService->joinForum($request->user(), $id);
            return $this->successResponse(null, 'Berhasil bergabung dengan forum.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function invite(InviteMemberRequest $request, int $id): JsonResponse
    {
        try {
            $invitation = $this->forumService->inviteToForum(
                $request->user(),
                $id,
                $request->input('invited_user_id')
            );

            return $this->successResponse(
                new ForumInvitationResource($invitation),
                'Undangan forum berhasil dikirim.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function respond(RespondInvitationRequest $request, int $id): JsonResponse
    {
        try {
            $invitation = $this->forumService->respondToInvitation(
                $request->user(),
                $id,
                $request->input('status')
            );

            return $this->successResponse(
                new ForumInvitationResource($invitation),
                'Undangan forum berhasil direspon.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function leave(Request $request, int $id): JsonResponse
    {
        try {
            $this->forumService->leaveForum($request->user(), $id);
            return $this->successResponse(null, 'Berhasil meninggalkan forum.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function kick(Request $request, int $id, int $memberId): JsonResponse
    {
        try {
            $this->forumService->kickMember($request->user(), $id, $memberId);
            return $this->successResponse(null, 'Anggota berhasil dikeluarkan dari forum.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function createTopic(StoreTopicRequest $request, int $id): JsonResponse
    {
        try {
            $mediaData = [];
            if ($request->hasFile('file')) {
                $url = StorageHelper::storeFile($request->file('file'), 'forum/topics');
                if ($url) {
                    $mediaData[] = [
                        'file_url' => $url,
                        'media_type' => $request->file('file')->getMimeType(),
                    ];
                }
            }

            $topic = $this->forumService->createTopic(
                $request->user(),
                $id,
                $request->validated(),
                $mediaData
            );

            return $this->successResponse(
                new ForumTopicResource($topic),
                'Topik diskusi berhasil dibuat.',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function listTopics(Request $request, int $id): JsonResponse
    {
        $forum = Forum::findOrFail($id);
        $topics = $forum->topics()->with(['user'])->get();

        return $this->successResponse(
            ForumTopicResource::collection($topics),
            'Daftar topik diskusi berhasil dimuat.'
        );
    }

    public function replyTopic(StoreForumCommentRequest $request, int $topicId): JsonResponse
    {
        try {
            $mediaData = [];
            if ($request->hasFile('file')) {
                $url = StorageHelper::storeFile($request->file('file'), 'forum/comments');
                if ($url) {
                    $mediaData[] = [
                        'file_url' => $url,
                        'media_type' => $request->file('file')->getMimeType(),
                    ];
                }
            }

            $comment = $this->forumService->replyTopic(
                $request->user(),
                $topicId,
                $request->validated(),
                $mediaData
            );

            return $this->successResponse(
                new ForumCommentResource($comment),
                'Komentar berhasil ditambahkan.',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function showTopic(Request $request, int $topicId): JsonResponse
    {
        $topic = ForumTopic::with(['forum', 'user', 'comments' => function ($q) {
            $q->whereNull('parent_comment_id')->with(['user', 'replies.user']); // Load root comments + 1st level replies
        }])->findOrFail($topicId);

        return $this->successResponse(
            new ForumTopicResource($topic),
            'Topik diskusi berhasil dimuat.'
        );
    }

    public function destroyTopic(Request $request, int $topicId): JsonResponse
    {
        try {
            $this->forumService->deleteTopic($request->user(), $topicId);
            return $this->successResponse(null, 'Topik berhasil dihapus.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function destroyTopicComment(Request $request, int $commentId): JsonResponse
    {
        try {
            $this->forumService->deleteTopicComment($request->user(), $commentId);
            return $this->successResponse(null, 'Komentar berhasil dihapus.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $this->forumService->deleteForum($request->user(), $id);
            return $this->successResponse(null, 'Forum berhasil dihapus.');
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
