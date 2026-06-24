<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\ToggleReactionRequest;
use App\Http\Resources\PostCommentResource;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\PostComment;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PostController extends ApiController
{
    public function __construct(
        protected PostService $postService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 20);
        $paginator = $this->postService->getFeed($request->user(), $limit);
        
        return $this->paginatedResponse(
            PostResource::collection($paginator),
            'Daftar postingan berhasil dimuat.'
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $post = \App\Models\Post::with(['user', 'media', 'comments.user', 'reactions'])->findOrFail($id);

        return $this->successResponse(
            new PostResource($post),
            'Detail postingan berhasil dimuat.'
        );
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $mediaFiles = $request->file('media') ?? [];
        $post = $this->postService->createPost(
            $request->user(),
            $request->validated(),
            $mediaFiles
        );

        $post->load(['user', 'media', 'comments', 'reactions']);

        return $this->successResponse(
            new PostResource($post),
            'Postingan berhasil dibuat.',
            201
        );
    }

    public function update(StorePostRequest $request, int $id): JsonResponse
    {
        $post = Post::findOrFail($id);
        
        // Authorization check
        Gate::authorize('update', $post);

        $updated = $this->postService->updatePost($request->user(), $post, $request->validated());

        return $this->successResponse(
            new PostResource($updated),
            'Postingan berhasil diperbarui.'
        );
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $post = Post::findOrFail($id);

        // Authorization check
        Gate::authorize('delete', $post);

        $this->postService->deletePost($request->user(), $post);

        return $this->successResponse(null, 'Postingan berhasil dihapus.');
    }

    public function comment(StoreCommentRequest $request, int $id): JsonResponse
    {
        try {
            $comment = $this->postService->commentPost(
                $request->user(),
                $id,
                $request->input('comment')
            );

            return $this->successResponse(
                new PostCommentResource($comment),
                'Komentar berhasil ditambahkan.',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function destroyComment(Request $request, int $postId, int $commentId): JsonResponse
    {
        $post = Post::findOrFail($postId);
        $comment = PostComment::where('post_id', $post->id)->findOrFail($commentId);

        if ($comment->user_id !== $request->user()->id) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $comment->delete();

        return $this->successResponse(null, 'Komentar berhasil dihapus.');
    }

    public function react(ToggleReactionRequest $request, int $id): JsonResponse
    {
        try {
            $reaction = $this->postService->reactPost(
                $request->user(),
                $id,
                $request->input('reaction_type')
            );

            return $this->successResponse(
                $reaction ? [
                    'reaction_type' => $reaction->reaction_type,
                    'status' => 'added',
                ] : [
                    'reaction_type' => null,
                    'status' => 'removed',
                ],
                'Reaksi berhasil diperbarui.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
