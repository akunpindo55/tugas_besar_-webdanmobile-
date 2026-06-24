<?php

namespace App\Repositories\Contracts;

use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostReaction;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PostRepositoryInterface
{
    public function paginateFeed(User $user, int $limit = 20): LengthAwarePaginator;

    public function findById(int $id): ?Post;

    public function create(array $data): Post;

    public function update(Post $post, array $data): Post;

    public function delete(Post $post): void;

    public function addMedia(Post $post, string $url, string $type): void;

    public function addComment(Post $post, User $user, string $content): PostComment;

    public function toggleReaction(Post $post, User $user, string $reactionType): ?PostReaction;
}
