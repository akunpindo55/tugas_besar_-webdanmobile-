<?php

namespace App\Repositories\Eloquent;

use App\Models\BlockedUser;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Collection;

class UserRepository implements UserRepositoryInterface
{
    public function create(array $data): User
    {
        return User::create($data);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findByUsername(string $username): ?User
    {
        return User::where('username', $username)->first();
    }

    public function searchByUsername(string $keyword, User $currentUser): Collection
    {
        // Get user IDs that blocked the current user or are blocked by the current user
        $blockedUserIds = BlockedUser::where('user_id', $currentUser->id)
            ->pluck('blocked_user_id')
            ->merge(
                BlockedUser::where('blocked_user_id', $currentUser->id)->pluck('user_id')
            )
            ->unique();

        return User::where('username', 'like', "%{$keyword}%")
            ->where('id', '!=', $currentUser->id)
            ->whereNotIn('id', $blockedUserIds)
            ->limit(30)
            ->get();
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);
        return $user;
    }

    public function blockUser(User $user, User $target): void
    {
        $user->blockedUsers()->syncWithoutDetaching([$target->id => ['created_at' => now()]]);
    }

    public function unblockUser(User $user, User $target): void
    {
        $user->blockedUsers()->detach($target->id);
    }

    public function isBlocked(int $userId, int $targetId): bool
    {
        return BlockedUser::where('user_id', $userId)
            ->where('blocked_user_id', $targetId)
            ->exists();
    }

    public function isAnyBlocked(int $user1, int $user2): bool
    {
        return BlockedUser::where(function ($query) use ($user1, $user2) {
            $query->where('user_id', $user1)->where('blocked_user_id', $user2);
        })->orWhere(function ($query) use ($user1, $user2) {
            $query->where('user_id', $user2)->where('blocked_user_id', $user1);
        })->exists();
    }
}
