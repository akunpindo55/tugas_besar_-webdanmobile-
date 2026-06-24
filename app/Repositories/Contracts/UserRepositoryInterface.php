<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Support\Collection;

interface UserRepositoryInterface
{
    public function create(array $data): User;

    public function findByEmail(string $email): ?User;

    public function findByUsername(string $username): ?User;

    public function searchByUsername(string $keyword, User $currentUser): Collection;

    public function update(User $user, array $data): User;

    public function blockUser(User $user, User $target): void;

    public function unblockUser(User $user, User $target): void;

    public function isBlocked(int $userId, int $targetId): bool;

    public function isAnyBlocked(int $user1, int $user2): bool;
}
