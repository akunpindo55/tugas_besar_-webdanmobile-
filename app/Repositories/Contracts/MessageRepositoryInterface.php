<?php

namespace App\Repositories\Contracts;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Contracts\Pagination\CursorPaginator;

interface MessageRepositoryInterface
{
    public function create(array $data): Message;

    public function findHistory(Conversation $conversation, int $limit = 20): CursorPaginator;

    public function markAsRead(Message $message, User $user): void;

    public function markAllAsRead(Conversation $conversation, User $user): void;
}
