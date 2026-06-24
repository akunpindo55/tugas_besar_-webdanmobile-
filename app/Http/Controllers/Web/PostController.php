<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\ToggleReactionRequest;
use App\Models\Post;
use App\Models\PostComment;
use App\Services\PostService;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function __construct(
        protected PostService $postService
    ) {}

    public function store(StorePostRequest $request)
    {
        try {
            $post = $this->postService->createPost(
                $request->user(),
                $request->validated(),
                $request->file('media', [])
            );

            $post->load(['user', 'media', 'reactions']);

            return response()->json([
                'data' => $post
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function update(Request $request, Post $post)
    {
        if ($post->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'content' => 'sometimes|string|max:5000',
            'visibility' => 'sometimes|in:public,private',
        ]);

        try {
            $post = $this->postService->updatePost($request->user(), $post, $validated);
            $post->load(['user', 'media', 'reactions']);
            return response()->json(['data' => $post]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function destroy(Request $request, Post $post)
    {
        if ($post->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $this->postService->deletePost($request->user(), $post);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function comment(StoreCommentRequest $request, Post $post)
    {
        try {
            $comment = $this->postService->commentPost(
                $request->user(),
                $post->id,
                $request->validated()['comment']
            );

            $comment->load('user');

            return response()->json([
                'data' => $comment
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function destroyComment(Request $request, Post $post, PostComment $comment)
    {
        if ($comment->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $comment->delete();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function react(ToggleReactionRequest $request, Post $post)
    {
        try {
            $reaction = $this->postService->reactPost(
                $request->user(),
                $post->id,
                $request->validated()['reaction_type']
            );

            // Get updated reaction counts
            $reactions = $post->reactions()
                ->selectRaw('reaction_type, count(*) as count')
                ->groupBy('reaction_type')
                ->pluck('count', 'reaction_type');

            return response()->json([
                'data' => $reaction,
                'reactions' => $reactions
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
