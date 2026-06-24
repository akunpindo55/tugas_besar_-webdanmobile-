<?php

namespace App\Repositories\Eloquent;

use App\Models\BlockedUser;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostMedia;
use App\Models\PostReaction;
use App\Models\User;
use App\Repositories\Contracts\PostRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PostRepository implements PostRepositoryInterface
{
    public function paginateFeed(User $user, int $limit = 20): LengthAwarePaginator
    {
        $blockedUserIds = BlockedUser::where('user_id', $user->id)
            ->pluck('blocked_user_id')
            ->merge(
                BlockedUser::where('blocked_user_id', $user->id)->pluck('user_id')
            )
            ->unique();

        return Post::where(function ($q) use ($user) {
            $q->where('visibility', 'public')
              ->orWhere(function ($sq) use ($user) {
                  $sq->where('user_id', $user->id)->where('visibility', 'private');
              });
        })
        ->whereNotIn('user_id', $blockedUserIds)
        ->with(['user', 'media', 'comments' => function ($q) {
            $q->latest()->limit(5); // Load latest 5 comments for preview
        }, 'comments.user', 'reactions'])
        ->latest()
        ->paginate($limit);
    }

    public function findById(int $id): ?Post
    {
        return Post::with(['user', 'media', 'comments.user', 'reactions'])->find($id);
    }

    public function create(array $data): Post
    {
        return Post::create($data);
    }

    public function update(Post $post, array $data): Post
    {
        $post->update($data);
        return $post;
    }

    public function delete(Post $post): void
    {
        $post->delete();
    }

    public function addMedia(Post $post, string $url, string $type): void
    {
        PostMedia::create([
            'post_id' => $post->id,
            'media_url' => $url,
            'media_type' => $type,
        ]);
    }

    public function addComment(Post $post, User $user, string $content): PostComment
    {
        return PostComment::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'comment' => $content,
            'created_at' => now(),
        ]);
    }

    public function toggleReaction(Post $post, User $user, string $reactionType): ?PostReaction
    {
        $existing = PostReaction::where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            if ($existing->reaction_type === $reactionType) {
                $existing->delete();
                return null;
            } else {
                $existing->update(['reaction_type' => $reactionType]);
                return $existing;
            }
        }

        return PostReaction::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'reaction_type' => $reactionType,
        ]);
    }
}
