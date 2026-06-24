<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    public function search(string $keyword, User $currentUser): Collection
    {
        return $this->userRepository->searchByUsername($keyword, $currentUser);
    }

    public function updateProfile(User $user, array $data): User
    {
        return $this->userRepository->update($user, $data);
    }

    public function updateAvatar(User $user, $file): User
    {
        // Delete old avatar if exists
        if ($user->avatar) {
            $oldPath = str_replace(asset('storage/'), '', $user->avatar);
            Storage::disk('public')->delete($oldPath);
        }

        $path = $file->store('avatars', 'public');
        $url = asset('storage/' . $path);

        return $this->userRepository->update($user, ['avatar' => $url]);
    }

    public function block(User $user, int $targetId): void
    {
        if ($user->id === $targetId) {
            throw ValidationException::withMessages([
                'user_id' => ['Anda tidak dapat memblokir diri sendiri.'],
            ]);
        }

        $target = User::findOrFail($targetId);
        $this->userRepository->blockUser($user, $target);
    }

    public function unblock(User $user, int $targetId): void
    {
        $target = User::findOrFail($targetId);
        $this->userRepository->unblockUser($user, $target);
    }
}
