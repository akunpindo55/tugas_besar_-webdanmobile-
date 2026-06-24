<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostReaction;
use App\Models\User;
use App\Repositories\Contracts\PostRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostService
{
    public function __construct(
        protected PostRepositoryInterface $postRepository
    ) {}

    public function getFeed(User $user, int $limit = 20): LengthAwarePaginator
    {
        return $this->postRepository->paginateFeed($user, $limit);
    }

    public function createPost(User $user, array $data, array $mediaFiles = []): Post
    {
        return DB::transaction(function () use ($user, $data, $mediaFiles) {
            $post = $this->postRepository->create([
                'user_id' => $user->id,
                'content' => $data['content'],
                'visibility' => $data['visibility'] ?? 'public',
            ]);

            foreach ($mediaFiles as $file) {
                $path = $file->store('posts', 'public');
                $url = asset('storage/' . $path);
                
                // Simple mime type detection to separate image/video
                $mime = $file->getMimeType();
                $type = str_contains($mime, 'video') ? 'video' : 'image';

                $this->postRepository->addMedia($post, $url, $type);
            }

            return $post;
        });
    }

    public function updatePost(User $user, Post $post, array $data): Post
    {
        return $this->postRepository->update($post, $data);
    }

    public function deletePost(User $user, Post $post): void
    {
        $this->postRepository->delete($post);
    }

    public function commentPost(User $user, int $postId, string $content): PostComment
    {
        $post = $this->postRepository->findById($postId);
        if (!$post) {
            throw new \Exception('Postingan tidak ditemukan.');
        }

        return DB::transaction(function () use ($post, $user, $content) {
            $comment = $this->postRepository->addComment($post, $user, $content);

            // Notify post owner if not self
            if ($post->user_id !== $user->id) {
                $notification = $post->user->notifications()->create([
                    'id' => Str::uuid()->toString(),
                    'type' => 'post_comment',
                    'data' => [
                        'post_id' => $post->id,
                        'comment_by_name' => $user->name,
                        'comment' => $content,
                    ],
                ]);

                event(new \App\Events\NotificationCreated($post->user->id, [
                    'id' => $notification->id,
                    'type' => 'post_comment',
                    'data' => $notification->data,
                    'is_read' => false,
                    'created_at' => $notification->created_at->toIso8601String(),
                ]));
            }

            return $comment;
        });
    }

    public function reactPost(User $user, int $postId, string $reactionType): ?PostReaction
    {
        $post = $this->postRepository->findById($postId);
        if (!$post) {
            throw new \Exception('Postingan tidak ditemukan.');
        }

        return DB::transaction(function () use ($post, $user, $reactionType) {
            $reaction = $this->postRepository->toggleReaction($post, $user, $reactionType);

            // Notify post owner if not self and reaction was created (not removed)
            if ($reaction && $post->user_id !== $user->id) {
                $notification = $post->user->notifications()->create([
                    'id' => Str::uuid()->toString(),
                    'type' => 'post_reaction',
                    'data' => [
                        'post_id' => $post->id,
                        'reacted_by_name' => $user->name,
                        'reaction_type' => $reactionType,
                    ],
                ]);

                event(new \App\Events\NotificationCreated($post->user->id, [
                    'id' => $notification->id,
                    'type' => 'post_reaction',
                    'data' => $notification->data,
                    'is_read' => false,
                    'created_at' => $notification->created_at->toIso8601String(),
                ]));
            }

            return $reaction;
        });
    }
}
